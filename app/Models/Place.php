<?php

declare(strict_types=1);

class Place
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT p.*, COUNT(v.id) as visit_count,
                AVG(vr.total_rating_cached) as avg_rating
                FROM places p
                LEFT JOIN visits v ON v.place_id = p.id
                LEFT JOIN visit_ratings vr ON vr.visit_id = v.id';
        $where = [];
        $params = [];

        if (!empty($filters['place_type'])) {
            $where[] = 'p.place_type = ?';
            $params[] = $filters['place_type'];
        }
        if (!empty($filters['country_code'])) {
            $where[] = 'p.country_code = ?';
            $params[] = $filters['country_code'];
        }
        if (!empty($filters['search'])) {
            $where[] = 'p.name LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY p.id ORDER BY p.updated_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function publicListing(array $filters = []): array
    {
        $sql = 'SELECT
                    p.id,
                    p.slug,
                    p.name,
                    p.lat,
                    p.lng,
                    p.place_type,
                    p.country_code,
                    p.default_public_text,
                    p.is_featured,
                    COUNT(v.id) as visit_count,
                    AVG(vr.total_rating_cached) as avg_rating
                FROM places p
                LEFT JOIN visits v ON v.place_id = p.id AND v.ready_for_publish = 1
                LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
                WHERE p.public_allowed = 1';
        $params = [];

        if (!empty($filters['place_type'])) {
            $sql .= ' AND p.place_type = ?';
            $params[] = $filters['place_type'];
        }
        if (!empty($filters['country_code'])) {
            $sql .= ' AND p.country_code = ?';
            $params[] = $filters['country_code'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND p.name LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
        }

        $sql .= ' GROUP BY p.id ORDER BY p.is_featured DESC, p.updated_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM places WHERE slug = ?');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM places WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO places (slug, name, lat, lng, address_text, country_code, place_type, default_public_text, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['slug'], $data['name'], $data['lat'], $data['lng'],
            $data['address_text'] ?? null, $data['country_code'] ?? null,
            $data['place_type'], $data['default_public_text'] ?? null, $data['created_by'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE places SET name = ?, lat = ?, lng = ?, address_text = ?,
            country_code = ?, place_type = ?, default_public_text = ?,
            meta_description = ?, faq_content = ?, updated_at = NOW() WHERE id = ?
        ');
        $stmt->execute([
            $data['name'], $data['lat'], $data['lng'],
            $data['address_text'] ?? null, $data['country_code'] ?? null,
            $data['place_type'], $data['default_public_text'] ?? null,
            $data['meta_description'] ?? null, $data['faq_content'] ?? null,
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM places WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function findNearby(float $lat, float $lng, int $radiusMeters): array
    {
        $sql = '
            SELECT *, (
                6371000 * acos(
                    cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?))
                    + sin(radians(?)) * sin(radians(lat))
                )
            ) AS distance_meters
            FROM places
            HAVING distance_meters <= ?
            ORDER BY distance_meters ASC
            LIMIT 5
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$lat, $lng, $lat, $radiusMeters]);
        return $stmt->fetchAll();
    }

    public static function generateSlug(string $name): string
    {
        $slug = mb_strtolower($name);
        $slug = strtr($slug, [
            'å' => 'a', 'ä' => 'a', 'ö' => 'o',
            'é' => 'e', 'è' => 'e', 'ü' => 'u',
        ]);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    }
}
