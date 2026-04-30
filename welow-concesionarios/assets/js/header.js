/**
 * Cabecera del sitio: toggle del menú hamburger en móvil.
 * Vanilla JS, sin dependencias.
 *
 * @since 2.6.0
 */
(function () {
    'use strict';

    function initHeader(header) {
        var hamburger = header.querySelector('.welow-header__hamburger');
        var overlay = header.querySelector('.welow-header__overlay');
        if (!hamburger || !overlay) return;

        function abrir() {
            overlay.removeAttribute('hidden');
            // Esperar un tick para que la transición de opacity funcione
            requestAnimationFrame(function () {
                overlay.classList.add('is-open');
            });
            hamburger.setAttribute('aria-expanded', 'true');
            hamburger.setAttribute('aria-label', 'Cerrar menú');
            document.body.classList.add('welow-header-open');
        }

        function cerrar() {
            overlay.classList.remove('is-open');
            hamburger.setAttribute('aria-expanded', 'false');
            hamburger.setAttribute('aria-label', 'Abrir menú');
            document.body.classList.remove('welow-header-open');
            // Tras la transición, ocultar
            setTimeout(function () {
                if (!overlay.classList.contains('is-open')) {
                    overlay.setAttribute('hidden', '');
                }
            }, 280);
        }

        hamburger.addEventListener('click', function (e) {
            e.preventDefault();
            var open = hamburger.getAttribute('aria-expanded') === 'true';
            if (open) cerrar(); else abrir();
        });

        // Cerrar al pulsar un enlace del overlay
        overlay.addEventListener('click', function (e) {
            if (e.target.tagName === 'A' && !e.target.closest('.welow-header__overlay-tel')) {
                cerrar();
            }
        });

        // Cerrar con Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && hamburger.getAttribute('aria-expanded') === 'true') {
                cerrar();
            }
        });

        // Cerrar al cambiar a desktop
        var mq = window.matchMedia('(min-width: 981px)');
        function onMq(e) {
            if (e.matches && hamburger.getAttribute('aria-expanded') === 'true') {
                cerrar();
            }
        }
        if (mq.addEventListener) mq.addEventListener('change', onMq);
        else if (mq.addListener) mq.addListener(onMq); // Safari < 14
    }

    /**
     * v2.6.2 — Maneja el spacer del header sticky.
     * Como cambiamos position: sticky → position: fixed, necesitamos
     * un elemento "spacer" que ocupe el hueco que el header dejaría
     * vacío arriba (porque fixed se sale del flujo normal del DOM).
     */
    function setupStickySpacer(header) {
        if (!header.classList.contains('welow-header--sticky')) return;

        var spacer = header.nextElementSibling;
        if (!spacer || !spacer.classList || !spacer.classList.contains('welow-header-spacer')) {
            spacer = document.createElement('div');
            spacer.className = 'welow-header-spacer';
            spacer.setAttribute('aria-hidden', 'true');
            header.parentNode.insertBefore(spacer, header.nextSibling);
        }

        function actualizar() {
            // offsetHeight incluye padding y border. Para sticky con admin bar,
            // sumamos el offset top porque el header fixed empieza en top:32px (o 46px móvil)
            var h = header.offsetHeight;
            spacer.style.height = h + 'px';
        }

        actualizar();

        // Recalcular en resize y tras cargar imágenes (cambia altura del logo)
        var resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(actualizar, 100);
        });

        // Recalcular tras cargar el logo (si la imagen aún no había cargado)
        var img = header.querySelector('.welow-header__logo-img');
        if (img && !img.complete) {
            img.addEventListener('load', actualizar);
        }

        // Recalcular tras carga completa de la página
        window.addEventListener('load', actualizar);
    }

    function init() {
        document.querySelectorAll('.welow-header').forEach(function(header) {
            initHeader(header);
            setupStickySpacer(header);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
