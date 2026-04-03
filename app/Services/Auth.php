<?php

declare(strict_types=1);

class Auth
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function attempt(string $username, string $password): bool
    {
        app_start_session();

        $stmt = $this->pdo->prepare('SELECT id, password_hash, display_name FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['display_name'];
        session_regenerate_id(true);
        return true;
    }

    public static function check(): bool
    {
        app_start_session();
        return isset($_SESSION['user_id']);
    }

    public static function userId(): ?int
    {
        app_start_session();
        return $_SESSION['user_id'] ?? null;
    }

    public static function userName(): ?string
    {
        app_start_session();
        return $_SESSION['user_name'] ?? null;
    }

    public static function logout(): void
    {
        app_start_session();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('error', 'Du måste logga in.');
            redirect('/adm/login');
        }
    }
}
