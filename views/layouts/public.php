<?php
$pageTitle = $pageTitle ?? 'Frizon of Sweden';
$seoMeta   = $seoMeta ?? [];
$schemas   = $schemas ?? [];
$appUrl    = rtrim($_ENV['APP_URL'] ?? 'https://frizon.org', '/');
$reqPath   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

$metaDescription = $seoMeta['description'] ?? 'Platser vi besökt med Frizze, vår Adria Twin. Ställplatser, campingar, restauranger och sevärdheter — sett ur ett husbilsperspektiv.';
$canonicalUrl    = $seoMeta['og_url']      ?? $appUrl . $reqPath;
$ogImage         = $seoMeta['og_image']    ?? $appUrl . '/img/frizon-logo.png';
$ogType          = $seoMeta['og_type']     ?? 'website';
$ogTitle         = htmlspecialchars($pageTitle);
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    <?php if (!empty($seoMeta['noindex'])): ?>
    <meta name="robots" content="noindex,follow">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:type"        content="<?= htmlspecialchars($ogType) ?>">
    <meta property="og:site_name"   content="Frizon of Sweden">
    <meta property="og:title"       content="<?= $ogTitle ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:url"         content="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta property="og:image"       content="<?= htmlspecialchars($ogImage) ?>">
    <meta property="og:locale"      content="sv_SE">

    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= $ogTitle ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="twitter:image"       content="<?= htmlspecialchars($ogImage) ?>">

    <!-- JSON-LD structured data -->
    <?php foreach ($schemas as $schemaObj): ?>
    <script type="application/ld+json"<?= app_csp_nonce_attr() ?>><?= json_encode($schemaObj, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
    <?php endforeach; ?>

    <link rel="preload" as="image" href="/img/frizon-logo.webp" type="image/webp" fetchpriority="high">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php if (!empty($useLeaflet)): ?>
    <link rel="stylesheet" href="/leaflet/leaflet.css">
    <?php endif; ?>
    <link rel="stylesheet" href="/css/main.css">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#3D4F5F">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
</head>
<body class="public-layout" data-ga-id="<?= htmlspecialchars($_ENV['GA_MEASUREMENT_ID'] ?? '') ?>">
    <header class="public-header" style="height:auto; padding:var(--space-4) var(--space-6); position:relative; z-index:500;">
        <div class="public-header__inner" style="flex-direction:row; align-items:center; justify-content:center; gap:var(--space-6); max-width:var(--content-max-width); margin:0 auto; width:100%;">
            <a href="/" class="public-header__link" style="font-weight:var(--weight-semibold);">Platser</a>
            <a href="/shop" class="public-header__link <?= str_starts_with($reqPath ?? '/', '/shop') ? 'public-header__link--active' : '' ?>" style="font-weight:var(--weight-semibold);">Shop</a>
            <a href="/" style="text-decoration:none; flex-shrink:0;">
                <picture>
                    <source srcset="/img/frizon-logo.webp" type="image/webp">
                    <img src="/img/frizon-logo.png" alt="Frizon of Sweden" width="64" height="64" fetchpriority="high" style="width:64px; height:64px; border-radius:50%; display:block;">
                </picture>
            </a>
            <a href="/topplista" class="public-header__link" style="font-weight:var(--weight-semibold);">Topplista</a>
            <a href="/samarbeta" class="public-header__link <?= str_starts_with($reqPath ?? '/', '/samarbeta') ? 'public-header__link--active' : '' ?>" style="font-weight:var(--weight-semibold);">Samarbeta</a>
        </div>
    </header>

    <main class="public-main">
        <?= $content ?>
    </main>

    <footer style="background:var(--color-brand-dark); text-align:center; padding:var(--space-8) var(--space-4) var(--space-6);">
        <p style="color:rgba(255,255,255,0.85); font-size:var(--text-sm); margin-bottom:var(--space-3);">Frizon of Sweden — Resedagbok med Frizze</p>
        <p style="font-size:var(--text-xs); margin-bottom:var(--space-3);">
            <a href="/topplista" style="color:rgba(255,255,255,0.8); text-decoration:underline;">Topplista</a>
            <span style="color:rgba(255,255,255,0.4);"> &middot; </span>
            <a href="/shop" style="color:rgba(255,255,255,0.8); text-decoration:underline;">Shop</a>
            <span style="color:rgba(255,255,255,0.4);"> &middot; </span>
            <a href="/integritetspolicy" style="color:rgba(255,255,255,0.8); text-decoration:underline;">Integritetspolicy</a>
            <span style="color:rgba(255,255,255,0.4);"> &middot; </span>
            <a href="/cookiepolicy" style="color:rgba(255,255,255,0.8); text-decoration:underline;">Cookiepolicy</a>
            <span style="color:rgba(255,255,255,0.4);"> &middot; </span>
            <a href="/samarbeta" style="color:rgba(255,255,255,0.8); text-decoration:underline;">Samarbeta</a>
            <span style="color:rgba(255,255,255,0.4);"> &middot; </span>
            <a href="/adm" style="color:rgba(255,255,255,0.65); text-decoration:underline;">Admin</a>
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
                <button id="accept-cookies-btn" style="background:var(--color-accent); color:white; border:none; padding:var(--space-2) var(--space-4); border-radius:var(--radius-md); cursor:pointer; font-size:var(--text-sm); font-weight:var(--weight-semibold); min-height:44px;">Godkänn</button>
                <button id="decline-cookies-btn" style="background:transparent; color:rgba(255,255,255,0.7); border:1px solid rgba(255,255,255,0.3); padding:var(--space-2) var(--space-4); border-radius:var(--radius-md); cursor:pointer; font-size:var(--text-sm); min-height:44px;">Avböj</button>
            </div>
        </div>
    </div>

    <?php if (!empty($useLeaflet)): ?>
    <script src="/leaflet/leaflet.js"></script>
    <?php endif; ?>
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

    document.addEventListener('click', function(event) {
        var link = event.target.closest('[data-affiliate-click]');
        if (!link || typeof window.gtag !== 'function') return;

        var payload = {
            product_slug: link.dataset.affiliateProductSlug || '',
            product_name: link.dataset.affiliateProductName || ''
        };

        if (link.dataset.affiliateSource) {
            payload.source = link.dataset.affiliateSource;
        }

        window.gtag('event', 'affiliate_click', payload);
    });

    // Non-blocking font load (avoids render-blocking Google Fonts request)
    (function() {
        var fl = document.createElement('link');
        fl.rel = 'stylesheet';
        fl.href = 'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap';
        document.head.appendChild(fl);
    })();
    if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js');
    </script>
</body>
</html>
