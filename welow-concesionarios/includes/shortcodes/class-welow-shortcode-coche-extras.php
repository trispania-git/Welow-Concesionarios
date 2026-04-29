<?php
/**
 * Shortcodes complementarios para la ficha del coche:
 *
 *   [welow_coche_breadcrumb]   — Breadcrumb dinámico
 *   [welow_coches_similares]   — Grid de coches relacionados
 *   [welow_coche_compartir]    — Botones de compartir en redes
 *   [welow_coche_formulario]   — Formulario de contacto pre-rellenado
 *
 * Todos detectan automáticamente el coche del contexto (single template
 * de welow_coche_nuevo / welow_coche_ocasion).
 *
 * @since 2.5.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Coche_Extras {

    public static function init() {
        add_shortcode( 'welow_coche_breadcrumb', array( __CLASS__, 'render_breadcrumb' ) );
        add_shortcode( 'welow_coches_similares', array( __CLASS__, 'render_similares' ) );
        add_shortcode( 'welow_coche_compartir',  array( __CLASS__, 'render_compartir' ) );
        add_shortcode( 'welow_coche_formulario', array( __CLASS__, 'render_formulario' ) );

        // AJAX para el envío del formulario
        add_action( 'wp_ajax_welow_coche_contacto',        array( __CLASS__, 'ajax_enviar_formulario' ) );
        add_action( 'wp_ajax_nopriv_welow_coche_contacto', array( __CLASS__, 'ajax_enviar_formulario' ) );
    }

    /* ========================================================================
       BREADCRUMB
       ======================================================================== */
    public static function render_breadcrumb( $atts ) {
        $atts = shortcode_atts( array(
            'separador' => '›',
            'inicio'    => 'Inicio',
        ), $atts );

        $coche_id = Welow_Helpers::get_current_coche_id();
        if ( ! $coche_id ) return '<!-- [welow_coche_breadcrumb]: no hay coche en contexto -->';

        $data = Welow_Helpers::get_coche_completo_data( $coche_id );
        if ( ! $data ) return '';

        $sep = ' <span class="welow-breadcrumb__sep">' . esc_html( $atts['separador'] ) . '</span> ';
        $items = array();

        // Inicio
        $items[] = '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( $atts['inicio'] ) . '</a>';

        // Coches → /coches/
        $items[] = '<a href="' . esc_url( home_url( '/coches/' ) ) . '">Coches</a>';

        // Si es ocasión, añadir "Segunda mano"
        if ( ! $data['es_nuevo'] ) {
            $items[] = '<a href="' . esc_url( home_url( '/coches/segunda-mano/' ) ) . '">Segunda mano</a>';
        }

        // Marca
        if ( $data['marca'] ) {
            if ( $data['es_nuevo'] && $data['marca_nombre'] ?? false ) {
                // Ya tenemos el nombre, no usamos un link a marca page si no existe
            }
            $items[] = '<span>' . esc_html( $data['marca'] ) . '</span>';
        }

        // Modelo
        if ( $data['modelo'] ) {
            $items[] = '<span>' . esc_html( $data['modelo'] ) . '</span>';
        }

        // Versión (último, no clicable)
        if ( $data['version'] ) {
            $items[] = '<span class="welow-breadcrumb__current">' . esc_html( $data['version'] ) . '</span>';
        }

        wp_enqueue_style( 'welow-coche-extras' );

        return '<nav class="welow-breadcrumb" aria-label="Breadcrumb">'
            . implode( $sep, $items )
            . '</nav>';
    }

    /* ========================================================================
       COCHES SIMILARES
       ======================================================================== */
    public static function render_similares( $atts ) {
        $atts = shortcode_atts( array(
            'max'      => '4',
            'columnas' => '4',
            'titulo'   => 'Otros coches que pueden interesarte',
        ), $atts );

        $coche_id = Welow_Helpers::get_current_coche_id();
        if ( ! $coche_id ) return '';

        $data = Welow_Helpers::get_coche_completo_data( $coche_id );
        if ( ! $data ) return '';

        $tipo_cpt = $data['cpt'];
        $max      = intval( $atts['max'] );
        $columnas = intval( $atts['columnas'] );

        // Estrategia de "similares":
        // 1) Mismo CPT
        // 2) Misma marca (si es posible)
        // 3) Estado disponible
        // 4) Excluir el actual
        // 5) Si no hay suficientes con la misma marca, completar con cualquiera del mismo tipo

        $args_marca_misma = array(
            'cpt'      => $tipo_cpt,
            'estado'   => 'disponible',
            'max'      => $max + 1,
        );

        if ( $data['es_nuevo'] && $data['marca'] ) {
            // Para nuevos, marca por slug oficial
            $marca_slug = '';
            $modelo_id = Welow_Helpers::get_coche_meta( $coche_id, 'modelo' );
            if ( $modelo_id ) {
                $marca_id = get_post_meta( $modelo_id, '_welow_modelo_marca', true );
                if ( $marca_id ) $marca_slug = get_post_field( 'post_name', $marca_id );
            }
            if ( $marca_slug ) {
                $args_marca_misma['marca'] = $marca_slug;
            }
        } elseif ( ! $data['es_nuevo'] ) {
            // Ocasión: marca externa (taxonomía)
            $marcas = wp_get_post_terms( $coche_id, 'welow_marca_externa', array( 'fields' => 'slugs' ) );
            if ( ! empty( $marcas ) && ! is_wp_error( $marcas ) ) {
                $args_marca_misma['marca_externa'] = $marcas[0];
            }
        }

        $coches = Welow_Helpers::get_coches( $args_marca_misma );
        // Excluir el actual
        $coches = array_filter( $coches, function( $c ) use ( $coche_id ) { return $c->ID !== $coche_id; } );

        // Si no hay suficientes con la misma marca, ampliar
        if ( count( $coches ) < $max ) {
            $extra = Welow_Helpers::get_coches( array(
                'cpt' => $tipo_cpt, 'estado' => 'disponible', 'max' => $max + 5,
            ) );
            $extra = array_filter( $extra, function( $c ) use ( $coche_id ) { return $c->ID !== $coche_id; } );

            // Combinar sin duplicar
            $existentes_ids = wp_list_pluck( $coches, 'ID' );
            foreach ( $extra as $e ) {
                if ( count( $coches ) >= $max ) break;
                if ( in_array( $e->ID, $existentes_ids, true ) ) continue;
                $coches[] = $e;
            }
        }

        $coches = array_slice( $coches, 0, $max );
        if ( empty( $coches ) ) return '';

        wp_enqueue_style( 'welow-coches' );
        wp_enqueue_style( 'welow-coche-extras' );

        $template = $data['es_nuevo'] ? 'coches-grid-nuevos.php' : 'coches-grid-ocasion.php';

        ob_start();
        ?>
        <section class="welow-coches-similares">
            <?php if ( $atts['titulo'] ) : ?>
                <h2 class="welow-coches-similares__titulo"><?php echo esc_html( $atts['titulo'] ); ?></h2>
            <?php endif; ?>

            <?php
            Welow_Helpers::get_template( $template, array(
                'coches'          => $coches,
                'columnas'        => $columnas,
                'columnas_tablet' => min( 2, $columnas ),
                'columnas_movil'  => 1,
            ) );
            ?>
        </section>
        <?php
        return ob_get_clean();
    }

    /* ========================================================================
       COMPARTIR EN REDES
       ======================================================================== */
    public static function render_compartir( $atts ) {
        $atts = shortcode_atts( array(
            'redes'  => 'whatsapp,facebook,twitter,email,copiar',
            'titulo' => 'Compartir',
        ), $atts );

        $coche_id = Welow_Helpers::get_current_coche_id();
        if ( ! $coche_id ) return '';

        $url_coche = get_permalink( $coche_id );
        $titulo_coche = get_the_title( $coche_id );
        $url_enc = rawurlencode( $url_coche );
        $titulo_enc = rawurlencode( $titulo_coche );
        $texto_enc = rawurlencode( "Mira este coche: $titulo_coche" );

        $redes = array_map( 'trim', explode( ',', $atts['redes'] ) );

        $links = array(
            'whatsapp' => array(
                'icon'  => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.149-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>',
                'label' => 'WhatsApp',
                'url'   => 'https://wa.me/?text=' . $texto_enc . '%20' . $url_enc,
                'class' => 'whatsapp',
            ),
            'facebook' => array(
                'icon'  => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
                'label' => 'Facebook',
                'url'   => 'https://www.facebook.com/sharer/sharer.php?u=' . $url_enc,
                'class' => 'facebook',
            ),
            'twitter' => array(
                'icon'  => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
                'label' => 'X (Twitter)',
                'url'   => 'https://twitter.com/intent/tweet?text=' . $texto_enc . '&url=' . $url_enc,
                'class' => 'twitter',
            ),
            'email' => array(
                'icon'  => '<span class="dashicons dashicons-email" style="font-size:20px;width:20px;height:20px;line-height:1;"></span>',
                'label' => 'Email',
                'url'   => 'mailto:?subject=' . $titulo_enc . '&body=' . $texto_enc . '%0A' . $url_enc,
                'class' => 'email',
            ),
            'copiar' => array(
                'icon'  => '<span class="dashicons dashicons-admin-page" style="font-size:20px;width:20px;height:20px;line-height:1;"></span>',
                'label' => 'Copiar URL',
                'url'   => '#',
                'class' => 'copiar',
            ),
        );

        wp_enqueue_style( 'welow-coche-extras' );

        ob_start();
        ?>
        <div class="welow-coche-compartir" data-url="<?php echo esc_attr( $url_coche ); ?>">
            <?php if ( $atts['titulo'] ) : ?>
                <span class="welow-coche-compartir__titulo"><?php echo esc_html( $atts['titulo'] ); ?>:</span>
            <?php endif; ?>
            <ul>
                <?php foreach ( $redes as $red ) :
                    if ( ! isset( $links[ $red ] ) ) continue;
                    $l = $links[ $red ];
                    $atributos = 'copiar' === $red
                        ? 'href="#" data-action="copy"'
                        : 'href="' . esc_url( $l['url'] ) . '" target="_blank" rel="noopener"';
                ?>
                    <li>
                        <a <?php echo $atributos; ?> class="welow-share-btn welow-share-btn--<?php echo esc_attr( $l['class'] ); ?>" aria-label="<?php echo esc_attr( $l['label'] ); ?>">
                            <?php echo $l['icon']; ?>
                            <span class="welow-share-btn__label"><?php echo esc_html( $l['label'] ); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <script>
        (function(){
            var container = document.currentScript && document.currentScript.previousElementSibling;
            if (!container || !container.classList) {
                container = document.querySelector('.welow-coche-compartir:last-of-type');
            }
            if (!container) return;
            var btn = container.querySelector('[data-action="copy"]');
            if (!btn) return;
            btn.addEventListener('click', function(e){
                e.preventDefault();
                var url = container.dataset.url;
                navigator.clipboard.writeText(url).then(function(){
                    var label = btn.querySelector('.welow-share-btn__label');
                    if (label) {
                        var orig = label.textContent;
                        label.textContent = '¡Copiado!';
                        setTimeout(function(){ label.textContent = orig; }, 1500);
                    }
                });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    /* ========================================================================
       FORMULARIO DE CONTACTO
       ======================================================================== */
    public static function render_formulario( $atts ) {
        $atts = shortcode_atts( array(
            'titulo'      => '¿Te interesa este coche?',
            'mostrar_ref' => 'si',
        ), $atts );

        $coche_id = Welow_Helpers::get_current_coche_id();
        $data = $coche_id ? Welow_Helpers::get_coche_completo_data( $coche_id ) : null;

        $titulo_coche = $data ? trim( $data['marca'] . ' ' . $data['modelo'] . ' ' . $data['version'] ) : '';
        $referencia = $data ? $data['referencia'] : '';

        wp_enqueue_style( 'welow-coche-extras' );

        ob_start();
        ?>
        <div class="welow-coche-formulario">
            <h3 class="welow-coche-formulario__titulo"><?php echo esc_html( $atts['titulo'] ); ?></h3>

            <?php if ( $coche_id && 'si' === $atts['mostrar_ref'] ) : ?>
                <p class="welow-coche-formulario__ref">
                    <strong>Sobre:</strong> <?php echo esc_html( $titulo_coche ); ?>
                    <?php if ( $referencia ) : ?> · Ref: <code><?php echo esc_html( $referencia ); ?></code><?php endif; ?>
                </p>
            <?php endif; ?>

            <form class="welow-formulario" data-coche-id="<?php echo esc_attr( $coche_id ); ?>">
                <?php wp_nonce_field( 'welow_coche_contacto', 'welow_nonce' ); ?>
                <input type="hidden" name="coche_id" value="<?php echo esc_attr( $coche_id ); ?>" />
                <input type="hidden" name="referencia" value="<?php echo esc_attr( $referencia ); ?>" />
                <input type="hidden" name="coche_titulo" value="<?php echo esc_attr( $titulo_coche ); ?>" />

                <div class="welow-formulario__row">
                    <label>
                        Nombre completo *
                        <input type="text" name="nombre" required />
                    </label>
                </div>

                <div class="welow-formulario__row welow-formulario__row--two">
                    <label>
                        Teléfono *
                        <input type="tel" name="telefono" required pattern="[0-9 +()-]{6,20}" />
                    </label>
                    <label>
                        Email *
                        <input type="email" name="email" required />
                    </label>
                </div>

                <div class="welow-formulario__row">
                    <label>
                        Mensaje
                        <textarea name="mensaje" rows="4" placeholder="Cuéntanos qué te gustaría saber..."></textarea>
                    </label>
                </div>

                <div class="welow-formulario__row">
                    <label class="welow-formulario__check">
                        <input type="checkbox" name="acepto" required />
                        He leído y acepto la <a href="<?php echo esc_url( get_privacy_policy_url() ); ?>" target="_blank">política de privacidad</a> *
                    </label>
                </div>

                <div class="welow-formulario__row">
                    <button type="submit" class="welow-btn welow-btn-primary welow-btn-grande">
                        Solicitar información
                    </button>
                </div>

                <div class="welow-formulario__resultado" role="alert" aria-live="polite"></div>
            </form>
        </div>

        <script>
        (function(){
            document.querySelectorAll('.welow-coche-formulario .welow-formulario').forEach(function(form){
                if (form.dataset.welowInit) return;
                form.dataset.welowInit = '1';

                form.addEventListener('submit', function(e){
                    e.preventDefault();
                    var resultado = form.querySelector('.welow-formulario__resultado');
                    var btn = form.querySelector('button[type="submit"]');
                    var btnTexto = btn.textContent;
                    btn.disabled = true;
                    btn.textContent = 'Enviando...';
                    resultado.innerHTML = '';

                    var data = new FormData(form);
                    data.append('action', 'welow_coche_contacto');

                    fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: data
                    })
                    .then(function(r){ return r.json(); })
                    .then(function(json){
                        if (json.success) {
                            resultado.innerHTML = '<div class="welow-form-msg welow-form-msg--ok">✓ ' + (json.data && json.data.message ? json.data.message : 'Mensaje enviado. Te contactaremos pronto.') + '</div>';
                            form.reset();
                        } else {
                            resultado.innerHTML = '<div class="welow-form-msg welow-form-msg--error">' + (json.data && json.data.message ? json.data.message : 'Error al enviar. Inténtalo de nuevo.') + '</div>';
                        }
                    })
                    .catch(function(){
                        resultado.innerHTML = '<div class="welow-form-msg welow-form-msg--error">Error de conexión. Revisa tu red e inténtalo de nuevo.</div>';
                    })
                    .finally(function(){
                        btn.disabled = false;
                        btn.textContent = btnTexto;
                    });
                });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler del formulario de contacto.
     */
    public static function ajax_enviar_formulario() {
        if ( ! isset( $_POST['welow_nonce'] ) || ! wp_verify_nonce( $_POST['welow_nonce'], 'welow_coche_contacto' ) ) {
            wp_send_json_error( array( 'message' => 'Nonce inválido. Recarga la página.' ) );
        }

        $nombre   = isset( $_POST['nombre'] ) ? sanitize_text_field( wp_unslash( $_POST['nombre'] ) ) : '';
        $telefono = isset( $_POST['telefono'] ) ? sanitize_text_field( wp_unslash( $_POST['telefono'] ) ) : '';
        $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $mensaje  = isset( $_POST['mensaje'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mensaje'] ) ) : '';
        $coche_id = isset( $_POST['coche_id'] ) ? absint( $_POST['coche_id'] ) : 0;
        $titulo_coche = isset( $_POST['coche_titulo'] ) ? sanitize_text_field( wp_unslash( $_POST['coche_titulo'] ) ) : '';
        $referencia = isset( $_POST['referencia'] ) ? sanitize_text_field( wp_unslash( $_POST['referencia'] ) ) : '';
        $acepto = ! empty( $_POST['acepto'] );

        if ( ! $nombre || ! $telefono || ! $email ) {
            wp_send_json_error( array( 'message' => 'Faltan campos obligatorios.' ) );
        }
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => 'Email no válido.' ) );
        }
        if ( ! $acepto ) {
            wp_send_json_error( array( 'message' => 'Debes aceptar la política de privacidad.' ) );
        }

        // Determinar email destinatario:
        // 1) Email del concesionario asociado al coche (si existe)
        // 2) Email del admin (fallback)
        $destino = get_option( 'admin_email' );
        if ( $coche_id ) {
            $conc_id = get_post_meta( $coche_id, '_welow_coche_concesionario', true );
            if ( $conc_id ) {
                $email_conc = get_post_meta( $conc_id, '_welow_conc_email', true );
                if ( $email_conc && is_email( $email_conc ) ) {
                    $destino = $email_conc;
                }
            }
        }

        $asunto = '[' . get_bloginfo( 'name' ) . '] Nueva solicitud: ' . ( $titulo_coche ?: 'Coche sin título' );
        $body  = "Nueva solicitud de información sobre un coche:\n\n";
        $body .= "Nombre: {$nombre}\n";
        $body .= "Email: {$email}\n";
        $body .= "Teléfono: {$telefono}\n";
        if ( $titulo_coche ) $body .= "\nCoche: {$titulo_coche}\n";
        if ( $referencia )   $body .= "Referencia: {$referencia}\n";
        if ( $coche_id )     $body .= "URL ficha: " . get_permalink( $coche_id ) . "\n";
        if ( $mensaje )      $body .= "\nMensaje:\n{$mensaje}\n";
        $body .= "\n---\nEnviado desde " . home_url() . " el " . current_time( 'Y-m-d H:i:s' );

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'Reply-To: ' . $nombre . ' <' . $email . '>',
        );

        $enviado = wp_mail( $destino, $asunto, $body, $headers );

        if ( $enviado ) {
            // Hook por si alguien quiere guardar el lead en otro sitio
            do_action( 'welow_coche_contacto_enviado', array(
                'coche_id'   => $coche_id,
                'nombre'     => $nombre,
                'email'      => $email,
                'telefono'   => $telefono,
                'mensaje'    => $mensaje,
                'referencia' => $referencia,
                'destino'    => $destino,
            ) );
            wp_send_json_success( array( 'message' => '¡Mensaje enviado! Te contactaremos lo antes posible.' ) );
        } else {
            wp_send_json_error( array( 'message' => 'No se pudo enviar el mensaje. Inténtalo más tarde o llámanos.' ) );
        }
    }
}
