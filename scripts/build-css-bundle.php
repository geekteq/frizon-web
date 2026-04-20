<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$cssDir = $root . '/public/css';

writeBundleFromEntry($cssDir, 'main.css', 'main.bundle.css');

writeBundleFromFiles($cssDir, [
    'variables.css',
    'reset.css',
    'base.css',
    'components/buttons.css',
    'components/tags.css',
    'components/gallery.css',
    'pages/public.css',
    'utilities.css',
], 'public.bundle.css');

function writeBundleFromEntry(string $cssDir, string $entryFile, string $targetFile): void
{
    $entry = $cssDir . '/' . $entryFile;
    $entryCss = file_get_contents($entry);
    if ($entryCss === false) {
        fwrite(STDERR, "Could not read {$entry}\n");
        exit(1);
    }

    $importPattern = '/@import\s+url\([\'"]?([^\'")]+)[\'"]?\)\s*;/';
    preg_match_all($importPattern, $entryCss, $matches);
    writeBundleFromFiles($cssDir, $matches[1], $targetFile);
}

function writeBundleFromFiles(string $cssDir, array $relativeImports, string $targetFile): void
{
    $bundle = '';

    foreach ($relativeImports as $relativeImport) {
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

        $bundle .= rtrim($css) . "\n";
    }

    $target = $cssDir . '/' . $targetFile;
    $bundle = minifyCss($bundle);

    if (file_put_contents($target, $bundle . "\n") === false) {
        fwrite(STDERR, "Could not write {$target}\n");
        exit(1);
    }

    echo "Wrote public/css/{$targetFile}\n";
}

function minifyCss(string $css): string
{
    $css = preg_replace('#/\*.*?\*/#s', '', $css) ?? $css;
    $css = preg_replace('/\s+/', ' ', $css) ?? $css;
    $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css) ?? $css;
    $css = str_replace(';}', '}', $css);
    $css = preg_replace('/\s*!important/', '!important', $css) ?? $css;

    return trim($css);
}
