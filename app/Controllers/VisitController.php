<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Services/ImageService.php';
require_once dirname(__DIR__) . '/Services/AiService.php';
require_once dirname(__DIR__) . '/Models/Place.php';
require_once dirname(__DIR__) . '/Models/Visit.php';
require_once dirname(__DIR__) . '/Models/VisitRating.php';
require_once dirname(__DIR__) . '/Models/VisitImage.php';

class VisitController
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function create(array $params): void
    {
        Auth::requireLogin();
        $placeModel = new Place($this->pdo);
        $p = $placeModel->findBySlug($params['slug']);

        if (!$p) {
            http_response_code(404);
            return;
        }

        $visitModel = new Visit($this->pdo);
        $suitableForSuggestions = $visitModel->suitableForValues();

        $pageTitle = 'Nytt besök — ' . $p['name'];
        view('visits/create', compact('p', 'pageTitle', 'suitableForSuggestions'));
    }

    public function store(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $placeModel = new Place($this->pdo);
        $p = $placeModel->findBySlug($params['slug']);
        if (!$p) { http_response_code(404); return; }

        $visitModel = new Visit($this->pdo);
        $visitId = $visitModel->create([
            'place_id'       => $p['id'],
            'user_id'        => Auth::userId(),
            'visited_at'     => $_POST['visited_at'] ?? date('Y-m-d'),
            'raw_note'       => trim($_POST['raw_note'] ?? '') ?: null,
            'plus_notes'     => trim($_POST['plus_notes'] ?? '') ?: null,
            'minus_notes'    => trim($_POST['minus_notes'] ?? '') ?: null,
            'tips_notes'     => trim($_POST['tips_notes'] ?? '') ?: null,
            'price_level'    => $_POST['price_level'] ?? null,
            'would_return'   => $_POST['would_return'] ?? null,
            'suitable_for'   => trim($_POST['suitable_for'] ?? '') ?: null,
            'things_to_note' => trim($_POST['things_to_note'] ?? '') ?: null,
        ]);

        // Save ratings
        $ratingModel = new VisitRating($this->pdo);
        $ratingModel->save($visitId, [
            'location_rating'     => !empty($_POST['location_rating']) ? (int) $_POST['location_rating'] : null,
            'calmness_rating'     => !empty($_POST['calmness_rating']) ? (int) $_POST['calmness_rating'] : null,
            'service_rating'      => !empty($_POST['service_rating']) ? (int) $_POST['service_rating'] : null,
            'value_rating'        => !empty($_POST['value_rating']) ? (int) $_POST['value_rating'] : null,
            'return_value_rating' => !empty($_POST['return_value_rating']) ? (int) $_POST['return_value_rating'] : null,
        ]);

        // Handle image uploads
        if (!empty($_FILES['photos']['name'][0])) {
            $imageService = new ImageService($this->config);
            $imageModel   = new VisitImage($this->pdo);
            $aiService    = new AiService();
            $files        = $_FILES['photos'];

            for ($i = 0; $i < min(count($files['name']), 8); $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

                $result = $imageService->upload(
                    $files['tmp_name'][$i],
                    $files['name'][$i],
                    $files['type'][$i],
                    $files['size'][$i]
                );
                if ($result) {
                    // Generate AI caption from the cards variant (smaller/faster)
                    $cardsPath = dirname(__DIR__, 2) . '/storage/uploads/cards/' . $result['filename'];
                    $caption   = $aiService->describeImage($cardsPath);
                    $imageModel->create($visitId, $result['filename'], $files['name'][$i], $files['type'][$i], $files['size'][$i], $i, $caption);
                }
            }
        }

        flash('success', 'Besöket har sparats!');
        redirect('/adm/platser/' . $p['slug']);
    }

    public function show(array $params): void
    {
        Auth::requireLogin();
        $visitModel = new Visit($this->pdo);
        $visit = $visitModel->findById((int) $params['id']);
        if (!$visit) { http_response_code(404); return; }

        $ratingModel = new VisitRating($this->pdo);
        $ratings = $ratingModel->findByVisit((int) $params['id']);

        $imageModel = new VisitImage($this->pdo);
        $images = $imageModel->findByVisit((int) $params['id']);

        $pageTitle = 'Besök — ' . $visit['place_name'];
        view('visits/show', compact('visit', 'ratings', 'images', 'pageTitle'));
    }

    public function edit(array $params): void
    {
        Auth::requireLogin();
        $visitModel = new Visit($this->pdo);
        $visit = $visitModel->findById((int) $params['id']);
        if (!$visit) { http_response_code(404); return; }

        $ratingModel = new VisitRating($this->pdo);
        $ratings = $ratingModel->findByVisit((int) $params['id']);

        $suitableForSuggestions = $visitModel->suitableForValues();

        $pageTitle = 'Redigera besök';
        view('visits/edit', compact('visit', 'ratings', 'pageTitle', 'suitableForSuggestions'));
    }

    public function update(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $visitModel = new Visit($this->pdo);
        $visit = $visitModel->findById((int) $params['id']);
        if (!$visit) { http_response_code(404); return; }

        $visitModel->update((int) $params['id'], [
            'visited_at'     => $_POST['visited_at'] ?? $visit['visited_at'],
            'raw_note'       => trim($_POST['raw_note'] ?? '') ?: null,
            'plus_notes'     => trim($_POST['plus_notes'] ?? '') ?: null,
            'minus_notes'    => trim($_POST['minus_notes'] ?? '') ?: null,
            'tips_notes'     => trim($_POST['tips_notes'] ?? '') ?: null,
            'price_level'    => $_POST['price_level'] ?? null,
            'would_return'   => $_POST['would_return'] ?? null,
            'suitable_for'   => trim($_POST['suitable_for'] ?? '') ?: null,
            'things_to_note' => trim($_POST['things_to_note'] ?? '') ?: null,
        ]);

        $ratingModel = new VisitRating($this->pdo);
        $ratingModel->save((int) $params['id'], [
            'location_rating'     => !empty($_POST['location_rating']) ? (int) $_POST['location_rating'] : null,
            'calmness_rating'     => !empty($_POST['calmness_rating']) ? (int) $_POST['calmness_rating'] : null,
            'service_rating'      => !empty($_POST['service_rating']) ? (int) $_POST['service_rating'] : null,
            'value_rating'        => !empty($_POST['value_rating']) ? (int) $_POST['value_rating'] : null,
            'return_value_rating' => !empty($_POST['return_value_rating']) ? (int) $_POST['return_value_rating'] : null,
        ]);

        flash('success', 'Besöket har uppdaterats.');
        redirect('/adm/besok/' . $params['id']);
    }

    public function destroy(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $visitModel = new Visit($this->pdo);
        $visit = $visitModel->findById((int) $params['id']);
        if (!$visit) { redirect('/adm/platser'); return; }

        $imageModel = new VisitImage($this->pdo);
        $images = $imageModel->findByVisit((int) $params['id']);

        $visitModel->delete((int) $params['id']);

        $imageService = new ImageService($this->config);
        foreach ($images as $image) {
            if (!empty($image['filename'])) {
                $imageService->delete((string) $image['filename']);
            }
        }

        flash('success', 'Besöket har tagits bort.');
        redirect('/adm/platser/' . $visit['place_slug']);
    }

    public function uploadImage(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();
        header('Content-Type: application/json');

        if (empty($_FILES['photo'])) {
            echo json_encode(['error' => 'Ingen fil']);
            return;
        }

        $imageService = new ImageService($this->config);
        $file = $_FILES['photo'];
        $result = $imageService->upload($file['tmp_name'], $file['name'], $file['type'], $file['size']);

        if ($result) {
            echo json_encode(['success' => true, 'filename' => $result['filename']]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Uppladdningen misslyckades']);
        }
    }

    public function suitableForSuggestions(array $params): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');
        $visitModel = new Visit($this->pdo);
        echo json_encode($visitModel->suitableForValues());
    }

    /**
     * POST /adm/api/images/{id}/rotate
     * Rotate an image 90° left or right and regenerate all variants.
     */
    public function rotateImage(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();
        header('Content-Type: application/json');

        $id        = (int) ($params['id'] ?? 0);
        $direction = $_POST['direction'] ?? '';

        if (!in_array($direction, ['left', 'right'], true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Ogiltig riktning']);
            return;
        }

        $imageModel = new VisitImage($this->pdo);
        $image      = $imageModel->findById($id);

        if (!$image) {
            http_response_code(404);
            echo json_encode(['error' => 'Bilden hittades inte']);
            return;
        }

        $imageService = new ImageService($this->config);
        if ($imageService->rotate($image['filename'], $direction)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Rotering misslyckades']);
        }
    }

    /**
     * POST /adm/api/images/{id}/ai-caption
     * (Re-)generate AI caption for a single image using Claude vision.
     */
    public function generateCaption(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();
        header('Content-Type: application/json');

        $id         = (int) ($params['id'] ?? 0);
        $imageModel = new VisitImage($this->pdo);
        $image      = $imageModel->findById($id);

        if (!$image) {
            http_response_code(404);
            echo json_encode(['error' => 'Bilden hittades inte']);
            return;
        }

        $cardsPath = dirname(__DIR__, 2) . '/storage/uploads/cards/' . $image['filename'];
        try {
            $aiService = new AiService();
            $caption   = $aiService->describeImage($cardsPath);
        } catch (RuntimeException $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }

        if ($caption === '') {
            http_response_code(500);
            echo json_encode(['error' => 'AI returnerade ingen text. Kontrollera att ANTHROPIC_API_KEY är konfigurerad.']);
            return;
        }

        $imageModel->updateCaption($id, $caption);
        echo json_encode(['success' => true, 'caption' => $caption]);
    }

    /**
     * POST /adm/api/images/{id}/caption
     * Update the alt_text / caption for an image.
     */
    public function updateCaption(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();
        header('Content-Type: application/json');

        $id    = (int) ($params['id'] ?? 0);
        $input = json_decode(file_get_contents('php://input'), true);
        $caption = mb_substr(trim($input['caption'] ?? ''), 0, 500);

        $imageModel = new VisitImage($this->pdo);
        $image      = $imageModel->findById($id);

        if (!$image) {
            http_response_code(404);
            echo json_encode(['error' => 'Bilden hittades inte']);
            return;
        }

        $imageModel->updateCaption($id, $caption);
        echo json_encode(['success' => true]);
    }
}
