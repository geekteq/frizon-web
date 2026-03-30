<?php
require_once dirname(__DIR__, 2) . '/app/Services/Auth.php';
Auth::requireLogin();
$pageTitle = $pageTitle ?? 'Frizon';
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Frizon</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="/css/main.css">
</head>
<body class="app-layout">
    <?php include dirname(__DIR__) . '/partials/nav-desktop.php'; ?>
    <?php include dirname(__DIR__) . '/partials/header.php'; ?>

    <main class="app-main">
        <?php include dirname(__DIR__) . '/partials/toast.php'; ?>
        <?= $content ?>
    </main>

    <?php include dirname(__DIR__) . '/partials/nav-mobile.php'; ?>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="/js/app.js"></script>
</body>
</html>
