// Frizon.org — Lightbox with touch-swipe and keyboard nav
(function () {
    'use strict';

    var overlay, imgEl, captionEl, prevBtn, nextBtn;
    var images  = [];
    var current = 0;
    var startX  = 0;

    function buildOverlay() {
        overlay = document.createElement('div');
        overlay.className = 'lightbox';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'Bildvisare');
        overlay.setAttribute('tabindex', '-1');

        var closeBtn = document.createElement('button');
        closeBtn.className = 'lightbox__close';
        closeBtn.setAttribute('aria-label', 'Stäng');
        closeBtn.innerHTML = '&times;';
        closeBtn.addEventListener('click', closeLightbox);

        prevBtn = document.createElement('button');
        prevBtn.className = 'lightbox__nav lightbox__nav--prev';
        prevBtn.setAttribute('aria-label', 'Föregående bild');
        prevBtn.innerHTML = '&#8249;';
        prevBtn.addEventListener('click', function () { navigate(-1); });

        nextBtn = document.createElement('button');
        nextBtn.className = 'lightbox__nav lightbox__nav--next';
        nextBtn.setAttribute('aria-label', 'Nästa bild');
        nextBtn.innerHTML = '&#8250;';
        nextBtn.addEventListener('click', function () { navigate(1); });

        var inner = document.createElement('div');
        inner.className = 'lightbox__inner';

        imgEl = document.createElement('img');
        imgEl.className = 'lightbox__img';
        imgEl.alt = '';

        captionEl = document.createElement('p');
        captionEl.className = 'lightbox__caption';

        inner.appendChild(imgEl);
        inner.appendChild(captionEl);

        overlay.appendChild(closeBtn);
        overlay.appendChild(prevBtn);
        overlay.appendChild(nextBtn);
        overlay.appendChild(inner);
        document.body.appendChild(overlay);

        // Close on backdrop click (outside inner)
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeLightbox();
        });

        // Touch swipe
        inner.addEventListener('touchstart', function (e) {
            startX = e.touches[0].clientX;
        }, { passive: true });

        inner.addEventListener('touchend', function (e) {
            var dx = e.changedTouches[0].clientX - startX;
            if (Math.abs(dx) > 44) navigate(dx < 0 ? 1 : -1);
        }, { passive: true });

        document.addEventListener('keydown', function (e) {
            if (!overlay.classList.contains('is-open')) return;
            if (e.key === 'Escape')      closeLightbox();
            if (e.key === 'ArrowLeft')   navigate(-1);
            if (e.key === 'ArrowRight')  navigate(1);
        });
    }

    function openLightbox(index) {
        if (!overlay) buildOverlay();
        current = index;
        renderCurrent();
        overlay.classList.add('is-open');
        document.body.classList.add('lightbox-open');
        overlay.focus();
    }

    function closeLightbox() {
        if (!overlay) return;
        overlay.classList.remove('is-open');
        document.body.classList.remove('lightbox-open');
    }

    function navigate(dir) {
        current = (current + dir + images.length) % images.length;
        renderCurrent();
    }

    function renderCurrent() {
        var item    = images[current];
        imgEl.src   = item.src;
        imgEl.alt   = item.caption || '';
        captionEl.textContent = item.caption || '';
        captionEl.hidden      = !item.caption;
        prevBtn.hidden = images.length <= 1;
        nextBtn.hidden = images.length <= 1;
    }

    function init() {
        var triggers = Array.prototype.slice.call(
            document.querySelectorAll('[data-lightbox]')
        );
        if (!triggers.length) return;

        images = triggers.map(function (el) {
            return {
                src:     el.dataset.lightboxSrc || '',
                caption: el.dataset.lightboxCaption || '',
            };
        });

        triggers.forEach(function (el, i) {
            // Avoid duplicate listeners by cloning the node with events cleared
            el.removeEventListener('click', el._lbHandler);
            el._lbHandler = function (e) {
                e.preventDefault();
                openLightbox(i);
            };
            el.addEventListener('click', el._lbHandler);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Re-init hook used after dynamic updates (rotate / caption save)
    window.initLightbox = init;
}());
