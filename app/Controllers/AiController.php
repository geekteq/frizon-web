<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/ActionRateLimiter.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Services/AiService.php';
require_once dirname(__DIR__) . '/Models/Visit.php';
require_once dirname(__DIR__) . '/Models/VisitRating.php';
require_once dirname(__DIR__) . '/Models/AiDraft.php';
require_once dirname(__DIR__) . '/Models/Place.php';

class AiController
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo    = $pdo;
        $this->config = $config;
    }

    /**
     * POST /adm/besok/{id}/ai/generera
     * Generate a new AI draft for the given visit.
     */
    public function generateDraft(array $params): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');

        if (!CsrfService::verify()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Ogiltig säkerhetstoken. Ladda om sidan.']);
            return;
        }

        try {
            $this->consumeActionQuota('visit-ai-draft', 12, 900);
        } catch (RuntimeException) {
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => 'För många AI-förfrågningar just nu. Försök igen om en stund.']);
            return;
        }

        $visitId = (int) ($params['id'] ?? 0);
        $visitModel = new Visit($this->pdo);
        $visit = $visitModel->findById($visitId);

        if (!$visit) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Besöket hittades inte.']);
            return;
        }

        // Load ratings for additional context
        $ratingModel = new VisitRating($this->pdo);
        $ratings = $ratingModel->findByVisit($visitId);

        // Build context array for the AI provider
        $context = [
            'place_name'   => $visit['place_name'],
            'place_type'   => $visit['place_type'],
            'visited_at'   => $visit['visited_at'],
            'raw_note'     => $visit['raw_note'],
            'plus_notes'   => $visit['plus_notes'],
            'minus_notes'  => $visit['minus_notes'],
            'tips_notes'   => $visit['tips_notes'],
            'suitable_for' => $visit['suitable_for'],
            'price_level'  => $visit['price_level'],
            'would_return' => $visit['would_return'],
            'total_rating' => $ratings['total_rating_cached'] ?? null,
        ];

        try {
            $aiService  = new AiService();
            $draftText  = $aiService->generateDraft($context);
        } catch (RuntimeException $e) {
            error_log('AI draft generation failed for visit ' . $visitId . ': ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'AI-tjänsten kunde inte generera text just nu. Försök igen senare.']);
            return;
        }

        // Persist the draft
        $draftModel = new AiDraft($this->pdo);
        $draftId    = $draftModel->createDraft(
            $visitId,
            json_encode($context, JSON_UNESCAPED_UNICODE),
            $draftText
        );

        $draft = $draftModel->findById($draftId);

        echo json_encode([
            'success' => true,
            'draft'   => [
                'id'         => $draft['id'],
                'text'       => $draft['draft_text'],
                'created_at' => $draft['created_at'],
            ],
        ]);
    }

    /**
     * POST /adm/platser/{slug}/ai/generera
     * Generate AI text from place data + any existing description as seed.
     */
    public function generatePlaceDraft(array $params): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');

        if (!CsrfService::verify()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Ogiltig säkerhetstoken.']);
            return;
        }

        try {
            $this->consumeActionQuota('place-ai-draft', 8, 900);
        } catch (RuntimeException) {
            http_response_code(429);
            echo json_encode(['success' => false, 'error' => 'För många AI-förfrågningar just nu. Försök igen om en stund.']);
            return;
        }

        $placeModel = new Place($this->pdo);
        $place = $placeModel->findBySlug($params['slug']);
        if (!$place) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Platsen hittades inte.']);
            return;
        }

        // Gather context from place + best visit data if any
        $context = [
            'place_name'  => $place['name'],
            'place_type'  => $place['place_type'],
            'country_code' => $place['country_code'],
            'raw_note'    => $place['default_public_text'] ?? '',
        ];

        // Enrich with latest visit data if available
        $stmt = $this->pdo->prepare('
            SELECT v.*, vr.total_rating_cached, vr.location_rating, vr.calmness_rating,
                   vr.service_rating, vr.value_rating, vr.return_value_rating
            FROM visits v
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE v.place_id = ?
            ORDER BY v.visited_at DESC LIMIT 1
        ');
        $stmt->execute([$place['id']]);
        $visit = $stmt->fetch();

        if ($visit) {
            $context['plus_notes']   = $visit['plus_notes'];
            $context['minus_notes']  = $visit['minus_notes'];
            $context['tips_notes']   = $visit['tips_notes'];
            $context['suitable_for'] = $visit['suitable_for'];
            $context['price_level']  = $visit['price_level'];
            $context['would_return'] = $visit['would_return'];
            $context['total_rating'] = $visit['total_rating_cached'];
        }

        // Also include user-provided seed text from the textarea
        $input = json_decode(file_get_contents('php://input'), true);
        if (!empty($input['current_text'])) {
            $context['raw_note'] = $input['current_text'];
        }

        try {
            $aiService = new AiService();
            $draftText = $aiService->generateDraft($context);
        } catch (RuntimeException $e) {
            error_log('AI place draft generation failed for place ' . ($place['id'] ?? 'unknown') . ': ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'AI-tjänsten kunde inte generera text just nu. Försök igen senare.']);
            return;
        }

        echo json_encode(['success' => true, 'text' => $draftText]);
    }

    /**
     * POST /adm/platser/{slug}/ai/seo
     * Manually (re)generate SEO content for a place.
     */
    public function generatePlaceSeo(array $params): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');

        if (!CsrfService::verify()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Ogiltig säkerhetstoken.']);
            return;
        }

        $placeModel = new Place($this->pdo);
        $place = $placeModel->findBySlug($params['slug']);
        if (!$place) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Platsen hittades inte.']);
            return;
        }

        $stmt = $this->pdo->prepare('
            SELECT v.*, vr.total_rating_cached
            FROM visits v
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE v.place_id = ? AND v.ready_for_publish = 1
        ');
        $stmt->execute([$place['id']]);
        $visits = $stmt->fetchAll();

        try {
            $aiService = new AiService();
            $seo = $aiService->generatePlaceSeo($place, $visits);

            $this->pdo->prepare(
                'UPDATE places SET meta_description = ?, faq_content = ?, updated_at = NOW() WHERE id = ?'
            )->execute([$seo['meta_description'], $seo['faq_content'], $place['id']]);

            echo json_encode([
                'success'          => true,
                'meta_description' => $seo['meta_description'],
                'faq'              => json_decode($seo['faq_content'], true),
            ]);
        } catch (RuntimeException $e) {
            error_log('Manual SEO generation failed for place ' . $place['id'] . ': ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'SEO-generering misslyckades: ' . $e->getMessage()]);
        }
    }

    /**
     * POST /adm/besok/{id}/ai/{draftId}/godkann
     * Approve a draft — copy text to visits.approved_public_text and flag ready_for_publish.
     */
    public function approveDraft(array $params): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');

        if (!CsrfService::verify()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Ogiltig säkerhetstoken. Ladda om sidan.']);
            return;
        }

        $visitId = (int) ($params['id'] ?? 0);
        $draftId = (int) ($params['draftId'] ?? 0);

        $draftModel = new AiDraft($this->pdo);
        $draft      = $draftModel->findById($draftId);

        if (!$draft || (int) $draft['visit_id'] !== $visitId) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Utkastet hittades inte.']);
            return;
        }

        // Mark draft approved
        $draftModel->approve($draftId);

        // Copy text to the visit and flag as ready to publish
        $stmt = $this->pdo->prepare('
            UPDATE visits
            SET approved_public_text = ?, ready_for_publish = 1, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([$draft['draft_text'], $visitId]);

        // Also copy to the place's default_public_text
        $stmt = $this->pdo->prepare('
            UPDATE places SET default_public_text = ?, updated_at = NOW()
            WHERE id = (SELECT place_id FROM visits WHERE id = ?)
        ');
        $stmt->execute([$draft['draft_text'], $visitId]);

        // Regenerate SEO if the place is already public
        $placeModel = new Place($this->pdo);
        $stmt = $this->pdo->prepare('SELECT place_id FROM visits WHERE id = ?');
        $stmt->execute([$visitId]);
        $placeId = (int) $stmt->fetchColumn();
        $place = $placeModel->findById($placeId);

        if ($place && $place['public_allowed']) {
            try {
                $visitStmt = $this->pdo->prepare('
                    SELECT v.*, vr.total_rating_cached
                    FROM visits v
                    LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
                    WHERE v.place_id = ? AND v.ready_for_publish = 1
                ');
                $visitStmt->execute([$placeId]);
                $allVisits = $visitStmt->fetchAll();

                $aiService = new AiService();
                $seo = $aiService->generatePlaceSeo($place, $allVisits);

                $this->pdo->prepare(
                    'UPDATE places SET meta_description = ?, faq_content = ?, updated_at = NOW() WHERE id = ?'
                )->execute([$seo['meta_description'], $seo['faq_content'], $placeId]);
            } catch (RuntimeException $e) {
                error_log('SEO regeneration failed after visit approval for place ' . $placeId . ': ' . $e->getMessage());
            }

            // Ping Google to re-crawl sitemap
            $sitemapUrl = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/') . '/sitemap.xml';
            @file_get_contents('https://www.google.com/ping?sitemap=' . urlencode($sitemapUrl));
        }

        echo json_encode(['success' => true]);
    }

    /**
     * POST /adm/besok/{id}/ai/{draftId}/avvisa
     * Reject a draft.
     */
    public function rejectDraft(array $params): void
    {
        Auth::requireLogin();
        header('Content-Type: application/json');

        if (!CsrfService::verify()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Ogiltig säkerhetstoken. Ladda om sidan.']);
            return;
        }

        $visitId = (int) ($params['id'] ?? 0);
        $draftId = (int) ($params['draftId'] ?? 0);

        $draftModel = new AiDraft($this->pdo);
        $draft      = $draftModel->findById($draftId);

        if (!$draft || (int) $draft['visit_id'] !== $visitId) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Utkastet hittades inte.']);
            return;
        }

        $draftModel->reject($draftId);

        echo json_encode(['success' => true]);
    }

    private function consumeActionQuota(string $action, int $maxAttempts, int $windowSeconds): void
    {
        $limiter = new ActionRateLimiter();
        $limiter->consumeForUser($action, Auth::userId(), $maxAttempts, $windowSeconds);
    }
}
