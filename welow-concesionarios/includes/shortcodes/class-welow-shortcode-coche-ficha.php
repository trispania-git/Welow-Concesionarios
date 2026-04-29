<?php
/**
 * Shortcode: [welow_coche_ficha] — Ficha individual completa.
 *
 * @since 2.0.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Coche_Ficha {

    public static function init() {
        add_shortcode( 'welow_coche_ficha', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'id'      => 'auto',
            // v2.5.1 — bloque "formulario" añadido al default (en aside, compacto)
            'mostrar' => 'galeria,destacados,precio,formulario,equipamiento,garantias,concesionario',
        ), $atts );

        // Resolver coche
        if ( 'auto' === $atts['id'] || '' === $atts['id'] ) {
            $coche_id = Welow_Helpers::get_current_coche_id();
        } else {
            $coche_id = absint( $atts['id'] );
        }

        if ( ! $coche_id ) {
            return '<!-- [welow_coche_ficha]: no se encontró coche -->';
        }

        $data = Welow_Helpers::get_coche_ficha_data( $coche_id );
        if ( ! $data ) {
            return '<!-- [welow_coche_ficha]: coche inválido -->';
        }

        $bloques = array_map( 'trim', explode( ',', $atts['mostrar'] ) );

        wp_enqueue_style( 'welow-coche-ficha' );
        wp_enqueue_script( 'welow-coche-galeria' );

        ob_start();
        Welow_Helpers::get_template( 'coche-ficha.php', array(
            'data'    => $data,
            'bloques' => $bloques,
        ) );
        return ob_get_clean();
    }
}
