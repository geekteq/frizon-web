<?php

return [
    'name'   => $_ENV['APP_NAME'] ?? 'Frizon',
    'url'    => $_ENV['APP_URL'] ?? 'http://localhost',
    'debug'  => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    'trusted_proxies' => array_values(array_filter(array_map(
        static fn (string $proxy): string => trim($proxy),
        explode(',', $_ENV['TRUSTED_PROXIES'] ?? '')
    ))),

    'db' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'name' => $_ENV['DB_NAME'] ?? 'frizon',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? '',
    ],

    'upload_max_size'      => (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760),
    'upload_max_width'     => (int) ($_ENV['UPLOAD_MAX_WIDTH'] ?? 8000),
    'upload_max_height'    => (int) ($_ENV['UPLOAD_MAX_HEIGHT'] ?? 8000),
    'upload_max_pixels'    => (int) ($_ENV['UPLOAD_MAX_PIXELS'] ?? 40000000),
    'nearby_radius_meters' => (int) ($_ENV['NEARBY_RADIUS_METERS'] ?? 100),

    'instagram' => [
        'user_id'      => $_ENV['INSTAGRAM_USER_ID'] ?? '',
        'access_token' => $_ENV['INSTAGRAM_ACCESS_TOKEN'] ?? '',
    ],
];
