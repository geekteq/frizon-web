<?php
$placeTypes = [
    'breakfast'=>'Frukost','lunch'=>'Lunch','dinner'=>'Middag','fika'=>'Fika',
    'sight'=>'Sevärdhet','shopping'=>'Shopping','stellplatz'=>'Ställplats',
    'wild_camping'=>'Fricamping','camping'=>'Camping',
];
?>

<!-- Welcome section -->
<div style="max-width:680px; margin:0 auto; padding:var(--space-8) var(--space-4) var(--space-6); text-align:center;">
    <h1 style="font-size:var(--text-2xl); font-weight:var(--weight-bold); margin-bottom:var(--space-3); color:var(--color-text);">Välkommen till Frizon of Sweden</h1>
    <p style="font-size:var(--text-base); line-height:var(--leading-relaxed); color:var(--color-text-muted); margin-bottom:var(--space-4);">
        Vi är Mattias och Ulrica — och Frizze, vår Adria Twin som tar oss ut på vägarna.
        Här delar vi platser vi besökt, vad vi tyckte och om de är värda ett återbesök.
        Allt sett ur en husbilsresandes perspektiv.
    </p>
    <div style="display:flex; gap:10px; justify-content:center; margin-top:var(--space-2);">
        <a href="https://www.instagram.com/frizon_of_sweden" target="_blank" rel="noopener noreferrer"
           aria-label="Följ oss på Instagram"
           style="display:flex; flex-direction:column; align-items:center; gap:4px; padding:10px 14px; border-radius:12px; text-decoration:none; color:#fff; font-size:11px; font-weight:600; background:radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%); box-shadow:0 3px 10px rgba(0,0,0,0.18);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <rect x="2" y="2" width="20" height="20" rx="5" ry="5" stroke="white" stroke-width="1.8"/>
                <circle cx="12" cy="12" r="4.5" stroke="white" stroke-width="1.8"/>
                <circle cx="17.5" cy="6.5" r="1.1" fill="white"/>
            </svg>
            Instagram
        </a>
        <a href="https://www.facebook.com/frizonofsweden" target="_blank" rel="noopener noreferrer"
           aria-label="Följ oss på Facebook"
           style="display:flex; flex-direction:column; align-items:center; gap:4px; padding:10px 14px; border-radius:12px; text-decoration:none; color:#fff; font-size:11px; font-weight:600; background:#0B5CAD; box-shadow:0 3px 10px rgba(0,0,0,0.18);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" stroke="white" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Facebook
        </a>
        <a href="https://www.youtube.com/@frizon_of_sweden" target="_blank" rel="noopener noreferrer"
           aria-label="Titta på oss på YouTube"
           style="display:flex; flex-direction:column; align-items:center; gap:4px; padding:10px 14px; border-radius:12px; text-decoration:none; color:#fff; font-size:11px; font-weight:600; background:#B00000; box-shadow:0 3px 10px rgba(0,0,0,0.18);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.95 1.96C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z" stroke="white" stroke-width="1.8"/>
                <polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" stroke="white" stroke-width="1.6" stroke-linejoin="round" fill="white"/>
            </svg>
            YouTube
        </a>
    </div>
</div>

<?php if (!empty($places)): ?>
<section class="public-section-heading" aria-labelledby="places-heading">
    <h2 id="places-heading">Platser vi besökt</h2>
    <p>Våra egna stopp, recensioner och favoriter från vägarna.</p>
</section>

<!-- Map -->
<div id="public-map" class="public-map"
     data-places='<?= htmlspecialchars(json_encode(array_map(fn($p) => [
         'lat' => (float)$p['lat'],
         'lng' => (float)$p['lng'],
         'name' => $p['name'],
         'slug' => $p['slug'],
         'type' => $p['place_type'],
         'rating' => $p['avg_rating'] ? round((float)$p['avg_rating'], 1) : null,
     ], $places))) ?>'>
</div>

<!-- Filters -->
<div class="public-filters">
    <div class="filter-bar">
        <a href="/" class="filter-bar__chip <?= !$filterType && !$filterCountry ? 'is-active' : '' ?>">Alla</a>
        <?php foreach ($allTypes as $type): ?>
            <a href="/?type=<?= $type ?>" class="filter-bar__chip <?= $filterType === $type ? 'is-active' : '' ?>"><?= $placeTypes[$type] ?? $type ?></a>
        <?php endforeach; ?>
        <?php foreach ($allPublic as $cc): ?>
            <a href="/?country=<?= $cc ?>" class="filter-bar__chip <?= $filterCountry === $cc ? 'is-active' : '' ?>"><?= strtoupper($cc) ?></a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Place cards -->
<div class="public-places">
    <?php if (empty($places)): ?>
        <p class="text-muted text-center" style="padding:var(--space-6) 0; font-style:italic;">Vi har inte publicerat några platser ännu — men det kommer snart!</p>
    <?php else: ?>
        <div class="place-grid">
            <?php foreach ($places as $p): ?>
                <a href="/platser/<?= htmlspecialchars($p['slug']) ?>" class="pub-place-card">
                    <?php if ($p['is_featured']): ?>
                        <span class="pub-place-card__featured">Utvald</span>
                    <?php endif; ?>
                    <div class="pub-place-card__body">
                        <h3 class="pub-place-card__name"><?= htmlspecialchars($p['name']) ?></h3>
                        <div class="pub-place-card__meta">
                            <span><?= $placeTypes[$p['place_type']] ?? $p['place_type'] ?></span>
                            <?php if ($p['country_code']): ?>
                                · <span><?= strtoupper($p['country_code']) ?></span>
                            <?php endif; ?>
                            <?php if ($p['avg_rating']): ?>
                                · <span>&#9733; <?= number_format((float)$p['avg_rating'], 1) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($p['default_public_text']): ?>
                            <p class="pub-place-card__desc"><?= htmlspecialchars(mb_strimwidth($p['default_public_text'], 0, 120, '...')) ?></p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($upcomingTrips)): ?>
<section style="max-width:var(--content-max-width); margin:0 auto var(--space-8); padding:0 var(--space-4);">
    <h2 style="font-size:var(--text-lg); font-weight:var(--weight-semibold); margin-bottom:var(--space-4); color:var(--color-text);">
        Kommande resor
    </h2>
    <div style="display:flex; flex-direction:column; gap:var(--space-3);">
        <?php foreach ($upcomingTrips as $t): ?>
        <div style="padding:var(--space-4); border:1px solid var(--color-border); border-radius:var(--radius-md); background:var(--color-bg);">
            <?php if ($t['start_date']): ?>
                <div style="font-size:var(--text-xs); color:var(--color-text-muted); text-transform:uppercase; letter-spacing:0.06em; margin-bottom:var(--space-1);">
                    <?php
                    $svMonths = ['Januari','Februari','Mars','April','Maj','Juni',
                                 'Juli','Augusti','September','Oktober','November','December'];
                    $ts = strtotime($t['start_date']);
                    echo $svMonths[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
                    ?>
                </div>
            <?php endif; ?>
            <?php if ($t['teaser_text']): ?>
                <p style="font-size:var(--text-sm); color:var(--color-text); margin:0; line-height:var(--leading-relaxed);">
                    <?= htmlspecialchars($t['teaser_text']) ?>
                </p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($shopTeaser)): ?>
<section style="max-width:var(--content-max-width); margin:var(--space-8) auto var(--space-6); padding:0 var(--space-4);">
    <div style="display:flex; align-items:baseline; justify-content:space-between; margin-bottom:var(--space-4);">
        <h2 style="font-size:var(--text-xl); font-weight:var(--weight-bold); color:var(--color-text);">Nytt i shoppen</h2>
        <a href="/shop" style="font-size:var(--text-sm); color:var(--color-accent); text-decoration:none; font-weight:var(--weight-medium); display:inline-flex; align-items:center; min-height:44px; padding:0 var(--space-2);">Se alla →</a>
    </div>
    <div class="place-grid">
        <?php foreach ($shopTeaser as $p): ?>
            <?php include dirname(__DIR__) . '/partials/shop-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<script<?= app_csp_nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function() {
    var mapEl = document.getElementById('public-map');
    if (!mapEl) return;

    var places = JSON.parse(mapEl.dataset.places || '[]');
    if (places.length === 0) return;

    var hasInitialized = false;
    function initMap() {
        if (hasInitialized) return;
        hasInitialized = true;

        window.loadFrizonMarkerCluster().then(function() {
            var map = L.map(mapEl);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 19,
                detectRetina: true
            }).addTo(map);

            var markerLayer = L.markerClusterGroup({
                maxClusterRadius: 48,
                showCoverageOnHover: false,
                spiderfyOnMaxZoom: true
            });
            var bounds = L.latLngBounds();
            places.forEach(function(p) {
                var marker = L.marker([p.lat, p.lng]);
                var popupWrapper = document.createElement('div');
                var title = document.createElement('strong');
                var link = document.createElement('a');
                link.href = '/platser/' + encodeURIComponent(p.slug);
                link.textContent = p.name;
                title.appendChild(link);
                popupWrapper.appendChild(title);

                if (p.rating) {
                    popupWrapper.appendChild(document.createElement('br'));
                    popupWrapper.appendChild(document.createTextNode('\u2605 ' + p.rating));
                }

                marker.bindPopup(popupWrapper);
                markerLayer.addLayer(marker);
                bounds.extend([p.lat, p.lng]);
            });

            map.addLayer(markerLayer);
            map.fitBounds(bounds, { padding: [30, 30], maxZoom: 10 });
        });
    }

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            if (!entries.some(function(entry) { return entry.isIntersecting; })) return;
            observer.disconnect();
            initMap();
        }, { rootMargin: '300px 0px' });
        observer.observe(mapEl);
        return;
    }

    if ('requestIdleCallback' in window) {
        requestIdleCallback(initMap, { timeout: 2000 });
    } else {
        setTimeout(initMap, 500);
    }
});
</script>
