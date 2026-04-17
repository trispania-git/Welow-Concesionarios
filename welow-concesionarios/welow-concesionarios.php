<?php
/**
 * Plugin Name: Welow Concesionarios
 * Plugin URI:  https://welow.es
 * Description: Sistema de gestión para concesionarios multimarca. CPTs, shortcodes y herramientas para coches nuevos y de segunda mano.
 * Version:     1.0.0
 * Author:      Welow
 * Author URI:  https://welow.es
 * License:     GPL-2.0+
 * Text Domain: welow-concesionarios
 * Domain Path: /languages
 *
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constantes del plugin
define( 'WELOW_CONC_VERSION', '1.0.0' );
define( 'WELOW_CONC_PATH', plugin_dir_path( __FILE__ ) );
define( 'WELOW_CONC_URL', plugin_dir_url( __FILE__ ) );
define( 'WELOW_CONC_BASENAME', plugin_basename( __FILE__ ) );

// Cargar clases
require_once WELOW_CONC_PATH . 'includes/helpers/class-welow-helpers.php';

// CPTs
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-marca.php';
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-slide.php';
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-modelo.php';

// Shortcodes
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-marcas.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-slider.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-modelos.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-slider-cta.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-contenido.php';

// Principal
require_once WELOW_CONC_PATH . 'includes/class-welow-main.php';

// Inicializar plugin
function welow_concesionarios_init() {
    Welow_Main::get_instance();
}
add_action( 'plugins_loaded', 'welow_concesionarios_init' );

// Flush rewrite rules al activar/desactivar
register_activation_hook( __FILE__, function() {
    Welow_CPT_Marca::registrar_cpt();
    Welow_CPT_Slide::registrar_cpt();
    Welow_CPT_Modelo::registrar_cpt();
    flush_rewrite_rules();
});

register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
});
