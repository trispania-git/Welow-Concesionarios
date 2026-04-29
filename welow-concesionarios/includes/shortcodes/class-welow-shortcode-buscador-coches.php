<?php
/**
 * Shortcode: [welow_buscador_coches] — Formulario de búsqueda.
 *
 * @since 2.0.0
 * @version 2.1.0 — Soporte de tipo (nuevos/ocasion/todos), filtros adaptables.
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
            'tipo'   => 'todos',          // nuevos | ocasion | todos
            'accion' => '',
            'campos' => '',               // si vacío, se usan campos por defecto según el tipo
            'titulo' => 'Buscar tu coche',
        ), $atts );

        $tipo_validos = array( 'nuevos', 'ocasion', 'todos' );
        $tipo = in_array( $atts['tipo'], $tipo_validos, true ) ? $atts['tipo'] : 'todos';

        // Campos por defecto según tipo
        if ( ! $atts['campos'] ) {
            if ( 'nuevos' === $tipo ) {
                $atts['campos'] = 'marca,combustible,carroceria,precio';
            } elseif ( 'ocasion' === $tipo ) {
                $atts['campos'] = 'marca_externa,combustible,carroceria,precio,km,anio';
            } else {
                $atts['campos'] = 'marca,marca_externa,combustible,carroceria,precio,km,anio';
            }
        }

        $campos = array_map( 'trim', explode( ',', $atts['campos'] ) );
        $accion = $atts['accion'] ?: get_permalink();

        wp_enqueue_style( 'welow-buscador' );

        ob_start();
        Welow_Helpers::get_template( 'buscador-coches.php', array(
            'tipo'   => $tipo,
            'accion' => $accion,
            'campos' => $campos,
            'titulo' => $atts['titulo'],
        ) );
        return ob_get_clean();
    }
}
