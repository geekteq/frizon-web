<div class="page-header mb-4">
    <a href="/adm/resor" class="btn-ghost btn--sm">&larr; Resor</a>
</div>

<div class="trip-detail">
    <h1 class="mb-2"><?= htmlspecialchars($trip['title']) ?></h1>

    <div class="trip-detail__meta text-sm text-muted mb-4">
        <?php
        $statusLabels = ['planned'=>'Planerad','ongoing'=>'Pågående','finished'=>'Avslutad'];
        ?>
        <span class="trip-card__status trip-card__status--<?= $trip['status'] ?>"><?= $statusLabels[$trip['status']] ?></span>
        <?php if ($trip['start_date']): ?>
            · <?= htmlspecialchars($trip['start_date']) ?>
            <?php if ($trip['end_date']): ?> → <?= htmlspecialchars($trip['end_date']) ?><?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if ($trip['intro_text']): ?>
        <p class="mb-4"><?= nl2br(htmlspecialchars($trip['intro_text'])) ?></p>
    <?php endif; ?>

    <!-- Route map -->
    <?php if (!empty($stops) && count($stops) >= 2): ?>
        <div id="trip-map" class="trip-detail__map mb-4"
             data-stops='<?= htmlspecialchars(json_encode(array_map(fn($s) => ['lat' => (float)$s['lat'], 'lng' => (float)$s['lng'], 'name' => $s['place_name']], $stops))) ?>'
             data-segments='<?= htmlspecialchars(json_encode(array_map(fn($seg) => [
                 'from_stop_id' => (int)$seg['from_stop_id'],
                 'to_stop_id' => (int)$seg['to_stop_id'],
                 'geometry' => $seg['geometry'],
             ], $segments))) ?>'>
        </div>
    <?php endif; ?>

    <!-- Summary -->
    <?php if ($summary['stop_count'] > 0): ?>
        <div class="trip-summary mb-4">
            <span><?= $summary['stop_count'] ?> platser</span>
            <?php if ($summary['total_km'] > 0): ?>
                <?php
                $totalMin = (int) $summary['total_eta_95'];
                $h = intdiv($totalMin, 60);
                $m = $totalMin % 60;
                $etaStr = $h > 0 ? "{$h} tim {$m} min" : "{$m} min";
                ?>
                · <span><?= number_format((float) $summary['total_km'], 0) ?> km</span>
                · <span><?= $etaStr ?> (95 km/h)</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Stops -->
    <div class="trip-detail__section mb-6">
        <div class="flex-between mb-4">
            <h3>Platser (<?= count($stops) ?>)</h3>
        </div>

        <?php if (empty($stops)): ?>
            <p class="text-muted text-sm">Inga platser ännu.</p>
        <?php else: ?>
            <div class="stop-list" id="stop-list">
                <?php foreach ($stops as $i => $stop): ?>
                    <?php include dirname(__DIR__) . '/partials/stop-card.php'; ?>

                    <?php
                    // Show segment between stops
                    if ($i < count($stops) - 1) {
                        foreach ($segments as $seg) {
                            if ((int)$seg['from_stop_id'] === (int)$stop['id']) {
                                echo '<div class="route-segment">';
                                echo '<span class="route-segment__line"></span>';
                                echo '<span class="route-segment__pill">';
                                echo number_format((float)$seg['distance_km'], 0) . ' km · ';
                                echo $seg['eta_95_minutes'] . ' min';
                                echo '</span>';
                                echo '</div>';
                                break;
                            }
                        }
                    }
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Add stop form -->
        <form method="POST" action="/adm/resor/<?= htmlspecialchars($trip['slug']) ?>/hallplatser" class="mt-4">
            <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
            <div class="flex gap-2">
                <select name="place_id" class="form-select" required style="flex:1;">
                    <option value="">Välj plats...</option>
                    <?php foreach ($allPlaces as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= $p['country_code'] ?? '?' ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn--sm">+ Lägg till</button>
            </div>
        </form>
    </div>

    <!-- Actions -->
    <div class="trip-detail__actions flex gap-3 mb-4">
        <?php if (count($stops) >= 2): ?>
            <form method="POST" action="/adm/resor/<?= htmlspecialchars($trip['slug']) ?>/berakna-rutt" style="display:inline;">
                <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
                <button type="submit" class="btn btn-secondary btn--sm">Beräkna rutt</button>
            </form>
        <?php endif; ?>
        <a href="/adm/resor/<?= htmlspecialchars($trip['slug']) ?>/redigera" class="btn btn-ghost btn--sm">Redigera resa</a>
    </div>

    <!-- Export buttons -->
    <?php if (count($stops) >= 1): ?>
    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:var(--space-2); margin-top:var(--space-3);">
        <span class="text-sm text-muted">Exportera:</span>
        <a href="/adm/resor/<?= htmlspecialchars($trip['slug']) ?>/export/gpx" class="btn btn-ghost btn--sm">GPX</a>
        <a href="/adm/resor/<?= htmlspecialchars($trip['slug']) ?>/export/csv" class="btn btn-ghost btn--sm">CSV</a>
        <a href="/adm/resor/<?= htmlspecialchars($trip['slug']) ?>/export/json" class="btn btn-ghost btn--sm">JSON</a>
        <a href="/adm/resor/<?= htmlspecialchars($trip['slug']) ?>/export/google-maps" class="btn btn-ghost btn--sm">Maps</a>
    </div>
    <?php endif; ?>
</div>

<script src="/js/trips.js"></script>
<script<?= app_csp_nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function() {
    var mapEl = document.getElementById('trip-map');
    if (mapEl && mapEl.dataset.stops) {
        var segments = mapEl.dataset.segments ? JSON.parse(mapEl.dataset.segments) : [];
        initTripMap(mapEl, JSON.parse(mapEl.dataset.stops), segments);
    }
});
</script>
