<div class="page-header mb-4">
    <a href="/adm/platser/<?= htmlspecialchars($p['slug']) ?>" class="btn-ghost btn--sm">&larr; <?= htmlspecialchars($p['name']) ?></a>
    <h2>Nytt besök</h2>
</div>

<form method="POST" action="/adm/platser/<?= htmlspecialchars($p['slug']) ?>/besok" enctype="multipart/form-data" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>

    <div class="form-group">
        <label for="visited_at" class="form-label">Datum *</label>
        <input type="date" id="visited_at" name="visited_at" class="form-input" value="<?= date('Y-m-d') ?>" required>
    </div>

    <div class="form-group">
        <label for="raw_note" class="form-label">Anteckning</label>
        <textarea id="raw_note" name="raw_note" class="form-textarea form-textarea--note" rows="4" placeholder="Skriv vad du vill..."></textarea>
    </div>

    <details class="mb-4">
        <summary class="btn btn-ghost btn--sm" style="cursor:pointer;">+ Strukturerade fält</summary>
        <div style="padding-top:var(--space-4);">
            <div class="form-group">
                <label for="plus_notes" class="form-label">Vad var bra?</label>
                <textarea id="plus_notes" name="plus_notes" class="form-textarea" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label for="minus_notes" class="form-label">Vad var dåligt?</label>
                <textarea id="minus_notes" name="minus_notes" class="form-textarea" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label for="tips_notes" class="form-label">Tips</label>
                <textarea id="tips_notes" name="tips_notes" class="form-textarea" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Prisnivå</label>
                <div class="flex gap-2">
                    <?php foreach (['free' => 'Gratis', 'low' => '€', 'medium' => '€€', 'high' => '€€€'] as $val => $label): ?>
                        <label class="chip-option">
                            <input type="radio" name="price_level" value="<?= $val ?>">
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
                            <input type="radio" name="would_return" value="<?= $val ?>">
                            <span class="chip"><?= $label ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label for="suitable_for" class="form-label">Passar för</label>
                <input type="text" id="suitable_for" name="suitable_for" class="form-input"
                    placeholder="t.ex. husbilar, hundar, familjer"
                    data-suggestions='<?= htmlspecialchars(json_encode($suitableForSuggestions)) ?>'>
                <span class="form-hint">Kommaseparerat. Tidigare använda förslag visas.</span>
            </div>
            <div class="form-group">
                <label for="things_to_note" class="form-label">Att notera</label>
                <textarea id="things_to_note" name="things_to_note" class="form-textarea" rows="2"></textarea>
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
        foreach ($ratingLabels as $field => $label): ?>
            <div class="rating-input-row flex-between mb-2">
                <span class="text-sm"><?= $label ?></span>
                <div class="rating-input flex gap-1" data-field="<?= $field ?>">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="rating-dot" data-value="<?= $i ?>"><?= $i ?></button>
                    <?php endfor; ?>
                    <input type="hidden" name="<?= $field ?>" value="">
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-group">
        <label class="form-label">Foton (max 8)</label>
        <input type="file" id="photos-input" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="form-input">
    </div>

    <button type="submit" id="save-btn" class="btn btn-primary btn--full">Spara besök</button>

    <!-- Shown while uploading + AI-processing images -->
    <div id="save-loading" style="display:none; margin-top:var(--space-4); text-align:center;">
        <div style="display:inline-flex; align-items:center; gap:var(--space-3); background:var(--color-bg); border:1px solid var(--color-border); border-radius:var(--radius-lg); padding:var(--space-3) var(--space-5);">
            <span style="display:inline-block; width:20px; height:20px; border:3px solid var(--color-border); border-top-color:var(--color-accent); border-radius:50%; animation:spin 0.8s linear infinite;" aria-hidden="true"></span>
            <span id="save-loading-msg" class="text-sm text-muted">Sparar besöket…</span>
        </div>
    </div>
</form>

<script src="/js/ratings.js"></script>
<script src="/js/tags.js"></script>
<script<?= app_csp_nonce_attr() ?>>
(function () {
    var form    = document.querySelector('form[action*="/besok"]');
    var btn     = document.getElementById('save-btn');
    var loading = document.getElementById('save-loading');
    var msg     = document.getElementById('save-loading-msg');
    var photos  = document.getElementById('photos-input');
    if (!form || !btn) return;

    // Add spinner keyframe once
    if (!document.getElementById('spin-kf')) {
        var s = document.createElement('style');
        s.id  = 'spin-kf';
        s.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
        document.head.appendChild(s);
    }

    form.addEventListener('submit', function () {
        btn.disabled = true;
        if (loading) loading.style.display = 'block';
        var hasPhotos = photos && photos.files && photos.files.length > 0;
        if (msg) msg.textContent = hasPhotos
            ? 'Sparar besöket och analyserar ' + photos.files.length + ' bild' + (photos.files.length > 1 ? 'er' : '') + ' med AI…'
            : 'Sparar besöket…';
    });
}());
</script>
