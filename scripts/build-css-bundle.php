<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$cssDir = $root . '/public/css';
$entry = $cssDir . '/main.css';
$target = $cssDir . '/main.bundle.css';

$entryCss = file_get_contents($entry);
if ($entryCss === false) {
    fwrite(STDERR, "Could not read {$entry}\n");
    exit(1);
}

$bundle = "/**\n";
$bundle .= " * Frizon.org — generated CSS bundle.\n";
$bundle .= " * Source: public/css/main.css and its local imports.\n";
$bundle .= " * Rebuild with: php scripts/build-css-bundle.php\n";
$bundle .= " */\n\n";

$importPattern = '/@import\s+url\([\'"]?([^\'")]+)[\'"]?\)\s*;/';
preg_match_all($importPattern, $entryCss, $matches);

foreach ($matches[1] as $relativeImport) {
    if (str_contains($relativeImport, '://') || str_starts_with($relativeImport, '//')) {
        fwrite(STDERR, "Remote imports are not supported: {$relativeImport}\n");
        exit(1);
    }

    $path = realpath($cssDir . '/' . $relativeImport);
    if ($path === false || !str_starts_with($path, $cssDir . DIRECTORY_SEPARATOR)) {
        fwrite(STDERR, "Invalid import path: {$relativeImport}\n");
        exit(1);
    }

    $css = file_get_contents($path);
    if ($css === false) {
        fwrite(STDERR, "Could not read {$path}\n");
        exit(1);
    }

    $bundle .= "/* ---- {$relativeImport} ---- */\n";
    $bundle .= rtrim($css) . "\n\n";
}

if (file_put_contents($target, $bundle) === false) {
    fwrite(STDERR, "Could not write {$target}\n");
    exit(1);
}

echo "Wrote public/css/main.bundle.css\n";
