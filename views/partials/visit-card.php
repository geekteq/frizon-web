<?php
$wouldReturnLabels = ['yes' => 'Ja', 'maybe' => 'Kanske', 'no' => 'Nej'];
$priceLevels = ['free' => 'Gratis', 'low' => '€', 'medium' => '€€', 'high' => '€€€'];
$rating = $visit['total_rating_cached'] ?? null;
$date = $visit['visited_at'] ?? '';
?>
<a href="/besok/<?= (int) $visit['id'] ?>" class="visit-card">
    <div class="visit-card__date text-sm text-muted"><?= htmlspecialchars($date) ?></div>
    <div class="visit-card__body">
        <?php if (!empty($visit['raw_note'])): ?>
            <div class="visit-card__note text-sm"><?= htmlspecialchars(mb_substr($visit['raw_note'], 0, 100)) ?><?= mb_strlen($visit['raw_note']) > 100 ? '…' : '' ?></div>
        <?php endif; ?>
        <div class="visit-card__meta text-xs text-muted mt-1">
            <?php if (!empty($visit['price_level'])): ?>
                <span><?= htmlspecialchars($priceLevels[$visit['price_level']] ?? $visit['price_level']) ?></span>
            <?php endif; ?>
            <?php if (!empty($visit['would_return'])): ?>
                <span>· Återvända: <?= htmlspecialchars($wouldReturnLabels[$visit['would_return']] ?? $visit['would_return']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($rating): ?>
        <div class="visit-card__rating">&#9733; <?= number_format((float) $rating, 1) ?></div>
    <?php endif; ?>
</a>
