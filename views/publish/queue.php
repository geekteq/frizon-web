<div class="flex-between mb-4">
    <h2>Publicera</h2>
    <a href="/pub" class="btn btn-ghost btn--sm" target="_blank">Visa publik sida</a>
</div>

<!-- Published -->
<div class="mb-6">
    <h3 class="mb-3">Publicerade (<?= count($published) ?>)</h3>
    <?php if (empty($published)): ?>
        <p class="text-muted text-sm">Inga publicerade platser ännu.</p>
    <?php else: ?>
        <?php foreach ($published as $p): ?>
            <div class="publish-card publish-card--published">
                <div class="publish-card__body">
                    <a href="/platser/<?= htmlspecialchars($p['slug']) ?>" class="publish-card__name"><?= htmlspecialchars($p['name']) ?></a>
                    <div class="publish-card__meta text-sm text-muted">
                        <?= $p['visit_count'] ?> besök
                        <?php if ($p['avg_rating']): ?>
                            · &#9733; <?= number_format((float)$p['avg_rating'], 1) ?>
                        <?php endif; ?>
                        <?php if ($p['is_toplisted']): ?>
                            · <span class="text-accent">Topplistan</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="publish-card__actions flex gap-2">
                    <form method="POST" action="/publicera/<?= htmlspecialchars($p['slug']) ?>/topplista">
                        <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
                        <button type="submit" class="btn btn-ghost btn--sm"><?= $p['is_toplisted'] ? 'Ta bort från topp' : '+ Topplista' ?></button>
                    </form>
                    <form method="POST" action="/publicera/<?= htmlspecialchars($p['slug']) ?>/avpublicera">
                        <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
                        <button type="submit" class="btn btn-danger btn--sm" onclick="return confirm('Avpublicera?')">Avpublicera</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Unpublished -->
<div class="mb-6">
    <h3 class="mb-3">Ej publicerade (<?= count($unpublished) ?>)</h3>
    <?php if (empty($unpublished)): ?>
        <p class="text-muted text-sm">Alla platser är publicerade.</p>
    <?php else: ?>
        <?php foreach ($unpublished as $p): ?>
            <div class="publish-card">
                <div class="publish-card__body">
                    <a href="/platser/<?= htmlspecialchars($p['slug']) ?>" class="publish-card__name"><?= htmlspecialchars($p['name']) ?></a>
                    <div class="publish-card__meta text-sm text-muted">
                        <?= $p['visit_count'] ?> besök
                        <?php if ($p['avg_rating']): ?>
                            · &#9733; <?= number_format((float)$p['avg_rating'], 1) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="publish-card__actions">
                    <form method="POST" action="/publicera/<?= htmlspecialchars($p['slug']) ?>/godkann">
                        <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
                        <button type="submit" class="btn btn-primary btn--sm">Publicera</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
