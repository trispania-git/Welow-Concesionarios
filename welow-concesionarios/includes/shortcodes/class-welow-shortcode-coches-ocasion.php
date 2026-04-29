<?php
/**
 * Shortcode: [welow_coches_ocasion] — Grid de coches de ocasión / KM0.
 *
 * @since 2.1.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Coches_Ocasion {

    public static function init() {
        add_shortcode( 'welow_coches_ocasion', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'marca_externa'   => '',
            'tipo'            => 'todos',  // ocasion | km0 | todos
            'combustible'     => '',
            'carroceria'      => '',
            'concesionario'   => '',
            'precio_min'      => '',
            'precio_max'      => '',
            'km_max'          => '',
            'anio_min'        => '',
            'estado'          => 'disponible',
            'orden'           => 'recientes',
            'columnas'        => '3',
            'columnas_tablet' => '2',
            'columnas_movil'  => '1',
            'max'             => '12',
        ), $atts );

        // Override de filtros desde GET
        $atts = self::merge_get_filters( $atts );

        $coches = Welow_Helpers::get_coches_ocasion( array(
            'marca_externa' => $atts['marca_externa'],
            'tipo_venta'    => $atts['tipo'],
            'combustible'   => $atts['combustible'],
            'carroceria'    => $atts['carroceria'],
            'concesionario' => $atts['concesionario'],
            'precio_min'    => $atts['precio_min'],
            'precio_max'    => $atts['precio_max'],
            'km_max'        => $atts['km_max'],
            'anio_min'      => $atts['anio_min'],
            'estado'        => $atts['estado'],
            'orden'         => $atts['orden'],
            'max'           => intval( $atts['max'] ),
        ) );

        if ( empty( $coches ) ) {
            return '<p class="welow-no-results">No se encontraron coches de ocasión con esos criterios.</p>';
        }

        wp_enqueue_style( 'welow-coches' );

        ob_start();
        Welow_Helpers::get_template( 'coches-grid-ocasion.php', array(
            'coches'          => $coches,
            'columnas'        => intval( $atts['columnas'] ),
            'columnas_tablet' => intval( $atts['columnas_tablet'] ),
            'columnas_movil'  => intval( $atts['columnas_movil'] ),
        ) );
        return ob_get_clean();
    }

    private static function merge_get_filters( $atts ) {
        $get_keys = array( 'marca_externa', 'tipo', 'combustible', 'carroceria',
                           'precio_min', 'precio_max', 'km_max', 'anio_min', 'orden' );
        foreach ( $get_keys as $k ) {
            if ( isset( $_GET[ 'welow_' . $k ] ) && '' !== $_GET[ 'welow_' . $k ] ) {
                $atts[ $k ] = sanitize_text_field( wp_unslash( $_GET[ 'welow_' . $k ] ) );
            }
        }
        return $atts;
    }
}
