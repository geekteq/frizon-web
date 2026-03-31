<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Models/Place.php';

class PublishController
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function queue(array $params): void
    {
        Auth::requireLogin();

        // All places with their publish status
        $stmt = $this->pdo->query('
            SELECT p.*, AVG(vr.total_rating_cached) as avg_rating,
                   COUNT(v.id) as visit_count,
                   SUM(v.ready_for_publish) as publishable_visits
            FROM places p
            LEFT JOIN visits v ON v.place_id = p.id
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            GROUP BY p.id
            ORDER BY p.public_allowed DESC, p.is_toplisted DESC, p.name ASC
        ');
        $places = $stmt->fetchAll();

        $published = array_filter($places, fn($p) => $p['public_allowed']);
        $unpublished = array_filter($places, fn($p) => !$p['public_allowed']);

        $pageTitle = 'Publicera';
        view('publish/queue', compact('published', 'unpublished', 'pageTitle'));
    }

    public function approve(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $placeModel = new Place($this->pdo);
        $place = $placeModel->findBySlug($params['slug']);
        if (!$place) { http_response_code(404); return; }

        $this->pdo->prepare('UPDATE places SET public_allowed = 1, updated_at = NOW() WHERE id = ?')
            ->execute([$place['id']]);

        flash('success', $place['name'] . ' är nu publik!');
        redirect('/adm/publicera');
    }

    public function unpublish(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $placeModel = new Place($this->pdo);
        $place = $placeModel->findBySlug($params['slug']);
        if (!$place) { http_response_code(404); return; }

        $this->pdo->prepare('UPDATE places SET public_allowed = 0, is_toplisted = 0, is_featured = 0, updated_at = NOW() WHERE id = ?')
            ->execute([$place['id']]);

        flash('success', $place['name'] . ' är inte längre publik.');
        redirect('/adm/publicera');
    }

    public function toggleToplist(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $placeModel = new Place($this->pdo);
        $place = $placeModel->findBySlug($params['slug']);
        if (!$place) { http_response_code(404); return; }

        $newVal = $place['is_toplisted'] ? 0 : 1;
        $this->pdo->prepare('UPDATE places SET is_toplisted = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$newVal, $place['id']]);

        $msg = $newVal ? 'Tillagd i topplistan!' : 'Borttagen från topplistan.';
        flash('success', $msg);
        redirect('/adm/publicera');
    }
}
