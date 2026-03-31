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

        $pageTitle = 'Dashboard';
        view('dashboard/index', compact('recentVisits', 'stats', 'pageTitle'));
    }
}
