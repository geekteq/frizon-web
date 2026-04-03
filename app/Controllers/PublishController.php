<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Models/Place.php';
require_once dirname(__DIR__) . '/Services/AiService.php';

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
        $place      = $placeModel->findBySlug($params['slug']);
        if (!$place) { http_response_code(404); return; }

        // Place goes live immediately
        $this->pdo->prepare('UPDATE places SET public_allowed = 1, updated_at = NOW() WHERE id = ?')
             ->execute([$place['id']]);

        // Fetch published visits for AI context
        $stmt = $this->pdo->prepare('
            SELECT v.*, vr.total_rating_cached
            FROM visits v
            LEFT JOIN visit_ratings vr ON vr.visit_id = v.id
            WHERE v.place_id = ? AND v.ready_for_publish = 1
        ');
        $stmt->execute([$place['id']]);
        $visits = $stmt->fetchAll();

        // Generate SEO content and write directly to the places row
        try {
            $aiService = new AiService();
            $seo       = $aiService->generatePlaceSeo($place, $visits);

            $this->pdo->prepare(
                'UPDATE places SET meta_description = ?, faq_content = ?, updated_at = NOW() WHERE id = ?'
            )->execute([$seo['meta_description'], $seo['faq_content'], $place['id']]);

            flash('success', $place['name'] . ' är nu publik med SEO-innehåll!');
        } catch (RuntimeException $e) {
            // Place is live — only SEO generation failed. Show warning, continue.
            flash('warning', $place['name'] . ' är publik men SEO-innehåll kunde inte genereras: ' . $e->getMessage());
        }

        ping_search_engines();
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
        ping_search_engines();
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
        ping_search_engines();
        redirect('/adm/publicera');
    }
}
