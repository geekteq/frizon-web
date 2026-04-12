<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/ActionRateLimiter.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Services/ImageService.php';
require_once dirname(__DIR__) . '/Services/AiService.php';
require_once dirname(__DIR__) . '/Services/InstagramService.php';
require_once dirname(__DIR__) . '/Services/SecurityAudit.php';
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
            $imgContext   = [
                'place_name' => $p['name'],
                'place_type' => $p['place_type'],
            ];

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
                    $caption   = $aiService->describeImage($cardsPath, $imgContext);
                    $imageModel->create($visitId, $result['filename'], $files['name'][$i], $files['type'][$i], $files['size'][$i], $i, $caption);
                }
            }
        }

        SecurityAudit::log($this->pdo, 'visit.created', [
            'visit_id' => (int) $visitId,
            'place_id' => (int) $p['id'],
            'place_slug' => $p['slug'],
        ], Auth::userId());
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

        SecurityAudit::log($this->pdo, 'visit.updated', [
            'visit_id' => (int) $params['id'],
            'place_id' => (int) $visit['place_id'],
        ], Auth::userId());
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

        SecurityAudit::log($this->pdo, 'visit.deleted', [
            'visit_id' => (int) $params['id'],
            'place_id' => (int) $visit['place_id'],
            'place_slug' => $visit['place_slug'],
        ], Auth::userId());
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

        try {
            $this->consumeActionQuota('image-upload', 24, 900);
        } catch (RuntimeException) {
            http_response_code(429);
            echo json_encode(['error' => 'För många bilduppladdningar just nu. Försök igen senare.']);
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

        try {
            $this->consumeActionQuota('image-ai-caption', 20, 900);
        } catch (RuntimeException) {
            http_response_code(429);
            echo json_encode(['error' => 'För många AI-förfrågningar just nu. Försök igen senare.']);
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

        // Fetch place context for a more accurate caption
        $stmt = $this->pdo->prepare('
            SELECT p.name AS place_name, p.place_type
            FROM visit_images vi
            JOIN visits v ON v.id = vi.visit_id
            JOIN places p  ON p.id = v.place_id
            WHERE vi.id = ?
        ');
        $stmt->execute([$id]);
        $placeRow   = $stmt->fetch() ?: [];
        $imgContext = [
            'place_name' => $placeRow['place_name'] ?? '',
            'place_type' => $placeRow['place_type'] ?? '',
        ];

        $cardsPath = dirname(__DIR__, 2) . '/storage/uploads/cards/' . $image['filename'];
        try {
            $aiService = new AiService();
            $caption   = $aiService->describeImage($cardsPath, $imgContext);
        } catch (RuntimeException $e) {
            error_log('AI caption generation failed for image ' . $id . ': ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Bildtext kunde inte genereras just nu. Försök igen senare.']);
            return;
        }

        if ($caption === '') {
            http_response_code(500);
            echo json_encode(['error' => 'Bildtext kunde inte genereras just nu. Försök igen senare.']);
            return;
        }

        $imageModel->updateCaption($id, $caption);
        echo json_encode(['success' => true, 'caption' => $caption]);
    }

    /**
     * GET /adm/api/besok/{id}/instagram/preview
     * Returns caption draft + image list for the Instagram publish modal.
     */
    public function instagramPreview(array $params): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');

        $id = (int) ($params['id'] ?? 0);

        $visitModel = new Visit($this->pdo);
        $visit      = $visitModel->findById($id);
        if (!$visit) { http_response_code(404); echo json_encode(['error' => 'Besöket hittades inte']); return; }

        $placeModel = new Place($this->pdo);
        $place      = $placeModel->findById($visit['place_id']);
        if (!$place) { http_response_code(404); echo json_encode(['error' => 'Platsen hittades inte']); return; }

        $imageModel = new VisitImage($this->pdo);
        $images     = $imageModel->findByVisit($id);

        if (empty($images)) {
            http_response_code(400);
            echo json_encode(['error' => 'Besöket har inga bilder att publicera.']);
            return;
        }

        $ig      = new InstagramService($this->config);
        $preview = $ig->buildPreview($visit, $place, $images);

        // Try AI-generated selling caption if approved text exists
        $approvedText = trim($visit['approved_public_text'] ?? '');
        if ($approvedText !== '') {
            try {
                $placeTypes = [
                    'stellplatz' => 'ställplats', 'camping' => 'camping',
                    'wild_camping' => 'fricamping', 'fika' => 'fika',
                    'lunch' => 'lunch', 'dinner' => 'middag',
                    'breakfast' => 'frukost', 'sight' => 'sevärdhet',
                    'shopping' => 'shopping',
                ];
                $ai = new AiService();
                $aiCaption = $ai->generateInstagramCaption([
                    'place_name'    => $place['name'],
                    'place_type'    => $placeTypes[$place['place_type'] ?? ''] ?? ($place['place_type'] ?? ''),
                    'visited_at'    => $visit['visited_at'] ?? '',
                    'address'       => $place['address_text'] ?? '',
                    'total_rating'  => $visit['total_rating'] ?? '',
                    'would_return'  => $visit['would_return'] ?? '',
                    'approved_text' => $approvedText,
                ]);

                // Append hashtags and place URL after AI caption
                $hashtags = '#husbil #husbilar #campinglivet #vanlife #plåtis #frizze #frizon #camping #äventyr #sverige';
                $preview['caption'] = $aiCaption . "\n\n" . $preview['place_url'] . "\n\n" . $hashtags;
            } catch (RuntimeException $e) {
                // AI failed — keep the regular buildCaption result
                error_log('Instagram AI caption failed: ' . $e->getMessage());
            }
        }

        echo json_encode($preview);
    }

    /**
     * POST /adm/api/besok/{id}/instagram
     * Publish visit images + caption to Instagram.
     */
    public function publishToInstagram(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();
        header('Content-Type: application/json');

        $id = (int) ($params['id'] ?? 0);

        $visitModel = new Visit($this->pdo);
        $visit      = $visitModel->findById($id);
        if (!$visit) { http_response_code(404); echo json_encode(['error' => 'Besöket hittades inte']); return; }

        $placeModel = new Place($this->pdo);
        $place      = $placeModel->findById($visit['place_id']);
        if (!$place) { http_response_code(404); echo json_encode(['error' => 'Platsen hittades inte']); return; }

        $imageModel = new VisitImage($this->pdo);
        $images     = $imageModel->findByVisit($id);

        if (empty($images)) {
            http_response_code(400);
            echo json_encode(['error' => 'Inga bilder att publicera.']);
            return;
        }

        try {
            $this->consumeActionQuota('instagram-publish', 5, 3600);
        } catch (RuntimeException) {
            http_response_code(429);
            echo json_encode(['error' => 'För många Instagram-publiceringar just nu. Försök igen senare.']);
            return;
        }

        $ig = new InstagramService($this->config);

        if (!$ig->isConfigured()) {
            http_response_code(400);
            echo json_encode(['error' => 'Instagram-publicering är inte tillgänglig just nu.']);
            return;
        }

        $input     = json_decode(file_get_contents('php://input'), true) ?? [];
        $caption   = trim($input['caption'] ?? '');
        $filenames = array_column($images, 'filename');

        try {
            $postId = $ig->publish($filenames, $caption);
            SecurityAudit::log($this->pdo, 'instagram.publish_success', [
                'visit_id' => (int) $id,
                'post_id' => $postId,
                'image_count' => count($filenames),
            ], Auth::userId());
            echo json_encode(['success' => true, 'post_id' => $postId]);
        } catch (RuntimeException $e) {
            error_log('Instagram publish failed for visit ' . $id . ': ' . $e->getMessage());
            SecurityAudit::log($this->pdo, 'instagram.publish_failed', [
                'visit_id' => (int) $id,
            ], Auth::userId());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Instagram-publicering misslyckades. Försök igen senare.']);
        }
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

    private function consumeActionQuota(string $action, int $maxAttempts, int $windowSeconds): void
    {
        $limiter = new ActionRateLimiter();
        $limiter->consumeForUser($action, Auth::userId(), $maxAttempts, $windowSeconds);
    }
}
