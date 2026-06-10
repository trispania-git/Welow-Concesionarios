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
            return self::msg_admin( 'No se ha encontrado el formulario. Has llamado con <code>id="' . esc_html( $atts['id'] ) . '" slug="' . esc_html( $atts['slug'] ) . '"</code>. Comprueba que el ID es correcto (cópialo desde el sidebar "Cómo usarlo" al editar el formulario) o pasa el slug exacto.' );
        }

        $form = get_post( $form_id );
        if ( ! $form || Welow_CPT_Formulario::POST_TYPE !== $form->post_type ) {
            return self::msg_admin( 'El ID "' . intval( $form_id ) . '" no corresponde a un formulario válido. Quizá fue borrado o cambiaste el ID.' );
        }

        if ( 'publish' !== $form->post_status ) {
            return self::msg_admin( 'El formulario "' . esc_html( $form->post_title ) . '" no está publicado (status: <code>' . esc_html( $form->post_status ) . '</code>). Publícalo desde Concesionarios → Formularios.' );
        }

        $campos = Welow_CPT_Formulario::get_campos( $form_id );
        if ( empty( $campos ) ) {
            return self::msg_admin( 'El formulario "' . esc_html( $form->post_title ) . '" no tiene campos definidos. Edita el formulario y añade al menos 1 campo.' );
        }

        wp_enqueue_style( 'welow-formulario' );
        wp_enqueue_script( 'welow-formulario' );

        // v2.53.0 — Cargar reCAPTCHA v3 si está configurado
        $recaptcha_site_key = '';
        if ( class_exists( 'Welow_Settings' ) ) {
            $opt_as = get_option( Welow_Settings::OPTION_KEY, array() );
            $opt_as = isset( $opt_as['antispam'] ) && is_array( $opt_as['antispam'] ) ? $opt_as['antispam'] : array();
            if ( ! empty( $opt_as['recaptcha_activo'] ) && ! empty( $opt_as['recaptcha_site_key'] ) ) {
                $recaptcha_site_key = $opt_as['recaptcha_site_key'];
                wp_enqueue_script(
                    'google-recaptcha-v3',
                    'https://www.google.com/recaptcha/api.js?render=' . urlencode( $recaptcha_site_key ),
                    array(), null, true
                );
            }
        }

        wp_localize_script( 'welow-formulario', 'welowFormCfg', array(
            'ajax_url'           => admin_url( 'admin-ajax.php' ),
            'action'             => self::AJAX_ACTION,
            'recaptcha_site_key' => $recaptcha_site_key,
            'recaptcha_action'   => 'welow_form',
        ) );

        $titulo      = get_post_meta( $form_id, '_welow_form_titulo_publico', true );
        $boton_texto = get_post_meta( $form_id, '_welow_form_boton_texto', true ) ?: 'Enviar';
        $consent     = get_post_meta( $form_id, '_welow_form_consent_texto', true );
        $politica    = get_post_meta( $form_id, '_welow_form_politica_url', true );

        // v2.40.0 — Fallback al RGPD global si el formulario no tiene texto/URL propios
        // v2.51.0 — Lectura del segundo consentimiento (marketing) opcional
        $marketing_activo = false;
        $marketing_texto  = '';
        if ( class_exists( 'Welow_Settings' ) ) {
            $opt_rgpd = get_option( Welow_Settings::OPTION_KEY, array() );
            $opt_rgpd = isset( $opt_rgpd['rgpd'] ) && is_array( $opt_rgpd['rgpd'] ) ? $opt_rgpd['rgpd'] : array();
            if ( ! $consent ) {
                $consent = $opt_rgpd['consent_texto'] ?? '';
                // Si tampoco hay global guardado, usar el texto recomendado por defecto
                if ( ! $consent && method_exists( 'Welow_Settings', 'get_rgpd_default_consent' ) ) {
                    $consent = Welow_Settings::get_rgpd_default_consent();
                }
            }
            if ( ! $politica ) {
                $politica = $opt_rgpd['politica_url'] ?? '';
            }
            // Segundo consentimiento (marketing)
            $marketing_activo = ! empty( $opt_rgpd['marketing_activo'] );
            if ( $marketing_activo ) {
                $marketing_texto = $opt_rgpd['marketing_texto'] ?? '';
                if ( ! $marketing_texto && method_exists( 'Welow_Settings', 'get_rgpd_default_marketing' ) ) {
                    $marketing_texto = Welow_Settings::get_rgpd_default_marketing();
                }
            }
        }

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

            <?php // Consentimiento RGPD (obligatorio) ?>
            <?php if ( $consent ) : ?>
                <div class="welow-form__campo welow-form__campo--consent">
                    <label>
                        <input type="checkbox" name="welow_consent" required />
                        <span><?php echo wp_kses_post( $consent ); ?></span>
                    </label>
                </div>
            <?php endif; ?>

            <?php // v2.51.0 — Segundo consentimiento (marketing, opcional) ?>
            <?php if ( $marketing_activo && $marketing_texto ) : ?>
                <div class="welow-form__campo welow-form__campo--consent welow-form__campo--marketing">
                    <label>
                        <input type="checkbox" name="welow_marketing_consent" value="1" />
                        <span><?php echo wp_kses_post( $marketing_texto ); ?></span>
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
            case 'fecha':
                // v2.37.0 — Fecha (HTML5 date picker nativo)
                $min = date( 'Y-m-d' ); // por defecto, no permitir fechas pasadas
                echo '<input type="date" name="' . esc_attr( $name ) . '" min="' . esc_attr( $min ) . '"' . $req_str . ' />';
                break;
            case 'hora':
                // v2.37.0 — Hora (HTML5 time picker)
                echo '<input type="time" name="' . esc_attr( $name ) . '"' . $req_str . ' />';
                break;
            case 'fecha_hora':
                // v2.37.0 — Fecha y hora combinadas
                $min = date( 'Y-m-d\TH:i' );
                echo '<input type="datetime-local" name="' . esc_attr( $name ) . '" min="' . esc_attr( $min ) . '"' . $req_str . ' />';
                break;
            case 'texto':
            default:
                echo '<input type="text" name="' . esc_attr( $name ) . '"' . $req_str . ' />';
                break;
        }
        echo '</div>';
    }

    private static function parsear_opciones( $raw ) {
        // v2.38.0 — Sustituir tokens dinámicos por listas reales (alfabéticas)
        $raw = self::expandir_tokens_dinamicos( $raw );
        return array_values( array_filter( array_map( 'trim', explode( '|', $raw ) ) ) );
    }

    /**
     * v2.38.0 — Expande tokens dinámicos en el campo "opciones":
     *   {marcas-oficiales}  → todas las welow_marca publicadas (alfabético)
     *   {marcas-externas}   → todos los términos welow_marca_externa
     *   {marcas-todas}      → unión sin duplicados
     *   {concesionarios}    → welow_concesionario publicados
     */
    private static function expandir_tokens_dinamicos( $raw ) {
        if ( false === strpos( $raw, '{' ) ) return $raw;

        $cargar_lista = function( $token ) {
            $items = array();
            switch ( $token ) {
                case 'marcas-oficiales':
                    $posts = get_posts( array(
                        'post_type'      => 'welow_marca',
                        'post_status'    => 'publish',
                        'posts_per_page' => -1,
                        'orderby'        => 'title',
                        'order'          => 'ASC',
                    ) );
                    foreach ( $posts as $p ) $items[] = $p->post_title;
                    break;
                case 'marcas-externas':
                    if ( taxonomy_exists( 'welow_marca_externa' ) ) {
                        $terms = get_terms( array(
                            'taxonomy'   => 'welow_marca_externa',
                            'hide_empty' => false,
                            'orderby'    => 'name',
                            'order'      => 'ASC',
                        ) );
                        if ( ! is_wp_error( $terms ) ) {
                            foreach ( $terms as $t ) $items[] = $t->name;
                        }
                    }
                    break;
                case 'marcas-todas':
                    $todas = array();
                    foreach ( get_posts( array( 'post_type' => 'welow_marca', 'post_status' => 'publish', 'posts_per_page' => -1 ) ) as $p ) {
                        $todas[ mb_strtolower( $p->post_title ) ] = $p->post_title;
                    }
                    if ( taxonomy_exists( 'welow_marca_externa' ) ) {
                        $terms = get_terms( array( 'taxonomy' => 'welow_marca_externa', 'hide_empty' => false ) );
                        if ( ! is_wp_error( $terms ) ) {
                            foreach ( $terms as $t ) $todas[ mb_strtolower( $t->name ) ] = $t->name;
                        }
                    }
                    ksort( $todas );
                    $items = array_values( $todas );
                    break;
                case 'concesionarios':
                    $posts = get_posts( array(
                        'post_type'      => 'welow_concesionario',
                        'post_status'    => 'publish',
                        'posts_per_page' => -1,
                        'orderby'        => 'title',
                        'order'          => 'ASC',
                    ) );
                    foreach ( $posts as $p ) $items[] = $p->post_title;
                    break;
            }
            return implode( '|', $items );
        };

        return preg_replace_callback(
            '/\{(marcas-oficiales|marcas-externas|marcas-todas|concesionarios)\}/',
            function( $m ) use ( $cargar_lista ) {
                return $cargar_lista( $m[1] );
            },
            $raw
        );
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

        // v2.53.0 — reCAPTCHA v3 (si está configurado)
        if ( class_exists( 'Welow_Settings' ) ) {
            $opt_as = get_option( Welow_Settings::OPTION_KEY, array() );
            $opt_as = isset( $opt_as['antispam'] ) && is_array( $opt_as['antispam'] ) ? $opt_as['antispam'] : array();
            if ( ! empty( $opt_as['recaptcha_activo'] ) && ! empty( $opt_as['recaptcha_secret_key'] ) ) {
                $rc_check = self::verificar_recaptcha(
                    $_POST['welow_recaptcha_token'] ?? '',
                    $opt_as['recaptcha_secret_key'],
                    floatval( $opt_as['recaptcha_score_min'] ?? 0.5 )
                );
                if ( is_wp_error( $rc_check ) ) {
                    wp_send_json_error( array( 'mensaje' => $rc_check->get_error_message() ), 400 );
                }
            }
        }

        // Formulario
        $form_id = intval( $_POST['welow_form_id'] ?? 0 );
        $form    = $form_id ? get_post( $form_id ) : null;
        if ( ! $form || Welow_CPT_Formulario::POST_TYPE !== $form->post_type ) {
            wp_send_json_error( array( 'mensaje' => 'Formulario inválido.' ), 400 );
        }

        // Consentimiento RGPD
        // v2.40.0 — Considerar el texto global como fallback (siempre habrá consentimiento)
        $consent_texto = get_post_meta( $form_id, '_welow_form_consent_texto', true );
        if ( ! $consent_texto && class_exists( 'Welow_Settings' ) ) {
            $opt = get_option( Welow_Settings::OPTION_KEY, array() );
            $consent_texto = $opt['rgpd']['consent_texto'] ?? '';
            if ( ! $consent_texto && method_exists( 'Welow_Settings', 'get_rgpd_default_consent' ) ) {
                $consent_texto = Welow_Settings::get_rgpd_default_consent();
            }
        }
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

        // v2.51.0 — Capturar consentimiento de marketing (opcional)
        $marketing_consent = ! empty( $_POST['welow_marketing_consent'] );
        update_post_meta( $lead_id, '_welow_lead_marketing_consent', $marketing_consent ? '1' : '0' );

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

        // v2.51.0 — Indicador de consentimiento marketing (si está activo el segundo checkbox)
        $marketing_consent = get_post_meta( $lead_id, '_welow_lead_marketing_consent', true );
        if ( '' !== $marketing_consent ) {
            $tick = ( '1' === $marketing_consent )
                ? '<span style="color:#16a34a;font-weight:700;">✓ SÍ acepta marketing</span>'
                : '<span style="color:#94a3b8;">✗ NO acepta marketing</span>';
            $cuerpo .= '<p style="margin-top:14px;"><strong>Marketing comercial:</strong> ' . $tick . '</p>';
        }

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

    /**
     * v2.53.0 — Verifica un token de reCAPTCHA v3 contra Google.
     *
     * @return true|WP_Error true si pasa, WP_Error con mensaje si falla
     */
    private static function verificar_recaptcha( $token, $secret_key, $score_min ) {
        $token = sanitize_text_field( wp_unslash( $token ) );
        if ( ! $token ) {
            return new WP_Error( 'recaptcha_missing', 'Falta token reCAPTCHA. Recarga la página e inténtalo de nuevo.' );
        }

        $response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
            'timeout' => 8,
            'body'    => array(
                'secret'   => $secret_key,
                'response' => $token,
                'remoteip' => self::client_ip(),
            ),
        ) );
        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'recaptcha_connect', 'No se pudo verificar reCAPTCHA. Inténtalo de nuevo.' );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $body ) || empty( $body['success'] ) ) {
            return new WP_Error( 'recaptcha_failed', 'Verificación reCAPTCHA fallida. Recarga e inténtalo de nuevo.' );
        }
        $score = isset( $body['score'] ) ? floatval( $body['score'] ) : 0;
        if ( $score < $score_min ) {
            return new WP_Error( 'recaptcha_low_score', 'Detectado tráfico sospechoso. Si eres una persona real, contáctanos por teléfono.' );
        }
        return true;
    }

    /**
     * v2.38.1 — Mensaje de diagnóstico visible SOLO a administradores logueados.
     * Para visitantes normales devuelve un comentario HTML invisible.
     */
    private static function msg_admin( $html ) {
        if ( current_user_can( 'manage_options' ) ) {
            return '<div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:12px 16px;margin:12px 0;border-radius:4px;font-family:system-ui;font-size:13px;color:#78350f;">'
                . '<strong>[welow_formulario]</strong> (visible solo a admins):<br>' . $html
                . '</div>';
        }
        return '<!-- [welow_formulario]: ' . wp_strip_all_tags( $html ) . ' -->';
    }
}
