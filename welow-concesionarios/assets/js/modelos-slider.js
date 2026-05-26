/**
 * Mini-slider de galería del modelo (card en [welow_modelos]).
 * - Scroll-snap nativo + dots/flechas con vanilla JS.
 * - Mantiene los dots sincronizados con el scroll del usuario (incluyendo swipe móvil).
 *
 * @since 2.24.0
 */
( function () {
    'use strict';

    function init( slider ) {
        var track = slider.querySelector( '.welow-modelo-slider__track' );
        var dots  = slider.querySelectorAll( '.welow-modelo-slider__dot' );
        var prev  = slider.querySelector( '.welow-modelo-slider__nav--prev' );
        var next  = slider.querySelector( '.welow-modelo-slider__nav--next' );
        if ( ! track || ! dots.length ) return;

        function getIndex() {
            var w = track.clientWidth || 1;
            return Math.round( track.scrollLeft / w );
        }

        function scrollTo( idx ) {
            var w = track.clientWidth;
            track.scrollTo( { left: idx * w, behavior: 'smooth' } );
        }

        function updateDots() {
            var idx = getIndex();
            dots.forEach( function ( d, i ) {
                d.classList.toggle( 'is-active', i === idx );
            } );
        }

        dots.forEach( function ( dot ) {
            dot.addEventListener( 'click', function () {
                scrollTo( parseInt( dot.getAttribute( 'data-index' ) || '0', 10 ) );
            } );
        } );

        if ( prev ) prev.addEventListener( 'click', function () {
            var i = Math.max( 0, getIndex() - 1 );
            scrollTo( i );
        } );
        if ( next ) next.addEventListener( 'click', function () {
            var i = Math.min( dots.length - 1, getIndex() + 1 );
            scrollTo( i );
        } );

        // Sincronizar dots con scroll/swipe (throttle simple via requestAnimationFrame)
        var ticking = false;
        track.addEventListener( 'scroll', function () {
            if ( ! ticking ) {
                window.requestAnimationFrame( function () {
                    updateDots();
                    ticking = false;
                } );
                ticking = true;
            }
        } );
    }

    function initAll() {
        document.querySelectorAll( '[data-welow-slider]' ).forEach( init );
    }

    if ( document.readyState !== 'loading' ) {
        initAll();
    } else {
        document.addEventListener( 'DOMContentLoaded', initAll );
    }
} )();
