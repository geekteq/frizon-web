<div class="page-header mb-4">
    <a href="/adm/platser" class="btn-ghost btn--sm">&larr; Platser</a>
</div>

<div class="place-detail">
    <h1><?= htmlspecialchars($p['name']) ?></h1>

    <div class="place-detail__meta flex gap-3 mb-4 text-sm text-muted">
        <span class="place-card__type-badge place-card__type-badge--<?= htmlspecialchars($p['place_type']) ?>">
            <?php
            $types = ['breakfast'=>'Frukost','lunch'=>'Lunch','dinner'=>'Middag','fika'=>'Fika','sight'=>'Sevärdhet','shopping'=>'Shopping','stellplatz'=>'Ställplats','wild_camping'=>'Fricamping','camping'=>'Camping'];
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
        <a href="/adm/platser/<?= htmlspecialchars($p['slug']) ?>/besok/nytt" class="btn btn-primary">+ Nytt besök</a>
        <a href="/adm/platser/<?= htmlspecialchars($p['slug']) ?>/redigera" class="btn btn-secondary">Redigera</a>
    </div>

    <h3 class="mb-4">Besök (<?= count($visits) ?>)</h3>

    <?php if (empty($visits)): ?>
        <p class="text-muted">Inga besök ännu.</p>
    <?php else: ?>
        <?php foreach ($visits as $visit): ?>
            <a href="/adm/besok/<?= $visit['id'] ?>" class="visit-card mb-3" style="display:block; text-decoration:none; color:inherit; border-left:3px solid <?= $visit['ready_for_publish'] ? 'var(--color-success)' : 'var(--color-border)' ?>;">
                <div class="flex-between">
                    <div>
                        <span class="visit-card__date"><?= htmlspecialchars($visit['visited_at']) ?></span>
                        <?php if ($visit['total_rating_cached']): ?>
                            <span class="visit-card__rating" style="margin-left:var(--space-2);">&#9733; <?= number_format((float) $visit['total_rating_cached'], 1) ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex; align-items:center; gap:var(--space-2);">
                        <span class="text-sm" style="color:<?= $visit['ready_for_publish'] ? 'var(--color-success)' : 'var(--color-text-muted)' ?>; font-weight:600;">
                            <?= $visit['ready_for_publish'] ? 'Pub' : 'Ej pub' ?>
                        </span>
                        <span style="color:var(--color-text-muted);">&rsaquo;</span>
                    </div>
                </div>
                <?php if ($visit['raw_note']): ?>
                    <p class="visit-card__note text-sm mt-2"><?= nl2br(htmlspecialchars(mb_strimwidth($visit['raw_note'], 0, 200, '...'))) ?></p>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="/js/map.js"></script>
<script<?= app_csp_nonce_attr() ?>>
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('place-map');
    if (el) {
        initStaticMap(el, parseFloat(el.dataset.lat), parseFloat(el.dataset.lng));
    }
});
</script>
