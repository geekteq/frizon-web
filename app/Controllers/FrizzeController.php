<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Services/SecurityAudit.php';
require_once dirname(__DIR__) . '/Models/FrizzeDocument.php';
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

    private function render(string $activeTab): void
    {
        Auth::requireLogin();

        $pageTitle = 'Frizze';
        $vehicle = $this->vehicle();
        $statusCards = $this->statusCards();
        $journal = $this->journalItems();
        $servicePlan = $this->servicePlanItems();
        $serviceTasks = $this->serviceTasks((int) $vehicle['id']);
        $equipment = $this->equipmentGroups();
        $manualSections = $this->manualSections();
        $budget = $this->budget();
        $tabs = $this->tabs();

        view('frizze/index', compact(
            'activeTab',
            'budget',
            'equipment',
            'journal',
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
            'receipts' => ['label' => 'Kvitton', 'href' => '/adm/frizze/kvitton'],
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
                'value' => 'April 2027',
                'meta' => 'Olja + oljefilter senast 12 månader eller ca 15 000 km efter 2026-04-02.',
                'state' => 'watch',
            ],
            [
                'label' => 'Gasol',
                'value' => 'April 2027',
                'meta' => 'Senast godkänd 2026-04-02 enligt EN 1949 hos Torvalla LCV.',
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
                'document_title' => $event['document_title'] ?? null,
            ];
        }, $events);
    }

    private function documents(int $vehicleId): array
    {
        $documentModel = new FrizzeDocument($this->pdo);
        return $documentModel->allForVehicle($vehicleId);
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
                    ['label' => 'Nästa service', 'value' => 'April 2027 eller ca 15 000 km efter 2026-04-02'],
                    ['label' => 'Nästa gasol', 'value' => 'April 2027'],
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
