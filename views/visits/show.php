<div class="page-header mb-4">
    <a href="/adm/platser/<?= htmlspecialchars($visit['place_slug']) ?>" class="btn-ghost btn--sm">&larr; <?= htmlspecialchars($visit['place_name']) ?></a>
</div>

<div class="visit-detail">
    <h2>Besök <?= htmlspecialchars($visit['visited_at']) ?></h2>
    <p class="text-sm text-muted mb-4"><?= htmlspecialchars($visit['place_name']) ?></p>

    <?php if ($visit['raw_note']): ?>
        <div class="visit-detail__note mb-4" style="background:#FDFCF8; padding:var(--space-4); border-radius:var(--radius-md); border:1px solid var(--color-warm-dark);">
            <?= nl2br(htmlspecialchars($visit['raw_note'])) ?>
        </div>
    <?php endif; ?>

    <?php if ($visit['plus_notes'] || $visit['minus_notes'] || $visit['tips_notes']): ?>
        <div class="visit-detail__fields mb-4">
            <?php if ($visit['plus_notes']): ?>
                <div class="mb-2"><strong class="text-sm">Plus:</strong> <?= nl2br(htmlspecialchars($visit['plus_notes'])) ?></div>
            <?php endif; ?>
            <?php if ($visit['minus_notes']): ?>
                <div class="mb-2"><strong class="text-sm">Minus:</strong> <?= nl2br(htmlspecialchars($visit['minus_notes'])) ?></div>
            <?php endif; ?>
            <?php if ($visit['tips_notes']): ?>
                <div class="mb-2"><strong class="text-sm">Tips:</strong> <?= nl2br(htmlspecialchars($visit['tips_notes'])) ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($visit['price_level'] || $visit['would_return'] || $visit['suitable_for']): ?>
        <div class="visit-detail__meta mb-4 text-sm">
            <?php
            $priceLevels = ['free' => 'Gratis', 'low' => '€', 'medium' => '€€', 'high' => '€€€'];
            $wouldReturnLabels = ['yes' => 'Ja', 'maybe' => 'Kanske', 'no' => 'Nej'];
            ?>
            <?php if ($visit['price_level']): ?>
                <div>Pris: <?= htmlspecialchars($priceLevels[$visit['price_level']] ?? $visit['price_level']) ?></div>
            <?php endif; ?>
            <?php if ($visit['would_return']): ?>
                <div>Återvända: <?= htmlspecialchars($wouldReturnLabels[$visit['would_return']] ?? $visit['would_return']) ?></div>
            <?php endif; ?>
            <?php if ($visit['suitable_for']): ?>
                <div>Passar för: <?= htmlspecialchars($visit['suitable_for']) ?></div>
            <?php endif; ?>
            <?php if ($visit['things_to_note']): ?>
                <div>Att notera: <?= htmlspecialchars($visit['things_to_note']) ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($ratings): ?>
        <?php include dirname(__DIR__) . '/partials/rating-display.php'; ?>
    <?php endif; ?>

    <?php if (!empty($images)): ?>
        <div class="img-manage mb-4">
            <?php foreach ($images as $img): ?>
            <div class="img-manage__item" data-image-id="<?= (int)$img['id'] ?>">
                <div class="img-manage__row">
                    <button type="button" class="img-manage__thumb"
                            data-lightbox
                            data-lightbox-src="/uploads/detail/<?= htmlspecialchars($img['filename']) ?>"
                            data-lightbox-caption="<?= htmlspecialchars($img['alt_text'] ?? '') ?>">
                        <img src="/uploads/cards/<?= htmlspecialchars($img['filename']) ?>"
                             alt="<?= htmlspecialchars($img['alt_text'] ?? '') ?>"
                             width="120" height="90"
                             loading="lazy">
                    </button>
                    <div class="img-manage__tools">
                        <button type="button" class="btn btn-ghost btn--sm img-rotate-btn"
                                data-direction="left" title="Rotera vänster" aria-label="Rotera vänster">↺</button>
                        <button type="button" class="btn btn-ghost btn--sm img-rotate-btn"
                                data-direction="right" title="Rotera höger" aria-label="Rotera höger">↻</button>
                    </div>
                    <form method="POST" action="/adm/platser/<?= htmlspecialchars($visit['place_slug']) ?>/preview-image" style="display:inline;">
                        <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
                        <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>">
                        <button type="submit" class="btn btn-ghost btn--sm" title="Använd som platsbild" aria-label="Använd som platsbild"
                            <?= (isset($place) && (int)($place['preview_image_id'] ?? 0) === (int)$img['id']) ? 'disabled style="opacity:0.5;"' : '' ?>>
                            📌
                        </button>
                    </form>
                </div>
                <div class="img-manage__caption">
                    <div style="display:flex; gap:var(--space-2); align-items:center;">
                        <input type="text"
                               class="form-input img-caption-input"
                               value="<?= htmlspecialchars($img['alt_text'] ?? '') ?>"
                               placeholder="Bildtext (visas i lightbox)..."
                               maxlength="500"
                               style="flex:1;">
                        <button type="button"
                                class="btn btn-ghost btn--sm img-ai-btn"
                                title="Generera bildtext med AI"
                                aria-label="Generera bildtext med AI"
                                style="flex-shrink:0; white-space:nowrap;">✦ AI</button>
                    </div>
                    <div class="img-caption-status" aria-live="polite"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <script src="/js/lightbox.js"></script>
        <script<?= app_csp_nonce_attr() ?>>
        (function () {
            var csrf = '<?= htmlspecialchars(CsrfService::token()) ?>';

            function setStatus(item, msg, ms) {
                var el = item.querySelector('.img-caption-status');
                if (!el) return;
                el.textContent = msg;
                if (ms) setTimeout(function () { el.textContent = ''; }, ms);
            }

            function syncLightbox(item, caption) {
                var thumb = item.querySelector('[data-lightbox]');
                if (thumb) thumb.dataset.lightboxCaption = caption;
                if (typeof window.initLightbox === 'function') window.initLightbox();
            }

            // Rotate buttons
            document.querySelectorAll('.img-rotate-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var item      = btn.closest('[data-image-id]');
                    var id        = item.dataset.imageId;
                    var direction = btn.dataset.direction;
                    btn.disabled  = true;
                    setStatus(item, '↻ Roterar…');

                    fetch('/adm/api/images/' + id + '/rotate', {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body:    '_csrf=' + encodeURIComponent(csrf) + '&direction=' + direction,
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            var ts    = '?t=' + Date.now();
                            var img   = item.querySelector('.img-manage__thumb img');
                            var thumb = item.querySelector('[data-lightbox]');
                            if (img)   img.src                  = img.src.replace(/\?.*$/, '') + ts;
                            if (thumb) thumb.dataset.lightboxSrc = thumb.dataset.lightboxSrc.replace(/\?.*$/, '') + ts;
                            if (typeof window.initLightbox === 'function') window.initLightbox();
                            setStatus(item, '✓ Roterad', 2500);
                        } else {
                            setStatus(item, data.error || 'Fel', 3000);
                        }
                        btn.disabled = false;
                    })
                    .catch(function () { btn.disabled = false; setStatus(item, 'Nätverksfel', 3000); });
                });
            });

            // AI caption buttons
            document.querySelectorAll('.img-ai-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var item  = btn.closest('[data-image-id]');
                    var id    = item.dataset.imageId;
                    var input = item.querySelector('.img-caption-input');
                    btn.disabled = true;
                    setStatus(item, '✦ Analyserar bild med AI…');

                    fetch('/adm/api/images/' + id + '/ai-caption', {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body:    '_csrf=' + encodeURIComponent(csrf),
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success && data.caption) {
                            input.value = data.caption;
                            syncLightbox(item, data.caption);
                            setStatus(item, '✓ Bildtext genererad', 3000);
                        } else {
                            setStatus(item, data.error || 'AI misslyckades', 4000);
                        }
                        btn.disabled = false;
                    })
                    .catch(function () { btn.disabled = false; setStatus(item, 'Nätverksfel', 3000); });
                });
            });

            // Caption auto-save on input (debounced) and on blur
            document.querySelectorAll('.img-caption-input').forEach(function (input) {
                var item  = input.closest('[data-image-id]');
                var id    = item.dataset.imageId;
                var timer;

                function save() {
                    fetch('/adm/api/images/' + id + '/caption', {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                        body:    JSON.stringify({ caption: input.value }),
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        setStatus(item, data.success ? '✓ Sparad' : 'Fel', 2500);
                        syncLightbox(item, input.value);
                    })
                    .catch(function () { setStatus(item, 'Nätverksfel', 3000); });
                }

                input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(save, 900); });
                input.addEventListener('blur',  function () { clearTimeout(timer); save(); });
            });
        }());
        </script>
    <?php endif; ?>

    <!-- AI-utkast -->
    <div class="ai-draft-section" style="margin-top:var(--space-6); padding-top:var(--space-4); border-top:1px solid var(--color-border);">
        <h3 class="text-sm" style="margin-bottom:var(--space-3);">Publik beskrivning</h3>

        <?php if (!empty($visit['approved_public_text'])): ?>
            <div style="background:var(--color-success-light, #f0fdf4); padding:var(--space-3); border-radius:var(--radius-md); margin-bottom:var(--space-3);">
                <span class="text-sm" style="color:var(--color-success); font-weight:600;">Godkänd text</span>
                <p style="margin-top:var(--space-2);"><?= nl2br(htmlspecialchars($visit['approved_public_text'])) ?></p>
            </div>
        <?php endif; ?>

        <div id="ai-draft-area"
             data-visit-id="<?= (int)$visit['id'] ?>"
             data-csrf="<?= htmlspecialchars(CsrfService::token()) ?>">
            <button type="button" id="ai-generate-btn" class="btn btn-secondary btn--sm">
                Brodera ut text
            </button>
            <div id="ai-draft-loading" style="display:none;" class="text-sm text-muted">Genererar utkast...</div>
            <div id="ai-draft-result" style="display:none;"></div>
        </div>
    </div>

    <div class="flex gap-3 mt-6" style="flex-wrap:wrap;">
        <a href="/adm/besok/<?= $visit['id'] ?>/redigera" class="btn btn-secondary btn--sm">Redigera</a>
        <?php if (!empty($images)): ?>
        <button type="button" id="ig-open-btn" class="btn btn-secondary btn--sm" style="background:#e1306c;color:#fff;border-color:#e1306c;">
            Publicera på Instagram
        </button>
        <?php endif; ?>
        <form method="POST" action="/adm/besok/<?= $visit['id'] ?>" style="display:inline;">
            <?php include dirname(__DIR__) . '/partials/csrf-field.php'; ?>
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="btn btn-danger btn--sm" data-confirm="Ta bort besöket?">Ta bort</button>
        </form>
    </div>

    <!-- Instagram publish modal -->
    <div id="ig-modal" role="dialog" aria-modal="true" aria-labelledby="ig-modal-title"
         style="display:none; position:fixed; inset:0; z-index:1000; background:rgba(0,0,0,.6); padding:var(--space-4); overflow-y:auto;">
        <div style="background:#fff; border-radius:var(--radius-lg); max-width:560px; margin:auto; padding:var(--space-6);">
            <h3 id="ig-modal-title" style="margin:0 0 var(--space-4);">Publicera på Instagram</h3>

            <div id="ig-loading" class="text-sm text-muted" style="display:none;">Laddar förhandsgranskning...</div>
            <div id="ig-error"   class="text-sm" style="display:none; color:var(--color-danger);"></div>

            <div id="ig-preview" style="display:none;">
                <p class="text-sm text-muted" style="margin-bottom:var(--space-2);">
                    <span id="ig-img-count"></span> bild(er) skickas.
                    Instagram beskär alla bilder till samma bildförhållande som den första.
                </p>

                <div id="ig-thumbs" style="display:flex; gap:var(--space-2); flex-wrap:wrap; margin-bottom:var(--space-4);"></div>

                <label for="ig-caption" class="text-sm" style="display:block; font-weight:600; margin-bottom:var(--space-1);">
                    Beskrivning
                </label>
                <textarea id="ig-caption"
                          rows="10"
                          maxlength="2200"
                          style="width:100%; box-sizing:border-box; font-family:inherit; font-size:0.85rem;
                                 border:1px solid var(--color-border); border-radius:var(--radius-md);
                                 padding:var(--space-3); resize:vertical;"></textarea>
                <p id="ig-char-count" class="text-sm text-muted" style="text-align:right; margin-top:var(--space-1);"></p>

                <div class="flex gap-3 mt-4" style="justify-content:flex-end;">
                    <button type="button" id="ig-cancel-btn" class="btn btn-ghost btn--sm">Avbryt</button>
                    <button type="button" id="ig-publish-btn" class="btn btn--sm" style="background:#e1306c;color:#fff;border-color:#e1306c;">
                        Publicera
                    </button>
                </div>
            </div>

            <div id="ig-success" style="display:none; text-align:center; padding:var(--space-4) 0;">
                <p style="font-size:1.5rem; margin-bottom:var(--space-2);">Publicerat!</p>
                <p class="text-sm text-muted">Inlägget ligger nu på Instagram.</p>
                <button type="button" id="ig-done-btn" class="btn btn-secondary btn--sm" style="margin-top:var(--space-4);">Stäng</button>
            </div>
        </div>
    </div>

    <script<?= app_csp_nonce_attr() ?>>
    (function () {
        var visitId = <?= (int) $visit['id'] ?>;
        var csrf    = '<?= htmlspecialchars(CsrfService::token()) ?>';

        var modal      = document.getElementById('ig-modal');
        var loading    = document.getElementById('ig-loading');
        var errorBox   = document.getElementById('ig-error');
        var preview    = document.getElementById('ig-preview');
        var successBox = document.getElementById('ig-success');
        var thumbs     = document.getElementById('ig-thumbs');
        var caption    = document.getElementById('ig-caption');
        var charCount  = document.getElementById('ig-char-count');
        var imgCount   = document.getElementById('ig-img-count');

        function showOnly(el) {
            [loading, errorBox, preview, successBox].forEach(function (e) { e.style.display = 'none'; });
            el.style.display = '';
        }

        function updateCharCount() {
            var n = caption.value.length;
            charCount.textContent = n + ' / 2200';
            charCount.style.color = n > 2100 ? 'var(--color-danger)' : '';
        }
        caption.addEventListener('input', updateCharCount);

        // Open modal
        document.getElementById('ig-open-btn').addEventListener('click', function () {
            modal.style.display = '';
            showOnly(loading);
            thumbs.innerHTML = '';

            fetch('/adm/api/besok/' + visitId + '/instagram/preview')
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) {
                        errorBox.textContent = data.error;
                        showOnly(errorBox);
                        return;
                    }
                    imgCount.textContent = data.count;
                    data.images.forEach(function (img) {
                        var el = document.createElement('img');
                        el.src    = img.url;
                        el.width  = 80;
                        el.height = 60;
                        el.style.cssText = 'object-fit:cover; border-radius:var(--radius-sm); border:1px solid var(--color-border);';
                        thumbs.appendChild(el);
                    });
                    caption.value = data.caption;
                    updateCharCount();
                    showOnly(preview);
                })
                .catch(function () {
                    errorBox.textContent = 'Nätverksfel. Försök igen.';
                    showOnly(errorBox);
                });
        });

        // Cancel / close
        ['ig-cancel-btn', 'ig-done-btn'].forEach(function (id) {
            document.getElementById(id).addEventListener('click', function () {
                modal.style.display = 'none';
            });
        });

        // Close on backdrop click
        modal.addEventListener('click', function (e) {
            if (e.target === modal) modal.style.display = 'none';
        });

        // Publish
        document.getElementById('ig-publish-btn').addEventListener('click', function () {
            var btn = this;
            btn.disabled = true;
            btn.textContent = 'Publicerar...';

            fetch('/adm/api/besok/' + visitId + '/instagram', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body:    JSON.stringify({ caption: caption.value }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    showOnly(successBox);
                } else {
                    errorBox.textContent = data.error || 'Okänt fel.';
                    showOnly(errorBox);
                    btn.disabled    = false;
                    btn.textContent = 'Publicera';
                }
            })
            .catch(function () {
                errorBox.textContent = 'Nätverksfel. Försök igen.';
                showOnly(errorBox);
                btn.disabled    = false;
                btn.textContent = 'Publicera';
            });
        });
    }());
    </script>
</div>

<script src="/js/ai.js"></script>
<script<?= app_csp_nonce_attr() ?>>
(function () {
    var area = document.getElementById('ai-draft-area');
    if (!area) return;
    var visitId   = parseInt(area.dataset.visitId, 10);
    var csrfToken = area.dataset.csrf;
    initAiDraft(visitId, csrfToken);
}());
</script>
