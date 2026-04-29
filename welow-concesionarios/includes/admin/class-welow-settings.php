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
}
