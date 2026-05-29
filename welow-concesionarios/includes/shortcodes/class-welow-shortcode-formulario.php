<?php
/**
 * Shortcode: [welow_formulario id="X"] o [welow_formulario slug="Y"]
 * Renderiza un formulario configurado en el CPT welow_formulario.
 *
 * Captura contexto automáticamente: coche/modelo/concesionario actual + UTMs
 * + referrer + IP, todo asociado al lead generado al enviar.
 *
 * @since 2.30.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Formulario {

    const AJAX_ACTION = 'welow_form_submit';
    const NONCE_KEY   = 'welow_form_submit_nonce';

    public static function init() {
        add_shortcode( 'welow_formulario', array( __CLASS__, 'render' ) );
        add_action( 'wp_ajax_'        . self::AJAX_ACTION, array( __CLASS__, 'procesar' ) );
        add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( __CLASS__, 'procesar' ) );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'id'    => '',
            'slug'  => '',
            'clase' => '',
        ), $atts );

        $form_id = self::resolver_form_id( $atts );
        if ( ! $form_id ) {
            return '<!-- [welow_formulario]: no se encontró formulario -->';
        }

        $form = get_post( $form_id );
        if ( ! $form || Welow_CPT_Formulario::POST_TYPE !== $form->post_type ) {
            return '<!-- [welow_formulario]: ID inválido -->';
        }

        $campos = Welow_CPT_Formulario::get_campos( $form_id );
        if ( empty( $campos ) ) {
            return '<!-- [welow_formulario]: sin campos definidos -->';
        }

        wp_enqueue_style( 'welow-formulario' );
        wp_enqueue_script( 'welow-formulario' );
        wp_localize_script( 'welow-formulario', 'welowFormCfg', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'action'   => self::AJAX_ACTION,
        ) );

        $titulo      = get_post_meta( $form_id, '_welow_form_titulo_publico', true );
        $boton_texto = get_post_meta( $form_id, '_welow_form_boton_texto', true ) ?: 'Enviar';
        $consent     = get_post_meta( $form_id, '_welow_form_consent_texto', true );
        $politica    = get_post_meta( $form_id, '_welow_form_politica_url', true );

        // Sustituir {politica} en el texto de consentimiento
        if ( $politica ) {
            $consent = str_replace( '{politica}', esc_url( $politica ), $consent );
        }

        // Contexto auto-detectado
        $contexto = self::detectar_contexto();

        ob_start();
        ?>
        <form class="welow-form <?php echo esc_attr( $atts['clase'] ); ?>"
              data-welow-form
              data-form-id="<?php echo intval( $form_id ); ?>"
              method="post" novalidate>

            <?php if ( $titulo ) : ?>
                <h3 class="welow-form__titulo"><?php echo esc_html( $titulo ); ?></h3>
            <?php endif; ?>

            <div class="welow-form__campos">
                <?php foreach ( $campos as $campo ) : self::render_campo( $campo ); endforeach; ?>
            </div>

            <?php // Consentimiento RGPD ?>
            <?php if ( $consent ) : ?>
                <div class="welow-form__campo welow-form__campo--consent">
                    <label>
                        <input type="checkbox" name="welow_consent" required />
                        <span><?php echo wp_kses_post( $consent ); ?></span>
                    </label>
                </div>
            <?php endif; ?>

            <?php // Honeypot ?>
            <div style="position:absolute;left:-9999px;height:0;overflow:hidden;" aria-hidden="true">
                <label>Sitio web (déjalo vacío)</label>
                <input type="text" name="welow_website" tabindex="-1" autocomplete="off" />
            </div>

            <?php // Hidden contexto ?>
            <input type="hidden" name="welow_form_id"  value="<?php echo intval( $form_id ); ?>" />
            <input type="hidden" name="welow_nonce"    value="<?php echo esc_attr( wp_create_nonce( self::NONCE_KEY ) ); ?>" />
            <input type="hidden" name="welow_ts"       value="<?php echo intval( time() ); ?>" />
            <input type="hidden" name="welow_url"      value="<?php echo esc_url( self::current_url() ); ?>" />
            <input type="hidden" name="welow_contexto" value="<?php echo esc_attr( wp_json_encode( $contexto, JSON_UNESCAPED_UNICODE ) ); ?>" />

            <button type="submit" class="welow-form__submit">
                <span class="welow-form__submit-text"><?php echo esc_html( $boton_texto ); ?></span>
                <span class="welow-form__submit-spinner" aria-hidden="true"></span>
            </button>

            <div class="welow-form__mensaje" role="alert" aria-live="polite"></div>
        </form>
        <?php
        return ob_get_clean();
    }

    /* =====================================================================
     * Render individual de un campo
     * ===================================================================== */
    private static function render_campo( $campo ) {
        $type    = $campo['type'] ?? 'texto';
        $name    = $campo['name'] ?? '';
        $label   = $campo['label'] ?? '';
        $req     = ! empty( $campo['required'] );
        $req_str = $req ? ' required' : '';
        $req_html = $req ? ' <span class="welow-form__req">*</span>' : '';
        if ( ! $name ) return;

        if ( 'oculto' === $type ) {
            echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="" />';
            return;
        }

        echo '<div class="welow-form__campo welow-form__campo--' . esc_attr( $type ) . '">';
        echo '<label class="welow-form__label">' . esc_html( $label ) . $req_html . '</label>';

        switch ( $type ) {
            case 'email':
                echo '<input type="email" name="' . esc_attr( $name ) . '"' . $req_str . ' />';
                break;
            case 'telefono':
                echo '<input type="tel" name="' . esc_attr( $name ) . '" inputmode="tel" pattern="[\d\s\+\-]{6,}"' . $req_str . ' />';
                break;
            case 'textarea':
                echo '<textarea name="' . esc_attr( $name ) . '" rows="4"' . $req_str . '></textarea>';
                break;
            case 'select':
                $opts = self::parsear_opciones( $campo['opciones'] ?? '' );
                echo '<select name="' . esc_attr( $name ) . '"' . $req_str . '>';
                echo '<option value="">— Selecciona —</option>';
                foreach ( $opts as $opt ) {
                    echo '<option value="' . esc_attr( $opt ) . '">' . esc_html( $opt ) . '</option>';
                }
                echo '</select>';
                break;
            case 'radio':
                $opts = self::parsear_opciones( $campo['opciones'] ?? '' );
                echo '<div class="welow-form__opciones">';
                foreach ( $opts as $opt ) {
                    echo '<label><input type="radio" name="' . esc_attr( $name ) . '" value="' . esc_attr( $opt ) . '"' . $req_str . ' /> ' . esc_html( $opt ) . '</label>';
                }
                echo '</div>';
                break;
            case 'checkbox':
                $opts = self::parsear_opciones( $campo['opciones'] ?? '' );
                if ( count( $opts ) <= 1 ) {
                    // checkbox simple
                    $val = $opts[0] ?? '1';
                    echo '<label><input type="checkbox" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '"' . $req_str . ' /> ' . esc_html( $val ) . '</label>';
                } else {
                    // múltiples
                    echo '<div class="welow-form__opciones">';
                    foreach ( $opts as $opt ) {
                        echo '<label><input type="checkbox" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( $opt ) . '" /> ' . esc_html( $opt ) . '</label>';
                    }
                    echo '</div>';
                }
                break;
            case 'texto':
            default:
                echo '<input type="text" name="' . esc_attr( $name ) . '"' . $req_str . ' />';
                break;
        }
        echo '</div>';
    }

    private static function parsear_opciones( $raw ) {
        return array_values( array_filter( array_map( 'trim', explode( '|', $raw ) ) ) );
    }

    /* =====================================================================
     * PROCESAR ENVÍO (AJAX)
     * ===================================================================== */
    public static function procesar() {
        // Nonce
        if ( ! isset( $_POST['welow_nonce'] ) || ! wp_verify_nonce( $_POST['welow_nonce'], self::NONCE_KEY ) ) {
            wp_send_json_error( array( 'mensaje' => 'Sesión expirada. Recarga la página.' ), 400 );
        }

        // Honeypot
        if ( ! empty( $_POST['welow_website'] ) ) {
            wp_send_json_error( array( 'mensaje' => 'Detección de bot.' ), 400 );
        }

        // Timing (al menos 2 segundos entre carga y envío)
        $ts = intval( $_POST['welow_ts'] ?? 0 );
        if ( $ts > 0 && ( time() - $ts ) < 2 ) {
            wp_send_json_error( array( 'mensaje' => 'Envío demasiado rápido.' ), 400 );
        }

        // Formulario
        $form_id = intval( $_POST['welow_form_id'] ?? 0 );
        $form    = $form_id ? get_post( $form_id ) : null;
        if ( ! $form || Welow_CPT_Formulario::POST_TYPE !== $form->post_type ) {
            wp_send_json_error( array( 'mensaje' => 'Formulario inválido.' ), 400 );
        }

        // Consentimiento RGPD
        $consent_texto = get_post_meta( $form_id, '_welow_form_consent_texto', true );
        if ( $consent_texto && empty( $_POST['welow_consent'] ) ) {
            wp_send_json_error( array( 'mensaje' => 'Debes aceptar la política de privacidad.' ), 400 );
        }

        // Recoger y validar campos
        $campos = Welow_CPT_Formulario::get_campos( $form_id );
        $datos  = array();
        foreach ( $campos as $c ) {
            $name = $c['name'] ?? '';
            $type = $c['type'] ?? 'texto';
            if ( ! $name ) continue;
            $valor = $_POST[ $name ] ?? '';
            // Validación required
            if ( ! empty( $c['required'] ) && self::vacio( $valor ) ) {
                wp_send_json_error( array( 'mensaje' => 'Falta el campo "' . $c['label'] . '".' ), 400 );
            }
            // Sanitización por tipo
            if ( 'email' === $type ) {
                $valor = sanitize_email( $valor );
                if ( $valor && ! is_email( $valor ) ) {
                    wp_send_json_error( array( 'mensaje' => 'Email inválido.' ), 400 );
                }
            } elseif ( 'textarea' === $type ) {
                $valor = sanitize_textarea_field( wp_unslash( $valor ) );
            } elseif ( in_array( $type, array( 'select', 'radio' ), true ) ) {
                $valor = sanitize_text_field( wp_unslash( $valor ) );
            } elseif ( 'checkbox' === $type ) {
                if ( is_array( $valor ) ) {
                    $valor = array_map( 'sanitize_text_field', wp_unslash( $valor ) );
                } else {
                    $valor = sanitize_text_field( wp_unslash( $valor ) );
                }
            } else {
                $valor = sanitize_text_field( wp_unslash( $valor ) );
            }
            $datos[ $name ] = $valor;
        }

        // Contexto auto-detectado del cliente
        $contexto_raw = isset( $_POST['welow_contexto'] ) ? wp_unslash( $_POST['welow_contexto'] ) : '{}';
        $contexto = json_decode( $contexto_raw, true );
        if ( ! is_array( $contexto ) ) $contexto = array();
        $contexto = array_map( 'sanitize_text_field', $contexto );

        // Crear lead
        $titulo_lead = self::generar_titulo_lead( $datos, $form->post_title );
        $lead_id = wp_insert_post( array(
            'post_type'   => Welow_CPT_Lead::POST_TYPE,
            'post_status' => 'publish',
            'post_title'  => $titulo_lead,
        ), true );

        if ( is_wp_error( $lead_id ) ) {
            wp_send_json_error( array( 'mensaje' => 'Error guardando lead.' ), 500 );
        }

        $url_origen = isset( $_POST['welow_url'] ) ? esc_url_raw( wp_unslash( $_POST['welow_url'] ) ) : '';
        $referrer   = wp_get_referer();

        update_post_meta( $lead_id, '_welow_lead_form_id',    $form_id );
        // v2.33.1 — JSON_UNESCAPED_UNICODE para preservar acentos (é, ó, ñ, í...)
        update_post_meta( $lead_id, '_welow_lead_datos',      wp_json_encode( $datos, JSON_UNESCAPED_UNICODE ) );
        update_post_meta( $lead_id, '_welow_lead_contexto',   wp_json_encode( $contexto, JSON_UNESCAPED_UNICODE ) );
        update_post_meta( $lead_id, '_welow_lead_url_origen', $url_origen );
        update_post_meta( $lead_id, '_welow_lead_referrer',   $referrer ?: '' );
        update_post_meta( $lead_id, '_welow_lead_ip',         self::client_ip() );
        update_post_meta( $lead_id, '_welow_lead_user_agent', sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
        update_post_meta( $lead_id, '_welow_lead_estado',     'nuevo' );

        // Enviar notificaciones
        self::enviar_notificacion( $form, $form_id, $datos, $contexto, $url_origen, $lead_id );

        // Respuesta
        $mensaje_ok = get_post_meta( $form_id, '_welow_form_mensaje_ok', true ) ?: 'Gracias por tu mensaje.';
        $redirect   = get_post_meta( $form_id, '_welow_form_redirect', true );

        wp_send_json_success( array(
            'mensaje'  => $mensaje_ok,
            'redirect' => $redirect ?: '',
        ) );
    }

    /* =====================================================================
     * Helpers
     * ===================================================================== */
    private static function vacio( $v ) {
        if ( is_array( $v ) ) return count( array_filter( $v, function( $x ) { return '' !== trim( (string) $x ); } ) ) === 0;
        return '' === trim( (string) $v );
    }

    private static function resolver_form_id( $atts ) {
        if ( ! empty( $atts['id'] ) && is_numeric( $atts['id'] ) ) return intval( $atts['id'] );
        if ( ! empty( $atts['slug'] ) ) {
            $p = get_page_by_path( sanitize_title( $atts['slug'] ), OBJECT, Welow_CPT_Formulario::POST_TYPE );
            return $p ? $p->ID : 0;
        }
        return 0;
    }

    private static function detectar_contexto() {
        $ctx = array();
        // Coche actual
        if ( method_exists( 'Welow_Helpers', 'get_current_coche_id' ) ) {
            $cid = Welow_Helpers::get_current_coche_id();
            if ( $cid ) {
                $ctx['coche_id'] = $cid;
                // Modelo del coche nuevo
                $mid = get_post_meta( $cid, '_welow_coche_modelo', true );
                if ( $mid ) $ctx['modelo_id'] = intval( $mid );
            }
        }
        // Modelo si single de modelo
        global $post;
        if ( $post instanceof WP_Post ) {
            if ( 'welow_modelo' === $post->post_type ) {
                $ctx['modelo_id'] = $post->ID;
            } elseif ( 'welow_concesionario' === $post->post_type ) {
                $ctx['concesionario_id'] = $post->ID;
            } elseif ( 'welow_marca' === $post->post_type ) {
                $ctx['marca_id'] = $post->ID;
            }
        }
        // Si tenemos modelo, derivar marca
        if ( ! empty( $ctx['modelo_id'] ) && empty( $ctx['marca_id'] ) ) {
            $marca_id = get_post_meta( $ctx['modelo_id'], '_welow_modelo_marca', true );
            if ( $marca_id ) $ctx['marca_id'] = intval( $marca_id );
        }
        // UTMs (de la query string al cargar el formulario)
        foreach ( array( 'utm_source','utm_medium','utm_campaign','utm_term','utm_content' ) as $utm ) {
            if ( isset( $_GET[ $utm ] ) && $_GET[ $utm ] !== '' ) {
                $ctx[ $utm ] = sanitize_text_field( wp_unslash( $_GET[ $utm ] ) );
            }
        }
        return $ctx;
    }

    private static function generar_titulo_lead( $datos, $form_titulo ) {
        $nombre = $datos['nombre'] ?? '';
        $email  = $datos['email'] ?? '';
        $base   = $nombre ?: $email ?: 'Lead';
        return $base . ' — ' . $form_titulo;
    }

    private static function enviar_notificacion( $form, $form_id, $datos, $contexto, $url_origen, $lead_id ) {
        $emails = Welow_CPT_Formulario::get_emails_notificacion( $form_id );
        if ( empty( $emails ) ) return;

        $asunto_tpl = get_post_meta( $form_id, '_welow_form_email_asunto', true ) ?: 'Nuevo lead recibido — {sitio}';
        $reemplazos = array(
            '{sitio}'      => get_bloginfo( 'name' ),
            '{formulario}' => $form->post_title,
            '{nombre}'     => $datos['nombre'] ?? '(sin nombre)',
        );
        $asunto = strtr( $asunto_tpl, $reemplazos );

        $cuerpo  = '<p>Has recibido un nuevo lead en <strong>' . esc_html( get_bloginfo( 'name' ) ) . '</strong>.</p>';
        $cuerpo .= '<p><strong>Formulario:</strong> ' . esc_html( $form->post_title ) . '</p>';
        $cuerpo .= '<table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;font-family:Arial;font-size:13px;">';
        foreach ( $datos as $k => $v ) {
            $val = is_array( $v ) ? esc_html( implode( ', ', $v ) ) : nl2br( esc_html( $v ) );
            $cuerpo .= '<tr><th align="left" style="background:#f5f5f5;">' . esc_html( $k ) . '</th><td>' . $val . '</td></tr>';
        }
        $cuerpo .= '</table>';

        if ( $url_origen ) {
            $cuerpo .= '<p style="margin-top:18px;"><strong>Página de origen:</strong> <a href="' . esc_url( $url_origen ) . '">' . esc_html( $url_origen ) . '</a></p>';
        }
        // Contexto detectado
        if ( ! empty( $contexto ) ) {
            $cuerpo .= '<p style="margin-top:14px;"><strong>Contexto:</strong></p><ul>';
            foreach ( $contexto as $k => $v ) {
                if ( '' === $v ) continue;
                $label = $k;
                if ( in_array( $k, array( 'coche_id','modelo_id','concesionario_id','marca_id' ), true ) ) {
                    $p = get_post( intval( $v ) );
                    if ( $p ) { $label = $k; $v = $p->post_title; }
                }
                $cuerpo .= '<li><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $v ) . '</li>';
            }
            $cuerpo .= '</ul>';
        }
        $cuerpo .= '<p style="margin-top:20px;font-size:12px;color:#666;">Lead ID: <a href="' . esc_url( admin_url( 'post.php?post=' . $lead_id . '&action=edit' ) ) . '">' . intval( $lead_id ) . '</a></p>';

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
        );
        // Reply-to al email del cliente si lo proporcionó
        if ( ! empty( $datos['email'] ) ) {
            $headers[] = 'Reply-To: ' . sanitize_email( $datos['email'] );
        }

        wp_mail( $emails, $asunto, $cuerpo, $headers );
    }

    private static function client_ip() {
        foreach ( array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ) as $key ) {
            if ( empty( $_SERVER[ $key ] ) ) continue;
            $ip = trim( explode( ',', $_SERVER[ $key ] )[0] );
            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) return $ip;
        }
        return '';
    }

    private static function current_url() {
        $scheme = is_ssl() ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? '';
        $uri    = $_SERVER['REQUEST_URI'] ?? '';
        return $scheme . '://' . $host . $uri;
    }
}
