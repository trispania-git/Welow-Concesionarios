<?php
/**
 * CPT: welow_formulario — Formularios de captación de leads.
 *
 * Cada formulario define sus campos (JSON), configuración, RGPD y emails
 * de notificación. Se renderiza vía [welow_formulario id="..." slug="..."]
 * y los envíos se guardan como CPT welow_lead.
 *
 * @since 2.30.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_CPT_Formulario {

    const POST_TYPE = 'welow_formulario';
    const META_PREFIX = '_welow_form_';

    public static function init() {
        add_action( 'init', array( __CLASS__, 'registrar_cpt' ) );
        add_action( 'add_meta_boxes', array( __CLASS__, 'registrar_metaboxes' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'guardar_meta' ), 10, 2 );

        // Lista admin: columna del shortcode listo para copiar
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columnas_admin' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'contenido_columnas' ), 10, 2 );
    }

    public static function registrar_cpt() {
        register_post_type( self::POST_TYPE, array(
            'labels' => array(
                'name'          => 'Formularios',
                'singular_name' => 'Formulario',
                'add_new'       => 'Añadir formulario',
                'add_new_item'  => 'Añadir formulario nuevo',
                'edit_item'     => 'Editar formulario',
                'all_items'     => 'Formularios',
                'menu_name'     => 'Formularios',
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false, // se cuelga del menú "Concesionarios" desde Welow_Admin_Menu
            'supports'            => array( 'title' ),
            'capability_type'     => 'post',
            'menu_icon'           => 'dashicons-feedback',
        ) );
    }

    public static function registrar_metaboxes() {
        add_meta_box( 'welow_form_campos', 'Campos del formulario',
            array( __CLASS__, 'render_metabox_campos' ),
            self::POST_TYPE, 'normal', 'high' );

        add_meta_box( 'welow_form_config', 'Configuración',
            array( __CLASS__, 'render_metabox_config' ),
            self::POST_TYPE, 'normal', 'default' );

        add_meta_box( 'welow_form_notif', 'Notificaciones por email',
            array( __CLASS__, 'render_metabox_notificaciones' ),
            self::POST_TYPE, 'normal', 'default' );

        add_meta_box( 'welow_form_rgpd', 'Consentimiento RGPD',
            array( __CLASS__, 'render_metabox_rgpd' ),
            self::POST_TYPE, 'normal', 'default' );

        add_meta_box( 'welow_form_shortcode', 'Cómo usarlo',
            array( __CLASS__, 'render_metabox_shortcode' ),
            self::POST_TYPE, 'side', 'high' );
    }

    /* =====================================================================
     * METABOX: Constructor de campos (JSON)
     * ===================================================================== */
    public static function render_metabox_campos( $post ) {
        wp_nonce_field( 'welow_form_save', 'welow_form_nonce' );
        $campos_json = get_post_meta( $post->ID, self::META_PREFIX . 'campos', true );

        if ( ! $campos_json ) {
            // Plantilla por defecto razonable (UTF-8 literal, sin escapes \uXXXX)
            $campos_json = wp_json_encode( array(
                array( 'type' => 'texto',          'label' => 'Nombre',     'name' => 'nombre',   'required' => true ),
                array( 'type' => 'email',          'label' => 'Email',      'name' => 'email',    'required' => true ),
                array( 'type' => 'telefono',       'label' => 'Teléfono',   'name' => 'telefono', 'required' => false ),
                array( 'type' => 'textarea',       'label' => 'Mensaje',    'name' => 'mensaje',  'required' => false ),
            ), JSON_UNESCAPED_UNICODE );
        } else {
            // v2.30.2 — Si hay datos guardados con escapes Unicode literales (bug v2.30.0),
            // los reparamos AQUÍ antes de que lleguen al builder JS. Así al guardar se
            // persisten ya limpios y desaparece el problema para siempre.
            $arr = json_decode( $campos_json, true );
            if ( is_array( $arr ) ) {
                $arr = self::reparar_campos( $arr );
                $campos_json = wp_json_encode( $arr, JSON_UNESCAPED_UNICODE );
            }
        }
        ?>
        <p style="background:#f0f6fc;border-left:3px solid #2271b1;padding:8px 12px;margin:0 0 14px;font-size:13px;">
            Construye los campos uno a uno. El <strong>nombre</strong> (interno) se autogenera del label
            si lo dejas vacío. Para select/radio/checkbox, escribe las opciones separadas por <code>|</code>
            (ejemplo: <code>SUV|Berlina|Compacto</code>).
        </p>

        <input type="hidden" id="welow_form_campos_json" name="welow_form_campos" value='<?php echo esc_attr( $campos_json ); ?>' />

        <div id="welow-form-builder" class="welow-form-builder"></div>

        <p>
            <button type="button" class="button" data-welow-add-campo="texto">+ Texto</button>
            <button type="button" class="button" data-welow-add-campo="email">+ Email</button>
            <button type="button" class="button" data-welow-add-campo="telefono">+ Teléfono</button>
            <button type="button" class="button" data-welow-add-campo="textarea">+ Textarea</button>
            <button type="button" class="button" data-welow-add-campo="select">+ Select</button>
            <button type="button" class="button" data-welow-add-campo="radio">+ Radio</button>
            <button type="button" class="button" data-welow-add-campo="checkbox">+ Checkbox</button>
            <button type="button" class="button" data-welow-add-campo="oculto">+ Oculto</button>
        </p>

        <style>
            .welow-form-builder { display: flex; flex-direction: column; gap: 8px; }
            .welow-form-row {
                background: #fafafa; border: 1px solid #ddd; border-radius: 6px;
                padding: 12px; display: grid;
                grid-template-columns: 110px 1fr 1fr 90px auto;
                gap: 8px; align-items: center;
            }
            .welow-form-row.is-options { grid-template-columns: 110px 1fr 1fr 90px auto; }
            .welow-form-row .welow-form-row__type {
                font-weight: 700; text-transform: uppercase; font-size: 11px;
                color: #fff; background: #2563eb; padding: 4px 8px; border-radius: 4px;
                text-align: center;
            }
            .welow-form-row input[type=text], .welow-form-row input[type=hidden]+input { width: 100%; }
            .welow-form-row__remove {
                background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c;
                width: 28px; height: 28px; border-radius: 50%; cursor: pointer;
                font-weight: 700; line-height: 1; padding: 0;
            }
            .welow-form-row__opts { grid-column: 1 / -1; padding-top: 6px; }
            .welow-form-row__opts input { width: 100%; }
            .welow-form-row__opts label { font-size: 11px; color: #64748b; display: block; margin-bottom: 2px; }
            .welow-form-row__handle { cursor: move; color: #94a3b8; padding: 0 4px; }
        </style>

        <script>
        (function($){
            var $hidden = $('#welow_form_campos_json');
            var $builder = $('#welow-form-builder');
            var data = [];
            try { data = JSON.parse($hidden.val() || '[]') || []; } catch(e) { data = []; }

            function tieneOpciones(type) { return type === 'select' || type === 'radio' || type === 'checkbox'; }

            function rowHtml(c, idx){
                var opts = tieneOpciones(c.type)
                    ? '<div class="welow-form-row__opts">' +
                          '<label>Opciones (separadas por |)</label>' +
                          '<input type="text" data-field="opciones" value="' + (c.opciones || '').replace(/"/g,'&quot;') + '" placeholder="Opción 1|Opción 2|Opción 3" />' +
                      '</div>'
                    : '';
                var requiredAttr = c.required ? 'checked' : '';
                return '<div class="welow-form-row' + (tieneOpciones(c.type) ? ' is-options' : '') + '" data-idx="' + idx + '">' +
                    '<span class="welow-form-row__handle">⋮⋮</span>' +
                    '<span class="welow-form-row__type">' + c.type + '</span>' +
                    '<input type="text" data-field="label" value="' + (c.label || '').replace(/"/g,'&quot;') + '" placeholder="Etiqueta visible" />' +
                    '<input type="text" data-field="name" value="' + (c.name || '').replace(/"/g,'&quot;') + '" placeholder="nombre_interno" />' +
                    '<label style="font-size:12px;"><input type="checkbox" data-field="required" ' + requiredAttr + ' /> Obligatorio</label>' +
                    '<button type="button" class="welow-form-row__remove" title="Eliminar">×</button>' +
                    opts +
                '</div>';
            }

            function render(){
                $builder.empty();
                data.forEach(function(c, idx){ $builder.append(rowHtml(c, idx)); });
                if ($.fn.sortable) {
                    $builder.sortable({ handle: '.welow-form-row__handle', update: function(){ syncFromDom(); } });
                }
            }

            function syncFromDom(){
                var nuevos = [];
                $builder.find('.welow-form-row').each(function(){
                    var $r = $(this);
                    var type = $r.find('.welow-form-row__type').text();
                    var item = {
                        type:     type,
                        label:    $r.find('[data-field=label]').val() || '',
                        name:     $r.find('[data-field=name]').val() || '',
                        required: $r.find('[data-field=required]').is(':checked'),
                    };
                    if (tieneOpciones(type)) item.opciones = $r.find('[data-field=opciones]').val() || '';
                    nuevos.push(item);
                });
                data = nuevos;
                $hidden.val(JSON.stringify(data));
            }

            $builder.on('input change', 'input', syncFromDom);
            $builder.on('click', '.welow-form-row__remove', function(){
                $(this).closest('.welow-form-row').remove();
                syncFromDom();
            });
            $(document).on('click', '[data-welow-add-campo]', function(e){
                e.preventDefault();
                var type = $(this).data('welow-add-campo');
                data.push({ type: type, label: '', name: '', required: false });
                render();
            });

            // Antes de submit del form de WP, asegurar JSON actualizado
            $('form#post').on('submit', syncFromDom);

            render();
        })(jQuery);
        </script>
        <?php
    }

    /* =====================================================================
     * METABOX: Configuración
     * ===================================================================== */
    public static function render_metabox_config( $post ) {
        $boton_texto = get_post_meta( $post->ID, self::META_PREFIX . 'boton_texto', true ) ?: 'Enviar';
        $mensaje_ok  = get_post_meta( $post->ID, self::META_PREFIX . 'mensaje_ok', true ) ?: 'Gracias por contactarnos. Te responderemos en breve.';
        $redirect    = get_post_meta( $post->ID, self::META_PREFIX . 'redirect', true );
        $titulo_pub  = get_post_meta( $post->ID, self::META_PREFIX . 'titulo_publico', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label>Título visible (opcional)</label></th>
                <td>
                    <input type="text" name="welow_form_titulo_publico" value="<?php echo esc_attr( $titulo_pub ); ?>" class="regular-text" placeholder="Ej: ¿Te interesa este coche?" />
                    <p class="description">Si vacío, no se muestra ningún título encima del formulario.</p>
                </td>
            </tr>
            <tr>
                <th><label>Texto del botón</label></th>
                <td>
                    <input type="text" name="welow_form_boton_texto" value="<?php echo esc_attr( $boton_texto ); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label>Mensaje de éxito</label></th>
                <td>
                    <textarea name="welow_form_mensaje_ok" rows="2" class="large-text"><?php echo esc_textarea( $mensaje_ok ); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label>URL de redirección (opcional)</label></th>
                <td>
                    <input type="url" name="welow_form_redirect" value="<?php echo esc_url( $redirect ); ?>" class="large-text" placeholder="https://... (ej. página de gracias)" />
                    <p class="description">Si la rellenas, tras el envío exitoso se redirige aquí (ignora el mensaje de éxito).</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /* =====================================================================
     * METABOX: Notificaciones
     * ===================================================================== */
    public static function render_metabox_notificaciones( $post ) {
        $emails  = get_post_meta( $post->ID, self::META_PREFIX . 'emails_notificacion', true );
        $asunto  = get_post_meta( $post->ID, self::META_PREFIX . 'email_asunto', true ) ?: 'Nuevo lead recibido — {sitio}';
        ?>
        <table class="form-table">
            <tr>
                <th><label>Emails destinatarios</label></th>
                <td>
                    <textarea name="welow_form_emails_notificacion" rows="3" class="large-text" placeholder="comercial@empresa.com, gerente@empresa.com"><?php echo esc_textarea( $emails ); ?></textarea>
                    <p class="description">Uno por línea, o separados por comas. Pueden ser varios (responsable de área, comerciales...). Si vacío, no se envía email pero el lead sí queda guardado.</p>
                </td>
            </tr>
            <tr>
                <th><label>Asunto del email</label></th>
                <td>
                    <input type="text" name="welow_form_email_asunto" value="<?php echo esc_attr( $asunto ); ?>" class="large-text" />
                    <p class="description">Comodines disponibles: <code>{sitio}</code>, <code>{formulario}</code>, <code>{nombre}</code>.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /* =====================================================================
     * METABOX: RGPD
     * ===================================================================== */
    public static function render_metabox_rgpd( $post ) {
        $consent_texto = get_post_meta( $post->ID, self::META_PREFIX . 'consent_texto', true )
            ?: 'He leído y acepto la <a href="{politica}" target="_blank" rel="noopener">política de privacidad</a>.';
        $politica_url  = get_post_meta( $post->ID, self::META_PREFIX . 'politica_url', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label>Texto del consentimiento</label></th>
                <td>
                    <textarea name="welow_form_consent_texto" rows="3" class="large-text"><?php echo esc_textarea( $consent_texto ); ?></textarea>
                    <p class="description">Se mostrará junto a un checkbox <strong>obligatorio</strong>. Usa <code>{politica}</code> donde quieras el link a la política de privacidad.</p>
                </td>
            </tr>
            <tr>
                <th><label>URL de política de privacidad</label></th>
                <td>
                    <input type="url" name="welow_form_politica_url" value="<?php echo esc_url( $politica_url ); ?>" class="large-text" placeholder="https://.../politica-privacidad/" />
                </td>
            </tr>
        </table>
        <?php
    }

    /* =====================================================================
     * METABOX: Cómo usarlo (sidebar)
     * ===================================================================== */
    public static function render_metabox_shortcode( $post ) {
        if ( 'auto-draft' === $post->post_status ) {
            echo '<p>Guarda el formulario para ver el shortcode.</p>';
            return;
        }
        $slug = $post->post_name;
        ?>
        <p style="margin-top:0;">Copia y pega este shortcode donde quieras mostrar el formulario:</p>
        <p>
            <input type="text" readonly onclick="this.select()" class="widefat code"
                   value="[welow_formulario id=&quot;<?php echo intval( $post->ID ); ?>&quot;]" />
        </p>
        <?php if ( $slug ) : ?>
            <p style="font-size:12px;color:#64748b;">O por slug:</p>
            <p>
                <input type="text" readonly onclick="this.select()" class="widefat code"
                       value='[welow_formulario slug="<?php echo esc_attr( $slug ); ?>"]' />
            </p>
        <?php endif; ?>
        <?php
    }

    /* =====================================================================
     * Guardado
     * ===================================================================== */
    public static function guardar_meta( $post_id, $post ) {
        if ( ! isset( $_POST['welow_form_nonce'] ) || ! wp_verify_nonce( $_POST['welow_form_nonce'], 'welow_form_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // Campos JSON: validar que es JSON razonable
        $campos_raw = isset( $_POST['welow_form_campos'] ) ? wp_unslash( $_POST['welow_form_campos'] ) : '[]';
        $decoded = json_decode( $campos_raw, true );
        if ( ! is_array( $decoded ) ) $decoded = array();
        // v2.30.2 — Reparar escapes Unicode literales si vienen de un guardado corrupto
        $decoded = self::reparar_campos( $decoded );
        // Sanitizar cada campo
        $limpio = array();
        $allowed_types = array( 'texto', 'email', 'telefono', 'textarea', 'select', 'radio', 'checkbox', 'oculto' );
        foreach ( $decoded as $c ) {
            $type = isset( $c['type'] ) && in_array( $c['type'], $allowed_types, true ) ? $c['type'] : 'texto';
            $label = isset( $c['label'] ) ? sanitize_text_field( $c['label'] ) : '';
            $name  = isset( $c['name'] ) && '' !== $c['name'] ? sanitize_key( $c['name'] ) : sanitize_key( $label );
            $item = array(
                'type'     => $type,
                'label'    => $label,
                'name'     => $name,
                'required' => ! empty( $c['required'] ),
            );
            if ( in_array( $type, array( 'select', 'radio', 'checkbox' ), true ) ) {
                $item['opciones'] = isset( $c['opciones'] ) ? sanitize_text_field( $c['opciones'] ) : '';
            }
            $limpio[] = $item;
        }
        // v2.30.1 — Guardar con JSON_UNESCAPED_UNICODE para preservar "é/á/ñ..." literales
        update_post_meta( $post_id, self::META_PREFIX . 'campos', wp_json_encode( $limpio, JSON_UNESCAPED_UNICODE ) );

        $map = array(
            'welow_form_titulo_publico'    => 'titulo_publico',
            'welow_form_boton_texto'       => 'boton_texto',
            'welow_form_mensaje_ok'        => 'mensaje_ok',
            'welow_form_redirect'          => 'redirect',
            'welow_form_emails_notificacion' => 'emails_notificacion',
            'welow_form_email_asunto'      => 'email_asunto',
            'welow_form_consent_texto'     => 'consent_texto',
            'welow_form_politica_url'      => 'politica_url',
        );
        foreach ( $map as $post_key => $meta_key ) {
            if ( ! isset( $_POST[ $post_key ] ) ) continue;
            $val = wp_unslash( $_POST[ $post_key ] );
            if ( in_array( $meta_key, array( 'mensaje_ok', 'consent_texto', 'emails_notificacion' ), true ) ) {
                $val = sanitize_textarea_field( $val );
            } elseif ( in_array( $meta_key, array( 'redirect', 'politica_url' ), true ) ) {
                $val = esc_url_raw( $val );
            } else {
                $val = sanitize_text_field( $val );
            }
            update_post_meta( $post_id, self::META_PREFIX . $meta_key, $val );
        }
    }

    /* =====================================================================
     * Columnas admin
     * ===================================================================== */
    public static function columnas_admin( $columns ) {
        $new = array();
        foreach ( $columns as $k => $v ) {
            $new[ $k ] = $v;
            if ( 'title' === $k ) $new['welow_form_shortcode'] = 'Shortcode';
        }
        return $new;
    }

    public static function contenido_columnas( $column, $post_id ) {
        if ( 'welow_form_shortcode' === $column ) {
            echo '<code>[welow_formulario id="' . intval( $post_id ) . '"]</code>';
        }
    }

    /* =====================================================================
     * Helpers públicos
     * ===================================================================== */
    public static function get_campos( $form_id ) {
        $json = get_post_meta( $form_id, self::META_PREFIX . 'campos', true );
        $arr  = $json ? json_decode( $json, true ) : array();
        if ( ! is_array( $arr ) ) return array();
        return self::reparar_campos( $arr );
    }

    /**
     * v2.30.3 — Repara cadenas con escapes Unicode corruptos en formularios.
     *
     * Cubre 3 casos:
     *   A) "Tel\\u00e9fono"  (literal con barra)          → "Teléfono"
     *   B) "Telu00e9fono"    (barra perdida en POST/save) → "Teléfono"  ← bug v2.30.0/1/2
     *   C) doble-codificados varias capas.
     */
    public static function reparar_campos( $arr ) {
        $repair_string = function( $v ) {
            if ( ! is_string( $v ) ) return $v;

            // Caso B (más común) — orphan "uXXXX" sin barra invertida.
            // Solo lo aplicamos si TODO el segmento "uXXXX" es válido hex.
            // Esto NO afecta a texto legítimo porque "u00e9" en un nombre real
            // sería extremadamente improbable.
            if ( preg_match( '/u[0-9a-fA-F]{4}/', $v ) ) {
                $reparado_b = preg_replace_callback( '/u([0-9a-fA-F]{4})/', function( $m ) {
                    $codepoint = hexdec( $m[1] );
                    // Solo decodificar caracteres latinos comunes (U+0080–U+024F)
                    // para no afectar a texto inocente como "url" o "user".
                    if ( $codepoint >= 0x80 && $codepoint <= 0x024F ) {
                        return function_exists( 'mb_chr' ) ? mb_chr( $codepoint, 'UTF-8' ) : html_entity_decode( '&#' . $codepoint . ';', ENT_NOQUOTES, 'UTF-8' );
                    }
                    return $m[0];
                }, $v );
                if ( $reparado_b !== $v ) $v = $reparado_b;
            }

            // Casos A + C: con barras literales \u o \\u (varias pasadas)
            for ( $pass = 0; $pass < 3; $pass++ ) {
                if ( false === strpos( $v, '\\u' ) ) break;
                $alt = json_decode( '"' . str_replace( '"', '\\"', $v ) . '"' );
                if ( ! is_string( $alt ) || $alt === $v ) break;
                $v = $alt;
            }
            return $v;
        };

        foreach ( $arr as &$campo ) {
            if ( ! is_array( $campo ) ) continue;
            foreach ( $campo as $k => $v ) {
                if ( is_string( $v ) ) {
                    $campo[ $k ] = $repair_string( $v );
                }
            }
        }
        unset( $campo );
        return $arr;
    }

    public static function get_emails_notificacion( $form_id ) {
        $raw = get_post_meta( $form_id, self::META_PREFIX . 'emails_notificacion', true );
        if ( ! $raw ) return array();
        $partes = preg_split( '/[,\r\n]+/', $raw );
        $out = array();
        foreach ( $partes as $p ) {
            $e = trim( $p );
            if ( is_email( $e ) ) $out[] = $e;
        }
        return $out;
    }
}
