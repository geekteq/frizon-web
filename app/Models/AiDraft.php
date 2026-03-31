<?php

declare(strict_types=1);

class AiDraft
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Save a new draft and return its ID.
     */
    public function createDraft(int $visitId, string $promptContext, string $draftText): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO ai_drafts (visit_id, prompt_context, draft_text, status)
            VALUES (?, ?, ?, \'pending\')
        ');
        $stmt->execute([$visitId, $promptContext, $draftText]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * All drafts for a visit, newest first.
     */
    public function findByVisit(int $visitId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM ai_drafts WHERE visit_id = ? ORDER BY created_at DESC
        ');
        $stmt->execute([$visitId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ai_drafts WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function approve(int $id): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE ai_drafts SET status = 'approved', updated_at = NOW() WHERE id = ?
        ");
        $stmt->execute([$id]);
    }

    public function reject(int $id): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE ai_drafts SET status = 'rejected', updated_at = NOW() WHERE id = ?
        ");
        $stmt->execute([$id]);
    }

    /**
     * Most recently created draft for a visit, regardless of status.
     */
    public function latestForVisit(int $visitId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM ai_drafts WHERE visit_id = ? ORDER BY created_at DESC LIMIT 1
        ');
        $stmt->execute([$visitId]);
        return $stmt->fetch() ?: null;
    }
}
