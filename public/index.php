<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/Router.php';
require_once dirname(__DIR__) . '/routes/web.php';

// Serve uploaded images from storage
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/uploads/(thumbnails|cards|detail|originals)/(.+)$#', $uri, $m)) {
    $filePath = dirname(__DIR__) . '/storage/uploads/' . $m[1] . '/' . $m[2];
    if (file_exists($filePath)) {
        $mime = mime_content_type($filePath);
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=31536000, immutable');
        readfile($filePath);
        exit;
    }
}

$router = new Router();
registerRoutes($router);

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$router->dispatch($method, $uri, $pdo, $config);
