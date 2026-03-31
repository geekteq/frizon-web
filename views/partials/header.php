<header class="app-header">
    <a href="/adm" class="app-header__logo">
        <img src="/img/frizon-logo.png" alt="Frizon of Sweden" width="36" height="36" style="border-radius:50%;">
    </a>
    <span class="app-header__title"><?= htmlspecialchars($pageTitle ?? '') ?></span>
    <a href="/adm/platser/ny" class="app-header__gps-btn" aria-label="Spara plats här">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    </a>
</header>
