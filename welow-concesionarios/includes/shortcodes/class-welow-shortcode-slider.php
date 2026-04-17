<?php
/**
 * Shortcode: [welow_slider] — Slider de imágenes fullwidth.
 * Intercambia imagen desktop/móvil automáticamente.
 *
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Slider {

    private static $instance_count = 0;

    public static function init() {
        add_shortcode( 'welow_slider', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'grupo'    => '',
            'autoplay' => 'si',
            'velocidad'=> '5000',
            'flechas'  => 'si',
            'puntos'   => 'si',
        ), $atts );

        if ( empty( $atts['grupo'] ) ) {
            return '<p class="welow-no-results">Shortcode [welow_slider]: falta el parámetro "grupo".</p>';
        }

        $slides = Welow_Helpers::get_slides( $atts['grupo'] );

        if ( empty( $slides ) ) {
            return '<p class="welow-no-results">No se encontraron slides para el grupo "' . esc_html( $atts['grupo'] ) . '".</p>';
        }

        // Encolar assets
        wp_enqueue_style( 'welow-slider' );
        wp_enqueue_script( 'welow-slider' );

        self::$instance_count++;

        ob_start();
        Welow_Helpers::get_template( 'slider.php', array(
            'slides'     => $slides,
            'slider_id'  => 'welow-slider-' . self::$instance_count,
            'autoplay'   => ( 'si' === $atts['autoplay'] ),
            'velocidad'  => intval( $atts['velocidad'] ),
            'flechas'    => ( 'si' === $atts['flechas'] ),
            'puntos'     => ( 'si' === $atts['puntos'] ),
            'es_single'  => ( count( $slides ) === 1 ),
        ) );
        return ob_get_clean();
    }
}
