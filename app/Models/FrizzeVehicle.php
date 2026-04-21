<?php

declare(strict_types=1);

class FrizzeVehicle
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function primary(): ?array
    {
        $stmt = $this->pdo->query('SELECT * FROM frizze_vehicles ORDER BY id ASC LIMIT 1');
        return $stmt->fetch() ?: null;
    }
}
