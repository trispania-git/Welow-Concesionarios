<?php
/**
 * Clase principal del plugin Welow Concesionarios.
 *
 * @package Welow_Concesionarios
 * @since 1.0.0
 * @version 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Main {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Admin
        Welow_Admin_Menu::init();              // v1.1.0
        Welow_Settings::init();                // v1.1.0
        Welow_Importer::init();                // v1.1.0
        Welow_Divi_Library_Admin::init();      // v1.4.0

        // Taxonomías
        Welow_Tax_Combustible::init();        // v1.1.0
        Welow_Tax_Categoria_Modelo::init();   // v1.2.0

        // CPTs
        Welow_CPT_Marca::init();
        Welow_CPT_Slide::init();
        Welow_CPT_Modelo::init();
        Welow_CPT_Etiqueta::init();

        // Shortcodes
        Welow_Shortcode_Marcas::init();
        Welow_Shortcode_Slider::init();
        Welow_Shortcode_Modelos::init();
        Welow_Shortcode_Slider_CTA::init();
        Welow_Shortcode_Contenido::init();
        Welow_Shortcode_Marca_Banner::init();
        Welow_Shortcode_Divi::init();          // v1.4.0

        // Enqueue assets
        add_action( 'wp_enqueue_scripts', array( $this, 'registrar_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'registrar_admin_assets' ) );
    }

    /**
     * Registra los assets del frontend (se encolan bajo demanda en los shortcodes).
     */
    public function registrar_assets() {
        // CSS
        wp_register_style(
            'welow-marcas',
            WELOW_CONC_URL . 'assets/css/marcas.css',
            array(),
            WELOW_CONC_VERSION
        );

        wp_register_style(
            'welow-slider',
            WELOW_CONC_URL . 'assets/css/slider.css',
            array(),
            WELOW_CONC_VERSION
        );

        wp_register_style(
            'welow-secciones',
            WELOW_CONC_URL . 'assets/css/secciones.css',
            array(),
            WELOW_CONC_VERSION
        );

        // JS
        wp_register_script(
            'welow-slider',
            WELOW_CONC_URL . 'assets/js/slider.js',
            array(),
            WELOW_CONC_VERSION,
            true
        );
    }

    /**
     * Assets para el admin.
     *
     * @since 1.1.0 — Añadido soporte para welow_etiqueta, welow_modelo y páginas del plugin.
     */
    public function registrar_admin_assets( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen ) return;

        // CPTs y páginas que usan el media uploader
        $cpts_con_media = array( 'welow_marca', 'welow_slide', 'welow_modelo', 'welow_etiqueta' );
        $paginas_con_media = array(
            'concesionarios_page_welow_settings',
            'welow-concesionarios_page_welow_settings',
        );

        $necesita_media = in_array( $screen->post_type, $cpts_con_media, true )
            || in_array( $hook, $paginas_con_media, true )
            || ( isset( $_GET['page'] ) && 'welow_settings' === $_GET['page'] );

        if ( $necesita_media ) {
            wp_enqueue_media();
            wp_enqueue_style(
                'welow-admin-marca',
                WELOW_CONC_URL . 'assets/css/admin-marca.css',
                array(),
                WELOW_CONC_VERSION
            );
            wp_enqueue_script(
                'welow-admin-marca',
                WELOW_CONC_URL . 'assets/js/admin-marca.js',
                array( 'jquery' ),
                WELOW_CONC_VERSION,
                true
            );
        }
    }
}
