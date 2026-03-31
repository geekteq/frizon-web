<?php

declare(strict_types=1);

// Load .env
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// Load config
$config = require dirname(__DIR__) . '/config/app.php';

// PDO connection
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $config['db']['host'],
    $config['db']['port'],
    $config['db']['name']
);
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);

// Helpers
require __DIR__ . '/Helpers/view.php';
require __DIR__ . '/Helpers/redirect.php';
require __DIR__ . '/Helpers/flash.php';
require __DIR__ . '/Helpers/security.php';

require __DIR__ . '/Services/CsrfService.php';
require __DIR__ . '/Services/Auth.php';
require __DIR__ . '/Services/LoginThrottle.php';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => app_is_https_request(),
    'httponly' => true,
    'samesite' => 'Lax',
]);

// Session
session_start();
