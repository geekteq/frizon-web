<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Services/SecurityAudit.php';
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
            SELECT v.*, v.ready_for_publish, vr.total_rating_cached FROM visits v
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

        SecurityAudit::log($this->pdo, 'place.created', [
            'place_name' => $name,
        ], Auth::userId());
        flash('success', 'Platsen har sparats!');
        redirect('/adm/platser');
    }

    public function edit(array $params): void
    {
        Auth::requireLogin();
        require_once dirname(__DIR__) . '/Models/AmazonProduct.php';

        $place = new Place($this->pdo);
        $p = $place->findBySlug($params['slug']);
        if (!$p) { http_response_code(404); echo '<h1>Platsen hittades inte</h1>'; return; }

        $productModel       = new AmazonProduct($this->pdo);
        $allProducts        = $productModel->allPublished();
        $attachedProductIds = array_map('intval', array_column($productModel->getByPlaceId((int) $p['id']), 'id'));

        $pageTitle = 'Redigera ' . $p['name'];
        view('places/edit', compact('p', 'pageTitle', 'allProducts', 'attachedProductIds'));
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
            'meta_description'    => trim($_POST['meta_description'] ?? '') ?: null,
            'faq_content'         => $this->buildFaqContent(),
        ]);

        // Sync place products
        require_once dirname(__DIR__) . '/Models/AmazonProduct.php';
        $productIds = array_map('intval', (array) ($_POST['product_ids'] ?? []));
        (new AmazonProduct($this->pdo))->syncPlaceProducts((int) $p['id'], $productIds);

        SecurityAudit::log($this->pdo, 'place.updated', [
            'place_id' => (int) $p['id'],
            'place_slug' => $p['slug'],
        ], Auth::userId());
        flash('success', 'Platsen har uppdaterats.');
        redirect('/adm/platser/' . $params['slug']);
    }

    public function destroy(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();
        $place = new Place($this->pdo);
        $p = $place->findBySlug($params['slug']);
        if ($p) {
            $place->delete((int) $p['id']);
            SecurityAudit::log($this->pdo, 'place.deleted', [
                'place_id' => (int) $p['id'],
                'place_slug' => $p['slug'],
            ], Auth::userId());
            flash('success', 'Platsen har tagits bort.');
        }
        redirect('/adm/platser');
    }

    private function buildFaqContent(): ?string
    {
        $questions = (array) ($_POST['faq_q'] ?? []);
        $answers   = (array) ($_POST['faq_a'] ?? []);
        $faq       = [];
        foreach ($questions as $i => $q) {
            $q = trim($q);
            $a = trim($answers[$i] ?? '');
            if ($q !== '' && $a !== '') {
                $faq[] = ['q' => $q, 'a' => $a];
            }
        }
        return empty($faq) ? null : json_encode($faq, JSON_UNESCAPED_UNICODE);
    }

    public function setPreviewImage(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $placeModel = new Place($this->pdo);
        $place = $placeModel->findBySlug($params['slug']);
        if (!$place) { http_response_code(404); return; }

        $imageId = (int) ($_POST['image_id'] ?? 0);

        // Verify image belongs to a visit on this place
        $stmt = $this->pdo->prepare('
            SELECT vi.id FROM visit_images vi
            JOIN visits v ON v.id = vi.visit_id
            WHERE vi.id = ? AND v.place_id = ?
        ');
        $stmt->execute([$imageId, $place['id']]);
        if (!$stmt->fetch()) {
            flash('error', 'Bilden hör inte till denna plats.');
            redirect('/adm/platser/' . $params['slug']);
            return;
        }

        $this->pdo->prepare('UPDATE places SET preview_image_id = ?, updated_at = NOW() WHERE id = ?')
                   ->execute([$imageId, $place['id']]);

        SecurityAudit::log($this->pdo, 'place.preview_image_set', [
            'place_id' => $place['id'],
            'image_id' => $imageId,
        ], Auth::userId());

        flash('success', 'Platsbild uppdaterad.');
        redirect('/adm/platser/' . $params['slug']);
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
