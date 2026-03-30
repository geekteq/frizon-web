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
