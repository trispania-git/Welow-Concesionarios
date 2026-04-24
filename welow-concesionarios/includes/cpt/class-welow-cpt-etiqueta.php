<?php
/**
 * CPT: Etiquetas visuales para modelos (ej: "Eco", "Nuevo", "Oferta").
 *
 * @since 1.1.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_CPT_Etiqueta {

    const POST_TYPE   = 'welow_etiqueta';
    const META_PREFIX = '_welow_etiqueta_';

    public static function init() {
        add_action( 'init', array( __CLASS__, 'registrar_cpt' ) );
        add_action( 'add_meta_boxes', array( __CLASS__, 'registrar_metaboxes' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'guardar_meta' ), 10, 2 );

        // Columnas
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columnas_admin' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'contenido_columnas' ), 10, 2 );
    }

    public static function registrar_cpt() {
        $labels = array(
            'name'               => 'Etiquetas',
            'singular_name'      => 'Etiqueta',
            'menu_name'          => 'Etiquetas',
            'add_new'            => 'Añadir etiqueta',
            'add_new_item'       => 'Añadir nueva etiqueta',
            'edit_item'          => 'Editar etiqueta',
            'all_items'          => 'Todas las etiquetas',
            'search_items'       => 'Buscar etiquetas',
            'not_found'          => 'No se encontraron etiquetas',
        );

        register_post_type( self::POST_TYPE, array(
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => 'welow_concesionarios',
            'show_in_rest'       => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_icon'          => 'dashicons-tag',
            'supports'           => array( 'title' ),
        ) );
    }

    public static function registrar_metaboxes() {
        add_meta_box(
            'welow_etiqueta_datos',
            'Imagen de la etiqueta',
            array( __CLASS__, 'render_metabox_datos' ),
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'welow_etiqueta_config',
            'Configuración',
            array( __CLASS__, 'render_metabox_config' ),
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    public static function render_metabox_datos( $post ) {
        wp_nonce_field( 'welow_etiqueta_save', 'welow_etiqueta_nonce' );

        $imagen_id = get_post_meta( $post->ID, self::META_PREFIX . 'imagen', true );
        $img_url   = $imagen_id ? wp_get_attachment_image_url( $imagen_id, 'medium' ) : '';
        ?>
        <div class="welow-media-field">
            <input type="hidden" id="welow_etiqueta_imagen" name="welow_etiqueta_imagen" value="<?php echo esc_attr( $imagen_id ); ?>" />
            <div id="welow-etiqueta-img-preview" class="welow-image-preview">
                <?php if ( $img_url ) : ?>
                    <img src="<?php echo esc_url( $img_url ); ?>" alt="" />
                <?php endif; ?>
            </div>
            <div class="welow-img-buttons">
                <button type="button" class="button button-primary welow-upload-btn"
                        data-target="welow_etiqueta_imagen"
                        data-preview="welow-etiqueta-img-preview">
                    <span class="dashicons dashicons-admin-media" style="margin-top:4px;"></span>
                    <?php echo $imagen_id ? 'Cambiar desde la galería' : 'Seleccionar desde la galería'; ?>
                </button>
                <?php if ( $imagen_id ) : ?>
                    <button type="button" class="button welow-remove-btn"
                            data-target="welow_etiqueta_imagen"
                            data-preview="welow-etiqueta-img-preview">Quitar</button>
                <?php endif; ?>
            </div>
        </div>
        <p class="description">Imagen que representa esta etiqueta (ej: icono "Eco", badge "Nuevo", sello "Oferta"). Recomendado: PNG transparente.</p>
        <?php
    }

    public static function render_metabox_config( $post ) {
        $orden  = get_post_meta( $post->ID, self::META_PREFIX . 'orden', true );
        $activa = get_post_meta( $post->ID, self::META_PREFIX . 'activa', true );
        if ( '' === $activa ) $activa = '1';
        ?>
        <p>
            <label for="welow_etiqueta_orden"><strong>Orden:</strong></label><br>
            <input type="number" id="welow_etiqueta_orden" name="welow_etiqueta_orden" value="<?php echo esc_attr( $orden ); ?>" min="0" step="1" style="width: 80px;" />
        </p>
        <p>
            <label class="welow-checkbox-label">
                <input type="checkbox" name="welow_etiqueta_activa" value="1" <?php checked( $activa, '1' ); ?> />
                <strong>Etiqueta activa</strong>
            </label>
        </p>
        <?php
    }

    public static function guardar_meta( $post_id, $post ) {
        if ( ! isset( $_POST['welow_etiqueta_nonce'] ) || ! wp_verify_nonce( $_POST['welow_etiqueta_nonce'], 'welow_etiqueta_save' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $imagen = isset( $_POST['welow_etiqueta_imagen'] ) ? absint( $_POST['welow_etiqueta_imagen'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'imagen', $imagen );

        $orden = isset( $_POST['welow_etiqueta_orden'] ) ? absint( $_POST['welow_etiqueta_orden'] ) : 0;
        update_post_meta( $post_id, self::META_PREFIX . 'orden', $orden );

        $activa = isset( $_POST['welow_etiqueta_activa'] ) ? '1' : '0';
        update_post_meta( $post_id, self::META_PREFIX . 'activa', $activa );
    }

    public static function columnas_admin( $columns ) {
        $new = array();
        foreach ( $columns as $key => $value ) {
            $new[ $key ] = $value;
            if ( 'title' === $key ) {
                $new['welow_imagen'] = 'Imagen';
                $new['welow_orden']  = 'Orden';
                $new['welow_activa'] = 'Activa';
            }
        }
        unset( $new['date'] );
        return $new;
    }

    public static function contenido_columnas( $column, $post_id ) {
        switch ( $column ) {
            case 'welow_imagen':
                $img_id = get_post_meta( $post_id, self::META_PREFIX . 'imagen', true );
                echo $img_id
                    ? wp_get_attachment_image( $img_id, array( 50, 50 ) )
                    : '<span style="color:#ccc;">—</span>';
                break;
            case 'welow_orden':
                echo esc_html( get_post_meta( $post_id, self::META_PREFIX . 'orden', true ) ?: '0' );
                break;
            case 'welow_activa':
                $activa = get_post_meta( $post_id, self::META_PREFIX . 'activa', true );
                echo ( '1' === $activa || '' === $activa )
                    ? '<span class="dashicons dashicons-yes-alt" style="color:#46b450;"></span>'
                    : '<span class="dashicons dashicons-dismiss" style="color:#dc3232;"></span>';
                break;
        }
    }
}
