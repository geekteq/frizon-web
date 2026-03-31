<?php

declare(strict_types=1);

class TripStop
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByTrip(int $tripId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT ts.*, p.name as place_name, p.slug as place_slug,
                   p.lat, p.lng, p.place_type, p.country_code
            FROM trip_stops ts
            JOIN places p ON p.id = ts.place_id
            WHERE ts.trip_id = ?
            ORDER BY ts.stop_order ASC
        ');
        $stmt->execute([$tripId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT ts.*, p.name as place_name, p.slug as place_slug,
                   p.lat, p.lng, p.place_type
            FROM trip_stops ts
            JOIN places p ON p.id = ts.place_id
            WHERE ts.id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function add(int $tripId, int $placeId, ?string $stopType, ?string $note): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(stop_order), 0) + 1 FROM trip_stops WHERE trip_id = ?');
        $stmt->execute([$tripId]);
        $nextOrder = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare('
            INSERT INTO trip_stops (trip_id, place_id, stop_order, stop_type, note)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$tripId, $placeId, $nextOrder, $stopType, $note]);
        return (int) $this->pdo->lastInsertId();
    }

    public function remove(int $id): void
    {
        $stop = $this->findById($id);
        if (!$stop) return;

        $this->pdo->prepare('DELETE FROM trip_stops WHERE id = ?')->execute([$id]);

        // Re-number remaining stops
        $stmt = $this->pdo->prepare('
            SELECT id FROM trip_stops WHERE trip_id = ? ORDER BY stop_order ASC
        ');
        $stmt->execute([$stop['trip_id']]);
        $remaining = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($remaining as $i => $stopId) {
            $this->pdo->prepare('UPDATE trip_stops SET stop_order = ? WHERE id = ?')
                ->execute([$i + 1, $stopId]);
        }
    }

    public function reorder(int $tripId, array $stopIds): void
    {
        foreach ($stopIds as $order => $stopId) {
            $this->pdo->prepare('UPDATE trip_stops SET stop_order = ? WHERE id = ? AND trip_id = ?')
                ->execute([$order + 1, $stopId, $tripId]);
        }
    }
}
