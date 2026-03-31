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
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#3D4F5F">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
</head>
<body class="public-layout" data-ga-id="<?= htmlspecialchars($_ENV['GA_MEASUREMENT_ID'] ?? '') ?>">
    <header class="public-header" style="height:auto; padding:var(--space-4) var(--space-6);">
        <div class="public-header__inner" style="flex-direction:row; align-items:center; justify-content:center; gap:var(--space-6); max-width:var(--content-max-width); margin:0 auto; width:100%;">
            <a href="/" class="public-header__link" style="font-weight:var(--weight-semibold);">Platser</a>
            <a href="/" style="text-decoration:none; flex-shrink:0;">
                <img src="/img/frizon-logo.png" alt="Frizon of Sweden" style="width:64px; height:64px; border-radius:50%; display:block;">
            </a>
            <a href="/topplista" class="public-header__link" style="font-weight:var(--weight-semibold);">Topplista</a>
        </div>
    </header>

    <main class="public-main">
        <?= $content ?>
    </main>

    <footer style="background:var(--color-brand-dark); text-align:center; padding:var(--space-8) var(--space-4) var(--space-6);">
        <p style="color:rgba(255,255,255,0.85); font-size:var(--text-sm); margin-bottom:var(--space-3);">Frizon of Sweden — Resedagbok med Frizze</p>
        <p style="font-size:var(--text-xs); margin-bottom:var(--space-3);">
            <a href="/integritetspolicy" style="color:rgba(255,255,255,0.6); text-decoration:underline;">Integritetspolicy</a>
            <span style="color:rgba(255,255,255,0.4);"> &middot; </span>
            <a href="/cookiepolicy" style="color:rgba(255,255,255,0.6); text-decoration:underline;">Cookiepolicy</a>
            <span style="color:rgba(255,255,255,0.4);"> &middot; </span>
            <a href="/adm" style="color:rgba(255,255,255,0.4); text-decoration:none;">Admin</a>
        </p>
        <p style="font-size:var(--text-xs); color:rgba(255,255,255,0.45);">
            &copy; <?= date('Y') ?> <a href="https://mobileminds.se" target="_blank" rel="noopener" style="color:rgba(255,255,255,0.55); text-decoration:underline;">Mobile Minds AB</a>
        </p>
    </footer>

    <!-- Cookie consent banner -->
    <div id="cookie-banner" style="display:none; position:fixed; bottom:0; left:0; right:0; background:var(--color-brand-dark); color:var(--color-white); padding:var(--space-4); z-index:9999; box-shadow:0 -2px 10px rgba(0,0,0,0.2);">
        <div style="max-width:var(--content-max-width); margin:0 auto; display:flex; align-items:center; justify-content:space-between; gap:var(--space-4); flex-wrap:wrap;">
            <p style="font-size:var(--text-sm); margin:0; flex:1; min-width:200px;">
                Vi använder cookies för att analysera besökstrafik via Google Analytics.
                Läs vår <a href="/cookiepolicy" style="color:var(--color-accent-light); text-decoration:underline;">cookiepolicy</a>.
            </p>
            <div style="display:flex; gap:var(--space-2); flex-shrink:0;">
                <button id="accept-cookies-btn" style="background:var(--color-accent); color:white; border:none; padding:var(--space-2) var(--space-4); border-radius:var(--radius-md); cursor:pointer; font-size:var(--text-sm); font-weight:var(--weight-semibold);">Godkänn</button>
                <button id="decline-cookies-btn" style="background:transparent; color:rgba(255,255,255,0.7); border:1px solid rgba(255,255,255,0.3); padding:var(--space-2) var(--space-4); border-radius:var(--radius-md); cursor:pointer; font-size:var(--text-sm);">Avböj</button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script<?= app_csp_nonce_attr() ?>>
    // Cookie consent
    function getCookieConsent() { return localStorage.getItem('cookie_consent'); }

    function acceptCookies() {
        localStorage.setItem('cookie_consent', 'accepted');
        document.getElementById('cookie-banner').style.display = 'none';
        loadGA();
    }

    function declineCookies() {
        localStorage.setItem('cookie_consent', 'declined');
        document.getElementById('cookie-banner').style.display = 'none';
    }

    function loadGA() {
        var gaId = document.body.dataset.gaId;
        if (!gaId) return;
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtag/js?id=' + gaId;
        document.head.appendChild(s);
        window.dataLayer = window.dataLayer || [];
        function gtag(){ dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', gaId, { anonymize_ip: true });
    }

    (function() {
        var consent = getCookieConsent();
        if (!consent) {
            document.getElementById('cookie-banner').style.display = 'block';
        } else if (consent === 'accepted') {
            loadGA();
        }
    })();

    var acceptBtn = document.getElementById('accept-cookies-btn');
    var declineBtn = document.getElementById('decline-cookies-btn');
    if (acceptBtn) acceptBtn.addEventListener('click', acceptCookies);
    if (declineBtn) declineBtn.addEventListener('click', declineCookies);

    if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js');
    </script>
</body>
</html>
