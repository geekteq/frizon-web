<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Models/Place.php';

class PlaceController
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
        $place = new Place($this->pdo);
        $filters = [
            'place_type'   => $_GET['type'] ?? null,
            'country_code' => $_GET['country'] ?? null,
            'search'       => $_GET['q'] ?? null,
        ];
        $places = $place->all($filters);
        $pageTitle = 'Platser';
        view('places/index', compact('places', 'pageTitle', 'filters'));
    }

    public function show(array $params): void
    {
        Auth::requireLogin();
        $place = new Place($this->pdo);
        $p = $place->findBySlug($params['slug']);
        if (!$p) { http_response_code(404); echo '<h1>Platsen hittades inte</h1>'; return; }

        $stmt = $this->pdo->prepare('
            SELECT v.*, vr.total_rating_cached FROM visits v
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE v.place_id = ? ORDER BY v.visited_at DESC
        ');
        $stmt->execute([$p['id']]);
        $visits = $stmt->fetchAll();

        $tagStmt = $this->pdo->prepare('SELECT tag FROM place_tags WHERE place_id = ?');
        $tagStmt->execute([$p['id']]);
        $tags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

        $pageTitle = $p['name'];
        view('places/show', compact('p', 'visits', 'tags', 'pageTitle'));
    }

    public function create(array $params): void
    {
        Auth::requireLogin();
        $pageTitle = 'Ny plats';
        view('places/create', compact('pageTitle'));
    }

    public function store(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $name = trim($_POST['name'] ?? '');
        $lat = (float) ($_POST['lat'] ?? 0);
        $lng = (float) ($_POST['lng'] ?? 0);

        if ($name === '' || $lat === 0.0 || $lng === 0.0) {
            flash('error', 'Namn och koordinater krävs.');
            redirect('/adm/platser/ny');
        }

        $place = new Place($this->pdo);
        $place->create([
            'slug'                => Place::generateSlug($name),
            'name'                => $name,
            'lat'                 => $lat,
            'lng'                 => $lng,
            'address_text'        => trim($_POST['address_text'] ?? '') ?: null,
            'country_code'        => trim($_POST['country_code'] ?? '') ?: null,
            'place_type'          => $_POST['place_type'] ?? 'stellplatz',
            'default_public_text' => trim($_POST['default_public_text'] ?? '') ?: null,
            'created_by'          => Auth::userId(),
        ]);

        flash('success', 'Platsen har sparats!');
        redirect('/adm/platser');
    }

    public function edit(array $params): void
    {
        Auth::requireLogin();
        $place = new Place($this->pdo);
        $p = $place->findBySlug($params['slug']);
        if (!$p) { http_response_code(404); echo '<h1>Platsen hittades inte</h1>'; return; }
        $pageTitle = 'Redigera ' . $p['name'];
        view('places/edit', compact('p', 'pageTitle'));
    }

    public function update(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();
        $place = new Place($this->pdo);
        $p = $place->findBySlug($params['slug']);
        if (!$p) { http_response_code(404); return; }

        $place->update((int) $p['id'], [
            'name'                => trim($_POST['name'] ?? $p['name']),
            'lat'                 => (float) ($_POST['lat'] ?? $p['lat']),
            'lng'                 => (float) ($_POST['lng'] ?? $p['lng']),
            'address_text'        => trim($_POST['address_text'] ?? '') ?: null,
            'country_code'        => trim($_POST['country_code'] ?? '') ?: null,
            'place_type'          => $_POST['place_type'] ?? $p['place_type'],
            'default_public_text' => trim($_POST['default_public_text'] ?? '') ?: null,
        ]);

        flash('success', 'Platsen har uppdaterats.');
        redirect('/adm/platser/' . $params['slug']);
    }

    public function destroy(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();
        $place = new Place($this->pdo);
        $p = $place->findBySlug($params['slug']);
        if ($p) { $place->delete((int) $p['id']); flash('success', 'Platsen har tagits bort.'); }
        redirect('/adm/platser');
    }

    public function nearby(array $params): void
    {
        Auth::requireLogin();
        $lat = (float) ($_GET['lat'] ?? 0);
        $lng = (float) ($_GET['lng'] ?? 0);
        if ($lat === 0.0 || $lng === 0.0) {
            header('Content-Type: application/json');
            echo json_encode(['places' => []]);
            return;
        }
        $place = new Place($this->pdo);
        $nearby = $place->findNearby($lat, $lng, $this->config['nearby_radius_meters']);
        header('Content-Type: application/json');
        echo json_encode(['places' => $nearby]);
    }
}
