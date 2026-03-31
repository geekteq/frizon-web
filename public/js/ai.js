// Frizon.org — AI Draft ("Brodera ut text")
// Handles generation, preview, approval, and rejection of AI-generated visit descriptions.

function initAiDraft(visitId, csrfToken) {
    var area      = document.getElementById('ai-draft-area');
    var genBtn    = document.getElementById('ai-generate-btn');
    var loadingEl = document.getElementById('ai-draft-loading');
    var resultEl  = document.getElementById('ai-draft-result');

    if (!area || !genBtn) return;

    genBtn.addEventListener('click', function () {
        generateDraft();
    });

    function generateDraft() {
        setLoading(true);
        resultEl.style.display = 'none';
        resultEl.innerHTML = '';

        fetch('/adm/besok/' + visitId + '/ai/generera', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': csrfToken,
            },
            body: '_csrf=' + encodeURIComponent(csrfToken),
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            setLoading(false);
            if (!data.success) {
                showError(data.error || 'Något gick fel. Försök igen.');
                return;
            }
            renderDraft(data.draft);
        })
        .catch(function () {
            setLoading(false);
            showError('Nätverksfel. Kontrollera anslutningen och försök igen.');
        });
    }

    function renderDraft(draft) {
        // Escape HTML in the draft text, then replace newlines with <br>
        var escaped = escapeHtml(draft.text).replace(/\n/g, '<br>');

        resultEl.innerHTML =
            '<div class="ai-draft-preview" style="'
            +   'background:var(--color-bg-subtle, #f8f7f4);'
            +   'border:1px solid var(--color-border);'
            +   'border-radius:var(--radius-md);'
            +   'padding:var(--space-4);'
            +   'margin-top:var(--space-3);'
            + '">'
            +   '<p class="text-sm" style="margin-bottom:var(--space-3);">' + escaped + '</p>'
            +   '<div class="flex gap-3" style="flex-wrap:wrap;">'
            +     '<button type="button" class="btn btn-primary btn--sm" id="ai-approve-btn">Godkann och spara</button>'
            +     '<button type="button" class="btn btn-secondary btn--sm" id="ai-reject-btn">Avvisa</button>'
            +     '<button type="button" class="btn-ghost btn--sm" id="ai-retry-btn">Generera nytt</button>'
            +   '</div>'
            +   '<p class="text-xs text-muted" style="margin-top:var(--space-2);">Genererades ' + escapeHtml(draft.created_at) + '</p>'
            + '</div>';

        resultEl.style.display = 'block';

        document.getElementById('ai-approve-btn').addEventListener('click', function () {
            approveDraft(draft.id);
        });
        document.getElementById('ai-reject-btn').addEventListener('click', function () {
            rejectDraft(draft.id);
        });
        document.getElementById('ai-retry-btn').addEventListener('click', function () {
            generateDraft();
        });
    }

    function approveDraft(draftId) {
        var btn = document.getElementById('ai-approve-btn');
        if (btn) { btn.disabled = true; btn.textContent = 'Sparar...'; }

        fetch('/adm/besok/' + visitId + '/ai/' + draftId + '/godkann', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': csrfToken,
            },
            body: '_csrf=' + encodeURIComponent(csrfToken),
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (!data.success) {
                showError(data.error || 'Kunde inte godkanna utkastet.');
                if (btn) { btn.disabled = false; btn.textContent = 'Godkann och spara'; }
                return;
            }
            // Reload to show the approved text in the view
            window.location.reload();
        })
        .catch(function () {
            showError('Nätverksfel. Försök igen.');
            if (btn) { btn.disabled = false; btn.textContent = 'Godkann och spara'; }
        });
    }

    function rejectDraft(draftId) {
        var btn = document.getElementById('ai-reject-btn');
        if (btn) { btn.disabled = true; btn.textContent = 'Avvisar...'; }

        fetch('/adm/besok/' + visitId + '/ai/' + draftId + '/avvisa', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': csrfToken,
            },
            body: '_csrf=' + encodeURIComponent(csrfToken),
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (!data.success) {
                showError(data.error || 'Kunde inte avvisa utkastet.');
                if (btn) { btn.disabled = false; btn.textContent = 'Avvisa'; }
                return;
            }
            // Clear the result area and let the user try again
            resultEl.style.display = 'none';
            resultEl.innerHTML = '';
            showInfo('Utkastet avvisades. Du kan generera ett nytt.');
        })
        .catch(function () {
            showError('Nätverksfel. Försök igen.');
            if (btn) { btn.disabled = false; btn.textContent = 'Avvisa'; }
        });
    }

    function setLoading(on) {
        genBtn.disabled = on;
        loadingEl.style.display = on ? 'block' : 'none';
        if (on) {
            genBtn.textContent = 'Genererar...';
        } else {
            genBtn.textContent = 'Brodera ut text';
        }
    }

    function showError(msg) {
        showMessage(msg, '#fef2f2', 'var(--color-danger, #dc2626)');
    }

    function showInfo(msg) {
        showMessage(msg, '#f0fdf4', 'var(--color-success, #16a34a)');
    }

    function showMessage(msg, bg, color) {
        var el = document.getElementById('ai-draft-message');
        if (!el) {
            el = document.createElement('p');
            el.id = 'ai-draft-message';
            el.className = 'text-sm';
            el.style.marginTop = 'var(--space-2)';
            area.appendChild(el);
        }
        el.style.background = bg;
        el.style.color = color;
        el.style.padding = 'var(--space-2) var(--space-3)';
        el.style.borderRadius = 'var(--radius-md)';
        el.textContent = msg;
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
}
