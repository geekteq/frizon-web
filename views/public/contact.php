<?php /* Samarbeta / sponsorship contact page */ ?>

<div style="max-width:640px; margin:0 auto; padding:var(--space-10) var(--space-4) var(--space-12);">

    <h1 style="font-size:var(--text-2xl); font-weight:var(--weight-bold); margin-bottom:var(--space-3);">
        Samarbeta med oss
    </h1>
    <p style="font-size:var(--text-base); line-height:var(--leading-relaxed); color:var(--color-text-muted); margin-bottom:var(--space-8);">
        Vi samarbetar med varumärken vi faktiskt använder och kan rekommendera på resan med Frizze.
        Intresserad? Fyll i formuläret nedan så hör vi av oss.
    </p>

    <?php if ($flash = flash('success')): ?>
        <div style="background:var(--color-success-bg,#dcfce7); color:var(--color-success,#166534); padding:var(--space-4); border-radius:var(--radius-md); margin-bottom:var(--space-6);">
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php elseif ($flash = flash('error')): ?>
        <div style="background:#fee2e2; color:#991b1b; padding:var(--space-4); border-radius:var(--radius-md); margin-bottom:var(--space-6);">
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/samarbeta" style="display:flex; flex-direction:column; gap:var(--space-5);">
        <?= CsrfService::field() ?>

        <!-- Honeypot: hidden from real users, bots fill it -->
        <input type="text" name="website" tabindex="-1" autocomplete="off"
               style="position:absolute; left:-9999px; width:1px; height:1px; overflow:hidden;" aria-hidden="true">

        <!-- Timing token -->
        <input type="hidden" name="loaded_at" value="<?= (int) $loadedAt ?>">
        <input type="hidden" name="form_token" value="<?= htmlspecialchars($formToken) ?>">

        <div>
            <label for="contact-name" style="display:block; font-size:var(--text-sm); font-weight:var(--weight-medium); margin-bottom:var(--space-1);">
                Namn <span aria-hidden="true">*</span>
            </label>
            <input type="text" id="contact-name" name="name" required autocomplete="name"
                   style="width:100%; padding:var(--space-3); border:1px solid var(--color-border); border-radius:var(--radius-md); font-size:var(--text-base); background:var(--color-bg);">
        </div>

        <div>
            <label for="contact-company" style="display:block; font-size:var(--text-sm); font-weight:var(--weight-medium); margin-bottom:var(--space-1);">
                Företag
            </label>
            <input type="text" id="contact-company" name="company" autocomplete="organization"
                   style="width:100%; padding:var(--space-3); border:1px solid var(--color-border); border-radius:var(--radius-md); font-size:var(--text-base); background:var(--color-bg);">
        </div>

        <div>
            <label for="contact-email" style="display:block; font-size:var(--text-sm); font-weight:var(--weight-medium); margin-bottom:var(--space-1);">
                E-post <span aria-hidden="true">*</span>
            </label>
            <input type="email" id="contact-email" name="email" required autocomplete="email"
                   style="width:100%; padding:var(--space-3); border:1px solid var(--color-border); border-radius:var(--radius-md); font-size:var(--text-base); background:var(--color-bg);">
        </div>

        <div>
            <label for="contact-message" style="display:block; font-size:var(--text-sm); font-weight:var(--weight-medium); margin-bottom:var(--space-1);">
                Meddelande <span aria-hidden="true">*</span>
            </label>
            <textarea id="contact-message" name="message" required rows="6"
                      style="width:100%; padding:var(--space-3); border:1px solid var(--color-border); border-radius:var(--radius-md); font-size:var(--text-base); background:var(--color-bg); resize:vertical;"></textarea>
        </div>

        <div>
            <button type="submit" class="btn btn-primary" style="font-size:var(--text-base); padding:var(--space-3) var(--space-6);">
                Skicka
            </button>
        </div>
    </form>

</div>
