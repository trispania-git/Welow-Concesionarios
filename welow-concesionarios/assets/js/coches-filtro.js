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

        // Autosubmit del selector de orden
        if (orden && form) {
            orden.addEventListener('change', function () {
                // Sincronizar el valor con un input oculto en el form si no existe
                var hidden = form.querySelector('input[name="welow_orden"]');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'welow_orden';
                    form.appendChild(hidden);
                }
                hidden.value = orden.value;
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
