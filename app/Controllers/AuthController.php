<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';

class AuthController
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo, array $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    public function showLogin(array $params): void
    {
        if (Auth::check()) {
            redirect('/adm');
        }
        view('auth/login', [], 'auth');
    }

    public function login(array $params): void
    {
        CsrfService::requireValid();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $ipAddress = trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        if ($username === '' || $password === '') {
            flash('error', 'Fyll i alla fält.');
            redirect('/adm/login');
        }

        $throttle = new LoginThrottle();

        try {
            $throttle->ensureAllowed($username, $ipAddress);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/adm/login');
        }

        $auth = new Auth($this->pdo);
        if ($auth->attempt($username, $password)) {
            $throttle->clear($username, $ipAddress);
            redirect('/adm');
        }

        $throttle->recordFailure($username, $ipAddress);
        flash('error', 'Fel användarnamn eller lösenord.');
        redirect('/adm/login');
    }

    public function logout(array $params): void
    {
        CsrfService::requireValid();
        Auth::logout();
        redirect('/adm/login');
    }
}
