<div class="page-header mb-4">
    <a href="/resor/<?= htmlspecialchars($trip['slug']) ?>" class="btn-ghost btn--sm">&larr; Tillbaka</a>
    <h2>Redigera resa</h2>
</div>

<form method="POST" action="/resor/<?= htmlspecialchars($trip['slug']) ?>" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="_method" value="PUT">

    <div class="form-group">
        <label for="title" class="form-label">Resenamn *</label>
        <input type="text" id="title" name="title" class="form-input" required value="<?= htmlspecialchars($trip['title']) ?>">
    </div>

    <div class="form-group">
        <label class="form-label">Datum</label>
        <div class="flex gap-2">
            <input type="date" name="start_date" class="form-input" style="flex:1;" value="<?= htmlspecialchars($trip['start_date'] ?? '') ?>">
            <span style="align-self:center;">→</span>
            <input type="date" name="end_date" class="form-input" style="flex:1;" value="<?= htmlspecialchars($trip['end_date'] ?? '') ?>">
        </div>
    </div>

    <div class="form-group">
        <label class="form-label">Status</label>
        <div class="flex gap-2">
            <?php foreach (['planned'=>'Planerad','ongoing'=>'Pågående','finished'=>'Avslutad'] as $val => $label): ?>
                <label class="chip-option">
                    <input type="radio" name="status" value="<?= $val ?>" <?= $trip['status'] === $val ? 'checked' : '' ?>>
                    <span class="chip"><?= $label ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-group">
        <label for="intro_text" class="form-label">Intro</label>
        <textarea id="intro_text" name="intro_text" class="form-textarea" rows="3"><?= htmlspecialchars($trip['intro_text'] ?? '') ?></textarea>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn btn-primary">Spara ändringar</button>
        <a href="/resor/<?= htmlspecialchars($trip['slug']) ?>" class="btn btn-ghost">Avbryt</a>
    </div>
</form>

<form method="POST" action="/resor/<?= htmlspecialchars($trip['slug']) ?>" style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="_method" value="DELETE">
    <button type="submit" class="btn btn-danger btn--sm" onclick="return confirm('Är du säker? Alla hållplatser och ruttdata tas bort.')">Ta bort resa</button>
</form>
