<?php

/**
 * Notify Google that the sitemap has changed.
 * Fire-and-forget: opens a non-blocking socket, sends the request, and closes.
 * We do not wait for a response — the goal is to trigger a re-crawl quickly.
 */
function ping_search_engines(): void
{
    $appUrl     = rtrim($_ENV['APP_URL'] ?? 'https://app.frizon.org', '/');
    $sitemapUrl = $appUrl . '/sitemap.xml';
    $pingUrl    = 'https://www.google.com/ping?sitemap=' . urlencode($sitemapUrl);

    $parts = parse_url($pingUrl);
    $host  = $parts['host'];
    $path  = ($parts['path'] ?? '/') . '?' . ($parts['query'] ?? '');

    $fp = @fsockopen('ssl://' . $host, 443, $errno, $errstr, 2);
    if ($fp) {
        $req = "GET {$path} HTTP/1.1\r\nHost: {$host}\r\nConnection: close\r\nUser-Agent: frizon-sitemap-ping/1.0\r\n\r\n";
        @fwrite($fp, $req);
        @fclose($fp);
    }
}
