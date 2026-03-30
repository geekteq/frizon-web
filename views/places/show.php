<div class="page-header mb-4">
    <a href="/platser" class="btn-ghost btn--sm">&larr; Platser</a>
</div>

<div class="place-detail">
    <h1><?= htmlspecialchars($p['name']) ?></h1>

    <div class="place-detail__meta flex gap-3 mb-4 text-sm text-muted">
        <span class="place-card__type-badge place-card__type-badge--<?= htmlspecialchars($p['place_type']) ?>">
            <?php
            $types = ['breakfast'=>'Frukost','lunch'=>'Lunch','dinner'=>'Middag','fika'=>'Fika','sight'=>'Sevärdhet','shopping'=>'Shopping','stellplatz'=>'Ställplats','wild_camping'=>'Vildcamping','camping'=>'Camping'];
            echo $types[$p['place_type']] ?? $p['place_type'];
            ?>
        </span>
        <?php if ($p['country_code']): ?>
            <span><?= htmlspecialchars($p['country_code']) ?></span>
        <?php endif; ?>
    </div>

    <div id="place-map" style="width:100%; height:180px; border-radius:var(--radius-lg); margin-bottom:var(--space-4);"
         data-lat="<?= htmlspecialchars((string) $p['lat']) ?>"
         data-lng="<?= htmlspecialchars((string) $p['lng']) ?>">
    </div>

    <div class="place-detail__coords text-sm text-muted mb-4">
        <?= htmlspecialchars((string) $p['lat']) ?>, <?= htmlspecialchars((string) $p['lng']) ?>
        <?php if ($p['address_text']): ?>
            <br><?= htmlspecialchars($p['address_text']) ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($tags)): ?>
        <div class="place-detail__tags mb-4">
            <?php foreach ($tags as $tag): ?>
                <span class="tag"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="place-detail__actions flex gap-3 mb-6">
        <a href="/platser/<?= htmlspecialchars($p['slug']) ?>/besok/nytt" class="btn btn-primary">+ Nytt besök</a>
        <a href="/platser/<?= htmlspecialchars($p['slug']) ?>/redigera" class="btn btn-secondary">Redigera</a>
    </div>

    <h3 class="mb-4">Besök (<?= count($visits) ?>)</h3>

    <?php if (empty($visits)): ?>
        <p class="text-muted">Inga besök ännu.</p>
    <?php else: ?>
        <?php foreach ($visits as $visit): ?>
            <div class="visit-card mb-3">
                <div class="flex-between">
                    <span class="visit-card__date"><?= htmlspecialchars($visit['visited_at']) ?></span>
                    <?php if ($visit['total_rating_cached']): ?>
                        <span class="visit-card__rating">&#9733; <?= number_format((float) $visit['total_rating_cached'], 1) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($visit['raw_note']): ?>
                    <p class="visit-card__note text-sm mt-2"><?= nl2br(htmlspecialchars(mb_strimwidth($visit['raw_note'], 0, 200, '...'))) ?></p>
                <?php endif; ?>
                <a href="/besok/<?= $visit['id'] ?>" class="text-sm" style="color:var(--color-accent);">Visa besök &rarr;</a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="/js/map.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('place-map');
    if (el) {
        initStaticMap(el, parseFloat(el.dataset.lat), parseFloat(el.dataset.lng));
    }
});
</script>
