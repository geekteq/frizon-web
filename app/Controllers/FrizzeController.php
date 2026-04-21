<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/ActionRateLimiter.php';
require_once dirname(__DIR__) . '/Services/AiService.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Services/SecurityAudit.php';
require_once dirname(__DIR__) . '/Lib/Parsedown.php';
require_once dirname(__DIR__) . '/Models/FrizzeDocument.php';
require_once dirname(__DIR__) . '/Models/FrizzeDocumentInterpretation.php';
require_once dirname(__DIR__) . '/Models/FrizzeEvent.php';
require_once dirname(__DIR__) . '/Models/FrizzeServiceTask.php';
require_once dirname(__DIR__) . '/Models/FrizzeVehicle.php';

class FrizzeController
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function overview(array $params): void
    {
        $this->render('overview');
    }

    public function journal(array $params): void
    {
        $this->render('journal');
    }

    public function createJournalEvent(array $params): void
    {
        Auth::requireLogin();

        $pageTitle = 'Ny Frizze-händelse';
        $vehicle = $this->vehicle();
        $documents = $this->documents((int) $vehicle['id']);
        $event = $this->emptyEvent();
        $eventTypes = $this->eventTypes();
        $formAction = '/adm/frizze/journal';
        $formMethod = 'POST';

        view('frizze/event-form', compact(
            'documents',
            'event',
            'eventTypes',
            'formAction',
            'formMethod',
            'pageTitle',
            'vehicle'
        ));
    }

    public function storeJournalEvent(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $vehicle = $this->vehicle();
        $data = $this->eventInput((int) $vehicle['id']);

        if ($data['title'] === '' || $data['event_date'] === '') {
            flash('error', 'Titel och datum krävs.');
            redirect('/adm/frizze/journal/ny');
        }

        $eventModel = new FrizzeEvent($this->pdo);
        $eventId = $eventModel->create($data);

        SecurityAudit::log($this->pdo, 'frizze.event.created', [
            'event_id' => $eventId,
            'event_title' => $data['title'],
        ], Auth::userId());

        flash('success', 'Journalhändelsen har sparats.');
        redirect('/adm/frizze/journal');
    }

    public function editJournalEvent(array $params): void
    {
        Auth::requireLogin();

        $eventModel = new FrizzeEvent($this->pdo);
        $event = $eventModel->findById((int) $params['id']);
        if (!$event) {
            http_response_code(404);
            echo '<h1>Händelsen hittades inte</h1>';
            return;
        }

        $pageTitle = 'Redigera Frizze-händelse';
        $vehicle = $this->vehicle();
        $documents = $this->documents((int) $vehicle['id']);
        $eventTypes = $this->eventTypes();
        $formAction = '/adm/frizze/journal/' . (int) $event['id'];
        $formMethod = 'PUT';

        view('frizze/event-form', compact(
            'documents',
            'event',
            'eventTypes',
            'formAction',
            'formMethod',
            'pageTitle',
            'vehicle'
        ));
    }

    public function updateJournalEvent(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $eventModel = new FrizzeEvent($this->pdo);
        $event = $eventModel->findById((int) $params['id']);
        if (!$event) {
            http_response_code(404);
            return;
        }

        $vehicle = $this->vehicle();
        $data = $this->eventInput((int) $vehicle['id']);

        if ($data['title'] === '' || $data['event_date'] === '') {
            flash('error', 'Titel och datum krävs.');
            redirect('/adm/frizze/journal/' . (int) $event['id'] . '/redigera');
        }

        $eventModel->update((int) $event['id'], $data);

        SecurityAudit::log($this->pdo, 'frizze.event.updated', [
            'event_id' => (int) $event['id'],
            'event_title' => $data['title'],
        ], Auth::userId());

        flash('success', 'Journalhändelsen har uppdaterats.');
        redirect('/adm/frizze/journal');
    }

    public function destroyJournalEvent(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $eventModel = new FrizzeEvent($this->pdo);
        $event = $eventModel->findById((int) $params['id']);

        if ($event) {
            $eventModel->delete((int) $event['id']);
            SecurityAudit::log($this->pdo, 'frizze.event.deleted', [
                'event_id' => (int) $event['id'],
                'event_title' => $event['title'],
            ], Auth::userId());
            flash('success', 'Journalhändelsen har tagits bort.');
        }

        redirect('/adm/frizze/journal');
    }

    public function receipts(array $params): void
    {
        $this->render('receipts');
    }

    public function storeDocument(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        if (empty($_FILES['document']) || ($_FILES['document']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'Välj en PDF eller bild att ladda upp.');
            redirect('/adm/frizze/kvitton');
        }

        $vehicle = $this->vehicle();
        $file = $_FILES['document'];
        $maxSize = (int) ($this->config['upload_max_size'] ?? 10485760);

        if ((int) $file['size'] > $maxSize) {
            flash('error', 'Filen är för stor.');
            redirect('/adm/frizze/kvitton');
        }

        $mimeType = $this->detectUploadMimeType((string) $file['tmp_name']);
        $extensions = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($extensions[$mimeType])) {
            flash('error', 'Tillåtna filtyper är PDF, JPG, PNG och WEBP.');
            redirect('/adm/frizze/kvitton');
        }

        $storageDir = $this->privateDocumentStorageDir();
        if (!is_dir($storageDir) && !mkdir($storageDir, 0755, true)) {
            flash('error', 'Kunde inte skapa privat dokumentmapp.');
            redirect('/adm/frizze/kvitton');
        }

        $filename = date('Ymd-His') . '-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mimeType];
        $destination = $storageDir . '/' . $filename;

        if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
            flash('error', 'Uppladdningen misslyckades.');
            redirect('/adm/frizze/kvitton');
        }

        $amount = str_replace(',', '.', trim($_POST['amount_total'] ?? ''));
        $title = trim($_POST['title'] ?? '') ?: pathinfo((string) $file['name'], PATHINFO_FILENAME);
        if ($title === '') {
            $title = 'Frizze-dokument ' . date('Y-m-d');
        }

        $documentType = $_POST['document_type'] ?? 'receipt';
        if (!array_key_exists($documentType, $this->documentTypes())) {
            $documentType = 'receipt';
        }

        $documentModel = new FrizzeDocument($this->pdo);
        $documentId = $documentModel->create([
            'vehicle_id' => (int) $vehicle['id'],
            'document_type' => $documentType,
            'title' => $title,
            'original_filename' => (string) $file['name'],
            'file_path' => 'frizze/documents/' . $filename,
            'mime_type' => $mimeType,
            'supplier' => trim($_POST['supplier'] ?? '') ?: null,
            'document_date' => trim($_POST['document_date'] ?? '') ?: null,
            'amount_total' => $amount !== '' && is_numeric($amount) ? (float) $amount : null,
            'currency' => 'SEK',
            'notes' => trim($_POST['notes'] ?? '') ?: null,
            'created_by' => Auth::userId(),
        ]);

        SecurityAudit::log($this->pdo, 'frizze.document.uploaded', [
            'document_id' => $documentId,
            'document_title' => $title,
            'mime_type' => $mimeType,
        ], Auth::userId());

        flash('success', 'Dokumentet har laddats upp privat.');
        redirect('/adm/frizze/kvitton');
    }

    public function showDocument(array $params): void
    {
        Auth::requireLogin();

        $documentModel = new FrizzeDocument($this->pdo);
        $document = $documentModel->findById((int) $params['id']);
        if (!$document) {
            http_response_code(404);
            echo '<h1>Dokumentet hittades inte</h1>';
            return;
        }

        $path = $this->privateStoragePath((string) $document['file_path']);
        if ($path === null || !is_file($path)) {
            http_response_code(404);
            echo '<h1>Filen hittades inte</h1>';
            return;
        }

        header('Content-Type: ' . ($document['mime_type'] ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="' . $this->downloadFilename($document) . '"');
        readfile($path);
    }

    public function destroyDocument(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $documentModel = new FrizzeDocument($this->pdo);
        $document = $documentModel->findById((int) $params['id']);

        if ($document) {
            $path = $this->privateStoragePath((string) $document['file_path']);
            if ($path !== null && is_file($path)) {
                unlink($path);
            }

            $documentModel->delete((int) $document['id']);
            SecurityAudit::log($this->pdo, 'frizze.document.deleted', [
                'document_id' => (int) $document['id'],
                'document_title' => $document['title'],
            ], Auth::userId());
            flash('success', 'Dokumentet har tagits bort.');
        }

        redirect('/adm/frizze/kvitton');
    }

    public function interpretDocument(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        try {
            (new ActionRateLimiter())->consumeForUser('frizze-document-ai', (int) Auth::userId(), 8, 900);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/adm/frizze/kvitton');
        }

        $documentModel = new FrizzeDocument($this->pdo);
        $document = $documentModel->findById((int) $params['id']);
        if (!$document) {
            http_response_code(404);
            echo '<h1>Dokumentet hittades inte</h1>';
            return;
        }

        $path = $this->privateStoragePath((string) $document['file_path']);
        if ($path === null || !is_file($path)) {
            flash('error', 'Dokumentfilen hittades inte.');
            redirect('/adm/frizze/kvitton');
        }

        try {
            $interpreted = (new AiService())->interpretFrizzeDocument($document, $path);
        } catch (RuntimeException $e) {
            flash('error', 'AI-tolkningen misslyckades: ' . $e->getMessage());
            redirect('/adm/frizze/kvitton');
        }

        $interpretationId = (new FrizzeDocumentInterpretation($this->pdo))->create(
            (int) $document['id'],
            $interpreted,
            (int) Auth::userId()
        );

        SecurityAudit::log($this->pdo, 'frizze.document.interpreted', [
            'document_id' => (int) $document['id'],
            'interpretation_id' => $interpretationId,
        ], Auth::userId());

        flash('success', 'Dokumentet har tolkats. Granska innan journalen uppdateras.');
        redirect('/adm/frizze/tolkningar/' . $interpretationId . '/granska');
    }

    public function reviewInterpretation(array $params): void
    {
        Auth::requireLogin();

        $interpretation = (new FrizzeDocumentInterpretation($this->pdo))->findById((int) $params['id']);
        if (!$interpretation) {
            http_response_code(404);
            echo '<h1>Tolkningen hittades inte</h1>';
            return;
        }

        $draft = $this->interpretationDraft($interpretation);
        $documentTypes = $this->documentTypes();
        $eventTypes = $this->eventTypes();
        $pageTitle = 'Granska dokumenttolkning';

        view('frizze/interpretation-form', compact(
            'documentTypes',
            'draft',
            'eventTypes',
            'interpretation',
            'pageTitle'
        ));
    }

    public function saveInterpretation(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $model = new FrizzeDocumentInterpretation($this->pdo);
        $interpretation = $model->findById((int) $params['id']);
        if (!$interpretation) {
            http_response_code(404);
            return;
        }

        if ($interpretation['status'] === 'applied') {
            flash('error', 'Tolkningen är redan godkänd och applicerad.');
            redirect('/adm/frizze/tolkningar/' . (int) $interpretation['id'] . '/granska');
        }

        $edited = $this->interpretationInput();
        $model->updateEdited((int) $interpretation['id'], $edited, 'reviewed', (int) Auth::userId());

        flash('success', 'Tolkningen har sparats.');
        redirect('/adm/frizze/tolkningar/' . (int) $interpretation['id'] . '/granska');
    }

    public function applyInterpretation(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $interpretationModel = new FrizzeDocumentInterpretation($this->pdo);
        $interpretation = $interpretationModel->findById((int) $params['id']);
        if (!$interpretation) {
            http_response_code(404);
            return;
        }

        if ($interpretation['status'] === 'applied') {
            flash('error', 'Tolkningen är redan godkänd och applicerad.');
            redirect('/adm/frizze/journal');
        }

        $vehicle = $this->vehicle();
        $edited = $this->interpretationInput();
        if ($edited['title'] === '' || $edited['event_date'] === '') {
            flash('error', 'Titel och händelsedatum krävs innan journalhändelsen kan skapas.');
            redirect('/adm/frizze/tolkningar/' . (int) $interpretation['id'] . '/granska');
        }

        $this->pdo->beginTransaction();
        try {
            (new FrizzeDocument($this->pdo))->updateMetadata((int) $interpretation['document_id'], [
                'document_type' => $edited['document_type'],
                'title' => $edited['title'],
                'supplier' => $edited['supplier'],
                'document_date' => $edited['document_date'],
                'amount_total' => $edited['amount_total'],
                'currency' => $edited['currency'],
                'notes' => $edited['description'],
            ]);

            $eventId = (new FrizzeEvent($this->pdo))->create([
                'vehicle_id' => (int) $vehicle['id'],
                'document_id' => (int) $interpretation['document_id'],
                'event_type' => $edited['event_type'],
                'event_date' => $edited['event_date'],
                'event_time' => $edited['event_time'],
                'title' => $edited['title'],
                'supplier' => $edited['supplier'],
                'odometer_km' => $edited['odometer_km'],
                'amount_total' => $edited['amount_total'],
                'currency' => $edited['currency'],
                'description' => $edited['description'],
                'details' => $edited['details'],
                'created_by' => Auth::userId(),
            ]);

            $interpretationModel->markApplied((int) $interpretation['id'], $eventId, $edited, (int) Auth::userId());
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            flash('error', 'Kunde inte skapa journalhändelsen: ' . $e->getMessage());
            redirect('/adm/frizze/tolkningar/' . (int) $interpretation['id'] . '/granska');
        }

        SecurityAudit::log($this->pdo, 'frizze.document.interpretation.applied', [
            'document_id' => (int) $interpretation['document_id'],
            'interpretation_id' => (int) $interpretation['id'],
        ], Auth::userId());

        flash('success', 'Tolkningen är godkänd och journalhändelsen har skapats.');
        redirect('/adm/frizze/journal');
    }

    public function rejectInterpretation(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $model = new FrizzeDocumentInterpretation($this->pdo);
        $interpretation = $model->findById((int) $params['id']);
        if ($interpretation) {
            $model->markRejected((int) $interpretation['id'], (int) Auth::userId());
            SecurityAudit::log($this->pdo, 'frizze.document.interpretation.rejected', [
                'document_id' => (int) $interpretation['document_id'],
                'interpretation_id' => (int) $interpretation['id'],
            ], Auth::userId());
            flash('success', 'Tolkningen har avvisats.');
        }

        redirect('/adm/frizze/kvitton');
    }

    public function servicePlan(array $params): void
    {
        $this->render('service');
    }

    public function equipment(array $params): void
    {
        $this->render('equipment');
    }

    public function manual(array $params): void
    {
        $this->render('manual');
    }

    public function manualDocument(array $params): void
    {
        Auth::requireLogin();

        $document = $this->manualDocumentBySlug((string) ($params['slug'] ?? ''));
        if (!$document) {
            http_response_code(404);
            echo '<h1>Dokumentet hittades inte</h1>';
            return;
        }

        $markdown = file_get_contents($document['path']);
        if ($markdown === false) {
            http_response_code(404);
            echo '<h1>Dokumentet kunde inte läsas</h1>';
            return;
        }

        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);

        $documentHtml = $parsedown->text($this->rewriteManualMarkdownLinks($markdown));
        $pageTitle = $document['title'] . ' — Frizze';
        $tabs = $this->tabs();

        view('frizze/manual-document', compact('document', 'documentHtml', 'pageTitle', 'tabs'));
    }

    private function render(string $activeTab): void
    {
        Auth::requireLogin();

        $pageTitle = 'Frizze';
        $vehicle = $this->vehicle();
        $statusCards = $this->statusCards();
        $journal = $this->journalItems();
        $servicePlan = $this->servicePlanItems();
        $serviceTasks = $this->serviceTasks((int) $vehicle['id']);
        $documents = $this->documents((int) $vehicle['id']);
        $interpretations = $this->latestInterpretations($documents);
        $documentTypes = $this->documentTypes();
        $equipment = $this->equipmentGroups();
        $manualSections = $this->manualSections();
        $manualDocuments = $this->manualDocuments();
        $budget = $this->budget();
        $tabs = $this->tabs();

        view('frizze/index', compact(
            'activeTab',
            'budget',
            'documents',
            'documentTypes',
            'equipment',
            'interpretations',
            'journal',
            'manualDocuments',
            'manualSections',
            'pageTitle',
            'servicePlan',
            'serviceTasks',
            'statusCards',
            'tabs',
            'vehicle'
        ));
    }

    private function tabs(): array
    {
        return [
            'overview' => ['label' => 'Översikt', 'href' => '/adm/frizze'],
            'journal' => ['label' => 'Journal', 'href' => '/adm/frizze/journal'],
            'receipts' => ['label' => 'Dokument', 'href' => '/adm/frizze/kvitton'],
            'service' => ['label' => 'Serviceplan', 'href' => '/adm/frizze/serviceplan'],
            'equipment' => ['label' => 'Utrustning', 'href' => '/adm/frizze/utrustning'],
            'manual' => ['label' => 'Manual', 'href' => '/adm/frizze/manual'],
        ];
    }

    private function vehicle(): array
    {
        $vehicleModel = new FrizzeVehicle($this->pdo);
        $vehicle = $vehicleModel->primary();

        if ($vehicle) {
            return [
                'id' => (int) $vehicle['id'],
                'name' => $vehicle['name'],
                'model' => $vehicle['model'],
                'year' => (string) ($vehicle['model_year'] ?? ''),
                'base' => $vehicle['base_vehicle'] ?? '',
                'engine' => $vehicle['engine'] ?? '',
                'registration' => $vehicle['registration'] ?? '',
                'vin' => $vehicle['vin'] ?? '',
                'odometer' => !empty($vehicle['odometer_km']) ? 'ca ' . number_format((int) $vehicle['odometer_km'], 0, ',', ' ') . ' km' : '',
                'owner' => $vehicle['owner_name'] ?? '',
                'purchased_at' => $vehicle['purchased_at'] ?? '',
            ];
        }

        return [
            'id' => 1,
            'name' => 'Frizze',
            'model' => 'Adria Twin 600 SPT / SPT 600 Platinum',
            'year' => '2017',
            'base' => 'Citroën Jumper III',
            'engine' => '2.0 HDI / BlueHDi diesel',
            'registration' => 'ZLG267',
            'vin' => 'VF7YD3MFC12C73353',
            'odometer' => 'ca 100 000 km',
            'owner' => 'Mattias Pettersson',
            'purchased_at' => '2022-06-14',
        ];
    }

    private function statusCards(): array
    {
        return [
            [
                'label' => 'Nästa motorservice',
                'value' => 'Februari 2027',
                'meta' => 'Olja + oljefilter senast 12 månader eller ca 15 000 km efter 2026-02-04.',
                'state' => 'watch',
            ],
            [
                'label' => 'Gasol',
                'value' => 'Februari 2027',
                'meta' => 'Senast godkänd 2026-02-04 enligt EN 1949 hos Torvalla LCV.',
                'state' => 'ok',
            ],
            [
                'label' => 'Fukt / habitation',
                'value' => 'Att boka 2026',
                'meta' => 'Bodelkontroll saknas i nuvarande historik för 2026.',
                'state' => 'due',
            ],
            [
                'label' => 'Besiktning',
                'value' => 'Början av juni',
                'meta' => 'Bokad inom perioden juni-augusti.',
                'state' => 'ok',
            ],
            [
                'label' => 'Främre taklucka',
                'value' => 'Tätad',
                'meta' => 'Inväntar kraftigt regn/skyfall för verifiering.',
                'state' => 'watch',
            ],
            [
                'label' => 'Däck',
                'value' => 'Däckfirma bevakar',
                'meta' => 'Sommar- och vinterdäck förvaras hos däckfirma.',
                'state' => 'ok',
            ],
        ];
    }

    private function journalItems(): array
    {
        $vehicle = $this->vehicle();
        $eventModel = new FrizzeEvent($this->pdo);
        $events = $eventModel->all((int) $vehicle['id']);
        $eventTypes = $this->eventTypes();

        return array_map(function (array $event) use ($eventTypes): array {
            return [
                'id' => (int) $event['id'],
                'date' => $event['event_date'],
                'time' => $event['event_time'],
                'type' => $eventTypes[$event['event_type']] ?? $event['event_type'],
                'type_key' => $event['event_type'],
                'title' => $event['title'],
                'meta' => $this->eventMeta($event),
                'details' => $event['details'],
                'description' => $event['description'],
                'document_id' => $event['document_id'] ?? null,
                'document_title' => $event['document_title'] ?? null,
            ];
        }, $events);
    }

    private function documents(int $vehicleId): array
    {
        $documentModel = new FrizzeDocument($this->pdo);
        return $documentModel->allForVehicle($vehicleId);
    }

    private function latestInterpretations(array $documents): array
    {
        $ids = array_map(static fn(array $document): int => (int) $document['id'], $documents);
        return (new FrizzeDocumentInterpretation($this->pdo))->latestByDocumentIds($ids);
    }

    private function documentTypes(): array
    {
        return [
            'receipt' => 'Kvitto',
            'invoice' => 'Faktura',
            'protocol' => 'Protokoll',
            'photo' => 'Foto',
            'manual' => 'Manual',
            'other' => 'Annat',
        ];
    }

    private function detectUploadMimeType(string $tmpPath): string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($tmpPath) ?: 'application/octet-stream';
    }

    private function privateDocumentStorageDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/frizze/documents';
    }

    private function privateStoragePath(string $relativePath): ?string
    {
        $storageRoot = realpath(dirname(__DIR__, 2) . '/storage');
        if ($storageRoot === false || str_contains($relativePath, '..')) {
            return null;
        }

        $path = $storageRoot . '/' . ltrim($relativePath, '/');
        $dir = realpath(dirname($path));
        if ($dir === false || !str_starts_with($dir, $storageRoot . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $path;
    }

    private function downloadFilename(array $document): string
    {
        $filename = $document['original_filename'] ?: $document['title'];
        return preg_replace('/[^A-Za-z0-9._ -]/', '_', (string) $filename) ?: 'frizze-dokument';
    }

    private function serviceTasks(int $vehicleId): array
    {
        $taskModel = new FrizzeServiceTask($this->pdo);
        return $taskModel->allForVehicle($vehicleId);
    }

    private function eventTypes(): array
    {
        return [
            'service' => 'Service',
            'repair' => 'Reparation',
            'control' => 'Kontroll',
            'inspection' => 'Besiktning',
            'cost' => 'Kostnad',
            'note' => 'Notering',
        ];
    }

    private function eventInput(int $vehicleId): array
    {
        $amount = str_replace(',', '.', trim($_POST['amount_total'] ?? ''));
        $odometer = trim($_POST['odometer_km'] ?? '');

        return [
            'vehicle_id' => $vehicleId,
            'document_id' => !empty($_POST['document_id']) ? (int) $_POST['document_id'] : null,
            'event_type' => array_key_exists($_POST['event_type'] ?? '', $this->eventTypes()) ? $_POST['event_type'] : 'note',
            'event_date' => trim($_POST['event_date'] ?? ''),
            'event_time' => trim($_POST['event_time'] ?? '') ?: null,
            'title' => trim($_POST['title'] ?? ''),
            'supplier' => trim($_POST['supplier'] ?? '') ?: null,
            'odometer_km' => $odometer !== '' ? max(0, (int) $odometer) : null,
            'amount_total' => $amount !== '' && is_numeric($amount) ? (float) $amount : null,
            'currency' => 'SEK',
            'description' => trim($_POST['description'] ?? '') ?: null,
            'details' => preg_split('/\R/', trim($_POST['details'] ?? '')) ?: [],
            'created_by' => Auth::userId(),
        ];
    }

    private function interpretationDraft(array $interpretation): array
    {
        $source = $interpretation['edited'] ?: $interpretation['interpreted'];
        $documentDate = $source['document_date'] ?? null;

        return [
            'document_type' => $this->validDocumentType($source['document_type'] ?? $interpretation['document_type'] ?? 'other'),
            'title' => trim((string) ($source['title'] ?? $interpretation['document_title'] ?? '')),
            'supplier' => trim((string) ($source['supplier'] ?? '')),
            'document_date' => $this->dateValue($documentDate),
            'event_type' => $this->validEventType($source['event_type'] ?? 'note'),
            'event_date' => $this->dateValue($source['event_date'] ?? $documentDate),
            'event_time' => $this->timeValue($source['event_time'] ?? null),
            'odometer_km' => isset($source['odometer_km']) && $source['odometer_km'] !== null ? (string) (int) $source['odometer_km'] : '',
            'amount_total' => isset($source['amount_total']) && $source['amount_total'] !== null ? (string) $source['amount_total'] : '',
            'currency' => trim((string) ($source['currency'] ?? 'SEK')) ?: 'SEK',
            'description' => trim((string) ($source['description'] ?? '')),
            'details' => is_array($source['details'] ?? null) ? $source['details'] : [],
            'confidence' => trim((string) ($source['confidence'] ?? '')),
            'needs_review' => is_array($source['needs_review'] ?? null) ? $source['needs_review'] : [],
        ];
    }

    private function interpretationInput(): array
    {
        $amount = str_replace(',', '.', trim($_POST['amount_total'] ?? ''));
        $odometer = trim($_POST['odometer_km'] ?? '');

        return [
            'document_type' => $this->validDocumentType($_POST['document_type'] ?? 'other'),
            'title' => trim($_POST['title'] ?? ''),
            'supplier' => trim($_POST['supplier'] ?? '') ?: null,
            'document_date' => $this->dateValue($_POST['document_date'] ?? null) ?: null,
            'event_type' => $this->validEventType($_POST['event_type'] ?? 'note'),
            'event_date' => $this->dateValue($_POST['event_date'] ?? null),
            'event_time' => $this->timeValue($_POST['event_time'] ?? null) ?: null,
            'odometer_km' => $odometer !== '' ? max(0, (int) $odometer) : null,
            'amount_total' => $amount !== '' && is_numeric($amount) ? (float) $amount : null,
            'currency' => trim($_POST['currency'] ?? 'SEK') ?: 'SEK',
            'description' => trim($_POST['description'] ?? '') ?: null,
            'details' => $this->linesFromTextarea($_POST['details'] ?? ''),
            'confidence' => trim($_POST['confidence'] ?? ''),
            'needs_review' => $this->linesFromTextarea($_POST['needs_review'] ?? ''),
        ];
    }

    private function validDocumentType(string $value): string
    {
        return array_key_exists($value, $this->documentTypes()) ? $value : 'other';
    }

    private function validEventType(string $value): string
    {
        return array_key_exists($value, $this->eventTypes()) ? $value : 'note';
    }

    private function dateValue(mixed $value): string
    {
        $text = trim((string) $value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $text) ? $text : '';
    }

    private function timeValue(mixed $value): string
    {
        $text = trim((string) $value);
        return preg_match('/^\d{2}:\d{2}$/', $text) ? $text : '';
    }

    private function linesFromTextarea(mixed $value): array
    {
        return array_values(array_filter(array_map(
            static fn($line) => trim((string) $line),
            preg_split('/\R/', trim((string) $value)) ?: []
        )));
    }

    private function emptyEvent(): array
    {
        return [
            'id' => null,
            'document_id' => null,
            'event_type' => 'service',
            'event_date' => date('Y-m-d'),
            'event_time' => null,
            'title' => '',
            'supplier' => '',
            'odometer_km' => null,
            'amount_total' => null,
            'description' => '',
            'details' => [],
        ];
    }

    private function eventMeta(array $event): string
    {
        $parts = [];

        if (!empty($event['supplier'])) {
            $parts[] = $event['supplier'];
        }

        if (!empty($event['odometer_km'])) {
            $parts[] = 'ca ' . number_format((int) $event['odometer_km'], 0, ',', ' ') . ' km';
        }

        if ($event['amount_total'] !== null && $event['amount_total'] !== '') {
            $parts[] = number_format((float) $event['amount_total'], 0, ',', ' ') . ' kr';
        }

        if (!empty($event['description'])) {
            $parts[] = $event['description'];
        }

        return implode(', ', $parts);
    }

    private function servicePlanItems(): array
    {
        return [
            [
                'year' => '2026',
                'km' => 'ca 100 000',
                'summary' => 'Motorservice och gasol är gjort. Kvar att komma ikapp är fukttest/habitation check om det inte redan är gjort.',
                'items' => ['Besiktning bokad i början av juni', 'Verifiera tätad taklucka vid skyfall'],
            ],
            [
                'year' => '2027',
                'km' => '~115 000',
                'summary' => 'Årlig service med extra fokus på bromsvätska och kupéfilter.',
                'items' => ['Olja + oljefilter', 'Diagnostik', 'Gasol', 'Fukttest', 'Habitation check', 'Bromsvätska', 'Kupéfilter'],
            ],
            [
                'year' => '2028',
                'km' => '~130 000',
                'summary' => 'Årlig service och större kontroll av bromsar/hjulupphängning vid behov.',
                'items' => ['Olja + oljefilter', 'Gasol', 'Fukt', 'Habitation', 'AC vid behov'],
            ],
            [
                'year' => '2030',
                'km' => '~160 000',
                'summary' => 'Nästa större filterservice och kylvätska.',
                'items' => ['Dieselfilter', 'Luftfilter', 'Kupéfilter', 'Bromsvätska', 'Kylvätska'],
            ],
            [
                'year' => '2035',
                'km' => '~235 000',
                'summary' => 'Kamremspaket enligt ny rem från 2025.',
                'items' => ['Kamrem', 'Spännare', 'Vattenpump'],
            ],
        ];
    }

    private function equipmentGroups(): array
    {
        return [
            'Basfordon' => [
                'Citroën Jumper III',
                '2.0 HDI / BlueHDi diesel',
                '6-växlad manuell',
                'Framhjulsdrift',
            ],
            'Bodel' => [
                'Truma Combi 4, gasol',
                'Fast säng med uppfällbar mittdel',
                'Kassetttoalett och dusch',
                'Thule Omnistor 6300 markis',
            ],
            'El' => [
                '2 x SunLux Lithium 100Ah',
                '40A DC/DC-laddare med boost till startbatteri',
                '120W solceller',
                'Victron 12V/30A solcellsregulator',
            ],
            'Hjul' => [
                'Sommar: 225/75 R16 Kumho på lättmetall',
                'Vinter: 225/75 R16 Nokian dubbfria på stålfälg',
                'Förvaras hos däckfirma som bevakar byte',
            ],
            'Tillbehör' => [
                'One Beam Air High lufttält',
                'Cadac Safari Chef 2',
                'Petromax Atago',
                'PC10 + PC5 gasolflaskor',
            ],
        ];
    }

    private function manualSections(): array
    {
        return [
            [
                'title' => 'Snabbsvar',
                'items' => [
                    ['label' => 'Nästa service', 'value' => 'Februari 2027 eller ca 15 000 km efter 2026-02-04'],
                    ['label' => 'Nästa gasol', 'value' => 'Februari 2027'],
                    ['label' => 'Nästa kamrem', 'value' => 'ca 2035 eller ca 235 000 km'],
                    ['label' => 'Rekommenderad olja', 'value' => 'PSA B71 2312 / PSA B71 2290'],
                ],
            ],
            [
                'title' => 'Dokument',
                'items' => [
                    ['label' => 'Fordonsidentifikation', 'value' => 'Chassi, reg.nr, besiktning, försäkring'],
                    ['label' => 'Servicehistorik', 'value' => 'Service, fakturor, reparationer'],
                    ['label' => 'Gasolcertifikat', 'value' => 'Gasolprotokoll och fukt/täthet'],
                    ['label' => 'Vintercamping', 'value' => 'Truma, FrostControl, tömning av vatten'],
                ],
            ],
        ];
    }

    private function manualDocuments(): array
    {
        $files = [
            '00-README.md',
            '01-fordon-identifikation.md',
            '02-servicehistorik.md',
            '03-gasolcertifikat.md',
            '04-citroen-underhall.md',
            '05-adria-manual-bostadsdel.md',
            '06-adria-manual-sovplatser.md',
            '07-adria-manual-elforsorjning.md',
            '08-adria-manual-gasol.md',
            '09-adria-manual-sakerhet.md',
            '10-utrustning-specifikation.md',
            '11-underhallsstatus.md',
            '12-serviceplan-2027-2031.md',
            '13-kanda-problem.md',
            '14-tekniska-specifikationer.md',
            '15-vintercamping.md',
        ];

        $documents = [];
        foreach ($files as $filename) {
            $path = $this->manualDocumentRoot() . '/' . $filename;
            if (!is_file($path)) {
                continue;
            }

            $slug = substr($filename, 0, -3);
            $documents[] = [
                'filename' => $filename,
                'slug' => $slug,
                'path' => $path,
                'title' => $this->manualTitle($path, $filename),
                'summary' => $this->manualSummary($filename),
                'href' => '/adm/frizze/manual/' . rawurlencode($slug),
            ];
        }

        return $documents;
    }

    private function manualDocumentBySlug(string $slug): ?array
    {
        foreach ($this->manualDocuments() as $document) {
            if ($document['slug'] === $slug) {
                return $document;
            }
        }

        return null;
    }

    private function manualDocumentRoot(): string
    {
        return dirname(__DIR__) . '/Data/frizze-docs';
    }

    private function manualTitle(string $path, string $fallback): string
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return $fallback;
        }

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if (str_starts_with($line, '# ')) {
                fclose($handle);
                return trim(substr($line, 2));
            }
        }

        fclose($handle);
        return $fallback;
    }

    private function manualSummary(string $filename): string
    {
        $summaries = [
            '00-README.md' => 'Översikt och snabbreferens för hela dokumentationen.',
            '01-fordon-identifikation.md' => 'Chassi, registrering, försäkring, besiktning och garanti.',
            '02-servicehistorik.md' => 'Servicejournal, fakturor, reparationer och historiska arbeten.',
            '03-gasolcertifikat.md' => 'Gasolintyg och täthetskontroller.',
            '04-citroen-underhall.md' => 'Citroën/PSA-intervaller och basfordonsunderhåll.',
            '05-adria-manual-bostadsdel.md' => 'Bodel, fönster, luckor, ventilation och vardagsanvändning.',
            '06-adria-manual-sovplatser.md' => 'Sängar, bäddning och sovplatslösningar.',
            '07-adria-manual-elforsorjning.md' => '230 V, 12 V, säkringar, batterier och laddning.',
            '08-adria-manual-gasol.md' => 'Gasolsystem, flaskbyte, regulator och säker hantering.',
            '09-adria-manual-sakerhet.md' => 'Säkerhet, brand, ventilation och larm.',
            '10-utrustning-specifikation.md' => 'Utrustning, tillbehör och specifikationer.',
            '11-underhallsstatus.md' => 'Vad som är gjort, status och kommande åtgärder.',
            '12-serviceplan-2027-2031.md' => 'Praktisk serviceplan och budget framåt.',
            '13-kanda-problem.md' => 'Kända problem, bevakningspunkter och felsökning.',
            '14-tekniska-specifikationer.md' => 'Mått, tekniska data och referensvärden.',
            '15-vintercamping.md' => 'Vinterdrift, vatten, värme och vinterförvaring.',
        ];

        return $summaries[$filename] ?? 'Internt Frizze-dokument.';
    }

    private function rewriteManualMarkdownLinks(string $markdown): string
    {
        $slugByFilename = [];
        foreach ($this->manualDocuments() as $document) {
            $slugByFilename[$document['filename']] = $document['slug'];
        }

        return preg_replace_callback(
            '/\]\((\.\/)?([^)\s#]+\.md)(#[^)]+)?\)/',
            static function (array $match) use ($slugByFilename): string {
                $filename = basename($match[2]);
                if (!isset($slugByFilename[$filename])) {
                    return $match[0];
                }

                $anchor = $match[3] ?? '';
                return '](/adm/frizze/manual/' . rawurlencode($slugByFilename[$filename]) . $anchor . ')';
            },
            $markdown
        ) ?? $markdown;
    }

    private function budget(): array
    {
        return [
            'year' => '2027',
            'planned' => '8 000-12 000 kr',
            'buffer' => '15 000 kr',
            'lines' => [
                ['label' => 'Årlig service + gasol', 'amount' => '4 000-6 000 kr'],
                ['label' => 'Bromsvätska + kupéfilter/extra kontroll', 'amount' => '2 000-4 000 kr'],
                ['label' => 'Fukttest / habitation', 'amount' => '2 000-4 000 kr'],
            ],
        ];
    }
}
