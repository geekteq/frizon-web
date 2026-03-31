<div class="page-header mb-4">
    <a href="/listor/<?= $list['id'] ?>" class="btn-ghost btn--sm">&larr; Tillbaka</a>
    <h2>Redigera lista</h2>
</div>

<form method="POST" action="/listor/<?= $list['id'] ?>" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="_method" value="PUT">

    <div class="form-group">
        <label for="title" class="form-label">Listnamn *</label>
        <input type="text" id="title" name="title" class="form-input" required value="<?= htmlspecialchars($list['title']) ?>">
    </div>

    <div class="form-group">
        <label class="form-label">Typ</label>
        <div class="flex gap-2">
            <label class="chip-option">
                <input type="radio" name="list_type" value="checklist" <?= $list['list_type'] === 'checklist' ? 'checked' : '' ?>>
                <span class="chip">Checklista</span>
            </label>
            <label class="chip-option">
                <input type="radio" name="list_type" value="shopping" <?= $list['list_type'] === 'shopping' ? 'checked' : '' ?>>
                <span class="chip">Inköpslista</span>
            </label>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="btn btn-primary">Spara ändringar</button>
        <a href="/listor/<?= $list['id'] ?>" class="btn btn-ghost">Avbryt</a>
    </div>
</form>

<form method="POST" action="/listor/<?= $list['id'] ?>" style="margin-top:var(--space-8); padding-top:var(--space-6); border-top:1px solid var(--color-border);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
    <input type="hidden" name="_method" value="DELETE">
    <button type="submit" class="btn btn-danger btn--sm" onclick="return confirm('Är du säker? Alla punkter tas bort.')">Ta bort lista</button>
</form>
