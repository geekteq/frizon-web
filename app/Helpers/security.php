<?php

declare(strict_types=1);

function app_is_https_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    if (($_SERVER['SERVER_PORT'] ?? null) === '443') {
        return true;
    }

    return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function app_request_path(): string
{
    return (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
}

function app_request_uses_session(): bool
{
    return str_starts_with(app_request_path(), '/adm');
}

function app_start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function app_csp_nonce(): string
{
    if (empty($_SERVER['app_csp_nonce'])) {
        $_SERVER['app_csp_nonce'] = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }

    return (string) $_SERVER['app_csp_nonce'];
}

function app_csp_nonce_attr(): string
{
    return ' nonce="' . htmlspecialchars(app_csp_nonce(), ENT_QUOTES, 'UTF-8') . '"';
}

function set_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    $nonce = app_csp_nonce();
    $csp = implode('; ', [
        "default-src 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'none'",
        "object-src 'none'",
        "manifest-src 'self'",
        "worker-src 'self'",
        "img-src 'self' data: blob: https://*.tile.openstreetmap.org https://unpkg.com https://www.google-analytics.com https://www.googletagmanager.com",
        "font-src 'self' data: https://fonts.gstatic.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com",
        "script-src 'self' 'nonce-{$nonce}' https://unpkg.com https://www.googletagmanager.com https://static.cloudflareinsights.com",
        "connect-src 'self' https://*.tile.openstreetmap.org https://unpkg.com https://www.googletagmanager.com https://www.google-analytics.com https://region1.google-analytics.com https://cloudflareinsights.com",
    ]);

    header('Content-Security-Policy: ' . $csp);
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Permissions-Policy: geolocation=(self)');

    if (app_is_https_request()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
