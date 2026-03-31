<div class="auth-card">
    <div class="auth-logo">
        <img src="/img/frizon-logo.png" alt="Frizon of Sweden" class="auth-logo__image">
    </div>

    <?php if ($error = flash('error')): ?>
        <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/adm/login" class="auth-form">
        <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>

        <div class="form-group">
            <label for="username" class="form-label">Användarnamn</label>
            <input type="text" id="username" name="username" class="form-input" required autofocus autocomplete="username">
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Lösenord</label>
            <input type="password" id="password" name="password" class="form-input" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn btn-primary btn--full">Logga in</button>
    </form>
</div>
