// Checklist: toggle item done via click
document.addEventListener('DOMContentLoaded', function() {
    var checklist = document.getElementById('checklist');
    if (!checklist) return;

    // Click to toggle
    checklist.addEventListener('click', function(e) {
        var checkBtn = e.target.closest('.checklist-item__check');
        if (!checkBtn) return;

        var item = checkBtn.closest('.checklist-item');
        var itemId = item.dataset.itemId;

        fetch('/listor/punkt/' + itemId + '/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': getCsrfToken()
            }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                item.classList.toggle('checklist-item--done');
                var checkbox = item.querySelector('.checklist-item__checkbox');
                checkbox.classList.toggle('checklist-item__checkbox--checked');
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
            // Clamp movement
            var maxSwipe = 120;
            dx = Math.max(-maxSwipe, Math.min(maxSwipe, dx));
            currentItem.style.transform = 'translateX(' + dx + 'px)';
        }
    }, { passive: false });

    checklist.addEventListener('touchend', function(e) {
        if (!currentItem || !swiping) {
            currentItem = null;
            return;
        }

        var dx = e.changedTouches[0].clientX - startX;

        if (dx > THRESHOLD) {
            // Swipe right → toggle done
            var itemId = currentItem.dataset.itemId;
            fetch('/listor/punkt/' + itemId + '/toggle', {
                method: 'POST',
                headers: { 'X-CSRF-Token': getCsrfToken() }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    currentItem.classList.toggle('checklist-item--done');
                    var cb = currentItem.querySelector('.checklist-item__checkbox');
                    cb.classList.toggle('checklist-item__checkbox--checked');
                }
            });
        }

        // Snap back
        currentItem.style.transform = '';
        currentItem = null;
        swiping = false;
    });
});
