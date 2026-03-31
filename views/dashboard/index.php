<div class="dashboard">
    <h2 class="mb-2">Hej <?= htmlspecialchars(Auth::userName() ?? '') ?>!</h2>
    <p class="text-muted text-sm mb-4" style="font-family:var(--font-script); font-size:1.1rem;">Frizon of Sweden</p>

    <?php if (!empty($places)): ?>
    <div id="dashboard-map" style="width:100%; height:260px; border-radius:var(--radius-lg); margin-bottom:var(--space-6); background:var(--color-brand-muted);"
         data-places='<?= htmlspecialchars(json_encode(array_map(fn($p) => [
             'lat' => (float)$p['lat'],
             'lng' => (float)$p['lng'],
             'name' => $p['name'],
             'slug' => $p['slug'],
             'type' => $p['place_type'],
         ], $places))) ?>'></div>
    <?php endif; ?>

    <div class="dashboard-stats mb-6">
        <div class="stat-card">
            <div class="stat-card__number"><?= $stats['places'] ?></div>
            <div class="stat-card__label">Platser</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__number"><?= $stats['visits'] ?></div>
            <div class="stat-card__label">Besök</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__number"><?= $stats['countries'] ?></div>
            <div class="stat-card__label">Länder</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__number"><?= $stats['trips'] ?></div>
            <div class="stat-card__label">Resor</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__number"><?= $stats['lists'] ?></div>
            <div class="stat-card__label">Listor</div>
        </div>
    </div>

    <h3 class="mb-4">Senaste besök</h3>
    <?php if (empty($recentVisits)): ?>
        <div class="empty-state text-center" style="padding:var(--space-8) 0;">
            <p class="text-muted mb-4">Inga besök ännu.</p>
            <a href="/adm/platser/ny" class="btn btn-primary">Spara din första plats</a>
        </div>
    <?php else: ?>
        <?php foreach ($recentVisits as $visit): ?>
            <div class="visit-card mb-3">
                <div class="flex-between">
                    <a href="/adm/platser/<?= htmlspecialchars($visit['place_slug']) ?>" style="font-weight:var(--weight-semibold); color:var(--color-brand-dark);">
                        <?= htmlspecialchars($visit['place_name']) ?>
                    </a>
                    <span class="text-xs text-muted"><?= htmlspecialchars($visit['visited_at']) ?></span>
                </div>
                <?php if ($visit['total_rating_cached']): ?>
                    <span class="text-sm">&#9733; <?= number_format((float) $visit['total_rating_cached'], 1) ?></span>
                <?php endif; ?>
                <?php if ($visit['raw_note']): ?>
                    <p class="text-sm text-muted mt-1"><?= htmlspecialchars(mb_strimwidth($visit['raw_note'], 0, 100, '...')) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script<?= app_csp_nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function() {
    var mapEl = document.getElementById('dashboard-map');
    if (!mapEl) return;
    var places = JSON.parse(mapEl.dataset.places || '[]');
    if (places.length === 0) return;

    var map = L.map(mapEl);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap', maxZoom: 19
    }).addTo(map);

    var bounds = L.latLngBounds();
    places.forEach(function(p) {
        var marker = L.marker([p.lat, p.lng]).addTo(map);
        var popupWrapper = document.createElement('div');
        var title = document.createElement('strong');
        var link = document.createElement('a');
        link.href = '/adm/platser/' + encodeURIComponent(p.slug);
        link.textContent = p.name;
        title.appendChild(link);
        popupWrapper.appendChild(title);
        marker.bindPopup(popupWrapper);
        bounds.extend([p.lat, p.lng]);
    });
    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 12 });
});
</script>
