<?php

declare(strict_types=1);

class FrizzeDocument
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function allForVehicle(int $vehicleId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
            FROM frizze_documents
            WHERE vehicle_id = ?
            ORDER BY document_date DESC, id DESC
        ');
        $stmt->execute([$vehicleId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM frizze_documents WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO frizze_documents (
                vehicle_id, document_type, title, original_filename, file_path, mime_type,
                supplier, document_date, amount_total, currency, notes, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['vehicle_id'],
            $data['document_type'] ?? 'receipt',
            $data['title'],
            $data['original_filename'] ?? null,
            $data['file_path'],
            $data['mime_type'],
            $data['supplier'] ?? null,
            $data['document_date'] ?? null,
            $data['amount_total'] ?? null,
            $data['currency'] ?? 'SEK',
            $data['notes'] ?? null,
            $data['created_by'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM frizze_documents WHERE id = ?');
        $stmt->execute([$id]);
    }
}
