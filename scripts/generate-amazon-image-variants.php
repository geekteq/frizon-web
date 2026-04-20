<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Services/AmazonFetcher.php';

$root = dirname(__DIR__);
$uploadDir = $root . '/storage/uploads/amazon';

if (!is_dir($uploadDir)) {
    fwrite(STDERR, "Amazon upload-katalog saknas: {$uploadDir}\n");
    exit(1);
}

$fetcher = new AmazonFetcher('', $uploadDir);
$files = glob($uploadDir . '/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [];

$created = 0;
$failed = 0;

foreach ($files as $path) {
    $filename = basename($path);
    if ($fetcher->ensureCardVariant($filename)) {
        $created++;
    } else {
        fwrite(STDERR, "Misslyckades med amazon-card/{$filename}\n");
        $failed++;
    }
}

echo "Amazon-varianter skapade/uppdaterade: {$created}\n";
echo "Misslyckades: {$failed}\n";

exit($failed > 0 ? 1 : 0);
