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

        // v2.53.0 — Antispam (reCAPTCHA v3)
        if ( isset( $input['antispam'] ) && is_array( $input['antispam'] ) ) {
            $a = $input['antispam'];
            $output['antispam'] = array(
                'recaptcha_activo'      => ! empty( $a['recaptcha_activo'] ),
                'recaptcha_site_key'    => isset( $a['recaptcha_site_key'] ) ? sanitize_text_field( $a['recaptcha_site_key'] ) : '',
                'recaptcha_secret_key'  => isset( $a['recaptcha_secret_key'] ) ? sanitize_text_field( $a['recaptcha_secret_key'] ) : '',
                'recaptcha_score_min'   => isset( $a['recaptcha_score_min'] ) ? max( 0.0, min( 1.0, floatval( $a['recaptcha_score_min'] ) ) ) : 0.5,
            );
        }

        // v2.40.0 — Texto RGPD global (fallback de los formularios)
        if ( isset( $input['rgpd'] ) && is_array( $input['rgpd'] ) ) {
            $r = $input['rgpd'];
            $output['rgpd'] = array(
                'consent_texto'    => isset( $r['consent_texto'] ) ? sanitize_textarea_field( $r['consent_texto'] ) : '',
                'politica_url'     => isset( $r['politica_url'] )  ? esc_url_raw( $r['politica_url'] ) : '',
                // v2.51.0 — Segundo consentimiento (marketing) opcional
                'marketing_activo' => ! empty( $r['marketing_activo'] ),
                'marketing_texto'  => isset( $r['marketing_texto'] ) ? sanitize_textarea_field( $r['marketing_texto'] ) : '',
            );
        }

        // v2.31.0 — Formularios por defecto en fichas de coche
        if ( isset( $input['formularios'] ) && is_array( $input['formularios'] ) ) {
            $f = $input['formularios'];
            $output['formularios'] = array(
                'coche_nuevo'   => isset( $f['coche_nuevo'] )   ? absint( $f['coche_nuevo'] )   : 0,
                'coche_ocasion' => isset( $f['coche_ocasion'] ) ? absint( $f['coche_ocasion'] ) : 0,
                // v2.32.0 — Página destino del botón "¡Me interesa!" en cards de modelo
                'me_interesa_page' => isset( $f['me_interesa_page'] ) ? absint( $f['me_interesa_page'] ) : 0,
                // v2.36.0 — Formulario para ficha de concesionario
                'concesionario'    => isset( $f['concesionario'] ) ? absint( $f['concesionario'] ) : 0,
                // v2.39.0 — Cita de taller
                'cita_taller'      => isset( $f['cita_taller'] ) ? absint( $f['cita_taller'] ) : 0,
                'cita_taller_page' => isset( $f['cita_taller_page'] ) ? absint( $f['cita_taller_page'] ) : 0,
            );
        }

        // v2.29.0 — Estilos generales del frontend
        if ( isset( $input['estilos'] ) && is_array( $input['estilos'] ) ) {
            $e = $input['estilos'];
            $output['estilos'] = array(
                'color_principal'    => isset( $e['color_principal'] ) ? sanitize_hex_color( $e['color_principal'] ) : '',
                'color_principal_hover' => isset( $e['color_principal_hover'] ) ? sanitize_hex_color( $e['color_principal_hover'] ) : '',
                'color_titulos'      => isset( $e['color_titulos'] ) ? sanitize_hex_color( $e['color_titulos'] ) : '',
                'color_boton_texto'  => isset( $e['color_boton_texto'] ) ? sanitize_hex_color( $e['color_boton_texto'] ) : '',
                'color_rotulo'       => isset( $e['color_rotulo'] ) ? sanitize_hex_color( $e['color_rotulo'] ) : '',
                'font_family'        => isset( $e['font_family'] ) ? sanitize_text_field( $e['font_family'] ) : '',
                'font_google'        => ! empty( $e['font_google'] ),
            );
        }

        // v2.45.0 — Footer (pie de página)
        if ( isset( $input['footer'] ) && is_array( $input['footer'] ) ) {
            $fo = $input['footer'];
            $output['footer'] = array(
                'logo_id'           => isset( $fo['logo_id'] ) ? absint( $fo['logo_id'] ) : 0,
                // v2.46.0 — Variante usada para los logos de marca en FILA 1
                'logos_marca_variante' => isset( $fo['logos_marca_variante'] ) && in_array( $fo['logos_marca_variante'], array( 'original', 'negro', 'blanco' ), true ) ? $fo['logos_marca_variante'] : 'blanco',
                // v2.46.0 — Título opcional para el bloque de ubicaciones
                'ubicaciones_titulo' => isset( $fo['ubicaciones_titulo'] ) ? sanitize_text_field( $fo['ubicaciones_titulo'] ) : '',
                // v2.47.0 — Toggles para mostrar/ocultar dirección y teléfono de cada ubicación
                'ubicaciones_mostrar_direccion' => ! empty( $fo['ubicaciones_mostrar_direccion'] ),
                'ubicaciones_mostrar_telefono'  => ! empty( $fo['ubicaciones_mostrar_telefono'] ),
                'descripcion'       => isset( $fo['descripcion'] ) ? sanitize_textarea_field( $fo['descripcion'] ) : '',
                'telefono'          => isset( $fo['telefono'] ) ? sanitize_text_field( $fo['telefono'] ) : '',
                'email'             => isset( $fo['email'] ) ? sanitize_email( $fo['email'] ) : '',
                'direccion'         => isset( $fo['direccion'] ) ? sanitize_textarea_field( $fo['direccion'] ) : '',
                'horario'           => isset( $fo['horario'] ) ? sanitize_textarea_field( $fo['horario'] ) : '',
                // Menús (3 columnas con sus IDs de menú WP + título)
                'col1_titulo'       => isset( $fo['col1_titulo'] ) ? sanitize_text_field( $fo['col1_titulo'] ) : '',
                'col1_menu_id'      => isset( $fo['col1_menu_id'] ) ? absint( $fo['col1_menu_id'] ) : 0,
                'col2_titulo'       => isset( $fo['col2_titulo'] ) ? sanitize_text_field( $fo['col2_titulo'] ) : '',
                'col2_menu_id'      => isset( $fo['col2_menu_id'] ) ? absint( $fo['col2_menu_id'] ) : 0,
                'col3_titulo'       => isset( $fo['col3_titulo'] ) ? sanitize_text_field( $fo['col3_titulo'] ) : '',
                'col3_menu_id'      => isset( $fo['col3_menu_id'] ) ? absint( $fo['col3_menu_id'] ) : 0,
                // Redes sociales
                'social_facebook'   => isset( $fo['social_facebook'] ) ? esc_url_raw( $fo['social_facebook'] ) : '',
                'social_instagram'  => isset( $fo['social_instagram'] ) ? esc_url_raw( $fo['social_instagram'] ) : '',
                'social_linkedin'   => isset( $fo['social_linkedin'] ) ? esc_url_raw( $fo['social_linkedin'] ) : '',
                'social_youtube'    => isset( $fo['social_youtube'] ) ? esc_url_raw( $fo['social_youtube'] ) : '',
                'social_tiktok'     => isset( $fo['social_tiktok'] ) ? esc_url_raw( $fo['social_tiktok'] ) : '',
                'social_x'          => isset( $fo['social_x'] ) ? esc_url_raw( $fo['social_x'] ) : '',
                // Legal
                'copyright'         => isset( $fo['copyright'] ) ? sanitize_text_field( $fo['copyright'] ) : '',
                'politica_url'      => isset( $fo['politica_url'] ) ? esc_url_raw( $fo['politica_url'] ) : '',
                'aviso_url'         => isset( $fo['aviso_url'] ) ? esc_url_raw( $fo['aviso_url'] ) : '',
                'cookies_url'       => isset( $fo['cookies_url'] ) ? esc_url_raw( $fo['cookies_url'] ) : '',
                // Estilos
                'color_fondo'       => isset( $fo['color_fondo'] ) ? sanitize_hex_color( $fo['color_fondo'] ) : '',
                'color_texto'       => isset( $fo['color_texto'] ) ? sanitize_hex_color( $fo['color_texto'] ) : '',
                'color_titulos'     => isset( $fo['color_titulos'] ) ? sanitize_hex_color( $fo['color_titulos'] ) : '',
                'color_link'        => isset( $fo['color_link'] ) ? sanitize_hex_color( $fo['color_link'] ) : '',
            );
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
                // v2.7.0 — Tipografía
                'font_family'       => isset( $h['font_family'] ) ? sanitize_text_field( $h['font_family'] ) : '',
                'font_google'       => ! empty( $h['font_google'] ),
                'font_weight_menu'  => isset( $h['font_weight_menu'] ) ? sanitize_text_field( $h['font_weight_menu'] ) : '',
                'font_weight_boton' => isset( $h['font_weight_boton'] ) ? sanitize_text_field( $h['font_weight_boton'] ) : '',
                'font_size_menu'    => isset( $h['font_size_menu'] ) ? absint( $h['font_size_menu'] ) : 0,
                'font_size_boton'   => isset( $h['font_size_boton'] ) ? absint( $h['font_size_boton'] ) : 0,
                'font_size_telefono'=> isset( $h['font_size_telefono'] ) ? absint( $h['font_size_telefono'] ) : 0,
                'text_transform_menu' => isset( $h['text_transform_menu'] ) && in_array( $h['text_transform_menu'], array( 'none', 'uppercase', 'capitalize' ), true ) ? $h['text_transform_menu'] : 'none',
                'letter_spacing_menu' => isset( $h['letter_spacing_menu'] ) ? sanitize_text_field( $h['letter_spacing_menu'] ) : '',
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

        // v2.45.0 — Tab activa (de la URL ?tab= o "general" por defecto)
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
        $tabs = array(
            'general'     => array( 'label' => 'General',     'icon' => 'dashicons-admin-generic' ),
            'formularios' => array( 'label' => 'Formularios', 'icon' => 'dashicons-feedback' ),
            'estilos'     => array( 'label' => 'Estilos',     'icon' => 'dashicons-art' ),
            'cabecera'    => array( 'label' => 'Cabecera',    'icon' => 'dashicons-menu-alt' ),
            'footer'      => array( 'label' => 'Footer',      'icon' => 'dashicons-arrow-down-alt2' ),
            'iconos'      => array( 'label' => 'Iconos',      'icon' => 'dashicons-format-image' ),
        );
        if ( ! isset( $tabs[ $active_tab ] ) ) $active_tab = 'general';
        $base_url = admin_url( 'admin.php?page=welow_settings' );
        ?>
        <div class="wrap welow-settings-wrap">
            <h1>Configuraciones <span style="font-size:14px;color:#666;">v<?php echo esc_html( WELOW_CONC_VERSION ); ?></span></h1>

            <nav class="nav-tab-wrapper welow-settings-tabs" style="margin-top:16px;">
                <?php foreach ( $tabs as $key => $info ) :
                    $url = $base_url . '&tab=' . $key;
                    $class = 'nav-tab' . ( $active_tab === $key ? ' nav-tab-active' : '' );
                ?>
                    <a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $class ); ?>">
                        <span class="dashicons <?php echo esc_attr( $info['icon'] ); ?>" style="vertical-align:middle;margin-top:-2px;"></span>
                        <?php echo esc_html( $info['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <form method="post" action="options.php" style="margin-top:20px;">
                <?php settings_fields( 'welow_settings_group' ); ?>
                <?php // Mantener pestaña activa al guardar (después del redirect de WP) ?>
                <input type="hidden" name="_wp_http_referer" value="<?php echo esc_url( $base_url . '&tab=' . $active_tab . '&settings-updated=true' ); ?>" />

                <?php
                // v2.45.0 — Renderizamos TODAS las pestañas en el DOM pero solo la activa visible.
                // Así al guardar se envían todos los campos y no se pierde nada.
                ?>

                <div class="welow-tab-content" data-tab="general" <?php echo $active_tab !== 'general' ? 'style="display:none;"' : ''; ?>>
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
                </div>

                <div class="welow-tab-content" data-tab="formularios" <?php echo $active_tab !== 'formularios' ? 'style="display:none;"' : ''; ?>>
                    <?php self::render_section_formularios( $options ); ?>
                </div>

                <div class="welow-tab-content" data-tab="estilos" <?php echo $active_tab !== 'estilos' ? 'style="display:none;"' : ''; ?>>
                    <?php self::render_section_estilos( $options ); ?>
                </div>

                <div class="welow-tab-content" data-tab="cabecera" <?php echo $active_tab !== 'cabecera' ? 'style="display:none;"' : ''; ?>>
                    <?php self::render_section_header( $options ); ?>
                </div>

                <div class="welow-tab-content" data-tab="footer" <?php echo $active_tab !== 'footer' ? 'style="display:none;"' : ''; ?>>
                    <?php self::render_section_footer( $options ); ?>
                </div>

                <div class="welow-tab-content" data-tab="iconos" <?php echo $active_tab !== 'iconos' ? 'style="display:none;"' : ''; ?>>
                    <?php
                    // v2.0.0 — Sección de iconos
                    if ( class_exists( 'Welow_Icons' ) ) {
                        Welow_Icons::render_section();
                    } else {
                        echo '<p><em>Sistema de iconos no disponible.</em></p>';
                    }
                    ?>
                </div>

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
     * v2.31.0 — Selector de formularios por defecto para fichas de coche.
     */
    public static function render_section_formularios( $options ) {
        $f = isset( $options['formularios'] ) && is_array( $options['formularios'] ) ? $options['formularios'] : array();
        $sel_nuevo   = intval( $f['coche_nuevo']   ?? 0 );
        $sel_ocasion = intval( $f['coche_ocasion'] ?? 0 );

        $base = self::OPTION_KEY . '[formularios]';

        // Listar formularios disponibles
        $forms = post_type_exists( 'welow_formulario' )
            ? get_posts( array( 'post_type' => 'welow_formulario', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) )
            : array();
        ?>
        <h2 class="title">Formularios por defecto en fichas de coche</h2>
        <p>Selecciona qué formulario se muestra en el bloque <code>formulario</code> del shortcode
            <code>[welow_coche_ficha]</code> según el tipo de coche. Crea formularios desde
            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=welow_formulario' ) ); ?>">Concesionarios → Formularios</a>.
        </p>

        <?php if ( empty( $forms ) ) : ?>
            <p style="background:#fef3c7;border-left:3px solid #f59e0b;padding:10px 14px;">
                <strong>Todavía no tienes formularios creados.</strong>
                <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=welow_formulario' ) ); ?>">Crear el primero →</a>
            </p>
        <?php else : ?>
            <table class="form-table">
                <tr>
                    <?php // v2.43.0 — Renombrado: ya no es para fichas de coche nuevo
                          // (CPT en soft-remove). Sigue siendo el formulario usado por
                          // [welow_me_interesa] cuando alguien pulsa el botón en una
                          // card de modelo. ?>
                    <th><label>Formulario para "Me Interesa" (modelos)</label></th>
                    <td>
                        <select name="<?php echo esc_attr( $base ); ?>[coche_nuevo]" class="regular-text">
                            <option value="0">— Sin formulario configurado —</option>
                            <?php foreach ( $forms as $form ) : ?>
                                <option value="<?php echo intval( $form->ID ); ?>" <?php selected( $sel_nuevo, $form->ID ); ?>>
                                    <?php echo esc_html( $form->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Se renderiza dentro del shortcode <code>[welow_me_interesa]</code> en la página que has configurado abajo como "Página del botón Me Interesa".</p>
                    </td>
                </tr>
                <tr>
                    <th><label>Formulario para coches OCASIÓN / KM0</label></th>
                    <td>
                        <select name="<?php echo esc_attr( $base ); ?>[coche_ocasion]" class="regular-text">
                            <option value="0">— Usar formulario clásico (legacy) —</option>
                            <?php foreach ( $forms as $form ) : ?>
                                <option value="<?php echo intval( $form->ID ); ?>" <?php selected( $sel_ocasion, $form->ID ); ?>>
                                    <?php echo esc_html( $form->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Se muestra en la ficha de segunda mano y KM0 (welow_coche_ocasion).</p>
                    </td>
                </tr>
                <?php // v2.36.0 — Formulario para ficha de concesionario
                $sel_conc = intval( $f['concesionario'] ?? 0 ); ?>
                <tr>
                    <th><label>Formulario para ficha de CONCESIONARIO</label></th>
                    <td>
                        <select name="<?php echo esc_attr( $base ); ?>[concesionario]" class="regular-text">
                            <option value="0">— No mostrar formulario —</option>
                            <?php foreach ( $forms as $form ) : ?>
                                <option value="<?php echo intval( $form->ID ); ?>" <?php selected( $sel_conc, $form->ID ); ?>>
                                    <?php echo esc_html( $form->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Se renderiza en la sección "Contacto y horario" de <code>[welow_concesionario_ficha]</code>, al lado derecho de los datos del concesionario. Si lo dejas en "No mostrar", esa sección sigue siendo solo de datos como antes.</p>
                    </td>
                </tr>
            </table>
            <p style="background:#eff6ff;border-left:3px solid #2271b1;padding:10px 14px;font-size:13px;">
                💡 Cuando configuras un formulario aquí, también afecta al shortcode legacy <code>[welow_coche_formulario]</code>
                — se redirige automáticamente al nuevo sistema. No hace falta cambiar nada en las páginas existentes.
            </p>

            <?php // v2.39.0 — Cita de taller ?>
            <h3 style="margin-top:30px;">Cita previa de taller</h3>
            <p>Selecciona el formulario y la página donde aparece el shortcode
                <code>[welow_cita_taller]</code>. Esa página renderiza el formulario
                configurado aquí abajo, sin tener que pegar el ID a mano.</p>
            <table class="form-table">
                <tr>
                    <th><label>Formulario de Cita Taller</label></th>
                    <td>
                        <?php $sel_taller = intval( $f['cita_taller'] ?? 0 ); ?>
                        <select name="<?php echo esc_attr( $base ); ?>[cita_taller]" class="regular-text">
                            <option value="0">— Sin formulario configurado —</option>
                            <?php foreach ( $forms as $form ) : ?>
                                <option value="<?php echo intval( $form->ID ); ?>" <?php selected( $sel_taller, $form->ID ); ?>>
                                    <?php echo esc_html( $form->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Página de Cita Taller</label></th>
                    <td>
                        <?php
                        $sel_taller_page = intval( $f['cita_taller_page'] ?? 0 );
                        wp_dropdown_pages( array(
                            'name'              => esc_attr( $base ) . '[cita_taller_page]',
                            'selected'          => $sel_taller_page,
                            'show_option_none'  => '— Sin página configurada —',
                            'option_none_value' => '0',
                        ) );
                        ?>
                        <p class="description">
                            Crea una página WordPress y pega dentro <code>[welow_cita_taller]</code>.
                            Luego selecciónala aquí (también la usamos para el enlace del botón del header).
                        </p>
                    </td>
                </tr>
            </table>

            <?php // v2.32.0 — Página para el botón "¡Me interesa!" de los modelos ?>
            <h3 style="margin-top:30px;">Página del botón "¡Me interesa!"</h3>
            <p>Selecciona la página donde aparece el shortcode <code>[welow_me_interesa]</code>.
                Al pulsar "¡Me interesa!" en la card de un modelo, se redirige a esa página añadiendo
                <code>?modelo=slug-del-modelo</code>, y allí se renderiza la foto + nombre del modelo
                + el formulario configurado arriba para "coches nuevos".
            </p>
            <table class="form-table">
                <tr>
                    <th><label>Página de destino</label></th>
                    <td>
                        <?php
                        $sel_page = intval( $f['me_interesa_page'] ?? 0 );
                        wp_dropdown_pages( array(
                            'name'              => esc_attr( $base ) . '[me_interesa_page]',
                            'selected'          => $sel_page,
                            'show_option_none'  => '— Sin página configurada —',
                            'option_none_value' => '0',
                        ) );
                        ?>
                        <p class="description">
                            Crea una página WordPress (ej. "Me interesa") y pega dentro
                            <code>[welow_me_interesa]</code>. Luego selecciónala aquí.
                            <br>Si el modelo tiene URL propia rellena en "URL del botón ¡Me interesa!"
                            esa prevalece sobre esta página global.
                        </p>
                    </td>
                </tr>
            </table>
        <?php endif; ?>

        <?php // v2.40.0 — Texto RGPD global (fallback de todos los formularios) ?>
        <h3 style="margin-top:30px;">Texto RGPD global (consentimiento)</h3>
        <p>Si dejas vacío el campo "Texto del consentimiento" en cualquier formulario,
            se usará este texto global. Útil para que todos los formularios muestren
            la misma cláusula sin tener que copiarla en cada uno.</p>
        <?php
        $rgpd_default_consent = 'He leído y acepto la <a href="{politica}" target="_blank" rel="noopener">Política de Privacidad</a>. El responsable del tratamiento es TALLERES CHINARES SA. La finalidad de la recogida de datos es la de poder atender sus cuestiones, sin ceder sus datos a terceros. Tiene derecho a saber qué información tenemos sobre usted, corregirla o eliminarla tal y como se explica en nuestra <a href="{politica}" target="_blank" rel="noopener">Política de Privacidad</a>.';
        $r = isset( $options['rgpd'] ) && is_array( $options['rgpd'] ) ? $options['rgpd'] : array();
        $rgpd_consent  = $r['consent_texto'] ?? '';
        $rgpd_politica = $r['politica_url']  ?? '';
        ?>
        <table class="form-table">
            <tr>
                <th><label>Texto del consentimiento</label></th>
                <td>
                    <textarea name="<?php echo esc_attr( self::OPTION_KEY ); ?>[rgpd][consent_texto]"
                              rows="5" class="large-text"
                              placeholder="<?php echo esc_attr( $rgpd_default_consent ); ?>"><?php echo esc_textarea( $rgpd_consent ); ?></textarea>
                    <p class="description">Usa <code>{politica}</code> donde quieras enlazar a la URL de política de privacidad. Si lo dejas vacío, se usará el texto que ves como placeholder.</p>
                    <p>
                        <button type="button" class="button" onclick="document.querySelector('textarea[name=&quot;<?php echo esc_attr( self::OPTION_KEY ); ?>[rgpd][consent_texto]&quot;]').value = <?php echo wp_json_encode( $rgpd_default_consent ); ?>; return false;">Usar texto recomendado</button>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label>URL Política de Privacidad</label></th>
                <td>
                    <input type="url" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[rgpd][politica_url]"
                           value="<?php echo esc_url( $rgpd_politica ); ?>" class="large-text"
                           placeholder="https://grupochinares.com/politica-privacidad/" />
                    <p class="description">URL global. Si un formulario tiene su propia URL de política, esa prevalece.</p>
                </td>
            </tr>
        </table>

        <?php // v2.51.0 — Segundo consentimiento (marketing) opcional ?>
        <h3 style="margin-top:30px;">Segundo consentimiento (opcional, marketing)</h3>
        <p>Si lo activas, aparecerá un segundo checkbox <strong>no obligatorio</strong> debajo del consentimiento RGPD en todos los formularios. Su valor (sí/no) se guarda en cada lead para que sepas a quién puedes mandar comunicaciones comerciales.</p>

        <?php
        $mk_activo = ! empty( $r['marketing_activo'] );
        $mk_texto  = $r['marketing_texto'] ?? '';
        $mk_default = self::get_rgpd_default_marketing();
        ?>
        <table class="form-table">
            <tr>
                <th><label>Activar segundo consentimiento</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[rgpd][marketing_activo]" value="1" <?php checked( $mk_activo ); ?> />
                        Mostrar el checkbox de marketing en todos los formularios
                    </label>
                </td>
            </tr>
            <tr>
                <th><label>Texto del consentimiento de marketing</label></th>
                <td>
                    <textarea name="<?php echo esc_attr( self::OPTION_KEY ); ?>[rgpd][marketing_texto]"
                              rows="3" class="large-text"
                              placeholder="<?php echo esc_attr( $mk_default ); ?>"><?php echo esc_textarea( $mk_texto ); ?></textarea>
                    <p class="description">Si lo dejas vacío, se usa el texto recomendado del placeholder.</p>
                    <p>
                        <button type="button" class="button"
                                onclick="document.querySelector('textarea[name=&quot;<?php echo esc_attr( self::OPTION_KEY ); ?>[rgpd][marketing_texto]&quot;]').value = <?php echo wp_json_encode( $mk_default ); ?>; return false;">Usar texto recomendado</button>
                    </p>
                </td>
            </tr>
        </table>

        <?php // v2.53.0 — Antispam reCAPTCHA v3 ?>
        <h3 style="margin-top:30px;">Antispam — Google reCAPTCHA v3</h3>
        <p>Protección invisible (sin captchas visibles para el usuario). Funciona en segundo plano y rechaza envíos sospechosos. Puedes consultar / obtener las claves en
            <a href="https://www.google.com/recaptcha/admin/create" target="_blank" rel="noopener">https://www.google.com/recaptcha/admin/create</a>
            registrando tu dominio como <strong>reCAPTCHA v3</strong>.
        </p>
        <?php
        $a = isset( $options['antispam'] ) && is_array( $options['antispam'] ) ? $options['antispam'] : array();
        $rc_activo = ! empty( $a['recaptcha_activo'] );
        $rc_site   = $a['recaptcha_site_key'] ?? '';
        $rc_secret = $a['recaptcha_secret_key'] ?? '';
        $rc_score  = $a['recaptcha_score_min'] ?? 0.5;
        ?>
        <table class="form-table">
            <tr>
                <th><label>Activar reCAPTCHA v3</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[antispam][recaptcha_activo]" value="1" <?php checked( $rc_activo ); ?> />
                        Validar todos los formularios con reCAPTCHA v3
                    </label>
                    <p class="description">Si lo activas, las claves de abajo son obligatorias. Si lo dejas desactivado, sigue funcionando el honeypot + timing.</p>
                </td>
            </tr>
            <tr>
                <th><label>Site key (clave del sitio)</label></th>
                <td>
                    <input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[antispam][recaptcha_site_key]"
                           value="<?php echo esc_attr( $rc_site ); ?>" class="large-text" autocomplete="off"
                           placeholder="6LdXXXXXXXXXXXXXXXXXXXXXXXXXXXX" />
                    <p class="description">Empieza por <code>6L</code>. Se incrusta en el HTML del frontend.</p>
                </td>
            </tr>
            <tr>
                <th><label>Secret key (clave secreta)</label></th>
                <td>
                    <input type="password" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[antispam][recaptcha_secret_key]"
                           value="<?php echo esc_attr( $rc_secret ); ?>" class="large-text" autocomplete="new-password"
                           placeholder="6LdXXXXXXXXXXXXXXXXXXXXXXXXXXXX" />
                    <p class="description">Nunca se expone al frontend. Solo se usa servidor-a-servidor para verificar el token con Google.</p>
                </td>
            </tr>
            <tr>
                <th><label>Score mínimo aceptado</label></th>
                <td>
                    <input type="number" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[antispam][recaptcha_score_min]"
                           value="<?php echo esc_attr( $rc_score ); ?>" min="0" max="1" step="0.1" style="width:80px;" />
                    <p class="description">Google devuelve un score de 0 (bot) a 1 (humano). Recomendado: <strong>0.5</strong>. Si recibes muchos falsos positivos, baja a 0.3. Si recibes spam, sube a 0.7.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * v2.40.0 — Devuelve el texto de consentimiento por defecto (constante).
     */
    public static function get_rgpd_default_consent() {
        return 'He leído y acepto la <a href="{politica}" target="_blank" rel="noopener">Política de Privacidad</a>. El responsable del tratamiento es TALLERES CHINARES SA. La finalidad de la recogida de datos es la de poder atender sus cuestiones, sin ceder sus datos a terceros. Tiene derecho a saber qué información tenemos sobre usted, corregirla o eliminarla tal y como se explica en nuestra <a href="{politica}" target="_blank" rel="noopener">Política de Privacidad</a>.';
    }

    /**
     * v2.51.0 — Texto por defecto del segundo consentimiento (marketing).
     */
    public static function get_rgpd_default_marketing() {
        return 'Doy mi consentimiento para el tratamiento de mis datos personales con fines de marketing y comerciales (Opcional).';
    }

    /**
     * v2.29.0 — Sección de estilos generales del frontend.
     */
    public static function render_section_estilos( $options ) {
        $e = isset( $options['estilos'] ) && is_array( $options['estilos'] ) ? $options['estilos'] : array();
        $color_principal       = $e['color_principal']       ?? '';
        $color_principal_hover = $e['color_principal_hover'] ?? '';
        $color_titulos         = $e['color_titulos']         ?? '';
        $color_boton_texto     = $e['color_boton_texto']     ?? '';
        $color_rotulo          = $e['color_rotulo']          ?? '';
        $font_family           = $e['font_family']           ?? '';
        $font_google           = ! empty( $e['font_google'] );

        $base = self::OPTION_KEY . '[estilos]';
        ?>
        <h2 class="title">Estilos generales del frontend</h2>
        <p>Personaliza colores y tipografía del plugin (cards, botones, rótulos, fichas...).
            Deja vacío cualquier campo para usar el estilo por defecto.</p>

        <table class="form-table">
            <tr>
                <th><label>Color principal (botones, CTA, rótulos)</label></th>
                <td>
                    <input type="text" class="welow-color-field" data-default-color="#2563eb"
                           name="<?php echo esc_attr( $base ); ?>[color_principal]"
                           value="<?php echo esc_attr( $color_principal ); ?>"
                           placeholder="#2563eb" />
                    <p class="description">Color principal de la marca del concesionario. Se aplica a botones "¡Me interesa!", "Ver concesionario", flecha "Ver modelo", hover de enlaces, etc.</p>
                </td>
            </tr>
            <tr>
                <th><label>Color principal — hover</label></th>
                <td>
                    <input type="text" class="welow-color-field" data-default-color="#1d4ed8"
                           name="<?php echo esc_attr( $base ); ?>[color_principal_hover]"
                           value="<?php echo esc_attr( $color_principal_hover ); ?>"
                           placeholder="#1d4ed8" />
                    <p class="description">Color al pasar el ratón sobre botones del color principal. Si vacío, se usa #1d4ed8.</p>
                </td>
            </tr>
            <tr>
                <th><label>Color de títulos</label></th>
                <td>
                    <input type="text" class="welow-color-field" data-default-color="#0f172a"
                           name="<?php echo esc_attr( $base ); ?>[color_titulos]"
                           value="<?php echo esc_attr( $color_titulos ); ?>"
                           placeholder="#0f172a" />
                    <p class="description">Color de h2/h3 de las cards (modelos, coches, concesionarios). Por defecto un negro azulado.</p>
                </td>
            </tr>
            <tr>
                <th><label>Color del texto sobre los botones</label></th>
                <td>
                    <input type="text" class="welow-color-field" data-default-color="#ffffff"
                           name="<?php echo esc_attr( $base ); ?>[color_boton_texto]"
                           value="<?php echo esc_attr( $color_boton_texto ); ?>"
                           placeholder="#ffffff" />
                    <p class="description">Color del texto dentro de los botones principales. Casi siempre blanco.</p>
                </td>
            </tr>
            <tr>
                <th><label>Color de los rótulos destacados</label></th>
                <td>
                    <input type="text" class="welow-color-field" data-default-color="#2563eb"
                           name="<?php echo esc_attr( $base ); ?>[color_rotulo]"
                           value="<?php echo esc_attr( $color_rotulo ); ?>"
                           placeholder="(usa color principal)" />
                    <p class="description">Color de fondo de las píldoras de rótulo (ej. "OFERTA", "NOVEDAD") en cards de modelo. Si vacío, se usa el color principal.</p>
                </td>
            </tr>
            <tr>
                <th><label for="welow_estilos_font_family">Tipografía</label></th>
                <td>
                    <input type="text" id="welow_estilos_font_family"
                           name="<?php echo esc_attr( $base ); ?>[font_family]"
                           value="<?php echo esc_attr( $font_family ); ?>"
                           class="regular-text" placeholder="Ej: Figtree, Inter, 'Helvetica Neue'" />
                    <p>
                        <label>
                            <input type="checkbox"
                                   name="<?php echo esc_attr( $base ); ?>[font_google]"
                                   value="1" <?php checked( $font_google ); ?> />
                            Cargar desde Google Fonts (si la fuente está en Google Fonts)
                        </label>
                    </p>
                    <p class="description">Nombre exacto de la familia tipográfica. Ej: "Figtree", "Inter", "Roboto". Si la marcas como Google Font, se cargará automáticamente con weights 400/600/700/800.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * v2.45.0 — Sección de configuración del Footer.
     */
    public static function render_section_footer( $options ) {
        $f = isset( $options['footer'] ) && is_array( $options['footer'] ) ? $options['footer'] : array();
        $base = self::OPTION_KEY . '[footer]';

        $logo_id    = intval( $f['logo_id'] ?? 0 );
        $logo_url   = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
        $desc       = $f['descripcion'] ?? '';
        $tel        = $f['telefono'] ?? '';
        $email      = $f['email'] ?? '';
        $dir        = $f['direccion'] ?? '';
        $horario    = $f['horario'] ?? '';

        // Menús WP disponibles
        $menus = wp_get_nav_menus();
        ?>
        <h2 class="title">Footer (pie de página)</h2>
        <p>Configura los campos del shortcode <code>[welow_footer]</code>. Pégalo en tu plantilla del Theme Builder de Divi (template global de Footer) para usarlo en todo el sitio.</p>
        <p style="background:#f0f6fc;border-left:3px solid #2271b1;padding:8px 12px;font-size:13px;">
            <strong>Estructura visual del footer:</strong><br>
            🔹 <strong>Fila 1</strong>: Logo empresa + logos de las marcas (todas las publicadas).<br>
            🔹 <strong>Fila 2</strong>: Ubicaciones (todos los concesionarios físicos publicados, automático) | 3 columnas de menús.<br>
            🔹 <strong>Fila 3</strong>: Copyright | redes sociales | enlaces legales.
        </p>

        <h3>FILA 1 — Logos</h3>
        <table class="form-table">
            <tr>
                <th>Logo de la empresa</th>
                <td>
                    <div class="welow-media-field">
                        <input type="hidden" id="welow_footer_logo_id"
                               name="<?php echo esc_attr( $base ); ?>[logo_id]"
                               value="<?php echo esc_attr( $logo_id ); ?>" />
                        <div id="welow-footer-logo-preview" class="welow-image-preview" style="max-width:240px;background:#1f2937;padding:8px;border-radius:4px;">
                            <?php if ( $logo_url ) : ?>
                                <img src="<?php echo esc_url( $logo_url ); ?>" alt="" style="max-width:220px;" />
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button welow-upload-btn"
                                data-target="welow_footer_logo_id"
                                data-preview="welow-footer-logo-preview">
                            <?php echo $logo_id ? 'Cambiar logo' : 'Seleccionar logo'; ?>
                        </button>
                        <?php if ( $logo_id ) : ?>
                            <button type="button" class="button welow-remove-btn"
                                    data-target="welow_footer_logo_id"
                                    data-preview="welow-footer-logo-preview">Quitar</button>
                        <?php endif; ?>
                    </div>
                    <p class="description">Se muestra grande a la izquierda. Si el footer es oscuro (default), usa una versión blanca/clara.</p>
                </td>
            </tr>
            <tr>
                <th><label>Variante de logos de marca</label></th>
                <td>
                    <?php $variante_sel = $f['logos_marca_variante'] ?? 'blanco'; ?>
                    <select name="<?php echo esc_attr( $base ); ?>[logos_marca_variante]">
                        <option value="original" <?php selected( $variante_sel, 'original' ); ?>>Original (a color)</option>
                        <option value="negro" <?php selected( $variante_sel, 'negro' ); ?>>Negro</option>
                        <option value="blanco" <?php selected( $variante_sel, 'blanco' ); ?>>Blanco</option>
                    </select>
                    <p class="description">A la derecha del logo empresa se muestran los logos de todas las marcas publicadas, ordenados según el orden definido en Marcas. Esta variante se aplica a todos.</p>
                </td>
            </tr>
            <tr>
                <th><label>Texto descriptivo (opcional)</label></th>
                <td>
                    <textarea name="<?php echo esc_attr( $base ); ?>[descripcion]" rows="2" class="large-text"
                              placeholder="(Opcional) Frase corta bajo el logo de empresa."><?php echo esc_textarea( $desc ); ?></textarea>
                </td>
            </tr>
        </table>

        <?php // Campos de contacto rápido se mantienen ocultos por compat con instalaciones anteriores ?>
        <input type="hidden" name="<?php echo esc_attr( $base ); ?>[telefono]"  value="<?php echo esc_attr( $tel ); ?>" />
        <input type="hidden" name="<?php echo esc_attr( $base ); ?>[email]"     value="<?php echo esc_attr( $email ); ?>" />
        <input type="hidden" name="<?php echo esc_attr( $base ); ?>[direccion]" value="<?php echo esc_attr( $dir ); ?>" />
        <input type="hidden" name="<?php echo esc_attr( $base ); ?>[horario]"   value="<?php echo esc_attr( $horario ); ?>" />

        <h3 style="margin-top:30px;">FILA 2 — Ubicaciones (automático)</h3>
        <table class="form-table">
            <tr>
                <th><label>Título del bloque de ubicaciones</label></th>
                <td>
                    <input type="text" name="<?php echo esc_attr( $base ); ?>[ubicaciones_titulo]"
                           value="<?php echo esc_attr( $f['ubicaciones_titulo'] ?? '' ); ?>" class="regular-text"
                           placeholder="Nuestras ubicaciones" />
                    <p class="description">Las ubicaciones se cargan automáticamente desde
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=welow_concesionario' ) ); ?>">Concesionarios físicos</a>
                        (todos los publicados).</p>
                </td>
            </tr>
            <?php
            // v2.47.0 — Toggles. Por defecto activos en instalación nueva, pero respetan lo que haya guardado.
            // Si NUNCA se ha guardado el campo (no existe la clave), interpretamos como true para no romper.
            $mostrar_dir = array_key_exists( 'ubicaciones_mostrar_direccion', $f ) ? ! empty( $f['ubicaciones_mostrar_direccion'] ) : true;
            $mostrar_tel = array_key_exists( 'ubicaciones_mostrar_telefono', $f )  ? ! empty( $f['ubicaciones_mostrar_telefono'] )  : true;
            ?>
            <tr>
                <th><label>Información a mostrar</label></th>
                <td>
                    <p style="margin:0 0 6px;">
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $base ); ?>[ubicaciones_mostrar_direccion]" value="1" <?php checked( $mostrar_dir ); ?> />
                            Mostrar dirección
                        </label>
                    </p>
                    <p style="margin:0;">
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $base ); ?>[ubicaciones_mostrar_telefono]" value="1" <?php checked( $mostrar_tel ); ?> />
                            Mostrar teléfono
                        </label>
                    </p>
                    <p class="description">Si desactivas alguno, ese dato no aparece en el footer aunque esté rellenado en la ficha del concesionario. El nombre se muestra siempre.</p>
                </td>
            </tr>
        </table>

        <h3 style="margin-top:30px;">FILA 2 — Columnas de enlaces (menús WP)</h3>
        <p class="description">Crea menús desde <a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">Apariencia → Menús</a> y asigna uno por columna. Si dejas vacío, esa columna no aparece.</p>
        <table class="form-table">
            <?php for ( $i = 1; $i <= 3; $i++ ) :
                $col_t = $f[ 'col' . $i . '_titulo' ] ?? '';
                $col_m = intval( $f[ 'col' . $i . '_menu_id' ] ?? 0 );
            ?>
                <tr>
                    <th><label>Columna <?php echo $i; ?></label></th>
                    <td>
                        <p style="margin:0 0 6px;">
                            <input type="text" name="<?php echo esc_attr( $base ); ?>[col<?php echo $i; ?>_titulo]"
                                   value="<?php echo esc_attr( $col_t ); ?>" class="regular-text"
                                   placeholder="Título de la columna (ej: Nuestra empresa)" />
                        </p>
                        <p style="margin:0;">
                            <select name="<?php echo esc_attr( $base ); ?>[col<?php echo $i; ?>_menu_id]">
                                <option value="0">— Sin menú —</option>
                                <?php foreach ( $menus as $m ) : ?>
                                    <option value="<?php echo intval( $m->term_id ); ?>" <?php selected( $col_m, $m->term_id ); ?>>
                                        <?php echo esc_html( $m->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                    </td>
                </tr>
            <?php endfor; ?>
        </table>

        <h3 style="margin-top:30px;">FILA 3 — Redes sociales</h3>
        <table class="form-table">
            <?php
            $redes = array(
                'facebook'  => 'Facebook',
                'instagram' => 'Instagram',
                'linkedin'  => 'LinkedIn',
                'youtube'   => 'YouTube',
                'tiktok'    => 'TikTok',
                'x'         => 'X (Twitter)',
            );
            foreach ( $redes as $key => $label ) :
                $val = $f[ 'social_' . $key ] ?? '';
            ?>
                <tr>
                    <th><label><?php echo esc_html( $label ); ?></label></th>
                    <td>
                        <input type="url" name="<?php echo esc_attr( $base ); ?>[social_<?php echo $key; ?>]"
                               value="<?php echo esc_url( $val ); ?>" class="large-text"
                               placeholder="https://..." />
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h3 style="margin-top:30px;">FILA 3 — Pie legal</h3>
        <table class="form-table">
            <tr>
                <th><label>Copyright</label></th>
                <td>
                    <input type="text" name="<?php echo esc_attr( $base ); ?>[copyright]"
                           value="<?php echo esc_attr( $f['copyright'] ?? '' ); ?>" class="large-text"
                           placeholder="© {year} Talleres Chinares S.A. Todos los derechos reservados." />
                    <p class="description">Usa <code>{year}</code> para que se reemplace por el año actual automáticamente.</p>
                </td>
            </tr>
            <?php
            // v2.54.0 — Selector de página de la web + URL manual.
            // Si el admin elige una página, su URL se vuelca al input de la derecha.
            $paginas = get_pages( array( 'sort_column' => 'post_title', 'sort_order' => 'ASC' ) );
            $render_legal_url = function ( $label, $field_key, $valor_actual, $placeholder ) use ( $base, $paginas ) {
                $input_id = esc_attr( $base . '_' . $field_key );
                ?>
                <tr>
                    <th><label for="<?php echo $input_id; ?>"><?php echo esc_html( $label ); ?></label></th>
                    <td>
                        <select onchange="if(this.value){document.getElementById('<?php echo $input_id; ?>').value=this.value;} this.selectedIndex=0;"
                                style="max-width:100%; margin-bottom:6px;">
                            <option value="">— Seleccionar una página de la web —</option>
                            <?php foreach ( $paginas as $p ) : ?>
                                <option value="<?php echo esc_url( get_permalink( $p->ID ) ); ?>">
                                    <?php echo esc_html( $p->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="url" id="<?php echo $input_id; ?>"
                               name="<?php echo esc_attr( $base ); ?>[<?php echo esc_attr( $field_key ); ?>]"
                               value="<?php echo esc_url( $valor_actual ); ?>"
                               class="large-text"
                               placeholder="<?php echo esc_attr( $placeholder ); ?>" />
                        <p class="description">Elige una página del desplegable o pega una URL manual.</p>
                    </td>
                </tr>
                <?php
            };
            $render_legal_url( 'URL Política de Privacidad', 'politica_url', $f['politica_url'] ?? '', 'https://.../politica-privacidad/' );
            $render_legal_url( 'URL Aviso Legal',           'aviso_url',    $f['aviso_url']    ?? '', 'https://.../aviso-legal/' );
            $render_legal_url( 'URL Política de Cookies',   'cookies_url',  $f['cookies_url']  ?? '', 'https://.../cookies/' );
            ?>
        </table>

        <h3 style="margin-top:30px;">Estilos</h3>
        <table class="form-table">
            <tr>
                <th><label>Color de fondo</label></th>
                <td>
                    <input type="text" class="welow-color-field" data-default-color="#0f172a"
                           name="<?php echo esc_attr( $base ); ?>[color_fondo]"
                           value="<?php echo esc_attr( $f['color_fondo'] ?? '' ); ?>"
                           placeholder="#0f172a" />
                    <p class="description">Color de fondo del footer. Default: gris oscuro.</p>
                </td>
            </tr>
            <tr>
                <th><label>Color del texto</label></th>
                <td>
                    <input type="text" class="welow-color-field" data-default-color="#cbd5e1"
                           name="<?php echo esc_attr( $base ); ?>[color_texto]"
                           value="<?php echo esc_attr( $f['color_texto'] ?? '' ); ?>"
                           placeholder="#cbd5e1" />
                </td>
            </tr>
            <tr>
                <th><label>Color de títulos</label></th>
                <td>
                    <input type="text" class="welow-color-field" data-default-color="#ffffff"
                           name="<?php echo esc_attr( $base ); ?>[color_titulos]"
                           value="<?php echo esc_attr( $f['color_titulos'] ?? '' ); ?>"
                           placeholder="#ffffff" />
                </td>
            </tr>
            <tr>
                <th><label>Color de enlaces (hover)</label></th>
                <td>
                    <input type="text" class="welow-color-field" data-default-color=""
                           name="<?php echo esc_attr( $base ); ?>[color_link]"
                           value="<?php echo esc_attr( $f['color_link'] ?? '' ); ?>"
                           placeholder="(usa el color principal de Estilos)" />
                    <p class="description">Si lo dejas vacío, usa el color principal definido en la pestaña "Estilos".</p>
                </td>
            </tr>
        </table>

        <p style="background:#eff6ff;border-left:3px solid #2271b1;padding:10px 14px;margin-top:18px;font-size:13px;">
            💡 Para usar este footer: ve a <strong>Divi → Theme Builder</strong>, edita el template del footer global y mete el shortcode <code>[welow_footer]</code>.
        </p>
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

        <h3 style="margin-top:30px;">🔤 Tipografía del header</h3>
        <p>Personaliza la fuente, peso y tamaño de los textos del header. Si usas una fuente de Google Fonts, márcalo y se cargará automáticamente.</p>

        <table class="form-table">
            <tr>
                <th scope="row"><label>Familia tipográfica</label></th>
                <td>
                    <input type="text" name="<?php echo esc_attr( $name ); ?>[font_family]"
                           value="<?php echo esc_attr( $h['font_family'] ?? '' ); ?>"
                           placeholder="ej: Figtree, Inter, Roboto..." class="regular-text" list="welow-fonts-suggestions" />
                    <datalist id="welow-fonts-suggestions">
                        <option value="Figtree">
                        <option value="Inter">
                        <option value="Roboto">
                        <option value="Poppins">
                        <option value="Open Sans">
                        <option value="Montserrat">
                        <option value="Lato">
                        <option value="Raleway">
                        <option value="Nunito">
                        <option value="Outfit">
                        <option value="DM Sans">
                        <option value="Manrope">
                        <option value="Plus Jakarta Sans">
                        <option value="Work Sans">
                        <option value="Source Sans 3">
                        <option value="Ubuntu">
                        <option value="Mulish">
                        <option value="Barlow">
                        <option value="Exo 2">
                    </datalist>
                    <p class="description">Nombre exacto de la fuente. Si la dejas vacía se usa la fuente del tema.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Origen</label></th>
                <td>
                    <label>
                        <input type="checkbox" name="<?php echo esc_attr( $name ); ?>[font_google]" value="1" <?php checked( ! empty( $h['font_google'] ) || ! isset( $h['font_google'] ) ); ?> />
                        <strong>Cargar desde Google Fonts</strong> automáticamente
                    </label>
                    <p class="description">Si la fuente está disponible en Google Fonts, se cargará en el head sin necesidad de plugins externos. Desmarca si la fuente está cargada por el tema o por otro plugin.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Peso</label></th>
                <td>
                    <label style="display:inline-block;margin-right:18px;">Menú:<br>
                        <select name="<?php echo esc_attr( $name ); ?>[font_weight_menu]">
                            <?php foreach ( array( '300' => '300 Light', '400' => '400 Regular', '500' => '500 Medium', '600' => '600 SemiBold', '700' => '700 Bold', '800' => '800 ExtraBold' ) as $w => $label ) : ?>
                                <option value="<?php echo esc_attr( $w ); ?>" <?php selected( $h['font_weight_menu'] ?? '600', $w ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label style="display:inline-block;">Botón CTA:<br>
                        <select name="<?php echo esc_attr( $name ); ?>[font_weight_boton]">
                            <?php foreach ( array( '400' => '400 Regular', '500' => '500 Medium', '600' => '600 SemiBold', '700' => '700 Bold', '800' => '800 ExtraBold' ) as $w => $label ) : ?>
                                <option value="<?php echo esc_attr( $w ); ?>" <?php selected( $h['font_weight_boton'] ?? '700', $w ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Tamaño (px)</label></th>
                <td>
                    <label style="display:inline-block;margin-right:18px;">Menú:<br>
                        <input type="number" name="<?php echo esc_attr( $name ); ?>[font_size_menu]"
                               value="<?php echo esc_attr( $h['font_size_menu'] ?? 14 ); ?>" min="10" max="32" step="1" style="width:80px;" /> px
                    </label>
                    <label style="display:inline-block;margin-right:18px;">Botón:<br>
                        <input type="number" name="<?php echo esc_attr( $name ); ?>[font_size_boton]"
                               value="<?php echo esc_attr( $h['font_size_boton'] ?? 14 ); ?>" min="10" max="24" step="1" style="width:80px;" /> px
                    </label>
                    <label style="display:inline-block;">Teléfono:<br>
                        <input type="number" name="<?php echo esc_attr( $name ); ?>[font_size_telefono]"
                               value="<?php echo esc_attr( $h['font_size_telefono'] ?? 14 ); ?>" min="10" max="24" step="1" style="width:80px;" /> px
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Estilo del menú</label></th>
                <td>
                    <label style="display:inline-block;margin-right:18px;">Transformación:<br>
                        <select name="<?php echo esc_attr( $name ); ?>[text_transform_menu]">
                            <option value="none" <?php selected( $h['text_transform_menu'] ?? 'none', 'none' ); ?>>Normal</option>
                            <option value="uppercase" <?php selected( $h['text_transform_menu'] ?? 'none', 'uppercase' ); ?>>MAYÚSCULAS</option>
                            <option value="capitalize" <?php selected( $h['text_transform_menu'] ?? 'none', 'capitalize' ); ?>>Capitalize</option>
                        </select>
                    </label>
                    <label style="display:inline-block;">Espaciado entre letras:<br>
                        <input type="text" name="<?php echo esc_attr( $name ); ?>[letter_spacing_menu]"
                               value="<?php echo esc_attr( $h['letter_spacing_menu'] ?? '' ); ?>"
                               placeholder="ej: 0.5px, 0.04em" style="width:120px;" />
                    </label>
                    <p class="description">Útil cuando usas mayúsculas (ej: <code>0.5px</code> o <code>0.04em</code>).</p>
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
            // v2.7.0 — Tipografía
            'font_family' => '',
            'font_google' => true,
            'font_weight_menu' => '600',
            'font_weight_boton' => '700',
            'font_size_menu' => 14,
            'font_size_boton' => 14,
            'font_size_telefono' => 14,
            'text_transform_menu' => 'none',
            'letter_spacing_menu' => '',
        );
        $h = isset( $opts['header'] ) && is_array( $opts['header'] ) ? $opts['header'] : array();
        return wp_parse_args( $h, $defaults );
    }
}
