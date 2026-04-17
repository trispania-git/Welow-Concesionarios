<?php
/**
 * Shortcode: [welow_slider_cta] — Sección hero con imagen de fondo, texto y botón.
 *
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Slider_CTA {

    private static $instance_count = 0;

    public static function init() {
        add_shortcode( 'welow_slider_cta', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'imagen'       => '',
            'imagen_movil' => '',
            'titulo'       => '',
            'texto'        => '',
            'boton_texto'  => '',
            'boton_enlace' => '',
            'overlay'      => 'rgba(0,0,0,0.4)',
            'alineacion'   => 'centro',
            'altura'       => '500px',
            'altura_movil' => '350px',
        ), $atts );

        if ( empty( $atts['imagen'] ) ) {
            return '<p class="welow-no-results">Shortcode [welow_slider_cta]: falta el parámetro "imagen".</p>';
        }

        wp_enqueue_style( 'welow-secciones' );

        self::$instance_count++;

        // Obtener URLs de las imágenes
        $img_desktop_url = wp_get_attachment_image_url( intval( $atts['imagen'] ), 'full' );
        $img_movil_url   = ! empty( $atts['imagen_movil'] )
            ? wp_get_attachment_image_url( intval( $atts['imagen_movil'] ), 'large' )
            : $img_desktop_url;

        ob_start();
        Welow_Helpers::get_template( 'slider-cta.php', array(
            'cta_id'          => 'welow-cta-' . self::$instance_count,
            'img_desktop_url' => $img_desktop_url,
            'img_movil_url'   => $img_movil_url,
            'titulo'          => sanitize_text_field( $atts['titulo'] ),
            'texto'           => sanitize_text_field( $atts['texto'] ),
            'boton_texto'     => sanitize_text_field( $atts['boton_texto'] ),
            'boton_enlace'    => esc_url( $atts['boton_enlace'] ),
            'overlay'         => sanitize_text_field( $atts['overlay'] ),
            'alineacion'      => sanitize_text_field( $atts['alineacion'] ),
            'altura'          => sanitize_text_field( $atts['altura'] ),
            'altura_movil'    => sanitize_text_field( $atts['altura_movil'] ),
        ) );
        return ob_get_clean();
    }
}
