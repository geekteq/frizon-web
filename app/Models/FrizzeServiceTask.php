<?php

declare(strict_types=1);

class FrizzeServiceTask
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
            FROM frizze_service_tasks
            WHERE vehicle_id = ?
            ORDER BY
                CASE status
                    WHEN "watch" THEN 0
                    WHEN "planned" THEN 1
                    WHEN "done" THEN 2
                    ELSE 3
                END,
                due_date ASC,
                due_odometer_km ASC,
                id ASC
        ');
        $stmt->execute([$vehicleId]);
        return $stmt->fetchAll();
    }
}
