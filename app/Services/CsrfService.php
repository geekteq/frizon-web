<?php

declare(strict_types=1);

class CsrfService
{
    public static function token(): string
    {
        app_start_session();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token()) . '">';
    }

    public static function verify(): bool
    {
        app_start_session();
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return hash_equals(self::token(), $token);
    }

    public static function requireValid(): void
    {
        if (!self::verify()) {
            http_response_code(403);
            die('Ogiltig CSRF-token. Ladda om sidan och försök igen.');
        }
    }
}
