<div class="page-header mb-4">
    <a href="/platser/<?= htmlspecialchars($visit['place_slug']) ?>" class="btn-ghost btn--sm">&larr; <?= htmlspecialchars($visit['place_name']) ?></a>
</div>

<div class="visit-detail">
    <h2>Besök <?= htmlspecialchars($visit['visited_at']) ?></h2>
    <p class="text-sm text-muted mb-4"><?= htmlspecialchars($visit['place_name']) ?></p>

    <?php if ($visit['raw_note']): ?>
        <div class="visit-detail__note mb-4" style="background:#FDFCF8; padding:var(--space-4); border-radius:var(--radius-md); border:1px solid var(--color-warm-dark);">
            <?= nl2br(htmlspecialchars($visit['raw_note'])) ?>
        </div>
    <?php endif; ?>

    <?php if ($visit['plus_notes'] || $visit['minus_notes'] || $visit['tips_notes']): ?>
        <div class="visit-detail__fields mb-4">
            <?php if ($visit['plus_notes']): ?>
                <div class="mb-2"><strong class="text-sm">Plus:</strong> <?= nl2br(htmlspecialchars($visit['plus_notes'])) ?></div>
            <?php endif; ?>
            <?php if ($visit['minus_notes']): ?>
                <div class="mb-2"><strong class="text-sm">Minus:</strong> <?= nl2br(htmlspecialchars($visit['minus_notes'])) ?></div>
            <?php endif; ?>
            <?php if ($visit['tips_notes']): ?>
                <div class="mb-2"><strong class="text-sm">Tips:</strong> <?= nl2br(htmlspecialchars($visit['tips_notes'])) ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($visit['price_level'] || $visit['would_return'] || $visit['suitable_for']): ?>
        <div class="visit-detail__meta mb-4 text-sm">
            <?php
            $priceLevels = ['free' => 'Gratis', 'low' => '€', 'medium' => '€€', 'high' => '€€€'];
            $wouldReturnLabels = ['yes' => 'Ja', 'maybe' => 'Kanske', 'no' => 'Nej'];
            ?>
            <?php if ($visit['price_level']): ?>
                <div>Pris: <?= htmlspecialchars($priceLevels[$visit['price_level']] ?? $visit['price_level']) ?></div>
            <?php endif; ?>
            <?php if ($visit['would_return']): ?>
                <div>Återvända: <?= htmlspecialchars($wouldReturnLabels[$visit['would_return']] ?? $visit['would_return']) ?></div>
            <?php endif; ?>
            <?php if ($visit['suitable_for']): ?>
                <div>Passar för: <?= htmlspecialchars($visit['suitable_for']) ?></div>
            <?php endif; ?>
            <?php if ($visit['things_to_note']): ?>
                <div>Att notera: <?= htmlspecialchars($visit['things_to_note']) ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($ratings): ?>
        <?php include dirname(__DIR__) . '/partials/rating-display.php'; ?>
    <?php endif; ?>

    <?php if (!empty($images)): ?>
        <div class="visit-detail__gallery mb-4" style="display:flex; flex-wrap:wrap; gap:var(--space-2);">
            <?php foreach ($images as $img): ?>
                <img src="/uploads/cards/<?= htmlspecialchars($img['filename']) ?>"
                     alt="<?= htmlspecialchars($img['alt_text'] ?? '') ?>"
                     style="width:120px; height:90px; object-fit:cover; border-radius:var(--radius-md);">
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="flex gap-3 mt-6">
        <a href="/besok/<?= $visit['id'] ?>/redigera" class="btn btn-secondary btn--sm">Redigera</a>
        <form method="POST" action="/besok/<?= $visit['id'] ?>" style="display:inline;">
            <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="btn btn-danger btn--sm" onclick="return confirm('Ta bort besöket?')">Ta bort</button>
        </form>
    </div>
</div>
