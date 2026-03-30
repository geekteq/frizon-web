<?php $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
<aside class="app-sidebar">
    <div class="sidebar-logo" style="display:flex; align-items:center; gap:var(--space-3); padding-bottom:var(--space-4); border-bottom:1px solid rgba(255,255,255,0.1); margin-bottom:var(--space-2);">
        <img src="/img/frizon-logo.png" alt="Frizon" width="48" style="border-radius:50%;">
        <span style="font-weight:700; font-size:1.1rem;">Frizon</span>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-nav__label">Privat</div>
        <a href="/" class="sidebar-nav__item <?= $currentPath === '/' ? 'sidebar-nav__item--active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/></svg>
            Karta
        </a>
        <a href="/platser" class="sidebar-nav__item <?= str_starts_with($currentPath, '/platser') ? 'sidebar-nav__item--active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
            Platser
        </a>
        <a href="/resor" class="sidebar-nav__item <?= str_starts_with($currentPath, '/resor') ? 'sidebar-nav__item--active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
            Resor
        </a>
    </nav>
    <div style="margin-top:auto; padding-top:var(--space-4); border-top:1px solid rgba(255,255,255,0.1);">
        <span style="font-size:var(--text-sm); opacity:0.65;"><?= htmlspecialchars(Auth::userName() ?? '') ?></span>
        <form method="POST" action="/logout" style="display:inline;">
            <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
            <button type="submit" class="btn-ghost" style="color:rgba(255,255,255,0.5); font-size:var(--text-sm);">Logga ut</button>
        </form>
    </div>
</aside>
