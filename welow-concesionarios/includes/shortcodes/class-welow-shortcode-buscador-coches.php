<?php
/**
 * Shortcode: [welow_buscador_coches] — Formulario de búsqueda de coches.
 *
 * @since 2.0.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Buscador_Coches {

    public static function init() {
        add_shortcode( 'welow_buscador_coches', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'accion' => '',
            'campos' => 'marca,combustible,carroceria,precio,km,anio',
            'titulo' => 'Buscar tu coche',
        ), $atts );

        $campos = array_map( 'trim', explode( ',', $atts['campos'] ) );
        $accion = $atts['accion'] ?: get_permalink();

        wp_enqueue_style( 'welow-buscador' );

        ob_start();
        Welow_Helpers::get_template( 'buscador-coches.php', array(
            'accion' => $accion,
            'campos' => $campos,
            'titulo' => $atts['titulo'],
        ) );
        return ob_get_clean();
    }
}
