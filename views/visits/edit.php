<div class="page-header mb-4">
    <a href="/besok/<?= (int) $visit['id'] ?>" class="btn-ghost btn--sm">&larr; Tillbaka till besök</a>
    <h2>Redigera besök</h2>
</div>

<form method="POST" action="/besok/<?= (int) $visit['id'] ?>" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="_method" value="PUT">

    <div class="form-group">
        <label for="visited_at" class="form-label">Datum *</label>
        <input type="date" id="visited_at" name="visited_at" class="form-input"
            value="<?= htmlspecialchars($visit['visited_at'] ?? date('Y-m-d')) ?>" required>
    </div>

    <div class="form-group">
        <label for="raw_note" class="form-label">Anteckning</label>
        <textarea id="raw_note" name="raw_note" class="form-textarea form-textarea--note" rows="4"
            placeholder="Skriv vad du vill..."><?= htmlspecialchars($visit['raw_note'] ?? '') ?></textarea>
    </div>

    <details class="mb-4" <?= ($visit['plus_notes'] || $visit['minus_notes'] || $visit['tips_notes'] || $visit['price_level'] || $visit['would_return'] || $visit['suitable_for'] || $visit['things_to_note']) ? 'open' : '' ?>>
        <summary class="btn btn-ghost btn--sm" style="cursor:pointer;">+ Strukturerade fält</summary>
        <div style="padding-top:var(--space-4);">
            <div class="form-group">
                <label for="plus_notes" class="form-label">Vad var bra?</label>
                <textarea id="plus_notes" name="plus_notes" class="form-textarea" rows="2"><?= htmlspecialchars($visit['plus_notes'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label for="minus_notes" class="form-label">Vad var dåligt?</label>
                <textarea id="minus_notes" name="minus_notes" class="form-textarea" rows="2"><?= htmlspecialchars($visit['minus_notes'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label for="tips_notes" class="form-label">Tips</label>
                <textarea id="tips_notes" name="tips_notes" class="form-textarea" rows="2"><?= htmlspecialchars($visit['tips_notes'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Prisnivå</label>
                <div class="flex gap-2">
                    <?php foreach (['free' => 'Gratis', 'low' => '€', 'medium' => '€€', 'high' => '€€€'] as $val => $label): ?>
                        <label class="chip-option">
                            <input type="radio" name="price_level" value="<?= $val ?>"
                                <?= ($visit['price_level'] ?? '') === $val ? 'checked' : '' ?>>
                            <span class="chip"><?= $label ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Skulle återvända?</label>
                <div class="flex gap-2">
                    <?php foreach (['yes' => 'Ja', 'maybe' => 'Kanske', 'no' => 'Nej'] as $val => $label): ?>
                        <label class="chip-option">
                            <input type="radio" name="would_return" value="<?= $val ?>"
                                <?= ($visit['would_return'] ?? '') === $val ? 'checked' : '' ?>>
                            <span class="chip"><?= $label ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label for="suitable_for" class="form-label">Passar för</label>
                <input type="text" id="suitable_for" name="suitable_for" class="form-input"
                    placeholder="t.ex. husbilar, hundar, familjer"
                    value="<?= htmlspecialchars($visit['suitable_for'] ?? '') ?>"
                    data-suggestions='<?= htmlspecialchars(json_encode($suitableForSuggestions)) ?>'>
                <span class="form-hint">Kommaseparerat. Tidigare använda förslag visas.</span>
            </div>
            <div class="form-group">
                <label for="things_to_note" class="form-label">Att notera</label>
                <textarea id="things_to_note" name="things_to_note" class="form-textarea" rows="2"><?= htmlspecialchars($visit['things_to_note'] ?? '') ?></textarea>
            </div>
        </div>
    </details>

    <div class="form-group">
        <label class="form-label">Betyg</label>
        <?php
        $ratingLabels = [
            'location_rating'     => 'Läge',
            'calmness_rating'     => 'Lugn',
            'service_rating'      => 'Service',
            'value_rating'        => 'Värde',
            'return_value_rating' => 'Återkomst',
        ];
        foreach ($ratingLabels as $field => $label):
            $currentVal = $ratings[$field] ?? null;
        ?>
            <div class="rating-input-row flex-between mb-2">
                <span class="text-sm"><?= $label ?></span>
                <div class="rating-input flex gap-1" data-field="<?= $field ?>">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="rating-dot <?= ($currentVal && $i <= (int)$currentVal) ? 'rating-dot--active' : '' ?>"
                            data-value="<?= $i ?>"><?= $i ?></button>
                    <?php endfor; ?>
                    <input type="hidden" name="<?= $field ?>" value="<?= htmlspecialchars((string)($currentVal ?? '')) ?>">
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <button type="submit" class="btn btn-primary btn--full">Spara ändringar</button>
</form>

<script src="/js/ratings.js"></script>
<script src="/js/tags.js"></script>
