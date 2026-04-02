// Checklist: toggle, swipe, AJAX add, and sync between users
document.addEventListener('DOMContentLoaded', function() {
    var checklist = document.getElementById('checklist');
    if (!checklist) return;

    // Apply server-authoritative done state to avoid flip-flop between users
    function applyItemState(item, isDone) {
        var checkbox = item.querySelector('.checklist-item__checkbox');
        var btn = item.querySelector('.checklist-item__check');
        if (isDone) {
            item.classList.add('checklist-item--done');
            if (checkbox) checkbox.classList.add('checklist-item__checkbox--checked');
            if (btn) btn.setAttribute('aria-label', 'Markera ej klar');
        } else {
            item.classList.remove('checklist-item--done');
            if (checkbox) checkbox.classList.remove('checklist-item__checkbox--checked');
            if (btn) btn.setAttribute('aria-label', 'Markera klar');
        }
    }

    // Click to toggle
    checklist.addEventListener('click', function(e) {
        var checkBtn = e.target.closest('.checklist-item__check');
        if (!checkBtn) return;

        var item = checkBtn.closest('.checklist-item');
        var itemId = item.dataset.itemId;

        fetch('/adm/listor/punkt/' + itemId + '/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCsrfToken()
            }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                applyItemState(item, data.is_done);
            }
        });
    });

    // Swipe support for mobile
    var startX = 0;
    var startY = 0;
    var currentItem = null;
    var swiping = false;
    var THRESHOLD = 80;

    checklist.addEventListener('touchstart', function(e) {
        var item = e.target.closest('.checklist-item');
        if (!item) return;

        currentItem = item;
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
        swiping = false;
    }, { passive: true });

    checklist.addEventListener('touchmove', function(e) {
        if (!currentItem) return;

        var dx = e.touches[0].clientX - startX;
        var dy = e.touches[0].clientY - startY;

        // Only swipe if horizontal movement dominates
        if (!swiping && Math.abs(dx) > 10 && Math.abs(dx) > Math.abs(dy)) {
            swiping = true;
        }

        if (swiping) {
            e.preventDefault();
            var maxSwipe = 120;
            dx = Math.max(-maxSwipe, Math.min(maxSwipe, dx));
            currentItem.style.transform = 'translateX(' + dx + 'px)';

            var isDone = currentItem.classList.contains('checklist-item--done');
            var progress = Math.abs(dx) / THRESHOLD;
            if (dx < 0 && !isDone) {
                currentItem.style.background = 'rgba(74, 140, 111, ' + Math.min(progress * 0.15, 0.15) + ')';
            } else if (dx > 0 && isDone) {
                currentItem.style.background = 'rgba(93, 126, 154, ' + Math.min(progress * 0.12, 0.12) + ')';
            } else {
                currentItem.style.background = '';
            }
        }
    }, { passive: false });

    checklist.addEventListener('touchend', function(e) {
        if (!currentItem || !swiping) {
            currentItem = null;
            return;
        }

        var dx = e.changedTouches[0].clientX - startX;
        var isDone = currentItem.classList.contains('checklist-item--done');
        var theItem = currentItem;

        if (dx < -THRESHOLD && !isDone) {
            // Swipe left → mark as done/picked
            theItem.style.transform = 'translateX(-100%)';
            theItem.style.transition = 'transform 200ms ease-out';
            setTimeout(function() {
                sendToggle(theItem);
                theItem.style.transform = '';
                theItem.style.transition = '';
                theItem.style.background = '';
            }, 200);
        } else if (dx > THRESHOLD && isDone) {
            // Swipe right → mark as undone/unpicked
            theItem.style.transform = 'translateX(100%)';
            theItem.style.transition = 'transform 200ms ease-out';
            setTimeout(function() {
                sendToggle(theItem);
                theItem.style.transform = '';
                theItem.style.transition = '';
                theItem.style.background = '';
            }, 200);
        } else {
            currentItem.style.transform = '';
            currentItem.style.background = '';
        }

        currentItem = null;
        swiping = false;
    });

    function sendToggle(item) {
        var itemId = item.dataset.itemId;
        fetch('/adm/listor/punkt/' + itemId + '/toggle', {
            method: 'POST',
            headers: { 'X-CSRF-Token': getCsrfToken() }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                applyItemState(item, data.is_done);
            }
        });
    }

    // --- Add item via AJAX (no page reload, stays at bottom) ---
    var addForm = document.querySelector('form[action$="/punkt"]');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();

            var input = addForm.querySelector('input[name="text"]');
            var text = input ? input.value.trim() : '';
            if (!text) return;

            fetch(addForm.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(addForm)
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (!data.success) return;

                // Remove "no items" placeholder if present
                var placeholder = checklist.querySelector('p.text-muted');
                if (placeholder) placeholder.remove();

                // Build and append new item
                var div = document.createElement('div');
                div.className = 'checklist-item';
                div.dataset.itemId = data.item.id;
                div.innerHTML =
                    '<button class="checklist-item__check" aria-label="Markera klar">' +
                        '<span class="checklist-item__checkbox"></span>' +
                    '</button>' +
                    '<span class="checklist-item__text">' + esc(data.item.text) + '</span>' +
                    (data.item.category ? '<span class="checklist-item__category">' + esc(data.item.category) + '</span>' : '') +
                    '<form method="POST" action="/adm/listor/punkt/' + data.item.id + '" class="checklist-item__delete">' +
                        '<input type="hidden" name="_csrf" value="' + esc(getCsrfToken()) + '">' +
                        '<input type="hidden" name="_method" value="DELETE">' +
                        '<button type="submit" class="btn-ghost btn--sm" aria-label="Ta bort">&times;</button>' +
                    '</form>';

                checklist.appendChild(div);
                div.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                if (input) { input.value = ''; input.focus(); }
            });
        });
    }

    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // --- Reload page when returning after 30s+ away (keeps two users in sync) ---
    var hiddenAt = 0;
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            hiddenAt = Date.now();
        } else if (hiddenAt > 0 && Date.now() - hiddenAt > 30000) {
            window.location.reload();
        }
    });
});
