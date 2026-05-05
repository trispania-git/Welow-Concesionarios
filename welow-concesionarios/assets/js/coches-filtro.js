/**
 * Página de filtros de coches: drawer móvil + autosubmit de orden.
 * @since 2.8.0
 */
(function () {
    'use strict';

    function initFiltro(container) {
        if (container.dataset.welowInit) return;
        container.dataset.welowInit = '1';

        var toggle   = container.querySelector('.welow-cf__movil-toggle');
        var cerrar   = container.querySelector('.welow-cf__cerrar');
        var backdrop = container.querySelector('.welow-cf__backdrop');
        var orden    = container.querySelector('[data-welow-autosubmit]');
        var form     = container.querySelector('.welow-cf__form');

        function abrirDrawer() {
            document.body.classList.add('welow-cf-drawer-open');
            if (toggle) toggle.setAttribute('aria-expanded', 'true');
        }
        function cerrarDrawer() {
            document.body.classList.remove('welow-cf-drawer-open');
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
        }

        if (toggle) toggle.addEventListener('click', function (e) {
            e.preventDefault();
            abrirDrawer();
        });
        if (cerrar)   cerrar.addEventListener('click', cerrarDrawer);
        if (backdrop) backdrop.addEventListener('click', cerrarDrawer);

        // Cerrar con Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && document.body.classList.contains('welow-cf-drawer-open')) {
                cerrarDrawer();
            }
        });

        /* ====================================================================
           v2.8.1 — Autosubmit del formulario al cambiar cualquier filtro
           ==================================================================== */
        if (form) {
            // Helper: enviar el form garantizando que la página vuelve a 1
            function autosubmit() {
                // Reset de paginación al cambiar un filtro
                var pagedInput = form.querySelector('input[name="welow_paged"]');
                if (pagedInput) pagedInput.value = 1;

                // Indicador visual de "cargando"
                container.classList.add('welow-cf--loading');
                form.submit();
            }

            // Debounce para inputs number (precio, km, año, cv) que escriben con teclado
            var debounceTimer;
            function autosubmitDebounced(ms) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(autosubmit, ms || 700);
            }

            // Checkboxes, radios y selects → submit inmediato al cambiar
            form.querySelectorAll('input[type="checkbox"], input[type="radio"], select').forEach(function (el) {
                el.addEventListener('change', autosubmit);
            });

            // Inputs number → debounce 700ms tras dejar de escribir
            form.querySelectorAll('input[type="number"]').forEach(function (el) {
                el.addEventListener('input', function () { autosubmitDebounced(700); });
                // Y también submit al perder el foco (más rápido si el usuario salta)
                el.addEventListener('blur', function () {
                    clearTimeout(debounceTimer);
                    if (el.value !== el.defaultValue) autosubmit();
                });
            });
        }

        // Autosubmit del selector de orden (estaba ya, ahora también
        // sincroniza la pagina actual a 1)
        if (orden && form) {
            orden.addEventListener('change', function () {
                var hidden = form.querySelector('input[name="welow_orden"]');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'welow_orden';
                    form.appendChild(hidden);
                }
                hidden.value = orden.value;
                container.classList.add('welow-cf--loading');
                form.submit();
            });
        }

        // Cerrar drawer al cambiar a desktop
        var mq = window.matchMedia('(min-width: 981px)');
        var onMq = function (e) {
            if (e.matches) cerrarDrawer();
        };
        if (mq.addEventListener) mq.addEventListener('change', onMq);
        else if (mq.addListener) mq.addListener(onMq);
    }

    function init() {
        document.querySelectorAll('.welow-coches-filtro').forEach(initFiltro);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
