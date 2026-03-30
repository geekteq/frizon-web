<?php

declare(strict_types=1);

class VisitImage
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByVisit(int $visitId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM visit_images WHERE visit_id = ? ORDER BY image_order ASC');
        $stmt->execute([$visitId]);
        return $stmt->fetchAll();
    }

    public function create(int $visitId, string $filename, string $originalName, string $mimeType, int $fileSize, int $order): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO visit_images (visit_id, filename, original_name, mime_type, file_size, image_order)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$visitId, $filename, $originalName, $mimeType, $fileSize, $order]);
        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT filename FROM visit_images WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row) {
            $del = $this->pdo->prepare('DELETE FROM visit_images WHERE id = ?');
            $del->execute([$id]);
            return $row['filename'];
        }
        return null;
    }
}
