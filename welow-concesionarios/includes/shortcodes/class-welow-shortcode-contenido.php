<?php
/**
 * Shortcode: [welow_contenido] — Sección de contenido flexible.
 * Soporta título, texto (entre tags), imagen y botón con layouts configurables.
 *
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Contenido {

    public static function init() {
        add_shortcode( 'welow_contenido', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts, $content = '' ) {
        $atts = shortcode_atts( array(
            'titulo'       => '',
            'imagen'       => '',
            'imagen_movil' => '',
            'layout'       => 'imagen-derecha',  // imagen-derecha, imagen-izquierda, imagen-arriba, solo-texto
            'boton_texto'  => '',
            'boton_enlace' => '',
            'fondo'        => 'transparente',
        ), $atts );

        wp_enqueue_style( 'welow-secciones' );

        // Procesar contenido (permitir shortcodes anidados y HTML básico)
        $contenido_procesado = do_shortcode( wpautop( $content ) );

        // Obtener URLs de imágenes
        $img_url       = ! empty( $atts['imagen'] ) ? wp_get_attachment_image_url( intval( $atts['imagen'] ), 'large' ) : '';
        $img_movil_url = ! empty( $atts['imagen_movil'] ) ? wp_get_attachment_image_url( intval( $atts['imagen_movil'] ), 'medium_large' ) : '';

        // Layouts válidos
        $layouts_validos = array( 'imagen-derecha', 'imagen-izquierda', 'imagen-arriba', 'solo-texto' );
        $layout = in_array( $atts['layout'], $layouts_validos, true ) ? $atts['layout'] : 'imagen-derecha';

        ob_start();
        Welow_Helpers::get_template( 'contenido.php', array(
            'titulo'       => sanitize_text_field( $atts['titulo'] ),
            'contenido'    => $contenido_procesado,
            'img_url'      => $img_url,
            'img_movil_url'=> $img_movil_url,
            'layout'       => $layout,
            'boton_texto'  => sanitize_text_field( $atts['boton_texto'] ),
            'boton_enlace' => esc_url( $atts['boton_enlace'] ),
            'fondo'        => sanitize_text_field( $atts['fondo'] ),
        ) );
        return ob_get_clean();
    }
}
