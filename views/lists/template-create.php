<div class="page-header mb-4">
    <a href="/listor/mallar" class="btn-ghost btn--sm">&larr; Mallar</a>
</div>

<form method="POST" action="/listor/mallar" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>

    <div class="form-group">
        <label for="title" class="form-label">Mallnamn *</label>
        <input type="text" id="title" name="title" class="form-input" required placeholder='t.ex. "Packlista resa"'>
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

    <div class="form-group">
        <label for="description" class="form-label">Beskrivning (valfritt)</label>
        <input type="text" id="description" name="description" class="form-input" placeholder="Kort beskrivning...">
    </div>

    <div class="form-group">
        <label for="items_text" class="form-label">Punkter (en per rad)</label>
        <textarea id="items_text" name="items_text" class="form-textarea" rows="10" placeholder="Passhållare
Laddkablar
Första hjälpen-kit
Kartor
..."></textarea>
    </div>

    <button type="submit" class="btn btn-primary btn--full">Skapa mall</button>
</form>
