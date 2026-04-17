<?php
/**
 * CPT: Slides reutilizables con imagen desktop y móvil.
 *
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_CPT_Slide {

    const POST_TYPE  = 'welow_slide';
    const META_PREFIX = '_welow_slide_';

    public static function init() {
        add_action( 'init', array( __CLASS__, 'registrar_cpt' ) );
        add_action( 'add_meta_boxes', array( __CLASS__, 'registrar_metaboxes' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'guardar_meta' ), 10, 2 );

        // Columnas admin
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columnas_admin' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'contenido_columnas' ), 10, 2 );
        add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( __CLASS__, 'columnas_ordenables' ) );
    }

    /**
     * Registra el CPT.
     */
    public static function registrar_cpt() {
        $labels = array(
            'name'               => 'Slides',
            'singular_name'      => 'Slide',
            'menu_name'          => 'Slides',
            'add_new'            => 'Añadir slide',
            'add_new_item'       => 'Añadir nuevo slide',
            'edit_item'          => 'Editar slide',
            'view_item'          => 'Ver slide',
            'all_items'          => 'Todos los slides',
            'search_items'       => 'Buscar slides',
            'not_found'          => 'No se encontraron slides',
            'not_found_in_trash' => 'No hay slides en la papelera',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => true,
            'query_var'          => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 6,
            'menu_icon'          => 'dashicons-slides',
            'supports'           => array( 'title' ),
        );

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Metaboxes.
     */
    public static function registrar_metaboxes() {
        add_meta_box(
            'welow_slide_imagenes',
            'Imágenes del slide',
            array( __CLASS__, 'render_metabox_imagenes' ),
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'welow_slide_config',
            'Configuración',
            array( __CLASS__, 'render_metabox_config' ),
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    /**
     * Metabox: Imágenes desktop y móvil.
     */
    public static function render_metabox_imagenes( $post ) {
        wp_nonce_field( 'welow_slide_save', 'welow_slide_nonce' );

        $img_desktop_id  = get_post_meta( $post->ID, self::META_PREFIX . 'img_desktop', true );
        $img_movil_id    = get_post_meta( $post->ID, self::META_PREFIX . 'img_movil', true );
        $enlace          = get_post_meta( $post->ID, self::META_PREFIX . 'enlace', true );

        $img_desktop_url = $img_desktop_id ? wp_get_attachment_image_url( $img_desktop_id, 'large' ) : '';
        $img_movil_url   = $img_movil_id ? wp_get_attachment_image_url( $img_movil_id, 'medium' ) : '';
        ?>
        <table class="form-table welow-metabox-table">
            <tr>
                <th><label>Imagen desktop</label></th>
                <td>
                    <div class="welow-media-field">
                        <input type="hidden" id="welow_img_desktop" name="welow_img_desktop" value="<?php echo esc_attr( $img_desktop_id ); ?>" />
                        <div id="welow-img-desktop-preview" class="welow-image-preview">
                            <?php if ( $img_desktop_url ) : ?>
                                <img src="<?php echo esc_url( $img_desktop_url ); ?>" alt="" />
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button welow-upload-btn" data-target="welow_img_desktop" data-preview="welow-img-desktop-preview">
                            <?php echo $img_desktop_id ? 'Cambiar' : 'Seleccionar imagen'; ?>
                        </button>
                        <?php if ( $img_desktop_id ) : ?>
                            <button type="button" class="button welow-remove-btn" data-target="welow_img_desktop" data-preview="welow-img-desktop-preview">Quitar</button>
                        <?php endif; ?>
                    </div>
                    <p class="description">Imagen fullwidth para escritorio. Recomendado: 1920×800px mínimo.</p>
                </td>
            </tr>
            <tr>
                <th><label>Imagen móvil</label></th>
                <td>
                    <div class="welow-media-field">
                        <input type="hidden" id="welow_img_movil" name="welow_img_movil" value="<?php echo esc_attr( $img_movil_id ); ?>" />
                        <div id="welow-img-movil-preview" class="welow-image-preview">
                            <?php if ( $img_movil_url ) : ?>
                                <img src="<?php echo esc_url( $img_movil_url ); ?>" alt="" />
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button welow-upload-btn" data-target="welow_img_movil" data-preview="welow-img-movil-preview">
                            <?php echo $img_movil_id ? 'Cambiar' : 'Seleccionar imagen'; ?>
                        </button>
                        <?php if ( $img_movil_id ) : ?>
                            <button type="button" class="button welow-remove-btn" data-target="welow_img_movil" data-preview="welow-img-movil-preview">Quitar</button>
                        <?php endif; ?>
                    </div>
                    <p class="description">Imagen para tablet y móvil. Recomendado: 768×600px mínimo. Si no se establece, se usará la de desktop.</p>
                </td>
            </tr>
            <tr>
                <th><label for="welow_enlace">Enlace</label></th>
                <td>
                    <input type="url" id="welow_enlace" name="welow_slide_enlace" value="<?php echo esc_url( $enlace ); ?>" class="large-text" placeholder="https://..." />
                    <p class="description">URL al hacer clic en el slide (opcional).</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Metabox: Configuración (grupo, orden, activo).
     */
    public static function render_metabox_config( $post ) {
        $grupo  = get_post_meta( $post->ID, self::META_PREFIX . 'grupo', true );
        $orden  = get_post_meta( $post->ID, self::META_PREFIX . 'orden', true );
        $activo = get_post_meta( $post->ID, self::META_PREFIX . 'activo', true );

        if ( '' === $activo ) {
            $activo = '1';
        }
        ?>
        <p>
            <label for="welow_grupo"><strong>Grupo:</strong></label><br>
            <input type="text" id="welow_grupo" name="welow_slide_grupo" value="<?php echo esc_attr( $grupo ); ?>" class="widefat" placeholder="ej: toyota-home" />
            <span class="description">Identificador para agrupar slides. Úsalo en el shortcode: [welow_slider grupo="..."]</span>
        </p>
        <p>
            <label for="welow_orden_slide"><strong>Orden:</strong></label><br>
            <input type="number" id="welow_orden_slide" name="welow_slide_orden" value="<?php echo esc_attr( $orden ); ?>" min="0" step="1" style="width: 80px;" />
            <span class="description">Menor = primero</span>
        </p>
        <p>
            <label class="welow-checkbox-label">
                <input type="checkbox" name="welow_slide_activo" value="1" <?php checked( $activo, '1' ); ?> />
                <strong>Slide activo</strong>
            </label>
        </p>
        <?php
    }

    /**
     * Guardar meta.
     */
    public static function guardar_meta( $post_id, $post ) {
        if ( ! isset( $_POST['welow_slide_nonce'] ) || ! wp_verify_nonce( $_POST['welow_slide_nonce'], 'welow_slide_save' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Imágenes
        $img_desktop = isset( $_POST['welow_img_desktop'] ) ? absint( $_POST['welow_img_desktop'] ) : '';
        $img_movil   = isset( $_POST['welow_img_movil'] ) ? absint( $_POST['welow_img_movil'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'img_desktop', $img_desktop );
        update_post_meta( $post_id, self::META_PREFIX . 'img_movil', $img_movil );

        // Enlace
        $enlace = isset( $_POST['welow_slide_enlace'] ) ? esc_url_raw( $_POST['welow_slide_enlace'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'enlace', $enlace );

        // Grupo
        $grupo = isset( $_POST['welow_slide_grupo'] ) ? sanitize_title( $_POST['welow_slide_grupo'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'grupo', $grupo );

        // Orden
        $orden = isset( $_POST['welow_slide_orden'] ) ? absint( $_POST['welow_slide_orden'] ) : 0;
        update_post_meta( $post_id, self::META_PREFIX . 'orden', $orden );

        // Activo
        $activo = isset( $_POST['welow_slide_activo'] ) ? '1' : '0';
        update_post_meta( $post_id, self::META_PREFIX . 'activo', $activo );
    }

    /**
     * Columnas admin.
     */
    public static function columnas_admin( $columns ) {
        $new = array();
        foreach ( $columns as $key => $value ) {
            $new[ $key ] = $value;
            if ( 'title' === $key ) {
                $new['welow_preview'] = 'Preview';
                $new['welow_grupo']   = 'Grupo';
                $new['welow_orden']   = 'Orden';
                $new['welow_activo']  = 'Activo';
            }
        }
        // Quitar fecha para ahorrar espacio
        unset( $new['date'] );
        return $new;
    }

    /**
     * Contenido columnas.
     */
    public static function contenido_columnas( $column, $post_id ) {
        switch ( $column ) {
            case 'welow_preview':
                $img_id = get_post_meta( $post_id, self::META_PREFIX . 'img_desktop', true );
                if ( $img_id ) {
                    echo wp_get_attachment_image( $img_id, array( 120, 50 ), false, array( 'style' => 'border-radius:4px;' ) );
                } else {
                    echo '<span style="color:#ccc;">Sin imagen</span>';
                }
                break;
            case 'welow_grupo':
                $grupo = get_post_meta( $post_id, self::META_PREFIX . 'grupo', true );
                echo $grupo ? '<code>' . esc_html( $grupo ) . '</code>' : '—';
                break;
            case 'welow_orden':
                echo esc_html( get_post_meta( $post_id, self::META_PREFIX . 'orden', true ) ?: '0' );
                break;
            case 'welow_activo':
                $activo = get_post_meta( $post_id, self::META_PREFIX . 'activo', true );
                echo ( '1' === $activo || '' === $activo )
                    ? '<span class="dashicons dashicons-yes-alt" style="color:#46b450;"></span>'
                    : '<span class="dashicons dashicons-dismiss" style="color:#dc3232;"></span>';
                break;
        }
    }

    /**
     * Columnas ordenables.
     */
    public static function columnas_ordenables( $columns ) {
        $columns['welow_grupo'] = 'welow_grupo';
        $columns['welow_orden'] = 'welow_orden';
        return $columns;
    }
}
