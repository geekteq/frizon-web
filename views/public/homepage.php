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
    <p style="font-size:var(--text-sm); color:var(--color-text-muted);">
        Följ oss på <a href="https://www.instagram.com/frizon_of_sweden" target="_blank" rel="noopener" style="color:var(--color-accent); text-decoration:underline;">Instagram @frizon_of_sweden</a>
    </p>
</div>

<?php if (!empty($places)): ?>
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

<script<?= app_csp_nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function() {
    var mapEl = document.getElementById('public-map');
    if (!mapEl) return;

    var places = JSON.parse(mapEl.dataset.places || '[]');
    if (places.length === 0) return;

    var map = L.map(mapEl);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    var bounds = L.latLngBounds();
    places.forEach(function(p) {
        var marker = L.marker([p.lat, p.lng]).addTo(map);
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
        bounds.extend([p.lat, p.lng]);
    });

    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 10 });
});
</script>
