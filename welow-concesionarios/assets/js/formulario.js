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

            // v2.53.0 — Si hay reCAPTCHA v3 configurado, pedir token antes de enviar
            getRecaptchaToken().then( function ( token ) {
                enviar( form, msgEl, submitBtn, token );
            } ).catch( function () {
                // Si falla obtener token (ej. usuario sin red, bloqueador), enviamos sin él
                // y el backend lo rechazará si está obligado.
                enviar( form, msgEl, submitBtn, '' );
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

    function enviar( form, msgEl, submitBtn, recaptchaToken ) {
        var fd = new FormData( form );
        fd.append( 'action', welowFormCfg.action );
        if ( recaptchaToken ) {
            fd.append( 'welow_recaptcha_token', recaptchaToken );
        }

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
    }

    function getRecaptchaToken() {
        // v2.53.0 — Devuelve una Promise con el token de reCAPTCHA v3 o '' si no procede
        return new Promise( function ( resolve, reject ) {
            var siteKey = welowFormCfg && welowFormCfg.recaptcha_site_key;
            var action  = ( welowFormCfg && welowFormCfg.recaptcha_action ) || 'welow_form';
            if ( ! siteKey || typeof grecaptcha === 'undefined' ) {
                resolve( '' );
                return;
            }
            grecaptcha.ready( function () {
                grecaptcha.execute( siteKey, { action: action } )
                    .then( function ( token ) { resolve( token || '' ); } )
                    .catch( function () { reject(); } );
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
