<div class="page-header mb-4">
    <a href="/adm" class="btn-ghost btn--sm">&larr; Dashboard</a>
    <h2>Byt lösenord</h2>
</div>

<form method="POST" action="/adm/byt-losenord" style="max-width:var(--form-max-width);">
    <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>

    <div class="form-group">
        <label for="current_password" class="form-label">Nuvarande lösenord</label>
        <input type="password" id="current_password" name="current_password" class="form-input" required autocomplete="current-password">
    </div>

    <div class="form-group">
        <label for="new_password" class="form-label">Nytt lösenord</label>
        <input type="password" id="new_password" name="new_password" class="form-input" required autocomplete="new-password" minlength="8">
        <span class="form-hint">Minst 8 tecken.</span>
    </div>

    <div class="form-group">
        <label for="confirm_password" class="form-label">Bekräfta nytt lösenord</label>
        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required autocomplete="new-password">
    </div>

    <button type="submit" class="btn btn-primary">Spara nytt lösenord</button>
</form>
