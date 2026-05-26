<?php
/**
 * CPT: Concesionario físico (ubicación de venta).
 *
 * @since 2.0.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_CPT_Concesionario {

    const POST_TYPE   = 'welow_concesionario';
    const META_PREFIX = '_welow_conc_';

    public static function init() {
        add_action( 'init', array( __CLASS__, 'registrar_cpt' ) );
        add_action( 'add_meta_boxes', array( __CLASS__, 'registrar_metaboxes' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'guardar_meta' ), 10, 2 );

        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columnas_admin' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'contenido_columnas' ), 10, 2 );
    }

    public static function registrar_cpt() {
        $labels = array(
            'name'                  => 'Concesionarios físicos',
            'singular_name'        => 'Concesionario',
            'menu_name'            => 'Concesionarios',
            'add_new'              => 'Añadir concesionario',
            'add_new_item'         => 'Añadir nuevo concesionario',
            'edit_item'            => 'Editar concesionario',
            'view_item'            => 'Ver concesionario',
            'all_items'            => 'Todos los concesionarios',
            'search_items'         => 'Buscar concesionarios',
            'not_found'            => 'No se encontraron concesionarios',
            'not_found_in_trash'   => 'No hay concesionarios en la papelera',
            'featured_image'       => 'Logo / imagen',
            'set_featured_image'   => 'Establecer imagen',
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => 'welow_concesionarios',
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => array( 'slug' => 'concesionario', 'with_front' => false ),
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_icon'           => 'dashicons-store',
            'supports'            => array( 'title', 'editor', 'thumbnail' ),
        );

        register_post_type( self::POST_TYPE, $args );
    }

    public static function registrar_metaboxes() {
        add_meta_box( 'welow_conc_contacto', 'Contacto y ubicación',
            array( __CLASS__, 'render_metabox_contacto' ),
            self::POST_TYPE, 'normal', 'high' );

        // v2.26.0 — Banner de portada (con overlay de texto opcional)
        add_meta_box( 'welow_conc_banner', 'Banner de portada',
            array( __CLASS__, 'render_metabox_banner' ),
            self::POST_TYPE, 'normal', 'default' );

        // v2.26.0 — Galería (hasta 6 fotos)
        add_meta_box( 'welow_conc_galeria', 'Galería de fotos (hasta 6)',
            array( __CLASS__, 'render_metabox_galeria' ),
            self::POST_TYPE, 'normal', 'default' );

        // v2.26.0 — Sección Divi Library
        add_meta_box( 'welow_conc_divi', 'Sección de biblioteca Divi',
            array( __CLASS__, 'render_metabox_divi' ),
            self::POST_TYPE, 'normal', 'default' );

        add_meta_box( 'welow_conc_marcas', 'Marcas representadas',
            array( __CLASS__, 'render_metabox_marcas' ),
            self::POST_TYPE, 'side', 'default' );

        add_meta_box( 'welow_conc_config', 'Configuración',
            array( __CLASS__, 'render_metabox_config' ),
            self::POST_TYPE, 'side', 'default' );
    }

    /* =================================================================
     * v2.26.0 — Banner de portada con overlay opcional
     * ================================================================= */
    public static function render_metabox_banner( $post ) {
        $img_desktop = get_post_meta( $post->ID, self::META_PREFIX . 'banner_desktop', true );
        $img_movil   = get_post_meta( $post->ID, self::META_PREFIX . 'banner_movil', true );

        // Overlay shared (un solo texto para ambos viewports — simple para concesionario)
        $titulo    = get_post_meta( $post->ID, self::META_PREFIX . 'banner_overlay_titulo', true );
        $subtitulo = get_post_meta( $post->ID, self::META_PREFIX . 'banner_overlay_subtitulo', true );
        $btn_txt   = get_post_meta( $post->ID, self::META_PREFIX . 'banner_overlay_btn_texto', true );
        $btn_url   = get_post_meta( $post->ID, self::META_PREFIX . 'banner_overlay_btn_url', true );
        $posicion  = get_post_meta( $post->ID, self::META_PREFIX . 'banner_overlay_posicion', true );
        if ( '' === $posicion ) $posicion = 'middle-center';

        $posiciones = array(
            'top-left' => '↖ Arriba izq.', 'top-center' => '↑ Arriba centro', 'top-right' => '↗ Arriba der.',
            'middle-left' => '← Medio izq.', 'middle-center' => '● Centro', 'middle-right' => '→ Medio der.',
            'bottom-left' => '↙ Abajo izq.', 'bottom-center' => '↓ Abajo centro', 'bottom-right' => '↘ Abajo der.',
        );
        ?>
        <p style="background:#eff6ff;border-left:3px solid #2563eb;padding:8px 12px;margin:0 0 14px;font-size:13px;">
            Imágenes de cabecera del concesionario. El texto superpuesto es opcional: si lo dejas vacío,
            solo se ve la imagen.
        </p>
        <div class="welow-banner-pair">
            <?php
            self::render_campo_imagen_simple( 'welow_conc_banner_desktop', 'Escritorio (1920×600)', $img_desktop );
            self::render_campo_imagen_simple( 'welow_conc_banner_movil',   'Móvil (600×600)',       $img_movil );
            ?>
        </div>

        <div style="margin-top:18px;padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;">
            <p style="margin:0 0 8px;font-size:12px;color:#475569;"><strong>Texto superpuesto (opcional)</strong></p>
            <p style="margin:0 0 6px;">
                <label style="font-size:12px;display:block;margin-bottom:2px;">Título</label>
                <input type="text" name="welow_conc_banner_overlay_titulo" value="<?php echo esc_attr( $titulo ); ?>" class="widefat" maxlength="80" placeholder="Ej: Tu concesionario JAECOO en Móstoles" />
            </p>
            <p style="margin:0 0 6px;">
                <label style="font-size:12px;display:block;margin-bottom:2px;">Subtítulo</label>
                <textarea name="welow_conc_banner_overlay_subtitulo" rows="2" class="widefat" maxlength="200" placeholder="Frase secundaria"><?php echo esc_textarea( $subtitulo ); ?></textarea>
            </p>
            <p style="margin:0 0 6px;display:flex;gap:6px;">
                <span style="flex:1;">
                    <label style="font-size:12px;display:block;margin-bottom:2px;">Texto del botón</label>
                    <input type="text" name="welow_conc_banner_overlay_btn_texto" value="<?php echo esc_attr( $btn_txt ); ?>" class="widefat" maxlength="40" placeholder="Cómo llegar" />
                </span>
                <span style="flex:2;">
                    <label style="font-size:12px;display:block;margin-bottom:2px;">URL del botón</label>
                    <input type="url" name="welow_conc_banner_overlay_btn_url" value="<?php echo esc_attr( $btn_url ); ?>" class="widefat" placeholder="https://..." />
                </span>
            </p>
            <p style="margin:0;">
                <label style="font-size:12px;display:block;margin-bottom:2px;">Posición del texto</label>
                <select name="welow_conc_banner_overlay_posicion" class="widefat" style="max-width:200px;">
                    <?php foreach ( $posiciones as $val => $label ) : ?>
                        <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $posicion, $val ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
        </div>

        <style>
            .welow-banner-pair { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
            .welow-conc-img-field { background: #fafafa; border: 1px dashed #ddd; border-radius: 6px; padding: 10px; text-align: center; }
            .welow-conc-img-field img { max-width: 100%; height: auto; margin: 6px 0; border-radius: 4px; }
            .welow-conc-img-field button { margin: 0 2px; }
        </style>
        <?php
    }

    /* =================================================================
     * v2.26.0 — Galería (hasta 6 fotos, ordenables)
     * ================================================================= */
    public static function render_metabox_galeria( $post ) {
        $max = 6;
        $ids = get_post_meta( $post->ID, self::META_PREFIX . 'galeria', true );
        if ( ! is_array( $ids ) ) $ids = array();
        ?>
        <p style="margin:0 0 10px;font-size:13px;color:#475569;">
            Fachada, exposición, talleres, equipo, eventos... Máximo <?php echo intval( $max ); ?> fotos.
            Arrastra las miniaturas para reordenar.
        </p>
        <div id="welow-conc-galeria" class="welow-conc-galeria-grid"
             data-max="<?php echo intval( $max ); ?>">
            <?php foreach ( $ids as $img_id ) :
                $img_id = intval( $img_id );
                $url = wp_get_attachment_image_url( $img_id, 'thumbnail' );
                if ( ! $url ) continue;
            ?>
                <div class="welow-conc-galeria-item" data-id="<?php echo $img_id; ?>">
                    <img src="<?php echo esc_url( $url ); ?>" />
                    <input type="hidden" name="welow_conc_galeria[]" value="<?php echo $img_id; ?>" />
                    <button type="button" class="welow-conc-galeria-remove button-link-delete" title="Quitar">×</button>
                </div>
            <?php endforeach; ?>
        </div>
        <p style="margin-top:10px;">
            <button type="button" class="button" id="welow-conc-galeria-add">+ Añadir fotos</button>
            <span class="description" style="margin-left:8px;"><span id="welow-conc-galeria-count"><?php echo count( $ids ); ?></span> / <?php echo intval( $max ); ?></span>
        </p>
        <style>
            .welow-conc-galeria-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; min-height: 30px; }
            .welow-conc-galeria-item { position: relative; aspect-ratio: 1/1; border-radius: 6px; overflow: hidden; cursor: move; background: #f5f5f5; }
            .welow-conc-galeria-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
            .welow-conc-galeria-item .welow-conc-galeria-remove {
                position: absolute; top: 4px; right: 4px;
                width: 22px; height: 22px; border-radius: 50%;
                background: rgba(220,38,38,0.92); color: #fff;
                border: none; font-size: 14px; line-height: 22px; padding: 0;
                cursor: pointer; text-decoration: none;
            }
            .welow-conc-galeria-item.ui-sortable-placeholder { visibility: visible !important; background: #dbeafe; border: 2px dashed #2563eb; }
        </style>
        <script>
        jQuery(function($){
            var $grid = $('#welow-conc-galeria');
            var max = parseInt($grid.data('max'), 10) || 6;
            var $count = $('#welow-conc-galeria-count');

            function refreshCount(){ $count.text($grid.find('.welow-conc-galeria-item').length); }

            // Sortable
            if ($.fn.sortable) {
                $grid.sortable({ placeholder: 'welow-conc-galeria-item ui-sortable-placeholder', items: '.welow-conc-galeria-item' });
            }

            // Eliminar
            $grid.on('click', '.welow-conc-galeria-remove', function(e){
                e.preventDefault();
                $(this).closest('.welow-conc-galeria-item').remove();
                refreshCount();
            });

            // Añadir
            var frame;
            $('#welow-conc-galeria-add').on('click', function(e){
                e.preventDefault();
                var actuales = $grid.find('.welow-conc-galeria-item').length;
                if (actuales >= max) { alert('Máximo ' + max + ' fotos.'); return; }

                if (frame) { frame.open(); return; }
                frame = wp.media({ title: 'Seleccionar fotos', multiple: true, library: { type: 'image' } });
                frame.on('select', function(){
                    var sel = frame.state().get('selection').toArray();
                    sel.forEach(function(a){
                        if ($grid.find('.welow-conc-galeria-item').length >= max) return;
                        var att = a.toJSON();
                        if ($grid.find('[data-id="'+att.id+'"]').length) return; // ya está
                        var thumb = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
                        var html = '<div class="welow-conc-galeria-item" data-id="'+att.id+'">' +
                                   '<img src="'+thumb+'" />' +
                                   '<input type="hidden" name="welow_conc_galeria[]" value="'+att.id+'" />' +
                                   '<button type="button" class="welow-conc-galeria-remove button-link-delete">×</button>' +
                                   '</div>';
                        $grid.append(html);
                    });
                    refreshCount();
                });
                frame.open();
            });
        });
        </script>
        <?php
    }

    /* =================================================================
     * v2.26.0 — Sección de Divi Library
     * ================================================================= */
    public static function render_metabox_divi( $post ) {
        $divi_id = intval( get_post_meta( $post->ID, self::META_PREFIX . 'divi_layout_id', true ) );
        $layouts = get_posts( array(
            'post_type'      => 'et_pb_layout',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );
        ?>
        <p style="margin:0 0 8px;font-size:13px;color:#475569;">
            Elige un layout de la biblioteca Divi para insertarlo como sección del concesionario
            (testimonios, mapa, equipo, garantías…). Se renderizará dentro del shortcode <code>[welow_concesionario_ficha]</code>.
        </p>
        <?php if ( empty( $layouts ) ) : ?>
            <p><em>No tienes layouts en la biblioteca Divi. Crea uno desde Divi → Biblioteca Divi.</em></p>
        <?php else : ?>
            <select name="welow_conc_divi_layout_id" class="widefat" style="max-width:520px;">
                <option value="0">— Sin sección Divi —</option>
                <?php foreach ( $layouts as $l ) : ?>
                    <option value="<?php echo intval( $l->ID ); ?>" <?php selected( $divi_id, $l->ID ); ?>>
                        <?php echo esc_html( $l->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
        <?php
    }

    /**
     * Helper sencillo para campo de imagen única (banner desktop/móvil).
     */
    private static function render_campo_imagen_simple( $name, $label, $value ) {
        $url = $value ? wp_get_attachment_image_url( intval( $value ), 'medium' ) : '';
        ?>
        <div class="welow-conc-img-field">
            <strong><?php echo esc_html( $label ); ?></strong>
            <div class="welow-conc-img-preview">
                <?php if ( $url ) : ?>
                    <img src="<?php echo esc_url( $url ); ?>" />
                <?php else : ?>
                    <p style="color:#999;margin:14px 0;">Sin imagen</p>
                <?php endif; ?>
            </div>
            <input type="hidden" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" />
            <button type="button" class="button welow-conc-img-pick" data-target="<?php echo esc_attr( $name ); ?>">Seleccionar</button>
            <button type="button" class="button welow-conc-img-clear" data-target="<?php echo esc_attr( $name ); ?>">Quitar</button>
        </div>
        <script>
        jQuery(function($){
            $('.welow-conc-img-pick[data-target="<?php echo esc_js( $name ); ?>"]').off('click').on('click', function(e){
                e.preventDefault();
                var target = $(this).data('target');
                var frame = wp.media({ title: 'Seleccionar imagen', multiple: false, library: { type: 'image' } });
                frame.on('select', function(){
                    var att = frame.state().get('selection').first().toJSON();
                    $('#'+target).val(att.id);
                    var thumb = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
                    $('#'+target).closest('.welow-conc-img-field').find('.welow-conc-img-preview').html('<img src="'+thumb+'" />');
                });
                frame.open();
            });
            $('.welow-conc-img-clear[data-target="<?php echo esc_js( $name ); ?>"]').off('click').on('click', function(e){
                e.preventDefault();
                var target = $(this).data('target');
                $('#'+target).val('');
                $('#'+target).closest('.welow-conc-img-field').find('.welow-conc-img-preview').html('<p style="color:#999;margin:14px 0;">Sin imagen</p>');
            });
        });
        </script>
        <?php
    }

    public static function render_metabox_contacto( $post ) {
        wp_nonce_field( 'welow_conc_save', 'welow_conc_nonce' );

        $direccion = get_post_meta( $post->ID, self::META_PREFIX . 'direccion', true );
        $cp        = get_post_meta( $post->ID, self::META_PREFIX . 'cp', true );
        $ciudad    = get_post_meta( $post->ID, self::META_PREFIX . 'ciudad', true );
        $provincia = get_post_meta( $post->ID, self::META_PREFIX . 'provincia', true );
        $telefono  = get_post_meta( $post->ID, self::META_PREFIX . 'telefono', true );
        $email     = get_post_meta( $post->ID, self::META_PREFIX . 'email', true );
        $horario   = get_post_meta( $post->ID, self::META_PREFIX . 'horario', true );
        $lat       = get_post_meta( $post->ID, self::META_PREFIX . 'lat', true );
        $lng       = get_post_meta( $post->ID, self::META_PREFIX . 'lng', true );
        ?>
        <table class="form-table welow-metabox-table">
            <tr>
                <th><label for="welow_conc_direccion">Dirección</label></th>
                <td><input type="text" id="welow_conc_direccion" name="welow_conc_direccion"
                           value="<?php echo esc_attr( $direccion ); ?>" class="large-text" /></td>
            </tr>
            <tr>
                <th><label>Código postal / Ciudad / Provincia</label></th>
                <td>
                    <input type="text" name="welow_conc_cp" value="<?php echo esc_attr( $cp ); ?>"
                           placeholder="C.P." style="width: 100px;" />
                    <input type="text" name="welow_conc_ciudad" value="<?php echo esc_attr( $ciudad ); ?>"
                           placeholder="Ciudad" style="width: 200px;" />
                    <input type="text" name="welow_conc_provincia" value="<?php echo esc_attr( $provincia ); ?>"
                           placeholder="Provincia" style="width: 200px;" />
                </td>
            </tr>
            <tr>
                <th><label>Teléfono / Email</label></th>
                <td>
                    <input type="text" name="welow_conc_telefono" value="<?php echo esc_attr( $telefono ); ?>"
                           placeholder="Teléfono" style="width: 180px;" />
                    <input type="email" name="welow_conc_email" value="<?php echo esc_attr( $email ); ?>"
                           placeholder="Email" style="width: 280px;" />
                </td>
            </tr>
            <tr>
                <th><label for="welow_conc_horario">Horario</label></th>
                <td><textarea id="welow_conc_horario" name="welow_conc_horario" rows="3"
                              class="large-text" placeholder="Lunes a Viernes 9:00–14:00 / 16:30–20:00..."><?php echo esc_textarea( $horario ); ?></textarea></td>
            </tr>
            <tr>
                <th><label>Coordenadas mapa</label></th>
                <td>
                    <input type="text" name="welow_conc_lat" value="<?php echo esc_attr( $lat ); ?>"
                           placeholder="Latitud" style="width: 160px;" />
                    <input type="text" name="welow_conc_lng" value="<?php echo esc_attr( $lng ); ?>"
                           placeholder="Longitud" style="width: 160px;" />
                    <p class="description">Para mostrar el mapa en la ficha del coche.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function render_metabox_marcas( $post ) {
        $marcas_guardadas = get_post_meta( $post->ID, self::META_PREFIX . 'marcas', true );
        if ( ! is_array( $marcas_guardadas ) ) $marcas_guardadas = array();

        $marcas = get_posts( array(
            'post_type' => 'welow_marca', 'post_status' => 'publish',
            'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC',
        ) );

        if ( empty( $marcas ) ) {
            echo '<p><em>Aún no hay marcas creadas.</em></p>';
            return;
        }
        ?>
        <p class="description" style="margin-top:0;">Marcas que vende este concesionario:</p>
        <?php foreach ( $marcas as $marca ) : ?>
            <label class="welow-checkbox-label" style="display:block;">
                <input type="checkbox" name="welow_conc_marcas[]"
                       value="<?php echo esc_attr( $marca->ID ); ?>"
                       <?php checked( in_array( $marca->ID, $marcas_guardadas ) ); ?> />
                <?php echo esc_html( $marca->post_title ); ?>
            </label>
        <?php endforeach;
    }

    public static function render_metabox_config( $post ) {
        $orden  = get_post_meta( $post->ID, self::META_PREFIX . 'orden', true );
        $activo = get_post_meta( $post->ID, self::META_PREFIX . 'activo', true );
        if ( '' === $activo ) $activo = '1';
        ?>
        <p>
            <label><strong>Orden:</strong></label><br>
            <input type="number" name="welow_conc_orden" value="<?php echo esc_attr( $orden ); ?>"
                   min="0" step="1" style="width: 80px;" />
        </p>
        <p>
            <label class="welow-checkbox-label">
                <input type="checkbox" name="welow_conc_activo" value="1" <?php checked( $activo, '1' ); ?> />
                <strong>Activo</strong>
            </label>
        </p>
        <?php
    }

    public static function guardar_meta( $post_id, $post ) {
        if ( ! isset( $_POST['welow_conc_nonce'] ) || ! wp_verify_nonce( $_POST['welow_conc_nonce'], 'welow_conc_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $campos_texto = array(
            'welow_conc_direccion' => 'direccion',
            'welow_conc_cp'        => 'cp',
            'welow_conc_ciudad'    => 'ciudad',
            'welow_conc_provincia' => 'provincia',
            'welow_conc_telefono'  => 'telefono',
            'welow_conc_lat'       => 'lat',
            'welow_conc_lng'       => 'lng',
        );
        foreach ( $campos_texto as $post_key => $meta_key ) {
            $val = isset( $_POST[ $post_key ] ) ? sanitize_text_field( $_POST[ $post_key ] ) : '';
            update_post_meta( $post_id, self::META_PREFIX . $meta_key, $val );
        }

        $email = isset( $_POST['welow_conc_email'] ) ? sanitize_email( $_POST['welow_conc_email'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'email', $email );

        $horario = isset( $_POST['welow_conc_horario'] ) ? sanitize_textarea_field( $_POST['welow_conc_horario'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'horario', $horario );

        $marcas = isset( $_POST['welow_conc_marcas'] ) ? array_map( 'absint', $_POST['welow_conc_marcas'] ) : array();
        update_post_meta( $post_id, self::META_PREFIX . 'marcas', $marcas );

        $orden = isset( $_POST['welow_conc_orden'] ) ? absint( $_POST['welow_conc_orden'] ) : 0;
        update_post_meta( $post_id, self::META_PREFIX . 'orden', $orden );

        $activo = isset( $_POST['welow_conc_activo'] ) ? '1' : '0';
        update_post_meta( $post_id, self::META_PREFIX . 'activo', $activo );

        // v2.26.0 — Banner de portada + overlay
        $banner_d = isset( $_POST['welow_conc_banner_desktop'] ) ? absint( $_POST['welow_conc_banner_desktop'] ) : 0;
        $banner_m = isset( $_POST['welow_conc_banner_movil'] )   ? absint( $_POST['welow_conc_banner_movil'] )   : 0;
        update_post_meta( $post_id, self::META_PREFIX . 'banner_desktop', $banner_d );
        update_post_meta( $post_id, self::META_PREFIX . 'banner_movil',   $banner_m );

        $posiciones_validas = array( 'top-left','top-center','top-right','middle-left','middle-center','middle-right','bottom-left','bottom-center','bottom-right' );
        $ov_titulo = isset( $_POST['welow_conc_banner_overlay_titulo'] )    ? sanitize_text_field( $_POST['welow_conc_banner_overlay_titulo'] ) : '';
        $ov_sub    = isset( $_POST['welow_conc_banner_overlay_subtitulo'] ) ? sanitize_textarea_field( $_POST['welow_conc_banner_overlay_subtitulo'] ) : '';
        $ov_btxt   = isset( $_POST['welow_conc_banner_overlay_btn_texto'] ) ? sanitize_text_field( $_POST['welow_conc_banner_overlay_btn_texto'] ) : '';
        $ov_burl   = isset( $_POST['welow_conc_banner_overlay_btn_url'] )   ? esc_url_raw( $_POST['welow_conc_banner_overlay_btn_url'] ) : '';
        $ov_pos    = isset( $_POST['welow_conc_banner_overlay_posicion'] )  ? sanitize_key( $_POST['welow_conc_banner_overlay_posicion'] ) : 'middle-center';
        if ( ! in_array( $ov_pos, $posiciones_validas, true ) ) $ov_pos = 'middle-center';
        update_post_meta( $post_id, self::META_PREFIX . 'banner_overlay_titulo',    $ov_titulo );
        update_post_meta( $post_id, self::META_PREFIX . 'banner_overlay_subtitulo', $ov_sub );
        update_post_meta( $post_id, self::META_PREFIX . 'banner_overlay_btn_texto', $ov_btxt );
        update_post_meta( $post_id, self::META_PREFIX . 'banner_overlay_btn_url',   $ov_burl );
        update_post_meta( $post_id, self::META_PREFIX . 'banner_overlay_posicion',  $ov_pos );

        // v2.26.0 — Galería (array de IDs, máximo 6)
        $galeria = array();
        if ( isset( $_POST['welow_conc_galeria'] ) && is_array( $_POST['welow_conc_galeria'] ) ) {
            foreach ( $_POST['welow_conc_galeria'] as $gid ) {
                $gid = absint( $gid );
                if ( $gid && ! in_array( $gid, $galeria, true ) ) $galeria[] = $gid;
                if ( count( $galeria ) >= 6 ) break;
            }
        }
        update_post_meta( $post_id, self::META_PREFIX . 'galeria', $galeria );

        // v2.26.0 — Sección Divi Library
        $divi_id = isset( $_POST['welow_conc_divi_layout_id'] ) ? absint( $_POST['welow_conc_divi_layout_id'] ) : 0;
        update_post_meta( $post_id, self::META_PREFIX . 'divi_layout_id', $divi_id );
    }

    public static function columnas_admin( $columns ) {
        $new = array();
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( 'title' === $key ) {
                $new['welow_logo']    = 'Logo';
                $new['welow_ciudad']  = 'Ciudad';
                $new['welow_telefono'] = 'Teléfono';
                $new['welow_activo']  = 'Activo';
            }
        }
        unset( $new['date'] );
        return $new;
    }

    public static function contenido_columnas( $column, $post_id ) {
        switch ( $column ) {
            case 'welow_logo':
                $thumb = get_the_post_thumbnail( $post_id, array( 60, 60 ) );
                echo $thumb ? $thumb : '<span style="color:#ccc;">—</span>';
                break;
            case 'welow_ciudad':
                $ciudad = get_post_meta( $post_id, self::META_PREFIX . 'ciudad', true );
                $prov   = get_post_meta( $post_id, self::META_PREFIX . 'provincia', true );
                echo esc_html( trim( $ciudad . ( $prov ? " ($prov)" : '' ) ) ?: '—' );
                break;
            case 'welow_telefono':
                echo esc_html( get_post_meta( $post_id, self::META_PREFIX . 'telefono', true ) ?: '—' );
                break;
            case 'welow_activo':
                $activo = get_post_meta( $post_id, self::META_PREFIX . 'activo', true );
                echo ( '1' === $activo || '' === $activo )
                    ? '<span class="dashicons dashicons-yes-alt" style="color:#46b450;"></span>'
                    : '<span class="dashicons dashicons-dismiss" style="color:#dc3232;"></span>';
                break;
        }
    }
}
