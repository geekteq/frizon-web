<?php

declare(strict_types=1);

class ItemList
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('
            SELECT l.*, COUNT(li.id) as item_count,
                   SUM(li.is_done) as done_count
            FROM lists l
            LEFT JOIN list_items li ON li.list_id = l.id
            GROUP BY l.id
            ORDER BY l.updated_at DESC
        ');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM lists WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByScope(string $scopeType, int $scopeId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT l.*, COUNT(li.id) as item_count,
                   SUM(li.is_done) as done_count
            FROM lists l
            LEFT JOIN list_items li ON li.list_id = l.id
            WHERE l.scope_type = ? AND l.scope_id = ?
            GROUP BY l.id
            ORDER BY l.created_at ASC
        ');
        $stmt->execute([$scopeType, $scopeId]);
        return $stmt->fetchAll();
    }

    public function findGlobal(): array
    {
        $stmt = $this->pdo->query('
            SELECT l.*, COUNT(li.id) as item_count,
                   SUM(li.is_done) as done_count
            FROM lists l
            LEFT JOIN list_items li ON li.list_id = l.id
            WHERE l.scope_type = "global"
            GROUP BY l.id
            ORDER BY l.updated_at DESC
        ');
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO lists (scope_type, scope_id, list_type, title, based_on_template_id, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['scope_type'] ?? 'global',
            $data['scope_id'] ?? null,
            $data['list_type'] ?? 'checklist',
            $data['title'],
            $data['based_on_template_id'] ?? null,
            $data['created_by'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE lists SET title = ?, list_type = ?, updated_at = NOW() WHERE id = ?
        ');
        $stmt->execute([$data['title'], $data['list_type'], $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM lists WHERE id = ?')->execute([$id]);
    }
}
