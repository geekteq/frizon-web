<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Services/AiService.php';
require_once dirname(__DIR__) . '/Models/Visit.php';
require_once dirname(__DIR__) . '/Models/VisitRating.php';
require_once dirname(__DIR__) . '/Models/AiDraft.php';

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
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
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
}
