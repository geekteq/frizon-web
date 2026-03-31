<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Models/Trip.php';
require_once dirname(__DIR__) . '/Models/TripStop.php';
require_once dirname(__DIR__) . '/Models/TripRouteSegment.php';
require_once dirname(__DIR__) . '/Models/Place.php';
require_once dirname(__DIR__) . '/Services/Export/GpxTripExporter.php';
require_once dirname(__DIR__) . '/Services/Export/CsvTripExporter.php';
require_once dirname(__DIR__) . '/Services/Export/JsonTripExporter.php';
require_once dirname(__DIR__) . '/Services/Export/GoogleMapsLinkExporter.php';

class TripController
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function index(array $params): void
    {
        Auth::requireLogin();
        $tripModel = new Trip($this->pdo);
        $trips = $tripModel->all();

        $grouped = ['ongoing' => [], 'planned' => [], 'finished' => []];
        foreach ($trips as $trip) {
            $grouped[$trip['status']][] = $trip;
        }

        $pageTitle = 'Resor';
        view('trips/index', compact('grouped', 'pageTitle'));
    }

    public function create(array $params): void
    {
        Auth::requireLogin();
        $pageTitle = 'Ny resa';
        view('trips/create', compact('pageTitle'));
    }

    public function store(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $title = trim($_POST['title'] ?? '');
        if ($title === '') {
            flash('error', 'Resenamn krävs.');
            redirect('/adm/resor/ny');
        }

        $tripModel = new Trip($this->pdo);
        $tripModel->create([
            'slug'       => Trip::generateSlug($title),
            'title'      => $title,
            'intro_text' => trim($_POST['intro_text'] ?? '') ?: null,
            'status'     => $_POST['status'] ?? 'planned',
            'created_by' => Auth::userId(),
            'start_date' => $_POST['start_date'] ?: null,
            'end_date'   => $_POST['end_date'] ?: null,
        ]);

        flash('success', 'Resan har skapats!');
        redirect('/adm/resor');
    }

    public function show(array $params): void
    {
        Auth::requireLogin();
        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if (!$trip) { http_response_code(404); echo '<h1>Resan hittades inte</h1>'; return; }

        $stopModel = new TripStop($this->pdo);
        $stops = $stopModel->findByTrip((int) $trip['id']);

        $segmentModel = new TripRouteSegment($this->pdo);
        $segments = $segmentModel->findByTrip((int) $trip['id']);

        $summary = $tripModel->summary((int) $trip['id']);

        // All places for "add stop" dropdown
        $placeModel = new Place($this->pdo);
        $allPlaces = $placeModel->all();

        $pageTitle = $trip['title'];
        view('trips/show', compact('trip', 'stops', 'segments', 'summary', 'allPlaces', 'pageTitle'));
    }

    public function edit(array $params): void
    {
        Auth::requireLogin();
        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if (!$trip) { http_response_code(404); return; }

        $pageTitle = 'Redigera ' . $trip['title'];
        view('trips/edit', compact('trip', 'pageTitle'));
    }

    public function update(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if (!$trip) { http_response_code(404); return; }

        $tripModel->update((int) $trip['id'], [
            'title'      => trim($_POST['title'] ?? $trip['title']),
            'intro_text' => trim($_POST['intro_text'] ?? '') ?: null,
            'status'     => $_POST['status'] ?? $trip['status'],
            'start_date' => $_POST['start_date'] ?: null,
            'end_date'   => $_POST['end_date'] ?: null,
        ]);

        flash('success', 'Resan har uppdaterats.');
        redirect('/adm/resor/' . $params['slug']);
    }

    public function destroy(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if ($trip) {
            $tripModel->delete((int) $trip['id']);
            flash('success', 'Resan har tagits bort.');
        }
        redirect('/adm/resor');
    }

    public function addStop(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if (!$trip) { http_response_code(404); return; }

        $placeId = (int) ($_POST['place_id'] ?? 0);
        if ($placeId === 0) {
            flash('error', 'Välj en plats.');
            redirect('/adm/resor/' . $params['slug']);
        }

        $stopModel = new TripStop($this->pdo);
        $stopModel->add(
            (int) $trip['id'],
            $placeId,
            $_POST['stop_type'] ?? null,
            trim($_POST['note'] ?? '') ?: null
        );

        flash('success', 'Hållplats tillagd!');
        redirect('/adm/resor/' . $params['slug']);
    }

    public function removeStop(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $stopModel = new TripStop($this->pdo);
        $stop = $stopModel->findById((int) $params['stopId']);
        if (!$stop) { redirect('/adm/resor'); return; }

        // Find trip slug for redirect
        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findById((int) $stop['trip_id']);

        $stopModel->remove((int) $params['stopId']);

        flash('success', 'Hållplatsen har tagits bort.');
        redirect('/adm/resor/' . ($trip['slug'] ?? ''));
    }

    public function reorderStops(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if (!$trip) { http_response_code(404); return; }

        $input = json_decode(file_get_contents('php://input'), true);
        $stopIds = $input['stop_ids'] ?? [];

        if (!empty($stopIds)) {
            $stopModel = new TripStop($this->pdo);
            $stopModel->reorder((int) $trip['id'], $stopIds);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    public function calculateRoute(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if (!$trip) { http_response_code(404); return; }

        $stopModel = new TripStop($this->pdo);
        $stops = $stopModel->findByTrip((int) $trip['id']);

        if (count($stops) < 2) {
            flash('error', 'Minst två hållplatser krävs för att beräkna rutt.');
            redirect('/adm/resor/' . $params['slug']);
        }

        // Get route provider
        $provider = $this->getRouteProvider();

        $segmentModel = new TripRouteSegment($this->pdo);
        $segmentModel->deleteByTrip((int) $trip['id']);

        for ($i = 0; $i < count($stops) - 1; $i++) {
            $from = $stops[$i];
            $to = $stops[$i + 1];

            $routeData = $provider->getRoute(
                (float) $from['lat'], (float) $from['lng'],
                (float) $to['lat'], (float) $to['lng']
            );

            $segmentModel->saveSegment(
                (int) $trip['id'],
                (int) $from['id'],
                (int) $to['id'],
                $routeData
            );
        }

        flash('success', 'Rutten har beräknats!');
        redirect('/adm/resor/' . $params['slug']);
    }

    public function exportGpx(array $params): void
    {
        Auth::requireLogin();

        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if (!$trip) { http_response_code(404); return; }

        $stopModel = new TripStop($this->pdo);
        $stops = $stopModel->findByTrip((int) $trip['id']);

        $exporter = new GpxTripExporter();
        $exporter->download($trip, $stops);
    }

    public function exportCsv(array $params): void
    {
        Auth::requireLogin();

        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if (!$trip) { http_response_code(404); return; }

        $stopModel = new TripStop($this->pdo);
        $stops = $stopModel->findByTrip((int) $trip['id']);

        $exporter = new CsvTripExporter();
        $exporter->download($trip, $stops);
    }

    public function exportJson(array $params): void
    {
        Auth::requireLogin();

        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if (!$trip) { http_response_code(404); return; }

        $stopModel = new TripStop($this->pdo);
        $stops = $stopModel->findByTrip((int) $trip['id']);

        $exporter = new JsonTripExporter();
        $exporter->download($trip, $stops);
    }

    public function exportGoogleMaps(array $params): void
    {
        Auth::requireLogin();

        $tripModel = new Trip($this->pdo);
        $trip = $tripModel->findBySlug($params['slug']);
        if (!$trip) { http_response_code(404); return; }

        $stopModel = new TripStop($this->pdo);
        $stops = $stopModel->findByTrip((int) $trip['id']);

        $exporter = new GoogleMapsLinkExporter();
        $exporter->download($trip, $stops);
    }

    private function getRouteProvider(): RouteProviderInterface
    {
        $apiKey = $_ENV['ORS_API_KEY'] ?? '';

        if ($apiKey !== '') {
            require_once dirname(__DIR__) . '/Services/Routing/OpenRouteServiceProvider.php';
            return new OpenRouteServiceProvider($apiKey);
        }

        require_once dirname(__DIR__) . '/Services/Routing/FakeRouteProvider.php';
        return new FakeRouteProvider();
    }
}
