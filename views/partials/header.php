<header class="app-header">
    <a href="/adm" class="app-header__logo">
        <img src="/img/frizon-logo.png" alt="Frizon of Sweden" width="36" height="36" style="border-radius:50%;">
    </a>
    <span class="app-header__title"><?= htmlspecialchars($pageTitle ?? '') ?></span>
    <div class="app-header__actions">
        <a href="/adm/frizze" class="app-header__icon-btn" aria-label="Frizze">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9L18.4 10l-2.1-3.2A2 2 0 0 0 14.6 6H7.4a2 2 0 0 0-1.7.9L3.6 10l-2.1 1.1A2 2 0 0 0 0 13v3c0 .6.4 1 1 1h2"/><circle cx="6" cy="17" r="2"/><circle cx="17" cy="17" r="2"/><path d="M8 10h7"/></svg>
        </a>
        <a href="/adm/statistik" class="app-header__icon-btn" aria-label="Statistik">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        </a>
        <a href="/adm/platser/ny" class="app-header__gps-btn" aria-label="Spara plats här">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </a>
    </div>
</header>
