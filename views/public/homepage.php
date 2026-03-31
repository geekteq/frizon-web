<?php
$placeTypes = [
    'breakfast'=>'Frukost','lunch'=>'Lunch','dinner'=>'Middag','fika'=>'Fika',
    'sight'=>'Sevärdhet','shopping'=>'Shopping','stellplatz'=>'Ställplats',
    'wild_camping'=>'Vildcamping','camping'=>'Camping',
];
?>

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
        <a href="/pub" class="filter-bar__chip <?= !$filterType && !$filterCountry ? 'is-active' : '' ?>">Alla</a>
        <?php foreach ($allTypes as $type): ?>
            <a href="/pub?type=<?= $type ?>" class="filter-bar__chip <?= $filterType === $type ? 'is-active' : '' ?>"><?= $placeTypes[$type] ?? $type ?></a>
        <?php endforeach; ?>
        <?php foreach ($allPublic as $cc): ?>
            <a href="/pub?country=<?= $cc ?>" class="filter-bar__chip <?= $filterCountry === $cc ? 'is-active' : '' ?>"><?= strtoupper($cc) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Place cards -->
<div class="public-places">
    <?php if (empty($places)): ?>
        <p class="text-muted text-center" style="padding:var(--space-8) 0;">Inga publika platser ännu.</p>
    <?php else: ?>
        <div class="place-grid">
            <?php foreach ($places as $p): ?>
                <a href="/pub/platser/<?= htmlspecialchars($p['slug']) ?>" class="pub-place-card">
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

<script>
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
        var popup = '<strong><a href="/pub/platser/' + p.slug + '">' + p.name + '</a></strong>';
        if (p.rating) popup += '<br>&#9733; ' + p.rating;
        marker.bindPopup(popup);
        bounds.extend([p.lat, p.lng]);
    });

    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 10 });
});
</script>
