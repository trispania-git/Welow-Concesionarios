/**
 * Galería de la ficha del coche.
 * Vanilla JS sin dependencias.
 *
 * @since 2.0.0
 */
(function () {
    'use strict';

    function initGaleria(galeria) {
        var slides = galeria.querySelectorAll('.welow-galeria-ficha__slide');
        var thumbs = galeria.querySelectorAll('.welow-galeria-ficha__thumb');
        var counter = galeria.querySelector('.welow-galeria-ficha__current');
        var prevBtn = galeria.querySelector('.welow-galeria-ficha__arrow--prev');
        var nextBtn = galeria.querySelector('.welow-galeria-ficha__arrow--next');

        var current = 0;
        var total = slides.length;
        if (total <= 1) return;

        function goTo(index) {
            if (index < 0) index = total - 1;
            if (index >= total) index = 0;

            slides[current].classList.remove('is-active');
            slides[index].classList.add('is-active');

            if (thumbs[current]) thumbs[current].classList.remove('is-active');
            if (thumbs[index])   thumbs[index].classList.add('is-active');

            if (counter) counter.textContent = index + 1;

            // Scroll thumb activa al centro
            if (thumbs[index]) {
                thumbs[index].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }

            current = index;
        }

        if (prevBtn) prevBtn.addEventListener('click', function (e) { e.preventDefault(); goTo(current - 1); });
        if (nextBtn) nextBtn.addEventListener('click', function (e) { e.preventDefault(); goTo(current + 1); });

        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function (e) {
                e.preventDefault();
                goTo(parseInt(this.dataset.index, 10));
            });
        });

        // Swipe táctil
        var startX = 0;
        var threshold = 50;
        galeria.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
        galeria.addEventListener('touchend', function (e) {
            var diff = startX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > threshold) goTo(current + (diff > 0 ? 1 : -1));
        }, { passive: true });

        // Teclado (cuando la galería tiene foco)
        galeria.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft')  goTo(current - 1);
            if (e.key === 'ArrowRight') goTo(current + 1);
        });
    }

    function init() {
        document.querySelectorAll('.welow-galeria-ficha').forEach(initGaleria);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
