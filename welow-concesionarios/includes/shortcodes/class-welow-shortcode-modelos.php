<?php
/**
 * Shortcode: [welow_modelos] — Grid de modelos por marca.
 *
 * @since 1.0.0
 * @version 1.3.0 — Auto-detección de marca cuando se omite el parámetro
 *                   o se pasa "auto" (útil en plantillas del Theme Builder).
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
            'marca'           => 'auto',     // auto | slug | ID
            'columnas'        => '3',
            'columnas_tablet' => '2',
            'columnas_movil'  => '1',
            'max'             => '-1',
            'texto_boton'     => 'Ver modelo',
        ), $atts );

        // v1.3.0 — Auto-detección
        $marca_param = $atts['marca'];
        if ( '' === $marca_param || 'auto' === $marca_param ) {
            $marca_id = Welow_Helpers::get_current_marca_id();
            if ( ! $marca_id ) {
                return '<!-- [welow_modelos]: no se detectó marca actual -->';
            }
            $marca_param = $marca_id;
        }

        $modelos = Welow_Helpers::get_modelos( $marca_param, intval( $atts['max'] ) );

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
