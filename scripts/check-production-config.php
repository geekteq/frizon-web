<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$envFile = $root . '/.env';

if (!is_file($envFile)) {
    fwrite(STDERR, "FAIL: .env saknas i projektroten.\n");
    exit(1);
}

$env = [];
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
foreach ($lines as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
        continue;
    }

    [$key, $value] = explode('=', $line, 2);
    $env[trim($key)] = trim($value);
}

$failures = [];
$warnings = [];
$passes = [];

$isTrue = static fn (string $value): bool => strtolower($value) === 'true';

if (($env['APP_ENV'] ?? '') !== 'production') {
    $failures[] = 'APP_ENV måste vara production i prod.';
} else {
    $passes[] = 'APP_ENV=production';
}

if ($isTrue($env['APP_DEBUG'] ?? 'false')) {
    $failures[] = 'APP_DEBUG måste vara false i prod.';
} else {
    $passes[] = 'APP_DEBUG=false';
}

if (($env['AI_PROVIDER'] ?? 'fake') === 'fake') {
    $failures[] = 'AI_PROVIDER får inte vara fake i prod.';
} else {
    $passes[] = 'AI_PROVIDER är inte fake';
}

if (($env['AI_PROVIDER'] ?? '') === 'claude' && ($env['ANTHROPIC_API_KEY'] ?? '') === '') {
    $failures[] = 'ANTHROPIC_API_KEY saknas för AI_PROVIDER=claude.';
}

if ($isTrue($env['ENFORCE_TRUSTED_PROXIES'] ?? 'false') && ($env['TRUSTED_PROXIES'] ?? '') === '') {
    $failures[] = 'TRUSTED_PROXIES måste anges om ENFORCE_TRUSTED_PROXIES=true.';
}

if (($env['ORS_API_KEY'] ?? '') === '') {
    $warnings[] = 'ORS_API_KEY saknas. Ruttberäkning kommer inte fungera i prod.';
} else {
    $passes[] = 'ORS_API_KEY finns';
}

if (($env['APP_KEY'] ?? '') === '' || ($env['APP_KEY'] ?? '') === 'change-me-to-random-32-char-string') {
    $failures[] = 'APP_KEY måste vara satt till ett riktigt hemligt värde.';
} else {
    $passes[] = 'APP_KEY ser satt ut';
}

$requiredDirs = [
    $root . '/storage/uploads/originals',
    $root . '/storage/uploads/thumbnails',
    $root . '/storage/uploads/cards',
    $root . '/storage/uploads/medium',
    $root . '/storage/uploads/detail',
    $root . '/storage/runtime-secrets',
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        $failures[] = 'Katalog saknas: ' . $dir;
        continue;
    }

    if (!is_writable($dir)) {
        $warnings[] = 'Katalogen är inte skrivbar: ' . $dir;
        continue;
    }

    $passes[] = 'Skrivbar katalog: ' . basename($dir);
}

foreach ($passes as $pass) {
    echo "PASS: {$pass}\n";
}

foreach ($warnings as $warning) {
    echo "WARN: {$warning}\n";
}

foreach ($failures as $failure) {
    echo "FAIL: {$failure}\n";
}

if ($failures !== []) {
    exit(1);
}

exit(0);
