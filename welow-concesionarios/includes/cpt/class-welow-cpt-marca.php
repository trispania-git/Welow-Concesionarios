<?php
/**
 * CPT: Marca de vehículos.
 *
 * @package Welow_Concesionarios
 * @since 1.0.0
 * @version 1.2.0 — Eliminada clasificación (categorías + tipo_venta).
 *                   Ahora la categoría/combustible se gestionan a nivel modelo.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_CPT_Marca {

    const POST_TYPE = 'welow_marca';
    const META_PREFIX = '_welow_marca_';

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
            'show_in_menu'        => 'welow_concesionarios',
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => array( 'slug' => 'marca', 'with_front' => false ),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_icon'           => 'dashicons-awards',
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
            'welow_marca_logos',
            'Logos de la marca',
            array( __CLASS__, 'render_metabox_logos' ),
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'welow_marca_banners',
            'Banners de la marca',
            array( __CLASS__, 'render_metabox_banners' ),
            self::POST_TYPE,
            'normal',
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
     * Helper: renderiza un campo de imagen con preview y botón.
     */
    private static function render_campo_imagen( $field_name, $label, $description = '', $value = '' ) {
        $img_url  = $value ? wp_get_attachment_image_url( $value, 'medium' ) : '';
        $preview_id = $field_name . '_preview';
        ?>
        <div class="welow-img-field-group">
            <label class="welow-img-label"><strong><?php echo esc_html( $label ); ?></strong></label>
            <div class="welow-media-field">
                <input type="hidden" id="<?php echo esc_attr( $field_name ); ?>"
                       name="<?php echo esc_attr( $field_name ); ?>"
                       value="<?php echo esc_attr( $value ); ?>" />
                <div id="<?php echo esc_attr( $preview_id ); ?>" class="welow-image-preview">
                    <?php if ( $img_url ) : ?>
                        <img src="<?php echo esc_url( $img_url ); ?>" alt="" />
                    <?php endif; ?>
                </div>
                <div class="welow-img-buttons">
                    <button type="button" class="button welow-upload-btn"
                            data-target="<?php echo esc_attr( $field_name ); ?>"
                            data-preview="<?php echo esc_attr( $preview_id ); ?>">
                        <?php echo $value ? 'Cambiar' : 'Seleccionar'; ?>
                    </button>
                    <?php if ( $value ) : ?>
                        <button type="button" class="button welow-remove-btn"
                                data-target="<?php echo esc_attr( $field_name ); ?>"
                                data-preview="<?php echo esc_attr( $preview_id ); ?>">Quitar</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ( $description ) : ?>
                <p class="description"><?php echo esc_html( $description ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Metabox: Logos (original, negro, blanco).
     */
    public static function render_metabox_logos( $post ) {
        $logo_original = get_post_thumbnail_id( $post->ID );
        $logo_negro    = get_post_meta( $post->ID, self::META_PREFIX . 'logo_negro', true );
        $logo_blanco   = get_post_meta( $post->ID, self::META_PREFIX . 'logo_blanco', true );
        ?>
        <div class="welow-logos-grid">
            <?php
            self::render_campo_imagen(
                'welow_logo_original_featured',
                'Logo original',
                'Se guarda como Imagen destacada. PNG sin fondo, 350×225px.',
                $logo_original
            );

            self::render_campo_imagen(
                'welow_logo_negro',
                'Logo negro',
                'PNG sin fondo, 350×225px.',
                $logo_negro
            );

            self::render_campo_imagen(
                'welow_logo_blanco',
                'Logo blanco',
                'PNG sin fondo, 350×225px.',
                $logo_blanco
            );
            ?>
        </div>
        <style>
            /* v2.9.1 — auto-fill para adaptarse al ancho del metabox sin desbordar */
            .welow-logos-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 20px;
            }
            .welow-logos-grid > .welow-img-field-group { min-width: 0; }
            .welow-logos-grid .welow-image-preview {
                background: #f5f5f5; padding: 10px; min-height: 100px;
                max-width: 100%; overflow: hidden;
            }
            .welow-logos-grid .welow-image-preview img { max-width: 100%; height: auto; }
        </style>
        <?php
    }

    /**
     * Metabox: Banners (Portada + Zona media, cada uno con desktop + móvil).
     */
    public static function render_metabox_banners( $post ) {
        $banner_portada_desktop = get_post_meta( $post->ID, self::META_PREFIX . 'banner_portada_desktop', true );
        $banner_portada_movil   = get_post_meta( $post->ID, self::META_PREFIX . 'banner_portada_movil', true );
        $banner_media_desktop   = get_post_meta( $post->ID, self::META_PREFIX . 'banner_media_desktop', true );
        $banner_media_movil     = get_post_meta( $post->ID, self::META_PREFIX . 'banner_media_movil', true );
        ?>
        <h3 class="welow-banner-section-title">
            <span class="dashicons dashicons-format-image"></span> Banner de Portada
        </h3>
        <div class="welow-banner-pair">
            <?php
            self::render_campo_imagen(
                'welow_banner_portada_desktop',
                'Escritorio (1920×600)',
                'Se muestra en pantallas > 980px.',
                $banner_portada_desktop
            );

            self::render_campo_imagen(
                'welow_banner_portada_movil',
                'Móvil (600×338)',
                'Se muestra en pantallas ≤ 980px.',
                $banner_portada_movil
            );
            ?>
        </div>

        <hr style="margin: 30px 0;">

        <h3 class="welow-banner-section-title">
            <span class="dashicons dashicons-align-center"></span> Banner de Zona Media
        </h3>
        <div class="welow-banner-pair">
            <?php
            self::render_campo_imagen(
                'welow_banner_media_desktop',
                'Escritorio (1920×400)',
                'Se muestra en pantallas > 980px.',
                $banner_media_desktop
            );

            self::render_campo_imagen(
                'welow_banner_media_movil',
                'Móvil (600×338)',
                'Se muestra en pantallas ≤ 980px.',
                $banner_media_movil
            );
            ?>
        </div>
        <style>
            .welow-banner-section-title { display: flex; align-items: center; gap: 8px; margin: 0 0 15px; font-size: 15px; }
            .welow-banner-section-title .dashicons { color: #2563eb; }
            /* v2.9.1 — auto-fill */
            .welow-banner-pair {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
            }
            .welow-banner-pair > .welow-img-field-group { min-width: 0; }
            .welow-banner-pair .welow-image-preview {
                min-height: 120px; background: #f5f5f5;
                max-width: 100%; overflow: hidden;
            }
            .welow-banner-pair .welow-image-preview img { max-width: 100%; height: auto; }
        </style>
        <?php
    }

    /**
     * Metabox: Datos textuales de la marca.
     */
    public static function render_metabox_datos( $post ) {
        wp_nonce_field( 'welow_marca_save', 'welow_marca_nonce' );

        $desc_corta = get_post_meta( $post->ID, self::META_PREFIX . 'desc_corta', true );
        $slogan     = get_post_meta( $post->ID, self::META_PREFIX . 'slogan', true );
        $web        = get_post_meta( $post->ID, self::META_PREFIX . 'web', true );
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
        </table>
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

        // Logo original → se guarda como imagen destacada
        if ( isset( $_POST['welow_logo_original_featured'] ) ) {
            $featured = absint( $_POST['welow_logo_original_featured'] );
            if ( $featured ) {
                set_post_thumbnail( $post_id, $featured );
            } else {
                delete_post_thumbnail( $post_id );
            }
        }

        // Logos adicionales (negro, blanco)
        $campos_imagen = array(
            'welow_logo_negro'              => 'logo_negro',
            'welow_logo_blanco'             => 'logo_blanco',
            'welow_banner_portada_desktop'  => 'banner_portada_desktop',
            'welow_banner_portada_movil'    => 'banner_portada_movil',
            'welow_banner_media_desktop'    => 'banner_media_desktop',
            'welow_banner_media_movil'      => 'banner_media_movil',
        );
        foreach ( $campos_imagen as $post_key => $meta_key ) {
            $val = isset( $_POST[ $post_key ] ) ? absint( $_POST[ $post_key ] ) : '';
            update_post_meta( $post_id, self::META_PREFIX . $meta_key, $val );
        }

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
                $new_columns['welow_logo']   = 'Logo';
                $new_columns['welow_orden']  = 'Orden';
                $new_columns['welow_activa'] = 'Activa';
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
