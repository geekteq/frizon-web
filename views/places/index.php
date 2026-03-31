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
            <?php include dirname(__DIR__) . '/partials/place-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
