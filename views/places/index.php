<div class="page-header flex-between mb-4">
    <h2>Platser</h2>
    <a href="/platser/ny" class="btn btn-primary btn--sm">+ Ny plats</a>
</div>

<div class="filter-bar mb-4">
    <form method="GET" action="/platser" class="flex gap-2" style="flex-wrap:wrap;">
        <input type="text" name="q" class="form-input" placeholder="Sök platser..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>" style="max-width:200px; min-height:40px;">
        <select name="type" class="form-select" style="max-width:160px; min-height:40px;" onchange="this.form.submit()">
            <option value="">Alla typer</option>
            <?php
            $types = ['breakfast'=>'Frukost','lunch'=>'Lunch','dinner'=>'Middag','fika'=>'Fika','sight'=>'Sevärdhet','shopping'=>'Shopping','stellplatz'=>'Ställplats','wild_camping'=>'Vildcamping','camping'=>'Camping'];
            foreach ($types as $val => $label): ?>
                <option value="<?= $val ?>" <?= ($filters['place_type'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-ghost btn--sm">Filtrera</button>
    </form>
</div>

<?php if (empty($places)): ?>
    <div class="empty-state text-center" style="padding:var(--space-12) 0;">
        <p class="text-muted">Inga platser ännu.</p>
        <a href="/platser/ny" class="btn btn-primary mt-4">Lägg till din första plats</a>
    </div>
<?php else: ?>
    <div class="place-list">
        <?php foreach ($places as $place): ?>
            <?php include dirname(__DIR__) . '/partials/place-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
