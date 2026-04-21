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
}
