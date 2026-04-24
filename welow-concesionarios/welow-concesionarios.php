<?php
/**
 * Plugin Name: Welow Concesionarios
 * Plugin URI:  https://welow.es
 * Description: Sistema de gestión para concesionarios multimarca. CPTs, shortcodes y herramientas para coches nuevos y de segunda mano.
 * Version:     1.1.0
 * Author:      Welow
 * Author URI:  https://welow.es
 * License:     GPL-2.0+
 * Text Domain: welow-concesionarios
 * Domain Path: /languages
 *
 * @package Welow_Concesionarios
 *
 * CHANGELOG
 * ---------
 * 1.1.0 (Fase 3) — Reestructuración administrativa + nuevos campos
 *   - Menú unificado "Concesionarios" con dashboard
 *   - Marcas: 3 logos (original, negro, blanco) + 2 banners (portada + zona media) con desktop/móvil
 *   - Modelos: galería de 5 imágenes, precio desde, disclaimer con override, etiquetas visuales
 *   - Nuevo CPT: welow_etiqueta (etiquetas visuales)
 *   - Nueva taxonomía: welow_combustible (Gasoil, Gasolina, Híbrido, etc.)
 *   - Nueva página Configuraciones (disclaimer global + icono)
 *   - Nuevo shortcode: [welow_marca_banner tipo="portada|media"]
 *   - Shortcodes marcas: parámetro variante_logo
 *   - Importador/Exportador CSV de marcas y modelos
 *
 * 1.0.0 — Versión inicial
 *   - CPTs: marca, slide, modelo
 *   - Shortcodes: [welow_marcas], [welow_marcas_cards], [welow_slider],
 *     [welow_modelos], [welow_slider_cta], [welow_contenido]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constantes del plugin
define( 'WELOW_CONC_VERSION', '1.1.0' );
define( 'WELOW_CONC_PATH', plugin_dir_path( __FILE__ ) );
define( 'WELOW_CONC_URL', plugin_dir_url( __FILE__ ) );
define( 'WELOW_CONC_BASENAME', plugin_basename( __FILE__ ) );

// Cargar clases
require_once WELOW_CONC_PATH . 'includes/helpers/class-welow-helpers.php';

// Admin (v1.1.0)
require_once WELOW_CONC_PATH . 'includes/admin/class-welow-admin-menu.php';
require_once WELOW_CONC_PATH . 'includes/admin/class-welow-settings.php';
require_once WELOW_CONC_PATH . 'includes/admin/class-welow-importer.php';

// CPTs
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-marca.php';
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-slide.php';
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-modelo.php';
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-etiqueta.php';

// Taxonomías (v1.1.0)
require_once WELOW_CONC_PATH . 'includes/taxonomies/class-welow-tax-combustible.php';

// Shortcodes
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-marcas.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-slider.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-modelos.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-slider-cta.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-contenido.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-marca-banner.php';

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
    Welow_CPT_Etiqueta::registrar_cpt();
    Welow_Tax_Combustible::registrar_taxonomia();
    Welow_Tax_Combustible::crear_terminos_defecto();
    flush_rewrite_rules();
});

register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
});
