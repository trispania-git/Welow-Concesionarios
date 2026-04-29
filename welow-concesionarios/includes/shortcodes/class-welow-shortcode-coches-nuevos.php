<?php
/**
 * Shortcode: [welow_coches_nuevos] — Grid de coches nuevos del catálogo.
 *
 * @since 2.1.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Coches_Nuevos {

    public static function init() {
        add_shortcode( 'welow_coches_nuevos', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'marca'           => '',
            'modelo'          => '',
            'combustible'     => '',
            'carroceria'      => '',
            'concesionario'   => '',
            'precio_min'      => '',
            'precio_max'      => '',
            'estado'          => 'disponible',
            'orden'           => 'recientes',
            'columnas'        => '3',
            'columnas_tablet' => '2',
            'columnas_movil'  => '1',
            'max'             => '12',
        ), $atts );

        // Auto-detección de marca si está en una página de marca
        if ( 'auto' === $atts['marca'] || ( '' === $atts['marca'] && Welow_Helpers::get_current_marca_id() ) ) {
            $marca_actual = Welow_Helpers::get_current_marca_id();
            if ( $marca_actual ) $atts['marca'] = $marca_actual;
        }

        // Override de filtros desde GET
        $atts = self::merge_get_filters( $atts );

        $coches = Welow_Helpers::get_coches_nuevos( array(
            'marca'         => $atts['marca'],
            'modelo'        => $atts['modelo'],
            'combustible'   => $atts['combustible'],
            'carroceria'    => $atts['carroceria'],
            'concesionario' => $atts['concesionario'],
            'precio_min'    => $atts['precio_min'],
            'precio_max'    => $atts['precio_max'],
            'estado'        => $atts['estado'],
            'orden'         => $atts['orden'],
            'max'           => intval( $atts['max'] ),
        ) );

        if ( empty( $coches ) ) {
            return '<p class="welow-no-results">No se encontraron coches nuevos con esos criterios.</p>';
        }

        wp_enqueue_style( 'welow-coches' );

        ob_start();
        Welow_Helpers::get_template( 'coches-grid-nuevos.php', array(
            'coches'          => $coches,
            'columnas'        => intval( $atts['columnas'] ),
            'columnas_tablet' => intval( $atts['columnas_tablet'] ),
            'columnas_movil'  => intval( $atts['columnas_movil'] ),
        ) );
        return ob_get_clean();
    }

    private static function merge_get_filters( $atts ) {
        $get_keys = array( 'marca', 'modelo', 'combustible', 'carroceria',
                           'precio_min', 'precio_max', 'orden' );
        foreach ( $get_keys as $k ) {
            if ( isset( $_GET[ 'welow_' . $k ] ) && '' !== $_GET[ 'welow_' . $k ] ) {
                $atts[ $k ] = sanitize_text_field( wp_unslash( $_GET[ 'welow_' . $k ] ) );
            }
        }
        return $atts;
    }
}
