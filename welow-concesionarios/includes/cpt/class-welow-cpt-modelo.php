<?php
/**
 * CPT: Modelos de vehículos vinculados a marcas.
 *
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_CPT_Modelo {

    const POST_TYPE   = 'welow_modelo';
    const META_PREFIX = '_welow_modelo_';

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
            'name'                  => 'Modelos',
            'singular_name'        => 'Modelo',
            'menu_name'            => 'Modelos',
            'add_new'              => 'Añadir modelo',
            'add_new_item'         => 'Añadir nuevo modelo',
            'edit_item'            => 'Editar modelo',
            'view_item'            => 'Ver modelo',
            'all_items'            => 'Todos los modelos',
            'search_items'         => 'Buscar modelos',
            'not_found'            => 'No se encontraron modelos',
            'not_found_in_trash'   => 'No hay modelos en la papelera',
            'featured_image'       => 'Imagen del modelo',
            'set_featured_image'   => 'Establecer imagen',
            'remove_featured_image' => 'Quitar imagen',
            'use_featured_image'   => 'Usar como imagen del modelo',
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => array( 'slug' => 'modelo', 'with_front' => false ),
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => 6,
            'menu_icon'           => 'dashicons-car',
            'supports'            => array( 'title', 'editor', 'thumbnail' ),
        );

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Metaboxes.
     */
    public static function registrar_metaboxes() {
        add_meta_box(
            'welow_modelo_datos',
            'Datos del modelo',
            array( __CLASS__, 'render_metabox_datos' ),
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'welow_modelo_config',
            'Configuración',
            array( __CLASS__, 'render_metabox_config' ),
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    /**
     * Metabox: Datos del modelo (marca, enlace).
     */
    public static function render_metabox_datos( $post ) {
        wp_nonce_field( 'welow_modelo_save', 'welow_modelo_nonce' );

        $marca_id     = get_post_meta( $post->ID, self::META_PREFIX . 'marca', true );
        $enlace       = get_post_meta( $post->ID, self::META_PREFIX . 'enlace', true );
        $texto_enlace = get_post_meta( $post->ID, self::META_PREFIX . 'texto_enlace', true );

        // Obtener todas las marcas para el selector
        $marcas = get_posts( array(
            'post_type'      => Welow_CPT_Marca::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );
        ?>
        <table class="form-table welow-metabox-table">
            <tr>
                <th><label for="welow_modelo_marca">Marca</label></th>
                <td>
                    <select id="welow_modelo_marca" name="welow_modelo_marca" class="widefat">
                        <option value="">— Seleccionar marca —</option>
                        <?php foreach ( $marcas as $marca ) : ?>
                            <option value="<?php echo esc_attr( $marca->ID ); ?>"
                                <?php selected( $marca_id, $marca->ID ); ?>>
                                <?php echo esc_html( $marca->post_title ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">Marca a la que pertenece este modelo.</p>
                </td>
            </tr>
            <tr>
                <th><label for="welow_modelo_enlace">Enlace</label></th>
                <td>
                    <input type="url" id="welow_modelo_enlace" name="welow_modelo_enlace" value="<?php echo esc_url( $enlace ); ?>" class="large-text" placeholder="https://..." />
                    <p class="description">URL de destino (ficha, catálogo, web oficial del modelo, etc.).</p>
                </td>
            </tr>
            <tr>
                <th><label for="welow_modelo_texto_enlace">Texto del botón</label></th>
                <td>
                    <input type="text" id="welow_modelo_texto_enlace" name="welow_modelo_texto_enlace" value="<?php echo esc_attr( $texto_enlace ); ?>" class="regular-text" placeholder="Ver modelo" />
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Metabox: Configuración (orden, activo).
     */
    public static function render_metabox_config( $post ) {
        $orden  = get_post_meta( $post->ID, self::META_PREFIX . 'orden', true );
        $activo = get_post_meta( $post->ID, self::META_PREFIX . 'activo', true );

        if ( '' === $activo ) {
            $activo = '1';
        }
        ?>
        <p>
            <label for="welow_modelo_orden"><strong>Orden:</strong></label><br>
            <input type="number" id="welow_modelo_orden" name="welow_modelo_orden" value="<?php echo esc_attr( $orden ); ?>" min="0" step="1" style="width: 80px;" />
            <span class="description">Menor = primero</span>
        </p>
        <p>
            <label class="welow-checkbox-label">
                <input type="checkbox" name="welow_modelo_activo" value="1" <?php checked( $activo, '1' ); ?> />
                <strong>Modelo activo</strong>
            </label>
        </p>
        <?php
    }

    /**
     * Guardar meta.
     */
    public static function guardar_meta( $post_id, $post ) {
        if ( ! isset( $_POST['welow_modelo_nonce'] ) || ! wp_verify_nonce( $_POST['welow_modelo_nonce'], 'welow_modelo_save' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Marca
        $marca = isset( $_POST['welow_modelo_marca'] ) ? absint( $_POST['welow_modelo_marca'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'marca', $marca );

        // Enlace
        $enlace = isset( $_POST['welow_modelo_enlace'] ) ? esc_url_raw( $_POST['welow_modelo_enlace'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'enlace', $enlace );

        // Texto enlace
        $texto = isset( $_POST['welow_modelo_texto_enlace'] ) ? sanitize_text_field( $_POST['welow_modelo_texto_enlace'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'texto_enlace', $texto );

        // Orden
        $orden = isset( $_POST['welow_modelo_orden'] ) ? absint( $_POST['welow_modelo_orden'] ) : 0;
        update_post_meta( $post_id, self::META_PREFIX . 'orden', $orden );

        // Activo
        $activo = isset( $_POST['welow_modelo_activo'] ) ? '1' : '0';
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
                $new['welow_imagen'] = 'Imagen';
                $new['welow_marca']  = 'Marca';
                $new['welow_orden']  = 'Orden';
                $new['welow_activo'] = 'Activo';
            }
        }
        unset( $new['date'] );
        return $new;
    }

    /**
     * Contenido columnas.
     */
    public static function contenido_columnas( $column, $post_id ) {
        switch ( $column ) {
            case 'welow_imagen':
                $thumb = get_the_post_thumbnail( $post_id, array( 60, 60 ) );
                echo $thumb ? $thumb : '<span class="dashicons dashicons-format-image" style="color:#ccc;font-size:40px;"></span>';
                break;
            case 'welow_marca':
                $marca_id = get_post_meta( $post_id, self::META_PREFIX . 'marca', true );
                if ( $marca_id ) {
                    $marca = get_post( $marca_id );
                    if ( $marca ) {
                        echo '<a href="' . esc_url( get_edit_post_link( $marca_id ) ) . '">' . esc_html( $marca->post_title ) . '</a>';
                    } else {
                        echo '—';
                    }
                } else {
                    echo '<span style="color:#dc3232;">Sin marca</span>';
                }
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
        $columns['welow_marca'] = 'welow_marca';
        $columns['welow_orden'] = 'welow_orden';
        return $columns;
    }
}
