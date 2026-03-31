<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Models/Place.php';

class PublicController
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function homepage(array $params): void
    {
        // Public places with ratings
        $stmt = $this->pdo->query('
            SELECT p.*, AVG(vr.total_rating_cached) as avg_rating,
                   COUNT(v.id) as visit_count
            FROM places p
            LEFT JOIN visits v ON v.place_id = p.id
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE p.public_allowed = 1
            GROUP BY p.id
            ORDER BY p.is_featured DESC, p.updated_at DESC
        ');
        $places = $stmt->fetchAll();

        // Active filters
        $filterType = $_GET['type'] ?? null;
        $filterCountry = $_GET['country'] ?? null;

        if ($filterType) {
            $places = array_filter($places, fn($p) => $p['place_type'] === $filterType);
        }
        if ($filterCountry) {
            $places = array_filter($places, fn($p) => $p['country_code'] === $filterCountry);
        }
        $places = array_values($places);

        // Unique countries and types for filters
        $allPublic = $this->pdo->query('SELECT DISTINCT country_code FROM places WHERE public_allowed = 1 AND country_code IS NOT NULL ORDER BY country_code')->fetchAll(PDO::FETCH_COLUMN);
        $allTypes = $this->pdo->query('SELECT DISTINCT place_type FROM places WHERE public_allowed = 1 ORDER BY place_type')->fetchAll(PDO::FETCH_COLUMN);

        $pageTitle = 'Frizon of Sweden';
        view('public/homepage', compact('places', 'filterType', 'filterCountry', 'allPublic', 'allTypes', 'pageTitle'), 'public');
    }

    public function placeDetail(array $params): void
    {
        $placeModel = new Place($this->pdo);
        $place = $placeModel->findBySlug($params['slug']);
        if (!$place || !$place['public_allowed']) {
            http_response_code(404);
            echo '<h1>Platsen hittades inte</h1>';
            return;
        }

        // Get visits with ratings and images
        $stmt = $this->pdo->prepare('
            SELECT v.*, vr.total_rating_cached, vr.location_rating, vr.calmness_rating,
                   vr.service_rating, vr.value_rating, vr.return_value_rating
            FROM visits v
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE v.place_id = ? AND v.ready_for_publish = 1
            ORDER BY v.visited_at DESC
        ');
        $stmt->execute([$place['id']]);
        $visits = $stmt->fetchAll();

        // Get published images
        $imageStmt = $this->pdo->prepare('
            SELECT vi.* FROM visit_images vi
            JOIN visits v ON v.id = vi.visit_id
            WHERE v.place_id = ? AND v.ready_for_publish = 1
            ORDER BY vi.image_order ASC
            LIMIT 12
        ');
        $imageStmt->execute([$place['id']]);
        $images = $imageStmt->fetchAll();

        // Tags
        $tagStmt = $this->pdo->prepare('SELECT tag FROM place_tags WHERE place_id = ?');
        $tagStmt->execute([$place['id']]);
        $tags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

        // Avg rating
        $ratingStmt = $this->pdo->prepare('
            SELECT AVG(vr.total_rating_cached) as avg_rating
            FROM visits v
            JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE v.place_id = ?
        ');
        $ratingStmt->execute([$place['id']]);
        $avgRating = $ratingStmt->fetchColumn();

        $pageTitle = $place['name'];
        view('public/place-detail', compact('place', 'visits', 'images', 'tags', 'avgRating', 'pageTitle'), 'public');
    }

    public function topList(array $params): void
    {
        $stmt = $this->pdo->query('
            SELECT p.*, AVG(vr.total_rating_cached) as avg_rating,
                   COUNT(v.id) as visit_count
            FROM places p
            LEFT JOIN visits v ON v.place_id = p.id
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE p.is_toplisted = 1 AND p.public_allowed = 1
            GROUP BY p.id
            ORDER BY p.toplist_order ASC, avg_rating DESC
        ');
        $places = $stmt->fetchAll();

        $pageTitle = 'Topplista — Frizon';
        view('public/toplist', compact('places', 'pageTitle'), 'public');
    }
}
