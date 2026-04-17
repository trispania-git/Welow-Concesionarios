<?php
/**
 * CPT: Marca de vehículos.
 *
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_CPT_Marca {

    const POST_TYPE = 'welow_marca';
    const META_PREFIX = '_welow_marca_';

    /**
     * Categorías de vehículos disponibles.
     */
    public static function get_categorias_disponibles() {
        return array(
            'turismos'     => 'Turismos',
            'suv'          => 'SUV / Crossover',
            'comerciales'  => 'Comerciales',
            'electricos'   => 'Eléctricos',
            'hibridos'     => 'Híbridos',
            'deportivos'   => 'Deportivos',
            'monovolumen'  => 'Monovolumen',
            'pickup'       => 'Pick-up',
        );
    }

    /**
     * Tipos de venta disponibles.
     */
    public static function get_tipos_venta() {
        return array(
            'nuevos'  => 'Nuevos',
            'ocasion' => 'Ocasión (2ª mano)',
            'km0'     => 'KM0',
        );
    }

    public static function init() {
        add_action( 'init', array( __CLASS__, 'registrar_cpt' ) );
        add_action( 'add_meta_boxes', array( __CLASS__, 'registrar_metaboxes' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'guardar_meta' ), 10, 2 );

        // Columnas personalizadas en el listado del admin
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columnas_admin' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'contenido_columnas' ), 10, 2 );
        add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( __CLASS__, 'columnas_ordenables' ) );
    }

    /**
     * Registra el Custom Post Type.
     */
    public static function registrar_cpt() {
        $labels = array(
            'name'                  => 'Marcas',
            'singular_name'        => 'Marca',
            'menu_name'            => 'Marcas',
            'name_admin_bar'       => 'Marca',
            'add_new'              => 'Añadir marca',
            'add_new_item'         => 'Añadir nueva marca',
            'new_item'             => 'Nueva marca',
            'edit_item'            => 'Editar marca',
            'view_item'            => 'Ver marca',
            'all_items'            => 'Todas las marcas',
            'search_items'         => 'Buscar marcas',
            'not_found'            => 'No se encontraron marcas',
            'not_found_in_trash'   => 'No hay marcas en la papelera',
            'featured_image'       => 'Logo de la marca',
            'set_featured_image'   => 'Establecer logo',
            'remove_featured_image' => 'Quitar logo',
            'use_featured_image'   => 'Usar como logo',
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => array( 'slug' => 'marca', 'with_front' => false ),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-car',
            'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        );

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Registra los metaboxes.
     */
    public static function registrar_metaboxes() {
        add_meta_box(
            'welow_marca_datos',
            'Datos de la marca',
            array( __CLASS__, 'render_metabox_datos' ),
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'welow_marca_clasificacion',
            'Clasificación y venta',
            array( __CLASS__, 'render_metabox_clasificacion' ),
            self::POST_TYPE,
            'side',
            'default'
        );

        add_meta_box(
            'welow_marca_config',
            'Configuración de visualización',
            array( __CLASS__, 'render_metabox_config' ),
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    /**
     * Metabox: Datos principales de la marca.
     */
    public static function render_metabox_datos( $post ) {
        wp_nonce_field( 'welow_marca_save', 'welow_marca_nonce' );

        $desc_corta = get_post_meta( $post->ID, self::META_PREFIX . 'desc_corta', true );
        $slogan     = get_post_meta( $post->ID, self::META_PREFIX . 'slogan', true );
        $web        = get_post_meta( $post->ID, self::META_PREFIX . 'web', true );
        $banner_id  = get_post_meta( $post->ID, self::META_PREFIX . 'banner', true );
        $banner_url = $banner_id ? wp_get_attachment_image_url( $banner_id, 'large' ) : '';
        ?>
        <table class="form-table welow-metabox-table">
            <tr>
                <th><label for="welow_desc_corta">Descripción corta</label></th>
                <td>
                    <textarea id="welow_desc_corta" name="welow_desc_corta" rows="3" class="large-text"><?php echo esc_textarea( $desc_corta ); ?></textarea>
                    <p class="description">Texto breve que aparece en las tarjetas del grid.</p>
                </td>
            </tr>
            <tr>
                <th><label for="welow_slogan">Slogan</label></th>
                <td>
                    <input type="text" id="welow_slogan" name="welow_slogan" value="<?php echo esc_attr( $slogan ); ?>" class="large-text" />
                </td>
            </tr>
            <tr>
                <th><label for="welow_web">Web oficial</label></th>
                <td>
                    <input type="url" id="welow_web" name="welow_web" value="<?php echo esc_url( $web ); ?>" class="large-text" placeholder="https://www.marca.com" />
                </td>
            </tr>
            <tr>
                <th><label>Imagen banner</label></th>
                <td>
                    <div class="welow-media-field">
                        <input type="hidden" id="welow_banner" name="welow_banner" value="<?php echo esc_attr( $banner_id ); ?>" />
                        <div id="welow-banner-preview" class="welow-image-preview">
                            <?php if ( $banner_url ) : ?>
                                <img src="<?php echo esc_url( $banner_url ); ?>" alt="" />
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button welow-upload-btn" data-target="welow_banner" data-preview="welow-banner-preview">
                            <?php echo $banner_id ? 'Cambiar imagen' : 'Seleccionar imagen'; ?>
                        </button>
                        <?php if ( $banner_id ) : ?>
                            <button type="button" class="button welow-remove-btn" data-target="welow_banner" data-preview="welow-banner-preview">Quitar</button>
                        <?php endif; ?>
                    </div>
                    <p class="description">Imagen de cabecera para la página individual de la marca. Recomendado: 1920×600px.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Metabox: Clasificación (categorías de vehículos y tipo de venta).
     */
    public static function render_metabox_clasificacion( $post ) {
        $categorias_guardadas = get_post_meta( $post->ID, self::META_PREFIX . 'categorias', true );
        $tipos_guardados      = get_post_meta( $post->ID, self::META_PREFIX . 'tipo_venta', true );

        if ( ! is_array( $categorias_guardadas ) ) {
            $categorias_guardadas = array();
        }
        if ( ! is_array( $tipos_guardados ) ) {
            $tipos_guardados = array();
        }
        ?>
        <p><strong>Categorías de vehículos:</strong></p>
        <?php foreach ( self::get_categorias_disponibles() as $key => $label ) : ?>
            <label class="welow-checkbox-label">
                <input type="checkbox" name="welow_categorias[]" value="<?php echo esc_attr( $key ); ?>"
                    <?php checked( in_array( $key, $categorias_guardadas, true ) ); ?> />
                <?php echo esc_html( $label ); ?>
            </label><br>
        <?php endforeach; ?>

        <hr />
        <p><strong>Tipo de venta:</strong></p>
        <?php foreach ( self::get_tipos_venta() as $key => $label ) : ?>
            <label class="welow-checkbox-label">
                <input type="checkbox" name="welow_tipo_venta[]" value="<?php echo esc_attr( $key ); ?>"
                    <?php checked( in_array( $key, $tipos_guardados, true ) ); ?> />
                <?php echo esc_html( $label ); ?>
            </label><br>
        <?php endforeach; ?>
        <?php
    }

    /**
     * Metabox: Configuración de visualización.
     */
    public static function render_metabox_config( $post ) {
        $orden  = get_post_meta( $post->ID, self::META_PREFIX . 'orden', true );
        $activa = get_post_meta( $post->ID, self::META_PREFIX . 'activa', true );

        // Por defecto, la marca está activa
        if ( '' === $activa ) {
            $activa = '1';
        }
        ?>
        <p>
            <label for="welow_orden"><strong>Orden de visualización:</strong></label><br>
            <input type="number" id="welow_orden" name="welow_orden" value="<?php echo esc_attr( $orden ); ?>" min="0" step="1" style="width: 80px;" />
            <span class="description">Menor = primero</span>
        </p>
        <p>
            <label class="welow-checkbox-label">
                <input type="checkbox" name="welow_activa" value="1" <?php checked( $activa, '1' ); ?> />
                <strong>Marca activa</strong>
            </label>
            <br><span class="description">Desmarcar para ocultar sin borrar.</span>
        </p>
        <?php
    }

    /**
     * Guarda los meta fields.
     */
    public static function guardar_meta( $post_id, $post ) {
        // Verificaciones de seguridad
        if ( ! isset( $_POST['welow_marca_nonce'] ) || ! wp_verify_nonce( $_POST['welow_marca_nonce'], 'welow_marca_save' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Datos principales
        $campos_texto = array(
            'welow_desc_corta' => 'desc_corta',
            'welow_slogan'     => 'slogan',
        );
        foreach ( $campos_texto as $campo => $meta_key ) {
            $valor = isset( $_POST[ $campo ] ) ? sanitize_textarea_field( $_POST[ $campo ] ) : '';
            update_post_meta( $post_id, self::META_PREFIX . $meta_key, $valor );
        }

        // URL
        $web = isset( $_POST['welow_web'] ) ? esc_url_raw( $_POST['welow_web'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'web', $web );

        // Banner (ID de imagen)
        $banner = isset( $_POST['welow_banner'] ) ? absint( $_POST['welow_banner'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'banner', $banner );

        // Categorías (array de checkboxes)
        $categorias = isset( $_POST['welow_categorias'] ) ? array_map( 'sanitize_text_field', $_POST['welow_categorias'] ) : array();
        update_post_meta( $post_id, self::META_PREFIX . 'categorias', $categorias );

        // Tipo de venta (array de checkboxes)
        $tipo_venta = isset( $_POST['welow_tipo_venta'] ) ? array_map( 'sanitize_text_field', $_POST['welow_tipo_venta'] ) : array();
        update_post_meta( $post_id, self::META_PREFIX . 'tipo_venta', $tipo_venta );

        // Orden
        $orden = isset( $_POST['welow_orden'] ) ? absint( $_POST['welow_orden'] ) : 0;
        update_post_meta( $post_id, self::META_PREFIX . 'orden', $orden );

        // Activa
        $activa = isset( $_POST['welow_activa'] ) ? '1' : '0';
        update_post_meta( $post_id, self::META_PREFIX . 'activa', $activa );
    }

    /**
     * Columnas del listado en admin.
     */
    public static function columnas_admin( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;
            if ( 'title' === $key ) {
                $new_columns['welow_logo']       = 'Logo';
                $new_columns['welow_tipo_venta'] = 'Tipo de venta';
                $new_columns['welow_orden']      = 'Orden';
                $new_columns['welow_activa']     = 'Activa';
            }
        }
        return $new_columns;
    }

    /**
     * Contenido de las columnas personalizadas.
     */
    public static function contenido_columnas( $column, $post_id ) {
        switch ( $column ) {
            case 'welow_logo':
                $thumb = get_the_post_thumbnail( $post_id, array( 60, 60 ) );
                echo $thumb ? $thumb : '<span class="dashicons dashicons-format-image" style="color:#ccc;font-size:40px;"></span>';
                break;
            case 'welow_tipo_venta':
                $tipos = get_post_meta( $post_id, self::META_PREFIX . 'tipo_venta', true );
                if ( is_array( $tipos ) && ! empty( $tipos ) ) {
                    $labels = array();
                    $todos  = self::get_tipos_venta();
                    foreach ( $tipos as $tipo ) {
                        if ( isset( $todos[ $tipo ] ) ) {
                            $labels[] = $todos[ $tipo ];
                        }
                    }
                    echo esc_html( implode( ', ', $labels ) );
                } else {
                    echo '—';
                }
                break;
            case 'welow_orden':
                $orden = get_post_meta( $post_id, self::META_PREFIX . 'orden', true );
                echo esc_html( $orden ?: '0' );
                break;
            case 'welow_activa':
                $activa = get_post_meta( $post_id, self::META_PREFIX . 'activa', true );
                echo ( '1' === $activa || '' === $activa ) ? '<span class="dashicons dashicons-yes-alt" style="color:#46b450;"></span>' : '<span class="dashicons dashicons-dismiss" style="color:#dc3232;"></span>';
                break;
        }
    }

    /**
     * Columnas ordenables.
     */
    public static function columnas_ordenables( $columns ) {
        $columns['welow_orden'] = 'welow_orden';
        return $columns;
    }
}
