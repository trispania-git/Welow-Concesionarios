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
            'show_in_menu'        => 'welow_concesionarios',
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => array( 'slug' => 'modelo', 'with_front' => false ),
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_icon'           => 'dashicons-car',
            'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
            'taxonomies'          => array( 'welow_combustible', 'welow_categoria_modelo' ),
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
            'welow_modelo_galeria',
            'Galería de imágenes',
            array( __CLASS__, 'render_metabox_galeria' ),
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'welow_modelo_precio',
            'Precio',
            array( __CLASS__, 'render_metabox_precio' ),
            self::POST_TYPE,
            'normal',
            'default'
        );

        add_meta_box(
            'welow_modelo_etiquetas',
            'Etiquetas visuales',
            array( __CLASS__, 'render_metabox_etiquetas' ),
            self::POST_TYPE,
            'side',
            'default'
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
     * Helper: renderiza un campo de imagen con preview y botón.
     */
    private static function render_campo_imagen( $field_name, $label, $value = '' ) {
        $img_url    = $value ? wp_get_attachment_image_url( $value, 'medium' ) : '';
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
        </div>
        <?php
    }

    /**
     * Metabox: Galería de imágenes (principal como featured + 4 adicionales).
     *
     * @since 1.1.0
     */
    public static function render_metabox_galeria( $post ) {
        $img_principal = get_post_thumbnail_id( $post->ID );
        $img_2 = get_post_meta( $post->ID, self::META_PREFIX . 'img_2', true );
        $img_3 = get_post_meta( $post->ID, self::META_PREFIX . 'img_3', true );
        $img_4 = get_post_meta( $post->ID, self::META_PREFIX . 'img_4', true );
        $img_5 = get_post_meta( $post->ID, self::META_PREFIX . 'img_5', true );
        ?>
        <p class="description" style="margin-top:0;">
            PNG transparentes, tamaño recomendado <strong>550×300px</strong>. La imagen principal se guarda como "Imagen destacada".
        </p>
        <div class="welow-galeria-grid">
            <?php
            self::render_campo_imagen( 'welow_img_principal_featured', 'Imagen principal (destacada)', $img_principal );
            self::render_campo_imagen( 'welow_modelo_img_2', 'Imagen 2', $img_2 );
            self::render_campo_imagen( 'welow_modelo_img_3', 'Imagen 3', $img_3 );
            self::render_campo_imagen( 'welow_modelo_img_4', 'Imagen 4', $img_4 );
            self::render_campo_imagen( 'welow_modelo_img_5', 'Imagen 5', $img_5 );
            ?>
        </div>
        <style>
            /* v2.9.1 — auto-fill se adapta al ancho real del metabox sin
               desbordar el sidebar de etiquetas (antes era repeat(5, 1fr)
               y la quinta columna se metía en la zona derecha) */
            .welow-galeria-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 12px;
            }
            .welow-galeria-grid > .welow-img-field-group {
                min-width: 0;          /* permite que el grid encoja la card */
            }
            .welow-galeria-grid .welow-image-preview {
                min-height: 80px;
                background: #f5f5f5;
                max-width: 100%;
                overflow: hidden;
            }
            .welow-galeria-grid .welow-image-preview img {
                max-width: 100%;
                height: auto;
            }
        </style>
        <?php
    }

    /**
     * Metabox: Precio + disclaimer override.
     *
     * @since 1.1.0
     */
    public static function render_metabox_precio( $post ) {
        $precio_desde = get_post_meta( $post->ID, self::META_PREFIX . 'precio_desde', true );
        $disclaimer   = get_post_meta( $post->ID, self::META_PREFIX . 'disclaimer', true );
        $moneda       = class_exists( 'Welow_Settings' ) ? Welow_Settings::get( 'moneda_simbolo', '€' ) : '€';

        $disclaimer_global = class_exists( 'Welow_Settings' ) ? Welow_Settings::get( 'disclaimer_global', '' ) : '';
        ?>
        <table class="form-table welow-metabox-table">
            <tr>
                <th><label for="welow_precio_desde">Precio desde</label></th>
                <td>
                    <input type="number" id="welow_precio_desde" name="welow_precio_desde"
                           value="<?php echo esc_attr( $precio_desde ); ?>"
                           min="0" step="0.01" class="regular-text" />
                    <span style="margin-left:8px;"><?php echo esc_html( $moneda ); ?></span>
                    <p class="description">Precio "desde" del modelo. Déjalo vacío si no quieres mostrar precio.</p>
                </td>
            </tr>
            <tr>
                <th><label for="welow_modelo_disclaimer">Disclaimer específico</label></th>
                <td>
                    <textarea id="welow_modelo_disclaimer" name="welow_modelo_disclaimer"
                              rows="5" class="large-text"
                              placeholder="<?php echo esc_attr( $disclaimer_global ?: 'Se usará el disclaimer global definido en Configuraciones.' ); ?>"><?php echo esc_textarea( $disclaimer ); ?></textarea>
                    <p class="description">
                        Si lo dejas vacío, se usará el disclaimer global de Configuraciones.
                        <?php if ( $disclaimer_global ) : ?>
                            <br><em>Global actual:</em> <?php echo esc_html( wp_trim_words( $disclaimer_global, 20 ) ); ?>
                        <?php else : ?>
                            <br><strong>Aviso:</strong> No hay disclaimer global definido.
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=welow_settings' ) ); ?>">Configurarlo ahora →</a>
                        <?php endif; ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Metabox: Etiquetas visuales (multi-select de CPT welow_etiqueta).
     *
     * @since 1.1.0
     */
    public static function render_metabox_etiquetas( $post ) {
        $etiquetas_guardadas = get_post_meta( $post->ID, self::META_PREFIX . 'etiquetas', true );
        if ( ! is_array( $etiquetas_guardadas ) ) $etiquetas_guardadas = array();

        $etiquetas = get_posts( array(
            'post_type'      => 'welow_etiqueta',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        if ( empty( $etiquetas ) ) {
            ?>
            <p><em>No hay etiquetas disponibles.</em></p>
            <p>
                <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=welow_etiqueta' ) ); ?>" class="button">
                    Crear primera etiqueta
                </a>
            </p>
            <?php
            return;
        }
        ?>
        <p class="description" style="margin-top:0;">
            Selecciona las etiquetas visuales a mostrar en este modelo.
        </p>
        <div class="welow-etiquetas-list">
            <?php foreach ( $etiquetas as $et ) :
                $img_id  = get_post_meta( $et->ID, '_welow_etiqueta_imagen', true );
                $img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'thumbnail' ) : '';
            ?>
                <label class="welow-etiqueta-item <?php echo in_array( $et->ID, $etiquetas_guardadas ) ? 'welow-etiqueta-item--selected' : ''; ?>">
                    <input type="checkbox" name="welow_modelo_etiquetas[]"
                           value="<?php echo esc_attr( $et->ID ); ?>"
                           <?php checked( in_array( $et->ID, $etiquetas_guardadas ) ); ?> />
                    <?php if ( $img_url ) : ?>
                        <img src="<?php echo esc_url( $img_url ); ?>" alt="" />
                    <?php endif; ?>
                    <span><?php echo esc_html( $et->post_title ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <style>
            .welow-etiquetas-list { display: flex; flex-direction: column; gap: 6px; }
            .welow-etiqueta-item {
                display: flex; align-items: center; gap: 8px;
                padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;
                cursor: pointer; transition: all 0.15s;
            }
            .welow-etiqueta-item:hover { border-color: #2563eb; background: #f0f9ff; }
            .welow-etiqueta-item--selected { border-color: #2563eb; background: #eff6ff; }
            .welow-etiqueta-item img { width: 32px; height: 32px; object-fit: contain; }
        </style>
        <?php
    }

    /**
     * Metabox: Datos del modelo (marca, enlace, plazas).
     *
     * @since 1.0.0
     * @version 1.2.0 — Añadido campo plazas.
     */
    public static function render_metabox_datos( $post ) {
        wp_nonce_field( 'welow_modelo_save', 'welow_modelo_nonce' );

        $marca_id     = get_post_meta( $post->ID, self::META_PREFIX . 'marca', true );
        $enlace       = get_post_meta( $post->ID, self::META_PREFIX . 'enlace', true );
        $texto_enlace = get_post_meta( $post->ID, self::META_PREFIX . 'texto_enlace', true );
        $plazas       = get_post_meta( $post->ID, self::META_PREFIX . 'plazas', true );
        // v2.10.0
        $rotulo       = get_post_meta( $post->ID, self::META_PREFIX . 'rotulo', true );
        $rotulo_color = get_post_meta( $post->ID, self::META_PREFIX . 'rotulo_color', true );

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
            <tr>
                <th><label for="welow_modelo_plazas">Plazas</label></th>
                <td>
                    <input type="number" id="welow_modelo_plazas" name="welow_modelo_plazas"
                           value="<?php echo esc_attr( $plazas ); ?>"
                           min="1" max="20" step="1" style="width: 80px;" />
                    <p class="description">Número de plazas del vehículo (1–20). Déjalo vacío si no aplica.</p>
                </td>
            </tr>
            <tr>
                <th><label for="welow_modelo_rotulo">Rótulo destacado</label></th>
                <td>
                    <input type="text" id="welow_modelo_rotulo" name="welow_modelo_rotulo"
                           value="<?php echo esc_attr( $rotulo ); ?>" class="large-text"
                           maxlength="60"
                           placeholder="ej: NUEVO · OFERTA EXCLUSIVA · 100% ELÉCTRICO · NOVEDAD 2026" />
                    <p class="description">Texto opcional destacado que aparece en la card del modelo (encima del título).
                    Ideal para llamadas de atención cortas. Máximo 60 caracteres.</p>
                </td>
            </tr>
            <tr>
                <th><label for="welow_modelo_rotulo_color">Color del rótulo</label></th>
                <td>
                    <input type="text" id="welow_modelo_rotulo_color" name="welow_modelo_rotulo_color"
                           value="<?php echo esc_attr( $rotulo_color ); ?>"
                           placeholder="#2563eb" class="welow-color-field"
                           data-default-color="#2563eb" />
                    <p class="description">Color de fondo del rótulo. Si lo dejas vacío, se usa el azul por defecto del plugin.</p>
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

        // Plazas (v1.2.0)
        $plazas = isset( $_POST['welow_modelo_plazas'] ) && '' !== $_POST['welow_modelo_plazas']
            ? absint( $_POST['welow_modelo_plazas'] )
            : '';
        update_post_meta( $post_id, self::META_PREFIX . 'plazas', $plazas );

        // Rótulo destacado (v2.10.0)
        $rotulo = isset( $_POST['welow_modelo_rotulo'] ) ? sanitize_text_field( $_POST['welow_modelo_rotulo'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'rotulo', $rotulo );

        $rotulo_color = isset( $_POST['welow_modelo_rotulo_color'] ) ? sanitize_text_field( $_POST['welow_modelo_rotulo_color'] ) : '';
        // Validar formato hex (#000 o #000000)
        if ( $rotulo_color && ! preg_match( '/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $rotulo_color ) ) {
            $rotulo_color = '';
        }
        update_post_meta( $post_id, self::META_PREFIX . 'rotulo_color', $rotulo_color );

        // Orden
        $orden = isset( $_POST['welow_modelo_orden'] ) ? absint( $_POST['welow_modelo_orden'] ) : 0;
        update_post_meta( $post_id, self::META_PREFIX . 'orden', $orden );

        // Activo
        $activo = isset( $_POST['welow_modelo_activo'] ) ? '1' : '0';
        update_post_meta( $post_id, self::META_PREFIX . 'activo', $activo );

        // === v1.1.0: Galería + precio + disclaimer + etiquetas ===

        // Imagen principal → se guarda como imagen destacada
        if ( isset( $_POST['welow_img_principal_featured'] ) ) {
            $featured = absint( $_POST['welow_img_principal_featured'] );
            if ( $featured ) {
                set_post_thumbnail( $post_id, $featured );
            } else {
                delete_post_thumbnail( $post_id );
            }
        }

        // Imágenes adicionales 2..5
        foreach ( array( 2, 3, 4, 5 ) as $n ) {
            $key = 'welow_modelo_img_' . $n;
            $val = isset( $_POST[ $key ] ) ? absint( $_POST[ $key ] ) : '';
            update_post_meta( $post_id, self::META_PREFIX . 'img_' . $n, $val );
        }

        // Precio desde
        $precio = isset( $_POST['welow_precio_desde'] ) ? sanitize_text_field( $_POST['welow_precio_desde'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'precio_desde', $precio );

        // Disclaimer específico (override)
        $disclaimer = isset( $_POST['welow_modelo_disclaimer'] ) ? wp_kses_post( $_POST['welow_modelo_disclaimer'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'disclaimer', $disclaimer );

        // Etiquetas (array de IDs)
        $etiquetas = isset( $_POST['welow_modelo_etiquetas'] ) && is_array( $_POST['welow_modelo_etiquetas'] )
            ? array_map( 'absint', $_POST['welow_modelo_etiquetas'] )
            : array();
        update_post_meta( $post_id, self::META_PREFIX . 'etiquetas', $etiquetas );
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
