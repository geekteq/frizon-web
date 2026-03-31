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

    public function showChangePassword(array $params): void
    {
        Auth::requireLogin();
        $pageTitle = 'Byt lösenord';
        view('auth/change-password', compact('pageTitle'));
    }

    public function changePassword(array $params): void
    {
        Auth::requireLogin();
        CsrfService::requireValid();

        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($current === '' || $new === '' || $confirm === '') {
            flash('error', 'Fyll i alla fält.');
            redirect('/adm/byt-losenord');
        }

        if ($new !== $confirm) {
            flash('error', 'Nya lösenorden matchar inte.');
            redirect('/adm/byt-losenord');
        }

        if (strlen($new) < 8) {
            flash('error', 'Lösenordet måste vara minst 8 tecken.');
            redirect('/adm/byt-losenord');
        }

        // Verify current password
        $stmt = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([Auth::userId()]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($current, $user['password_hash'])) {
            flash('error', 'Nuvarande lösenord är fel.');
            redirect('/adm/byt-losenord');
        }

        // Update
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$hash, Auth::userId()]);

        flash('success', 'Lösenordet har ändrats.');
        redirect('/adm');
    }

    public function logout(array $params): void
    {
        CsrfService::requireValid();
        Auth::logout();
        redirect('/adm/login');
    }
}
