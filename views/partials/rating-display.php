<?php if ($ratings): ?>
<div class="rating-display">
    <?php
    $ratingLabels = [
        'location_rating'     => 'Läge',
        'calmness_rating'     => 'Lugn',
        'service_rating'      => 'Service',
        'value_rating'        => 'Värde',
        'return_value_rating' => 'Återkomst',
    ];
    foreach ($ratingLabels as $field => $label):
        $val = $ratings[$field] ?? null;
        if ($val === null) continue;
    ?>
        <div class="rating-display__row flex-between text-sm mb-1">
            <span><?= $label ?></span>
            <span class="rating-dots">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="rating-dot--display <?= $i <= (int)$val ? 'rating-dot--filled' : '' ?>"></span>
                <?php endfor; ?>
                <span class="text-xs text-muted" style="margin-left:4px;"><?= (int)$val ?></span>
            </span>
        </div>
    <?php endforeach; ?>

    <?php if (!empty($ratings['total_rating_cached'])): ?>
        <div class="rating-display__total flex-between mt-2" style="border-top:1px solid var(--color-border); padding-top:var(--space-2);">
            <strong>Totalt</strong>
            <span>&#9733; <?= number_format((float) $ratings['total_rating_cached'], 1) ?></span>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>
