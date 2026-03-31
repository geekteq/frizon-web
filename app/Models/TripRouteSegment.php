<?php

declare(strict_types=1);

class TripRouteSegment
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByTrip(int $tripId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT trs.*,
                   fs.place_id as from_place_id, fp.name as from_name, fp.lat as from_lat, fp.lng as from_lng,
                   tos.place_id as to_place_id, tp.name as to_name, tp.lat as to_lat, tp.lng as to_lng
            FROM trip_route_segments trs
            JOIN trip_stops fs ON fs.id = trs.from_stop_id
            JOIN places fp ON fp.id = fs.place_id
            JOIN trip_stops tos ON tos.id = trs.to_stop_id
            JOIN places tp ON tp.id = tos.place_id
            WHERE trs.trip_id = ?
            ORDER BY fs.stop_order ASC
        ');
        $stmt->execute([$tripId]);
        return $stmt->fetchAll();
    }

    public function saveSegment(int $tripId, int $fromStopId, int $toStopId, array $routeData): void
    {
        // Delete existing segment for this pair
        $this->pdo->prepare('
            DELETE FROM trip_route_segments WHERE trip_id = ? AND from_stop_id = ? AND to_stop_id = ?
        ')->execute([$tripId, $fromStopId, $toStopId]);

        $eta95 = $routeData['distance_km'] > 0
            ? (int) round($routeData['distance_km'] / 95 * 60)
            : 0;

        $stmt = $this->pdo->prepare('
            INSERT INTO trip_route_segments
                (trip_id, from_stop_id, to_stop_id, distance_km, provider_eta_minutes, eta_95_minutes, geometry, provider_name)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $tripId,
            $fromStopId,
            $toStopId,
            $routeData['distance_km'],
            $routeData['provider_eta_minutes'],
            $eta95,
            $routeData['geometry'] ?? null,
            $routeData['provider_name'] ?? 'unknown',
        ]);
    }

    public function deleteByTrip(int $tripId): void
    {
        $this->pdo->prepare('DELETE FROM trip_route_segments WHERE trip_id = ?')->execute([$tripId]);
    }
}
