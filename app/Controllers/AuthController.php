<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Services/Auth.php';
require_once dirname(__DIR__) . '/Services/CsrfService.php';
require_once dirname(__DIR__) . '/Services/SecurityAudit.php';

class AuthController
{
    private const PASSWORD_MIN_LENGTH = 12;

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

        $throttles = $this->loginThrottles($username, $ipAddress);

        try {
            foreach ($throttles as [$throttle, $scopeUsername, $scopeIp]) {
                $throttle->ensureAllowed($scopeUsername, $scopeIp);
            }
        } catch (RuntimeException $e) {
            SecurityAudit::log($this->pdo, 'auth.login_throttled', [
                'username' => $username,
                'ip_address' => $ipAddress,
            ]);
            flash('error', $e->getMessage());
            redirect('/adm/login');
        }

        $auth = new Auth($this->pdo);
        if ($auth->attempt($username, $password)) {
            foreach ($throttles as [$throttle, $scopeUsername, $scopeIp]) {
                $throttle->clear($scopeUsername, $scopeIp);
            }
            SecurityAudit::log($this->pdo, 'auth.login_success', [
                'username' => $username,
            ], Auth::userId());
            redirect('/adm');
        }

        foreach ($throttles as [$throttle, $scopeUsername, $scopeIp]) {
            $throttle->recordFailure($scopeUsername, $scopeIp);
        }

        SecurityAudit::log($this->pdo, 'auth.login_failed', [
            'username' => $username,
            'ip_address' => $ipAddress,
        ]);
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

        if (mb_strlen($new) < self::PASSWORD_MIN_LENGTH) {
            flash('error', 'Lösenordet måste vara minst ' . self::PASSWORD_MIN_LENGTH . ' tecken.');
            redirect('/adm/byt-losenord');
        }

        if (!preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
            flash('error', 'Lösenordet måste innehålla både bokstäver och siffror.');
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

        SecurityAudit::log($this->pdo, 'auth.password_changed', [], Auth::userId());
        flash('success', 'Lösenordet har ändrats.');
        redirect('/adm');
    }

    public function logout(array $params): void
    {
        CsrfService::requireValid();
        $userId = Auth::userId();
        SecurityAudit::log($this->pdo, 'auth.logout', [], $userId);
        Auth::logout();
        redirect('/adm/login');
    }

    private function loginThrottles(string $username, string $ipAddress): array
    {
        return [
            [new LoginThrottle(maxAttempts: 5, windowSeconds: 900), $username, $ipAddress],
            [new LoginThrottle(maxAttempts: 10, windowSeconds: 900), $username, '*'],
            [new LoginThrottle(maxAttempts: 20, windowSeconds: 900), '*', $ipAddress],
        ];
    }
}
