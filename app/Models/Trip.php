<?php

declare(strict_types=1);

class Trip
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('
            SELECT t.*, COUNT(ts.id) as stop_count,
                   SUM(trs.distance_km) as total_km
            FROM trips t
            LEFT JOIN trip_stops ts ON ts.trip_id = t.id
            LEFT JOIN trip_route_segments trs ON trs.trip_id = t.id
            GROUP BY t.id
            ORDER BY FIELD(t.status, "ongoing", "planned", "finished"), t.start_date DESC
        ');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM trips WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM trips WHERE slug = ?');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO trips (slug, title, intro_text, status, created_by, start_date, end_date)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['slug'],
            $data['title'],
            $data['intro_text'] ?? null,
            $data['status'] ?? 'planned',
            $data['created_by'],
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE trips SET title = ?, intro_text = ?, status = ?,
                start_date = ?, end_date = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $data['title'],
            $data['intro_text'] ?? null,
            $data['status'],
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM trips WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function summary(int $id): array
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(ts.id) as stop_count,
                   COALESCE(SUM(trs.distance_km), 0) as total_km,
                   COALESCE(SUM(trs.provider_eta_minutes), 0) as total_eta_provider,
                   COALESCE(SUM(trs.eta_95_minutes), 0) as total_eta_95
            FROM trips t
            LEFT JOIN trip_stops ts ON ts.trip_id = t.id
            LEFT JOIN trip_route_segments trs ON trs.trip_id = t.id
            WHERE t.id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function generateSlug(string $title): string
    {
        $slug = mb_strtolower($title);
        $slug = strtr($slug, ['å' => 'a', 'ä' => 'a', 'ö' => 'o', 'é' => 'e', 'è' => 'e', 'ü' => 'u']);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    }
}
