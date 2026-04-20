<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$uploadDir = $root . '/storage/uploads';
$mediumDir = $uploadDir . '/medium';
$detailDir = $uploadDir . '/detail';
$originalDir = $uploadDir . '/originals';

if (!extension_loaded('gd') || !function_exists('imagewebp')) {
    fwrite(STDERR, "GD med WebP-stod saknas.\n");
    exit(1);
}

if (!is_dir($mediumDir) && !mkdir($mediumDir, 0775, true) && !is_dir($mediumDir)) {
    fwrite(STDERR, "Kunde inte skapa {$mediumDir}\n");
    exit(1);
}

$detailFiles = glob($detailDir . '/*.webp') ?: [];
$created = 0;
$skipped = 0;
$failed = 0;

foreach ($detailFiles as $detailPath) {
    $filename = basename($detailPath);
    $target = $mediumDir . '/' . $filename;

    if (is_file($target)) {
        $skipped++;
        continue;
    }

    [$source, $mimeType] = findSourceImage($originalDir, $detailPath, $filename);
    if ($source === null || $mimeType === null) {
        fwrite(STDERR, "Hoppar over {$filename}: ingen lasbar kallbild.\n");
        $failed++;
        continue;
    }

    if (resizeToWebp($source, $target, 800, 600, $mimeType)) {
        $created++;
    } else {
        fwrite(STDERR, "Misslyckades med {$filename}\n");
        $failed++;
    }
}

echo "Medium-varianter skapade: {$created}\n";
echo "Redan fanns: {$skipped}\n";
echo "Misslyckades: {$failed}\n";

exit($failed > 0 ? 1 : 0);

function findSourceImage(string $originalDir, string $detailPath, string $filename): array
{
    $stem = pathinfo($filename, PATHINFO_FILENAME);
    $matches = glob($originalDir . '/' . $stem . '.*') ?: [];

    foreach ($matches as $path) {
        $mimeType = mime_content_type($path) ?: null;
        if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return [$path, $mimeType];
        }
    }

    return [$detailPath, 'image/webp'];
}

function resizeToWebp(string $source, string $dest, int $maxW, int $maxH, string $mimeType): bool
{
    $img = match ($mimeType) {
        'image/jpeg' => imagecreatefromjpeg($source),
        'image/png' => imagecreatefrompng($source),
        'image/webp' => imagecreatefromwebp($source),
        default => null,
    };

    if (!$img) {
        return false;
    }

    $origW = imagesx($img);
    $origH = imagesy($img);
    $ratio = min($maxW / $origW, $maxH / $origH);
    if ($ratio >= 1) {
        $ratio = 1;
    }

    $newW = max(1, (int) round($origW * $ratio));
    $newH = max(1, (int) round($origH * $ratio));

    $resized = imagecreatetruecolor($newW, $newH);
    if ($mimeType === 'image/png') {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
    }

    imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    $ok = imagewebp($resized, $dest, 82);

    return $ok;
}
