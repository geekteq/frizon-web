<div class="page-header mb-4">
    <a href="/adm/resor/<?= htmlspecialchars($trip['slug']) ?>" class="btn-ghost btn--sm">&larr; Tillbaka</a>
    <h2>Redigera resa</h2>
</div>

<form method="POST" action="/adm/resor/<?= htmlspecialchars($trip['slug']) ?>" style="max-width:var(--form-max-width);">
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

    <section style="margin-top:var(--space-6); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
        <h2 style="font-size:var(--text-base); font-weight:var(--weight-semibold); margin-bottom:var(--space-3);">
            Kommande resa — publik teaser
        </h2>
        <label style="display:flex; align-items:center; gap:var(--space-2); cursor:pointer; margin-bottom:var(--space-4);">
            <input type="checkbox" name="public_teaser" value="1"
                   <?= !empty($trip['public_teaser']) ? 'checked' : '' ?>>
            <span style="font-size:var(--text-sm);">Visa som planerad resa på startsidan</span>
        </label>
        <div>
            <label for="teaser_text" style="display:block; font-size:var(--text-sm); font-weight:var(--weight-medium); margin-bottom:var(--space-1);">
                Teasertext (visas publikt — inga specifika platser)
            </label>
            <textarea id="teaser_text" name="teaser_text" rows="3" maxlength="500"
                      style="width:100%; padding:var(--space-3); border:1px solid var(--color-border); border-radius:var(--radius-md); font-size:var(--text-sm); resize:vertical;"
                      placeholder="T.ex. Vi planerar en Sverige-rund i sommar…"><?= htmlspecialchars($trip['teaser_text'] ?? '') ?></textarea>
            <p style="font-size:var(--text-xs); color:var(--color-text-muted); margin-top:var(--space-1);">
                Visas bara om "Visa som planerad resa" är ikryssad och startdatum är i framtiden.
            </p>
        </div>
    </section>

    <div class="flex gap-3">
        <button type="submit" class="btn btn-primary">Spara ändringar</button>
        <a href="/adm/resor/<?= htmlspecialchars($trip['slug']) ?>" class="btn btn-ghost">Avbryt</a>
    </div>
</form>

<form method="POST" action="/adm/resor/<?= htmlspecialchars($trip['slug']) ?>" style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="_method" value="DELETE">
    <button type="submit" class="btn btn-danger btn--sm" data-confirm="Är du säker? Alla platser och ruttdata tas bort.">Ta bort resa</button>
</form>
