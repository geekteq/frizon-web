<?php

declare(strict_types=1);

class Visit
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get the most recent visits for a given user, joined with place data and ratings.
     */
    public function recentForUser(int $userId, int $limit = 5): array
    {
        $stmt = $this->pdo->prepare('
            SELECT v.*, p.name AS place_name, p.slug AS place_slug,
                   vr.total_rating_cached
            FROM visits v
            JOIN places p ON p.id = v.place_id
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE v.user_id = ?
            ORDER BY v.visited_at DESC, v.created_at DESC
            LIMIT ' . (int) $limit
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM visits WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function allForPlace(int $placeId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT v.*, vr.total_rating_cached
            FROM visits v
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE v.place_id = ?
            ORDER BY v.visited_at DESC
        ');
        $stmt->execute([$placeId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO visits (
                place_id, user_id, visited_at, raw_note,
                plus_notes, minus_notes, tips_notes,
                price_level, would_return, suitable_for, things_to_note
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['place_id'],
            $data['user_id'],
            $data['visited_at'] ?? date('Y-m-d'),
            $data['raw_note'] ?? null,
            $data['plus_notes'] ?? null,
            $data['minus_notes'] ?? null,
            $data['tips_notes'] ?? null,
            $data['price_level'] ?? null,
            $data['would_return'] ?? null,
            $data['suitable_for'] ?? null,
            $data['things_to_note'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE visits SET
                visited_at = ?, raw_note = ?,
                plus_notes = ?, minus_notes = ?, tips_notes = ?,
                price_level = ?, would_return = ?, suitable_for = ?,
                things_to_note = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $data['visited_at'] ?? date('Y-m-d'),
            $data['raw_note'] ?? null,
            $data['plus_notes'] ?? null,
            $data['minus_notes'] ?? null,
            $data['tips_notes'] ?? null,
            $data['price_level'] ?? null,
            $data['would_return'] ?? null,
            $data['suitable_for'] ?? null,
            $data['things_to_note'] ?? null,
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM visits WHERE id = ?');
        $stmt->execute([$id]);
    }
}
