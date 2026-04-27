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

                <?php submit_button(); ?>
            </form>

            <hr>
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
