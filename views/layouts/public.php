<?php
$pageTitle = $pageTitle ?? 'Frizon of Sweden';
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/css/pages/public.css">
</head>
<body class="public-layout">
    <header class="public-header">
        <div class="public-header__inner">
            <a href="/pub" class="public-header__brand">
                <span class="public-header__name">Frizon</span>
                <span class="public-header__tagline">of Sweden</span>
            </a>
            <nav class="public-header__nav">
                <a href="/pub" class="public-header__link">Platser</a>
                <a href="/pub/topplista" class="public-header__link">Topplista</a>
            </nav>
        </div>
    </header>

    <main class="public-main">
        <?= $content ?>
    </main>

    <footer class="public-footer">
        <p>Frizon of Sweden — Resedagbok med Frizze</p>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body>
</html>
