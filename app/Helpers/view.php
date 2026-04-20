<?php

function view(string $template, array $data = [], string $layout = 'app'): void
{
    extract($data);
    $contentFile = dirname(__DIR__, 2) . '/views/' . $template . '.php';

    ob_start();
    require $contentFile;
    $content = ob_get_clean();

    require dirname(__DIR__, 2) . '/views/layouts/' . $layout . '.php';
}

function asset_url(string $path): string
{
    $normalizedPath = '/' . ltrim($path, '/');
    $filePath = dirname(__DIR__, 2) . '/public' . $normalizedPath;

    if (!is_file($filePath)) {
        return $normalizedPath;
    }

    return $normalizedPath . '?v=' . filemtime($filePath);
}
