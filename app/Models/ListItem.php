<?php

declare(strict_types=1);

class ListItem
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByList(int $listId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM list_items WHERE list_id = ? ORDER BY is_done ASC, item_order ASC
        ');
        $stmt->execute([$listId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM list_items WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function add(int $listId, string $text, ?string $category): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(item_order), 0) + 1 FROM list_items WHERE list_id = ?');
        $stmt->execute([$listId]);
        $nextOrder = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare('
            INSERT INTO list_items (list_id, item_order, text, category)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([$listId, $nextOrder, $text, $category]);
        return (int) $this->pdo->lastInsertId();
    }

    public function toggleDone(int $id): array
    {
        $item = $this->findById($id);
        if (!$item) return ['success' => false];

        $newDone = $item['is_done'] ? 0 : 1;
        $doneAt = $newDone ? date('Y-m-d H:i:s') : null;

        $stmt = $this->pdo->prepare('UPDATE list_items SET is_done = ?, done_at = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$newDone, $doneAt, $id]);

        return ['success' => true, 'is_done' => $newDone];
    }

    public function updateText(int $id, string $text): void
    {
        $this->pdo->prepare('UPDATE list_items SET text = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$text, $id]);
    }

    public function remove(int $id): void
    {
        $item = $this->findById($id);
        if (!$item) return;

        $this->pdo->prepare('DELETE FROM list_items WHERE id = ?')->execute([$id]);

        // Re-number remaining items
        $stmt = $this->pdo->prepare('SELECT id FROM list_items WHERE list_id = ? ORDER BY item_order ASC');
        $stmt->execute([$item['list_id']]);
        $remaining = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($remaining as $i => $itemId) {
            $this->pdo->prepare('UPDATE list_items SET item_order = ? WHERE id = ?')
                ->execute([$i + 1, $itemId]);
        }
    }

    public function reorder(int $listId, array $itemIds): void
    {
        foreach ($itemIds as $order => $itemId) {
            $this->pdo->prepare('UPDATE list_items SET item_order = ? WHERE id = ? AND list_id = ?')
                ->execute([$order + 1, $itemId, $listId]);
        }
    }
}
