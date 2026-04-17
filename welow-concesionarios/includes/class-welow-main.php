<?php
/**
 * Clase principal del plugin Welow Concesionarios.
 *
 * @package Welow_Concesionarios
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
        // Registrar CPTs
        Welow_CPT_Marca::init();
        Welow_CPT_Slide::init();
        Welow_CPT_Modelo::init();

        // Registrar Shortcodes
        Welow_Shortcode_Marcas::init();
        Welow_Shortcode_Slider::init();
        Welow_Shortcode_Modelos::init();
        Welow_Shortcode_Slider_CTA::init();
        Welow_Shortcode_Contenido::init();

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
     * Assets para el admin (metaboxes con media uploader).
     * Se cargan en los CPTs que lo necesitan.
     */
    public function registrar_admin_assets( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        // CPTs que usan el media uploader
        $cpts_con_media = array( 'welow_marca', 'welow_slide' );

        if ( in_array( $screen->post_type, $cpts_con_media, true ) ) {
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
