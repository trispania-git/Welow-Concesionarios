/**
 * Lightbox de la galería del concesionario.
 * Vanilla JS, sin dependencias.
 * @since 2.27.0
 */
( function () {
    'use strict';

    function buildLightbox() {
        var lb = document.createElement( 'div' );
        lb.className = 'welow-conc-lightbox';
        lb.innerHTML =
            '<button type="button" class="welow-conc-lightbox__btn welow-conc-lightbox__btn--close" aria-label="Cerrar">×</button>' +
            '<button type="button" class="welow-conc-lightbox__btn welow-conc-lightbox__btn--prev" aria-label="Anterior">‹</button>' +
            '<img class="welow-conc-lightbox__img" alt="" />' +
            '<button type="button" class="welow-conc-lightbox__btn welow-conc-lightbox__btn--next" aria-label="Siguiente">›</button>' +
            '<div class="welow-conc-lightbox__counter"></div>';
        document.body.appendChild( lb );
        return lb;
    }

    function init( grid ) {
        var items = Array.prototype.slice.call( grid.querySelectorAll( '.welow-conc-galeria__item' ) );
        if ( ! items.length ) return;

        var lb = document.querySelector( '.welow-conc-lightbox' ) || buildLightbox();
        var img      = lb.querySelector( '.welow-conc-lightbox__img' );
        var btnClose = lb.querySelector( '.welow-conc-lightbox__btn--close' );
        var btnPrev  = lb.querySelector( '.welow-conc-lightbox__btn--prev' );
        var btnNext  = lb.querySelector( '.welow-conc-lightbox__btn--next' );
        var counter  = lb.querySelector( '.welow-conc-lightbox__counter' );

        var current = 0;

        function show( i ) {
            current = ( i + items.length ) % items.length;
            var src = items[ current ].getAttribute( 'data-full' );
            img.src = src;
            counter.textContent = ( current + 1 ) + ' / ' + items.length;
        }

        function open( i ) {
            show( i );
            lb.classList.add( 'is-open' );
            document.body.style.overflow = 'hidden';
        }
        function close() {
            lb.classList.remove( 'is-open' );
            document.body.style.overflow = '';
        }

        items.forEach( function ( item, idx ) {
            item.addEventListener( 'click', function () { open( idx ); } );
        } );

        btnClose.addEventListener( 'click', close );
        btnPrev .addEventListener( 'click', function () { show( current - 1 ); } );
        btnNext .addEventListener( 'click', function () { show( current + 1 ); } );

        lb.addEventListener( 'click', function ( e ) {
            if ( e.target === lb ) close();
        } );

        document.addEventListener( 'keydown', function ( e ) {
            if ( ! lb.classList.contains( 'is-open' ) ) return;
            if ( e.key === 'Escape' )      close();
            if ( e.key === 'ArrowLeft' )   show( current - 1 );
            if ( e.key === 'ArrowRight' )  show( current + 1 );
        } );
    }

    function initAll() {
        document.querySelectorAll( '[data-welow-conc-galeria]' ).forEach( init );
    }

    if ( document.readyState !== 'loading' ) {
        initAll();
    } else {
        document.addEventListener( 'DOMContentLoaded', initAll );
    }
} )();
