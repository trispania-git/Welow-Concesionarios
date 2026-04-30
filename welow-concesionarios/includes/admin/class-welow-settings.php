<?php
/**
 * Página de Configuraciones del plugin.
 * Disclaimer global del precio + icono + opciones generales.
 *
 * @since 1.1.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Settings {

    const OPTION_KEY = 'welow_conc_settings';
    const PAGE_SLUG  = 'welow_settings';

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'registrar_pagina' ) );
        add_action( 'admin_init', array( __CLASS__, 'registrar_campos' ) );
        add_action( 'admin_post_welow_cargar_marcas_externas', array( __CLASS__, 'cargar_marcas_externas' ) );
        add_action( 'admin_notices', array( __CLASS__, 'mostrar_aviso_carga_marcas' ) );
    }

    /**
     * Handler del botón "Cargar marcas externas por defecto".
     * Carga las 99 marcas pre-cargadas del catálogo (idempotente).
     *
     * @since 2.3.0
     */
    public static function cargar_marcas_externas() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado' );
        check_admin_referer( 'welow_cargar_marcas_externas' );

        $resumen = array( 'creadas' => 0, 'existentes' => 0 );
        if ( class_exists( 'Welow_Tax_Marca_Externa' ) ) {
            $resumen = Welow_Tax_Marca_Externa::crear_terminos_defecto();
        }

        // También sincronizamos las marcas oficiales por si acaso
        if ( class_exists( 'Welow_Marca_Sync' ) ) {
            Welow_Marca_Sync::sincronizar_todas();
        }

        set_transient( 'welow_marcas_carga_resumen_' . get_current_user_id(), $resumen, 60 );

        wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
        exit;
    }

    /**
     * Muestra el aviso del resultado tras cargar marcas.
     */
    public static function mostrar_aviso_carga_marcas() {
        $screen = get_current_screen();
        if ( ! $screen || false === strpos( $screen->id, 'welow_settings' ) ) return;

        $key = 'welow_marcas_carga_resumen_' . get_current_user_id();
        $resumen = get_transient( $key );
        if ( ! $resumen ) return;

        delete_transient( $key );

        $msg = sprintf(
            'Marcas externas cargadas: <strong>%d nuevas</strong>, %d ya existían (no se han tocado).',
            intval( $resumen['creadas'] ?? 0 ),
            intval( $resumen['existentes'] ?? 0 )
        );
        echo '<div class="notice notice-success is-dismissible"><p>' . wp_kses_post( $msg ) . '</p></div>';
    }

    public static function registrar_pagina() {
        add_submenu_page(
            'welow_concesionarios',
            'Configuraciones',
            'Configuraciones',
            'manage_options',
            self::PAGE_SLUG,
            array( __CLASS__, 'render_pagina' )
        );
    }

    public static function registrar_campos() {
        register_setting( 'welow_settings_group', self::OPTION_KEY, array(
            'type'              => 'array',
            'sanitize_callback' => array( __CLASS__, 'sanitizar_opciones' ),
            'default'           => array(),
        ) );
    }

    public static function sanitizar_opciones( $input ) {
        $output = array();

        $output['disclaimer_global'] = isset( $input['disclaimer_global'] )
            ? wp_kses_post( $input['disclaimer_global'] )
            : '';

        $output['disclaimer_icono'] = isset( $input['disclaimer_icono'] )
            ? absint( $input['disclaimer_icono'] )
            : 0;

        $output['moneda_simbolo'] = isset( $input['moneda_simbolo'] )
            ? sanitize_text_field( $input['moneda_simbolo'] )
            : '€';

        // v2.0.0 — Sección iconos
        if ( isset( $input['iconos'] ) && class_exists( 'Welow_Icons' ) ) {
            $output['iconos'] = Welow_Icons::sanitize( $input['iconos'] );
        }

        // v2.6.0 — Cabecera (header)
        if ( isset( $input['header'] ) && is_array( $input['header'] ) ) {
            $h = $input['header'];
            $output['header'] = array(
                'logo_id'           => isset( $h['logo_id'] ) ? absint( $h['logo_id'] ) : 0,
                'logo_movil_id'     => isset( $h['logo_movil_id'] ) ? absint( $h['logo_movil_id'] ) : 0,
                'logo_altura'       => isset( $h['logo_altura'] ) ? absint( $h['logo_altura'] ) : 50,
                'menu_id'           => isset( $h['menu_id'] ) ? absint( $h['menu_id'] ) : 0,
                'telefono'          => isset( $h['telefono'] ) ? sanitize_text_field( $h['telefono'] ) : '',
                'boton_texto'       => isset( $h['boton_texto'] ) ? sanitize_text_field( $h['boton_texto'] ) : '',
                'boton_enlace'      => isset( $h['boton_enlace'] ) ? esc_url_raw( $h['boton_enlace'] ) : '',
                'boton2_texto'      => isset( $h['boton2_texto'] ) ? sanitize_text_field( $h['boton2_texto'] ) : '',
                'boton2_enlace'     => isset( $h['boton2_enlace'] ) ? esc_url_raw( $h['boton2_enlace'] ) : '',
                'color_fondo'       => isset( $h['color_fondo'] ) ? sanitize_hex_color( $h['color_fondo'] ) : '',
                'color_texto'       => isset( $h['color_texto'] ) ? sanitize_hex_color( $h['color_texto'] ) : '',
                'color_boton'       => isset( $h['color_boton'] ) ? sanitize_hex_color( $h['color_boton'] ) : '',
                'color_boton_texto' => isset( $h['color_boton_texto'] ) ? sanitize_hex_color( $h['color_boton_texto'] ) : '',
                'sticky'            => ! empty( $h['sticky'] ),
            );
        }

        return $output;
    }

    /**
     * Helper público para obtener una opción.
     */
    public static function get( $key, $default = '' ) {
        $options = get_option( self::OPTION_KEY, array() );
        return isset( $options[ $key ] ) && '' !== $options[ $key ]
            ? $options[ $key ]
            : $default;
    }

    public static function render_pagina() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $options   = get_option( self::OPTION_KEY, array() );
        $disclaimer = isset( $options['disclaimer_global'] ) ? $options['disclaimer_global'] : '';
        $icono_id   = isset( $options['disclaimer_icono'] ) ? intval( $options['disclaimer_icono'] ) : 0;
        $icono_url  = $icono_id ? wp_get_attachment_image_url( $icono_id, 'thumbnail' ) : '';
        $moneda     = isset( $options['moneda_simbolo'] ) ? $options['moneda_simbolo'] : '€';

        $disclaimer_default = 'Aviso legal: Precio indicativo, sin incluir opciones ni gastos de matriculación, IVA incluido. Precio "desde" basado en el precio de venta al público recomendado por el fabricante. Oferta informativa y no contractual, reservada a clientes particulares. Los precios pueden variar según las actualizaciones de tarifas del fabricante.';
        ?>
        <div class="wrap">
            <h1>Configuraciones <span style="font-size:14px;color:#666;">v<?php echo esc_html( WELOW_CONC_VERSION ); ?></span></h1>

            <form method="post" action="options.php">
                <?php settings_fields( 'welow_settings_group' ); ?>

                <h2 class="title">Disclaimer de precio</h2>
                <p>Texto legal que aparecerá junto al precio "desde" de cada modelo. Puedes sobrescribirlo en cada modelo individualmente si es necesario.</p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="welow_disclaimer_global">Texto del disclaimer global</label>
                        </th>
                        <td>
                            <textarea id="welow_disclaimer_global"
                                      name="<?php echo esc_attr( self::OPTION_KEY ); ?>[disclaimer_global]"
                                      rows="6" class="large-text"
                                      placeholder="<?php echo esc_attr( $disclaimer_default ); ?>"><?php echo esc_textarea( $disclaimer ); ?></textarea>
                            <p class="description">Si lo dejas vacío, no se mostrará ningún disclaimer global.</p>
                            <p>
                                <button type="button" class="button" id="welow-usar-default">Usar texto por defecto</button>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Icono del disclaimer</label></th>
                        <td>
                            <div class="welow-media-field">
                                <input type="hidden" id="welow_disclaimer_icono"
                                       name="<?php echo esc_attr( self::OPTION_KEY ); ?>[disclaimer_icono]"
                                       value="<?php echo esc_attr( $icono_id ); ?>" />
                                <div id="welow-icono-preview" class="welow-image-preview" style="max-width:80px;">
                                    <?php if ( $icono_url ) : ?>
                                        <img src="<?php echo esc_url( $icono_url ); ?>" alt="" style="max-width:60px;" />
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button welow-upload-btn"
                                        data-target="welow_disclaimer_icono"
                                        data-preview="welow-icono-preview">
                                    <?php echo $icono_id ? 'Cambiar icono' : 'Seleccionar icono'; ?>
                                </button>
                                <?php if ( $icono_id ) : ?>
                                    <button type="button" class="button welow-remove-btn"
                                            data-target="welow_disclaimer_icono"
                                            data-preview="welow-icono-preview">Quitar</button>
                                <?php endif; ?>
                            </div>
                            <p class="description">Icono ⓘ que aparece junto al precio. PNG transparente, recomendado 24×24px.</p>
                        </td>
                    </tr>
                </table>

                <h2 class="title">General</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="welow_moneda">Símbolo de moneda</label></th>
                        <td>
                            <input type="text" id="welow_moneda"
                                   name="<?php echo esc_attr( self::OPTION_KEY ); ?>[moneda_simbolo]"
                                   value="<?php echo esc_attr( $moneda ); ?>"
                                   class="small-text" maxlength="5" />
                            <p class="description">Se muestra junto al precio (ej: €, $, £).</p>
                        </td>
                    </tr>
                </table>

                <?php // v2.6.0 — Sección de Cabecera ?>
                <?php self::render_section_header( $options ); ?>

                <?php
                // v2.0.0 — Sección de iconos
                if ( class_exists( 'Welow_Icons' ) ) {
                    Welow_Icons::render_section();
                }
                ?>

                <?php submit_button(); ?>
            </form>

            <hr>

            <?php
            // v2.3.0 — Bloque de carga masiva de marcas externas
            $count_marcas_externas = 0;
            if ( taxonomy_exists( 'welow_marca_externa' ) ) {
                $terms_count = wp_count_terms( array( 'taxonomy' => 'welow_marca_externa', 'hide_empty' => false ) );
                if ( ! is_wp_error( $terms_count ) ) {
                    $count_marcas_externas = intval( $terms_count );
                }
            }
            $total_catalogo = class_exists( 'Welow_Tax_Marca_Externa' ) ? count( Welow_Tax_Marca_Externa::get_marcas_catalogo() ) : 99;
            ?>
            <div style="background:#f0f9ff;border:1px solid #7dd3fc;border-radius:8px;padding:16px 20px;margin-bottom:20px;">
                <h3 style="margin-top:0;">📋 Catálogo de marcas externas</h3>
                <p style="margin-bottom:12px;">
                    El plugin trae un catálogo de <strong><?php echo esc_html( $total_catalogo ); ?> marcas pre-cargadas</strong>
                    (Abarth, BMW, Audi, Mercedes-Benz, Renault, etc.) listas para usar en coches de ocasión.
                    Actualmente tienes <strong><?php echo esc_html( $count_marcas_externas ); ?> marcas externas</strong> en la base de datos.
                </p>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                    <?php wp_nonce_field( 'welow_cargar_marcas_externas' ); ?>
                    <input type="hidden" name="action" value="welow_cargar_marcas_externas">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-download" style="margin-top:4px;"></span>
                        Cargar las <?php echo esc_html( $total_catalogo ); ?> marcas del catálogo
                    </button>
                </form>
                <p class="description" style="margin-top:10px;">
                    <em>Es seguro pulsarlo varias veces: solo se añaden las marcas que <strong>no</strong> existan ya.
                    No se modifica ninguna marca existente ni se borra nada.</em>
                </p>
            </div>

            <h2>Accesos rápidos</h2>
            <p>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=welow_etiqueta' ) ); ?>" class="button">
                    <span class="dashicons dashicons-tag" style="margin-top:4px;"></span> Gestionar etiquetas
                </a>
                <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=welow_combustible&post_type=welow_modelo' ) ); ?>" class="button">
                    <span class="dashicons dashicons-category" style="margin-top:4px;"></span> Gestionar combustibles
                </a>
                <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=welow_categoria_modelo&post_type=welow_modelo' ) ); ?>" class="button">
                    <span class="dashicons dashicons-car" style="margin-top:4px;"></span> Gestionar carrocerías
                </a>
                <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=welow_marca_externa&post_type=welow_coche_ocasion' ) ); ?>" class="button">
                    <span class="dashicons dashicons-awards" style="margin-top:4px;"></span> Gestionar marcas externas
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=welow_importer' ) ); ?>" class="button">
                    <span class="dashicons dashicons-upload" style="margin-top:4px;"></span> Importar / Exportar
                </a>
            </p>
        </div>

        <script>
            jQuery(function($){
                $('#welow-usar-default').on('click', function(){
                    $('#welow_disclaimer_global').val(<?php echo wp_json_encode( $disclaimer_default ); ?>);
                });
            });
        </script>
        <?php
    }

    /**
     * v2.6.0 — Sección de configuración de la Cabecera (header).
     */
    public static function render_section_header( $options ) {
        $h = isset( $options['header'] ) && is_array( $options['header'] ) ? $options['header'] : array();
        $logo_id = isset( $h['logo_id'] ) ? intval( $h['logo_id'] ) : 0;
        $logo_movil_id = isset( $h['logo_movil_id'] ) ? intval( $h['logo_movil_id'] ) : 0;
        $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
        $logo_movil_url = $logo_movil_id ? wp_get_attachment_image_url( $logo_movil_id, 'thumbnail' ) : '';
        $name = self::OPTION_KEY . '[header]';

        // Menús de WP
        $menus = wp_get_nav_menus();
        $menu_id = isset( $h['menu_id'] ) ? intval( $h['menu_id'] ) : 0;
        ?>
        <h2 class="title">🧭 Cabecera (Header)</h2>
        <p>Defaults de la cabecera del sitio. Se usan automáticamente en el shortcode <code>[welow_header]</code> si no especificas parámetros propios.</p>

        <table class="form-table">
            <tr>
                <th scope="row"><label>Logo (escritorio)</label></th>
                <td>
                    <div class="welow-media-field">
                        <input type="hidden" id="welow_header_logo" name="<?php echo esc_attr( $name ); ?>[logo_id]" value="<?php echo esc_attr( $logo_id ); ?>" />
                        <div id="welow-header-logo-preview" class="welow-image-preview" style="max-width:200px;background:#f5f5f5;padding:8px;">
                            <?php if ( $logo_url ) : ?><img src="<?php echo esc_url( $logo_url ); ?>" alt="" style="max-height:60px;" /><?php endif; ?>
                        </div>
                        <button type="button" class="button welow-upload-btn" data-target="welow_header_logo" data-preview="welow-header-logo-preview">
                            <?php echo $logo_id ? 'Cambiar' : 'Seleccionar'; ?>
                        </button>
                        <?php if ( $logo_id ) : ?>
                            <button type="button" class="button welow-remove-btn" data-target="welow_header_logo" data-preview="welow-header-logo-preview">Quitar</button>
                        <?php endif; ?>
                    </div>
                    <p class="description">Logo principal. PNG/SVG, recomendado altura 50-60px.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Logo móvil (opcional)</label></th>
                <td>
                    <div class="welow-media-field">
                        <input type="hidden" id="welow_header_logo_movil" name="<?php echo esc_attr( $name ); ?>[logo_movil_id]" value="<?php echo esc_attr( $logo_movil_id ); ?>" />
                        <div id="welow-header-logo-movil-preview" class="welow-image-preview" style="max-width:120px;background:#f5f5f5;padding:8px;">
                            <?php if ( $logo_movil_url ) : ?><img src="<?php echo esc_url( $logo_movil_url ); ?>" alt="" style="max-height:40px;" /><?php endif; ?>
                        </div>
                        <button type="button" class="button welow-upload-btn" data-target="welow_header_logo_movil" data-preview="welow-header-logo-movil-preview">
                            <?php echo $logo_movil_id ? 'Cambiar' : 'Seleccionar'; ?>
                        </button>
                        <?php if ( $logo_movil_id ) : ?>
                            <button type="button" class="button welow-remove-btn" data-target="welow_header_logo_movil" data-preview="welow-header-logo-movil-preview">Quitar</button>
                        <?php endif; ?>
                    </div>
                    <p class="description">Si se deja vacío, se usa el logo principal en móvil. Útil para isotipo compacto.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Altura del logo</label></th>
                <td>
                    <input type="number" name="<?php echo esc_attr( $name ); ?>[logo_altura]" value="<?php echo esc_attr( $h['logo_altura'] ?? 50 ); ?>" min="20" max="120" step="1" /> px
                    <p class="description">Altura máxima del logo (entre 20 y 120px). Default: 50px.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="welow_header_menu">Menú de navegación</label></th>
                <td>
                    <select id="welow_header_menu" name="<?php echo esc_attr( $name ); ?>[menu_id]">
                        <option value="0">— No usar menú —</option>
                        <?php foreach ( $menus as $menu ) : ?>
                            <option value="<?php echo esc_attr( $menu->term_id ); ?>" <?php selected( $menu_id, $menu->term_id ); ?>>
                                <?php echo esc_html( $menu->name ); ?> (<?php echo intval( $menu->count ); ?> elementos)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">Crea o edita menús en <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">Apariencia → Menús</a>.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Teléfono (opcional)</label></th>
                <td>
                    <input type="text" name="<?php echo esc_attr( $name ); ?>[telefono]" value="<?php echo esc_attr( $h['telefono'] ?? '' ); ?>" class="regular-text" placeholder="ej: 919 496 619" />
                    <p class="description">Click-to-call en móvil. Deja vacío para no mostrar.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Botón principal</label></th>
                <td>
                    <input type="text" name="<?php echo esc_attr( $name ); ?>[boton_texto]" value="<?php echo esc_attr( $h['boton_texto'] ?? '' ); ?>" placeholder="Cita taller" style="width:200px;" />
                    <input type="url" name="<?php echo esc_attr( $name ); ?>[boton_enlace]" value="<?php echo esc_url( $h['boton_enlace'] ?? '' ); ?>" placeholder="https://..." style="width:300px;" />
                    <p class="description">Texto + URL del CTA principal.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Botón secundario (opcional)</label></th>
                <td>
                    <input type="text" name="<?php echo esc_attr( $name ); ?>[boton2_texto]" value="<?php echo esc_attr( $h['boton2_texto'] ?? '' ); ?>" placeholder="Cita concesionario" style="width:200px;" />
                    <input type="url" name="<?php echo esc_attr( $name ); ?>[boton2_enlace]" value="<?php echo esc_url( $h['boton2_enlace'] ?? '' ); ?>" placeholder="https://..." style="width:300px;" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Colores</label></th>
                <td>
                    <label style="display:inline-block;margin-right:18px;">Fondo:<br>
                        <input type="text" name="<?php echo esc_attr( $name ); ?>[color_fondo]" value="<?php echo esc_attr( $h['color_fondo'] ?? '' ); ?>" placeholder="#ffffff" style="width:100px;" />
                    </label>
                    <label style="display:inline-block;margin-right:18px;">Texto/menú:<br>
                        <input type="text" name="<?php echo esc_attr( $name ); ?>[color_texto]" value="<?php echo esc_attr( $h['color_texto'] ?? '' ); ?>" placeholder="#1f2937" style="width:100px;" />
                    </label>
                    <label style="display:inline-block;margin-right:18px;">Botón:<br>
                        <input type="text" name="<?php echo esc_attr( $name ); ?>[color_boton]" value="<?php echo esc_attr( $h['color_boton'] ?? '' ); ?>" placeholder="#2563eb" style="width:100px;" />
                    </label>
                    <label style="display:inline-block;">Texto botón:<br>
                        <input type="text" name="<?php echo esc_attr( $name ); ?>[color_boton_texto]" value="<?php echo esc_attr( $h['color_boton_texto'] ?? '' ); ?>" placeholder="#ffffff" style="width:100px;" />
                    </label>
                    <p class="description">Hex (#rrggbb). Vacío = colores por defecto.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Comportamiento</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( $name ); ?>[sticky]" value="1" <?php checked( ! empty( $h['sticky'] ) ); ?> />
                        <strong>Header pegado al hacer scroll</strong> (sticky)
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Helper público: devuelve los defaults de cabecera.
     *
     * @since 2.6.0
     */
    public static function get_header_defaults() {
        $opts = get_option( self::OPTION_KEY, array() );
        $defaults = array(
            'logo_id' => 0, 'logo_movil_id' => 0, 'logo_altura' => 50,
            'menu_id' => 0, 'telefono' => '',
            'boton_texto' => '', 'boton_enlace' => '',
            'boton2_texto' => '', 'boton2_enlace' => '',
            'color_fondo' => '', 'color_texto' => '',
            'color_boton' => '', 'color_boton_texto' => '',
            'sticky' => false,
        );
        $h = isset( $opts['header'] ) && is_array( $opts['header'] ) ? $opts['header'] : array();
        return wp_parse_args( $h, $defaults );
    }
}
