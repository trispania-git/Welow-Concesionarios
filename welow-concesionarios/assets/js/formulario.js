/**
 * Welow Concesionarios — Envío AJAX del formulario.
 * @since 2.30.0
 */
( function () {
    'use strict';

    function init( form ) {
        var msgEl = form.querySelector( '.welow-form__mensaje' );
        var submitBtn = form.querySelector( '.welow-form__submit' );
        if ( form.dataset.welowInit === '1' ) return;
        form.dataset.welowInit = '1';

        form.addEventListener( 'submit', function ( e ) {
            e.preventDefault();
            limpiarErrores( form, msgEl );

            // Validación nativa
            if ( ! form.checkValidity() ) {
                form.reportValidity();
                marcarInvalidos( form );
                return;
            }

            form.classList.add( 'is-loading' );
            submitBtn.disabled = true;

            var fd = new FormData( form );
            fd.append( 'action', welowFormCfg.action );

            fetch( welowFormCfg.ajax_url, {
                method: 'POST',
                body:   fd,
                credentials: 'same-origin',
            } )
            .then( function ( r ) { return r.json(); } )
            .then( function ( res ) {
                form.classList.remove( 'is-loading' );
                submitBtn.disabled = false;

                if ( res && res.success ) {
                    msgEl.className = 'welow-form__mensaje is-success';
                    msgEl.textContent = ( res.data && res.data.mensaje ) ? res.data.mensaje : 'Enviado correctamente.';
                    form.reset();
                    if ( res.data && res.data.redirect ) {
                        setTimeout( function () { window.location.href = res.data.redirect; }, 800 );
                    }
                } else {
                    msgEl.className = 'welow-form__mensaje is-error';
                    msgEl.textContent = ( res && res.data && res.data.mensaje ) ? res.data.mensaje : 'Error al enviar. Inténtalo de nuevo.';
                }
            } )
            .catch( function () {
                form.classList.remove( 'is-loading' );
                submitBtn.disabled = false;
                msgEl.className = 'welow-form__mensaje is-error';
                msgEl.textContent = 'Error de conexión. Comprueba tu red.';
            } );
        } );

        // Quitar marca de error al modificar el campo
        form.querySelectorAll( 'input, textarea, select' ).forEach( function ( el ) {
            el.addEventListener( 'input', function () {
                var campo = el.closest( '.welow-form__campo' );
                if ( campo ) campo.classList.remove( 'has-error' );
            } );
        } );
    }

    function limpiarErrores( form, msgEl ) {
        msgEl.className = 'welow-form__mensaje';
        msgEl.textContent = '';
        form.querySelectorAll( '.welow-form__campo.has-error' ).forEach( function ( c ) {
            c.classList.remove( 'has-error' );
        } );
    }

    function marcarInvalidos( form ) {
        form.querySelectorAll( ':invalid' ).forEach( function ( el ) {
            var campo = el.closest( '.welow-form__campo' );
            if ( campo ) campo.classList.add( 'has-error' );
        } );
    }

    function initAll() {
        document.querySelectorAll( '[data-welow-form]' ).forEach( init );
    }

    if ( document.readyState !== 'loading' ) {
        initAll();
    } else {
        document.addEventListener( 'DOMContentLoaded', initAll );
    }
} )();
