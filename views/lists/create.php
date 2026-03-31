<div class="page-header mb-4">
    <a href="/adm/listor" class="btn-ghost btn--sm">&larr; Listor</a>
</div>

<form method="POST" action="/adm/listor" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>

    <div class="form-group">
        <label for="title" class="form-label">Listnamn *</label>
        <input type="text" id="title" name="title" class="form-input" required placeholder='t.ex. "Packlista sommarresa"'>
    </div>

    <div class="form-group">
        <label class="form-label">Typ</label>
        <div class="flex gap-2">
            <label class="chip-option">
                <input type="radio" name="list_type" value="checklist" checked>
                <span class="chip">Checklista</span>
            </label>
            <label class="chip-option">
                <input type="radio" name="list_type" value="shopping">
                <span class="chip">Inköpslista</span>
            </label>
        </div>
    </div>

    <?php if (!empty($templates)): ?>
        <div class="form-group">
            <label for="template_id" class="form-label">Utgå från mall (valfritt)</label>
            <select name="template_id" id="template_id" class="form-select">
                <option value="">Ingen mall</option>
                <?php foreach ($templates as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <input type="hidden" name="scope_type" value="global">
    <input type="hidden" name="scope_id" value="">

    <button type="submit" class="btn btn-primary btn--full">Skapa lista</button>
</form>
