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
            SELECT t.*,
                   (SELECT COUNT(*) FROM trip_stops WHERE trip_id = t.id) as stop_count,
                   (SELECT COALESCE(SUM(distance_km), 0) FROM trip_route_segments WHERE trip_id = t.id) as total_km
            FROM trips t
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
                start_date = ?, end_date = ?,
                public_teaser = ?, teaser_text = ?,
                updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([
            $data['title'],
            $data['intro_text'] ?? null,
            $data['status'],
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            (int) ($data['public_teaser'] ?? 0),
            $data['teaser_text'] ?? null,
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
            SELECT
                (SELECT COUNT(*) FROM trip_stops WHERE trip_id = ?) as stop_count,
                (SELECT COALESCE(SUM(distance_km), 0) FROM trip_route_segments WHERE trip_id = ?) as total_km,
                (SELECT COALESCE(SUM(provider_eta_minutes), 0) FROM trip_route_segments WHERE trip_id = ?) as total_eta_provider,
                (SELECT COALESCE(SUM(eta_95_minutes), 0) FROM trip_route_segments WHERE trip_id = ?) as total_eta_95
        ');
        $stmt->execute([$id, $id, $id, $id]);
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
