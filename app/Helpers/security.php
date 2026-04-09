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

    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($forwardedProto !== 'https') {
        return false;
    }

    if (!app_enforce_trusted_proxies()) {
        return true;
    }

    return app_is_trusted_proxy_request();
}

function app_is_trusted_proxy_request(): bool
{
    $trustedProxies = app_trusted_proxies();
    if ($trustedProxies === []) {
        return false;
    }

    $remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($remoteAddr === '') {
        return false;
    }

    foreach ($trustedProxies as $proxy) {
        if (app_ip_matches_proxy($remoteAddr, $proxy)) {
            return true;
        }
    }

    return false;
}

function app_enforce_trusted_proxies(): bool
{
    return strtolower((string) ($_ENV['ENFORCE_TRUSTED_PROXIES'] ?? 'false')) === 'true';
}

function app_trusted_proxies(): array
{
    static $trustedProxies;
    if ($trustedProxies !== null) {
        return $trustedProxies;
    }

    $configured = $_ENV['TRUSTED_PROXIES'] ?? '';
    $trustedProxies = array_values(array_filter(array_map(
        static fn (string $proxy): string => trim($proxy),
        explode(',', $configured)
    )));

    return $trustedProxies;
}

function app_ip_matches_proxy(string $ipAddress, string $proxy): bool
{
    if ($proxy === $ipAddress) {
        return true;
    }

    if (!str_contains($proxy, '/')) {
        return false;
    }

    [$network, $prefixLength] = explode('/', $proxy, 2);
    $networkBin = @inet_pton($network);
    $ipBin = @inet_pton($ipAddress);
    $prefixLength = (int) $prefixLength;

    if ($networkBin === false || $ipBin === false || strlen($networkBin) !== strlen($ipBin)) {
        return false;
    }

    $maxBits = strlen($networkBin) * 8;
    if ($prefixLength < 0 || $prefixLength > $maxBits) {
        return false;
    }

    $fullBytes = intdiv($prefixLength, 8);
    $remainingBits = $prefixLength % 8;

    if ($fullBytes > 0 && substr($networkBin, 0, $fullBytes) !== substr($ipBin, 0, $fullBytes)) {
        return false;
    }

    if ($remainingBits === 0) {
        return true;
    }

    $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

    return (ord($networkBin[$fullBytes]) & $mask) === (ord($ipBin[$fullBytes]) & $mask);
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
        "img-src 'self' data: blob: https://*.tile.openstreetmap.org https://www.google-analytics.com https://www.googletagmanager.com",
        "font-src 'self' data: https://fonts.gstatic.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        "script-src 'self' 'nonce-{$nonce}' https://www.googletagmanager.com https://static.cloudflareinsights.com",
        "connect-src 'self' https://*.tile.openstreetmap.org https://www.googletagmanager.com https://www.google-analytics.com https://region1.google-analytics.com https://cloudflareinsights.com",
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
