<div class="filter-bar mb-4">
    <form method="GET" action="/adm/platser" class="filter-bar__form">
        <input type="text" name="q" class="form-input filter-bar__search" placeholder="Sök platser..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
        <select name="type" class="form-select filter-bar__select" data-auto-submit="true">
            <option value="">Alla typer</option>
            <?php
            $types = ['breakfast'=>'Frukost','lunch'=>'Lunch','dinner'=>'Middag','fika'=>'Fika','sight'=>'Sevärdhet','shopping'=>'Shopping','stellplatz'=>'Ställplats','wild_camping'=>'Fricamping','camping'=>'Camping'];
            foreach ($types as $val => $label): ?>
                <option value="<?= $val ?>" <?= ($filters['place_type'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if (empty($places)): ?>
    <div class="empty-state">
        <p class="text-muted">Inga platser ännu.</p>
        <a href="/adm/platser/ny" class="btn btn-primary mt-4">Lägg till din första plats</a>
    </div>
<?php else: ?>
    <div class="place-list">
        <?php foreach ($places as $place): ?>
            <div style="display:flex; align-items:stretch; gap:var(--space-2); margin-bottom:var(--space-2);">
                <div style="flex:1; min-width:0;">
                    <?php include dirname(__DIR__) . '/partials/place-card.php'; ?>
                </div>
                <form method="POST" action="/adm/publicera/<?= htmlspecialchars($place['slug']) ?>/<?= $place['public_allowed'] ? 'avpublicera' : 'godkann' ?>"
                      style="display:flex; align-items:center; flex-shrink:0;">
                    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
                    <button type="submit" class="btn btn--sm <?= $place['public_allowed'] ? 'btn-ghost' : 'btn-secondary' ?>"
                            style="white-space:nowrap;" <?= $place['public_allowed'] ? 'data-confirm="Avpublicera platsen?"' : '' ?>>
                        <?= $place['public_allowed'] ? 'Avpublicera' : 'Publicera' ?>
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
