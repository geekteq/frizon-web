<?php

declare(strict_types=1);

class FrizzeDocumentInterpretation
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function latestByDocumentIds(array $documentIds): array
    {
        $ids = array_values(array_filter(array_map('intval', $documentIds)));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("
            SELECT i.*
            FROM frizze_document_interpretations i
            INNER JOIN (
                SELECT document_id, MAX(id) AS latest_id
                FROM frizze_document_interpretations
                WHERE document_id IN ({$placeholders})
                GROUP BY document_id
            ) latest ON latest.latest_id = i.id
        ");
        $stmt->execute($ids);

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[(int) $row['document_id']] = $this->hydrate($row);
        }

        return $items;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT i.*, d.title AS document_title, d.file_path, d.mime_type, d.original_filename,
                   d.document_type, d.vehicle_id
            FROM frizze_document_interpretations i
            INNER JOIN frizze_documents d ON d.id = i.document_id
            WHERE i.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function create(int $documentId, array $interpreted, int $createdBy): int
    {
        $json = json_encode($interpreted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $stmt = $this->pdo->prepare('
            INSERT INTO frizze_document_interpretations (
                document_id, provider, status, interpreted_json, edited_json, created_by
            ) VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $documentId,
            'anthropic',
            'draft',
            $json,
            $json,
            $createdBy,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateEdited(int $id, array $edited, string $status = 'reviewed', ?int $reviewedBy = null): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE frizze_document_interpretations
            SET edited_json = ?, status = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            json_encode($edited, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $status,
            $reviewedBy,
            $id,
        ]);
    }

    public function markApplied(int $id, int $eventId, array $edited, int $reviewedBy): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE frizze_document_interpretations
            SET edited_json = ?, status = "applied", applied_event_id = ?, reviewed_by = ?, reviewed_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            json_encode($edited, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $eventId,
            $reviewedBy,
            $id,
        ]);
    }

    public function markRejected(int $id, int $reviewedBy): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE frizze_document_interpretations
            SET status = "rejected", reviewed_by = ?, reviewed_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([$reviewedBy, $id]);
    }

    private function hydrate(array $row): array
    {
        $row['interpreted'] = $this->decodeJson($row['interpreted_json'] ?? null);
        $row['edited'] = $this->decodeJson($row['edited_json'] ?? null);
        return $row;
    }

    private function decodeJson(?string $json): array
    {
        if (!$json) {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
