<?php
/**
 * Shortcode: [welow_modelos] — Grid de modelos por marca.
 *
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Modelos {

    public static function init() {
        add_shortcode( 'welow_modelos', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'marca'           => '',
            'columnas'        => '3',
            'columnas_tablet' => '2',
            'columnas_movil'  => '1',
            'max'             => '-1',
            'texto_boton'     => 'Ver modelo',
        ), $atts );

        if ( empty( $atts['marca'] ) ) {
            return '<p class="welow-no-results">Shortcode [welow_modelos]: falta el parámetro "marca".</p>';
        }

        $modelos = Welow_Helpers::get_modelos( $atts['marca'], intval( $atts['max'] ) );

        if ( empty( $modelos ) ) {
            return '<p class="welow-no-results">No se encontraron modelos para esta marca.</p>';
        }

        wp_enqueue_style( 'welow-secciones' );

        ob_start();
        Welow_Helpers::get_template( 'modelos-grid.php', array(
            'modelos'         => $modelos,
            'columnas'        => intval( $atts['columnas'] ),
            'columnas_tablet' => intval( $atts['columnas_tablet'] ),
            'columnas_movil'  => intval( $atts['columnas_movil'] ),
            'texto_boton'     => sanitize_text_field( $atts['texto_boton'] ),
        ) );
        return ob_get_clean();
    }
}
