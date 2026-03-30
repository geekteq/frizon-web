<?php

declare(strict_types=1);

class VisitRating
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByVisit(int $visitId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM visit_ratings WHERE visit_id = ?');
        $stmt->execute([$visitId]);
        return $stmt->fetch() ?: null;
    }

    public function save(int $visitId, array $ratings): void
    {
        $provided = array_filter($ratings, fn($v) => $v !== null && $v !== '');
        $count = count($provided);
        $total = $count > 0 ? round(array_sum($provided) / $count, 1) : null;

        $existing = $this->findByVisit($visitId);

        if ($existing) {
            $stmt = $this->pdo->prepare('
                UPDATE visit_ratings SET location_rating = ?, calmness_rating = ?, service_rating = ?,
                    value_rating = ?, return_value_rating = ?, total_rating_cached = ?, updated_at = NOW()
                WHERE visit_id = ?
            ');
            $stmt->execute([
                $ratings['location_rating'] ?? null,
                $ratings['calmness_rating'] ?? null,
                $ratings['service_rating'] ?? null,
                $ratings['value_rating'] ?? null,
                $ratings['return_value_rating'] ?? null,
                $total,
                $visitId,
            ]);
        } else {
            $stmt = $this->pdo->prepare('
                INSERT INTO visit_ratings (visit_id, location_rating, calmness_rating, service_rating,
                    value_rating, return_value_rating, total_rating_cached)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $visitId,
                $ratings['location_rating'] ?? null,
                $ratings['calmness_rating'] ?? null,
                $ratings['service_rating'] ?? null,
                $ratings['value_rating'] ?? null,
                $ratings['return_value_rating'] ?? null,
                $total,
            ]);
        }
    }
}
