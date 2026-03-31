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
            var maxSwipe = 120;
            dx = Math.max(-maxSwipe, Math.min(maxSwipe, dx));
            currentItem.style.transform = 'translateX(' + dx + 'px)';

            var isDone = currentItem.classList.contains('checklist-item--done');
            var progress = Math.abs(dx) / THRESHOLD;
            if (dx < 0 && !isDone) {
                // Swiping left to mark done — green hint
                currentItem.style.background = 'rgba(74, 140, 111, ' + Math.min(progress * 0.15, 0.15) + ')';
            } else if (dx > 0 && isDone) {
                // Swiping right to undo — subtle hint
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
                toggleItem(theItem, true);
                theItem.style.transform = '';
                theItem.style.transition = '';
                theItem.style.background = '';
            }, 200);
        } else if (dx > THRESHOLD && isDone) {
            // Swipe right → mark as undone/unpicked
            theItem.style.transform = 'translateX(100%)';
            theItem.style.transition = 'transform 200ms ease-out';
            setTimeout(function() {
                toggleItem(theItem, false);
                theItem.style.transform = '';
                theItem.style.transition = '';
                theItem.style.background = '';
            }, 200);
        } else {
            // Snap back
            currentItem.style.transform = '';
            currentItem.style.background = '';
        }

        currentItem = null;
        swiping = false;
    });

    function toggleItem(item, markDone) {
        var itemId = item.dataset.itemId;
        fetch('/adm/listor/punkt/' + itemId + '/toggle', {
            method: 'POST',
            headers: { 'X-CSRF-Token': getCsrfToken() }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                if (markDone) {
                    item.classList.add('checklist-item--done');
                    item.querySelector('.checklist-item__checkbox').classList.add('checklist-item__checkbox--checked');
                } else {
                    item.classList.remove('checklist-item--done');
                    item.querySelector('.checklist-item__checkbox').classList.remove('checklist-item__checkbox--checked');
                }
            }
        });
    }
});
