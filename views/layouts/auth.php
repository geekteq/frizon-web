<?php
$pageTitle = $pageTitle ?? 'Logga in';
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Frizon</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('/css/main.bundle.css')) ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
</head>
<body class="auth-page">
    <div class="auth-container">
        <?= $content ?>
    </div>
</body>
</html>
