<?php

return [
    'name'   => $_ENV['APP_NAME'] ?? 'Frizon',
    'url'    => $_ENV['APP_URL'] ?? 'http://localhost',
    'debug'  => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',

    'db' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'name' => $_ENV['DB_NAME'] ?? 'frizon',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? '',
    ],

    'upload_max_size'      => (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760),
    'nearby_radius_meters' => (int) ($_ENV['NEARBY_RADIUS_METERS'] ?? 100),

    'instagram' => [
        'user_id'      => $_ENV['INSTAGRAM_USER_ID'] ?? '',
        'access_token' => $_ENV['INSTAGRAM_ACCESS_TOKEN'] ?? '',
    ],
];
