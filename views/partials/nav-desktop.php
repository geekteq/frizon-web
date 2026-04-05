<?php $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
<aside class="app-sidebar" style="padding:var(--space-4);">
    <div style="text-align:center; padding-bottom:var(--space-4); border-bottom:1px solid rgba(255,255,255,0.1); margin-bottom:var(--space-2);">
        <img src="/img/frizon-logo.png" alt="Frizon" width="56" style="border-radius:50%; display:block; margin:0 auto var(--space-2);">
        <span style="font-weight:700; font-size:1.1rem; color:var(--color-white);">Frizon</span>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-nav__label">Privat</div>
        <a href="/adm" class="sidebar-nav__item <?= $currentPath === '/adm' ? 'sidebar-nav__item--active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/></svg>
            Karta
        </a>
        <a href="/adm/platser" class="sidebar-nav__item <?= (str_starts_with($currentPath, '/adm/platser') && $currentPath !== '/adm/platser/ny') ? 'sidebar-nav__item--active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
            Platser
        </a>
        <a href="/adm/platser/ny" class="sidebar-nav__item <?= $currentPath === '/adm/platser/ny' ? 'sidebar-nav__item--active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Ny plats
        </a>
        <a href="/adm/resor" class="sidebar-nav__item <?= str_starts_with($currentPath, '/adm/resor') ? 'sidebar-nav__item--active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
            Resor
        </a>
        <a href="/adm/listor" class="sidebar-nav__item <?= str_starts_with($currentPath, '/adm/listor') ? 'sidebar-nav__item--active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
            Listor
        </a>

        <a href="/adm/statistik" class="sidebar-nav__item <?= str_starts_with($currentPath, '/adm/statistik') ? 'sidebar-nav__item--active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            Statistik
        </a>

        <div class="sidebar-nav__label" style="margin-top:var(--space-4);">Publikt</div>
        <a href="/adm/amazon-lista" class="sidebar-nav__item <?= str_starts_with($currentPath ?? '', '/adm/amazon-lista') ? 'sidebar-nav__item--active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Shop
        </a>
        <a href="/adm/publicera" class="sidebar-nav__item <?= str_starts_with($currentPath, '/adm/publicera') ? 'sidebar-nav__item--active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            Publicera
        </a>
    </nav>
    <div style="margin-top:auto; padding-top:var(--space-4); border-top:1px solid rgba(255,255,255,0.1);">
        <span style="font-size:var(--text-sm); opacity:0.65;"><?= htmlspecialchars(Auth::userName() ?? '') ?></span>
        <a href="/adm/byt-losenord" style="color:rgba(255,255,255,0.4); font-size:var(--text-xs); margin-left:var(--space-2); text-decoration:none;">Byt lösenord</a>
        <form method="POST" action="/adm/logout" style="display:inline;">
            <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
            <button type="submit" class="btn-ghost" style="color:rgba(255,255,255,0.5); font-size:var(--text-sm);">Logga ut</button>
        </form>
    </div>
</aside>
