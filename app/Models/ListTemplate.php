<?php

declare(strict_types=1);

class ListTemplate
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM list_templates ORDER BY title ASC');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM list_templates WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO list_templates (list_type, title, description, items_json, created_by)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['list_type'] ?? 'checklist',
            $data['title'],
            $data['description'] ?? null,
            $data['items_json'],
            $data['created_by'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE list_templates SET title = ?, description = ?, list_type = ?,
                items_json = ?, updated_at = NOW() WHERE id = ?
        ');
        $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['list_type'],
            $data['items_json'],
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM list_templates WHERE id = ?')->execute([$id]);
    }

    /**
     * Instantiate a template into a list with items.
     */
    public function instantiate(int $templateId, int $listId, PDO $pdo): void
    {
        $template = $this->findById($templateId);
        if (!$template) return;

        $items = json_decode($template['items_json'], true);
        if (!is_array($items)) return;

        require_once __DIR__ . '/ListItem.php';
        $itemModel = new ListItem($pdo);

        foreach ($items as $item) {
            $itemModel->add($listId, $item['text'] ?? '', $item['category'] ?? null);
        }
    }
}
