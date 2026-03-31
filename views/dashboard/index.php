<div class="dashboard">
    <h2 class="mb-2">Hej <?= htmlspecialchars(Auth::userName() ?? '') ?>!</h2>
    <p class="text-muted text-sm mb-6" style="font-family:var(--font-script); font-size:1.1rem;">Frizon of Sweden</p>

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
            <a href="/platser/ny" class="btn btn-primary">Spara din första plats</a>
        </div>
    <?php else: ?>
        <?php foreach ($recentVisits as $visit): ?>
            <div class="visit-card mb-3">
                <div class="flex-between">
                    <a href="/platser/<?= htmlspecialchars($visit['place_slug']) ?>" style="font-weight:var(--weight-semibold); color:var(--color-brand-dark);">
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
