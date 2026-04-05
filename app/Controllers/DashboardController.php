<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Models/Visit.php';

class DashboardController
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function index(array $params): void
    {
        Auth::requireLogin();

        $visitModel = new Visit($this->pdo);
        $recentVisits = $visitModel->recentForUser(Auth::userId(), 5);

        $stats = [];
        $stats['places'] = (int) $this->pdo->query('SELECT COUNT(*) FROM places')->fetchColumn();
        $stats['visits'] = (int) $this->pdo->query('SELECT COUNT(*) FROM visits')->fetchColumn();
        $stats['countries'] = (int) $this->pdo->query('SELECT COUNT(DISTINCT country_code) FROM places WHERE country_code IS NOT NULL')->fetchColumn();
        $stats['trips'] = (int) $this->pdo->query('SELECT COUNT(*) FROM trips')->fetchColumn();
        $stats['lists'] = (int) $this->pdo->query('SELECT COUNT(*) FROM lists')->fetchColumn();

        // All places for the map
        $places = $this->pdo->query('SELECT id, slug, name, lat, lng, place_type, country_code FROM places ORDER BY updated_at DESC')->fetchAll();

        $pageTitle = 'Dashboard';
        view('dashboard/index', compact('recentVisits', 'stats', 'places', 'pageTitle'));
    }

    public function stats(array $params): void
    {
        Auth::requireLogin();

        // Top products by clicks — last 30 days
        $topProducts = $this->pdo->query('
            SELECT ap.title, ap.slug, COUNT(pc.id) AS clicks
            FROM product_clicks pc
            JOIN amazon_products ap ON ap.id = pc.product_id
            WHERE pc.clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY ap.id
            ORDER BY clicks DESC
            LIMIT 20
        ')->fetchAll();

        // Top referrer pages — last 30 days
        $topReferrers = $this->pdo->query('
            SELECT referrer, COUNT(*) AS clicks
            FROM product_clicks
            WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              AND referrer IS NOT NULL AND referrer != \'\'
            GROUP BY referrer
            ORDER BY clicks DESC
            LIMIT 20
        ')->fetchAll();

        // Top places by views
        $topPlaces = $this->pdo->query('
            SELECT name, slug, view_count
            FROM places
            WHERE public_allowed = 1
            ORDER BY view_count DESC
            LIMIT 20
        ')->fetchAll();

        // Totals
        $totalClicks30d = (int) $this->pdo->query('
            SELECT COUNT(*) FROM product_clicks
            WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ')->fetchColumn();

        $totalClicksAllTime = (int) $this->pdo->query('
            SELECT COUNT(*) FROM product_clicks
        ')->fetchColumn();

        $pageTitle = 'Statistik';
        view('dashboard/stats', compact(
            'topProducts', 'topReferrers', 'topPlaces',
            'totalClicks30d', 'totalClicksAllTime', 'pageTitle'
        ));
    }
}
