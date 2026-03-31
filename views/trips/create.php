<div class="page-header mb-4">
    <a href="/resor" class="btn-ghost btn--sm">&larr; Resor</a>
</div>

<form method="POST" action="/resor" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>

    <div class="form-group">
        <label for="title" class="form-label">Resenamn *</label>
        <input type="text" id="title" name="title" class="form-input" required placeholder='t.ex. "Normandie 2026"'>
    </div>

    <div class="form-group">
        <label class="form-label">Datum</label>
        <div class="flex gap-2">
            <input type="date" name="start_date" class="form-input" style="flex:1;">
            <span style="align-self:center;">→</span>
            <input type="date" name="end_date" class="form-input" style="flex:1;">
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Status</label>
        <div class="flex gap-2">
            <?php foreach (['planned'=>'Planerad','ongoing'=>'Pågående','finished'=>'Avslutad'] as $val => $label): ?>
                <label class="chip-option">
                    <input type="radio" name="status" value="<?= $val ?>" <?= $val === 'planned' ? 'checked' : '' ?>>
                    <span class="chip"><?= $label ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-group">
        <label for="intro_text" class="form-label">Intro (valfritt)</label>
        <textarea id="intro_text" name="intro_text" class="form-textarea" rows="3" placeholder="Kort beskrivning av resan..."></textarea>
    </div>

    <button type="submit" class="btn btn-primary btn--full">Skapa resa</button>
</form>
