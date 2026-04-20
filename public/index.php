<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/Router.php';
require_once dirname(__DIR__) . '/routes/web.php';

set_security_headers();

// Serve uploaded images from storage
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/uploads/(thumbnails|cards|gallery|medium|detail|amazon|amazon-card)/([^/]+)$#', $uri, $m)) {
    $uploadsDir = dirname(__DIR__) . '/storage/uploads';
    $variantDir = realpath($uploadsDir . '/' . $m[1]);
    $candidatePath = $variantDir ? $variantDir . DIRECTORY_SEPARATOR . $m[2] : null;
    $realFilePath = $candidatePath ? realpath($candidatePath) : false;

    if (
        (!$variantDir || !$realFilePath || !is_file($realFilePath))
        && $m[1] === 'amazon-card'
    ) {
        $variantDir = realpath($uploadsDir . '/amazon');
        $candidatePath = $variantDir ? $variantDir . DIRECTORY_SEPARATOR . $m[2] : null;
        $realFilePath = $candidatePath ? realpath($candidatePath) : false;
    }

    if ($variantDir && $realFilePath && str_starts_with($realFilePath, $variantDir . DIRECTORY_SEPARATOR) && is_file($realFilePath)) {
        $mime = mime_content_type($realFilePath);
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=31536000, immutable');
        readfile($realFilePath);
        exit;
    }
}

$router = new Router();
registerRoutes($router);

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$router->dispatch($method, $uri, $pdo, $config);
