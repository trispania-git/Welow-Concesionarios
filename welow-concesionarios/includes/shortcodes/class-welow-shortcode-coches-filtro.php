<?php
/**
 * Shortcode: [welow_coches_filtro] — Página de filtros + listado de coches.
 *
 * Layout: sidebar de filtros (izq) + grid de resultados (der).
 * En móvil los filtros se convierten en drawer fullscreen.
 *
 * Filtros vía GET (URL params), sin AJAX. Submit del form recarga la página.
 *
 * @since 2.8.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Coches_Filtro {

    public static function init() {
        add_shortcode( 'welow_coches_filtro', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'tipo'             => 'ocasion',     // ocasion | nuevos | todos
            'titulo'           => '',
            'subtitulo'        => '',
            'por_pagina'       => '12',
            'columnas'         => '3',
            'mostrar_filtros'  => 'marca,carroceria,combustible,cambio,precio,anio,km,cv',
            // Fija una marca oficial sin permitir cambiarla (útil en /toyota/ofertas-coches)
            'marca_fija'       => '',
            // Idem para marca externa (útil en /bmw-ocasion/)
            'marca_externa_fija' => '',
        ), $atts );

        // Determinar CPT según tipo
        $cpt = array( 'welow_coche_nuevo', 'welow_coche_ocasion' );
        if ( 'ocasion' === $atts['tipo'] ) $cpt = 'welow_coche_ocasion';
        elseif ( 'nuevos' === $atts['tipo'] ) $cpt = 'welow_coche_nuevo';

        // Recoger filtros de GET (con prefijo welow_)
        $get = $_GET;
        $get_value = function( $key, $default = '' ) use ( $get ) {
            return isset( $get[ 'welow_' . $key ] ) && '' !== $get[ 'welow_' . $key ]
                ? wp_unslash( $get[ 'welow_' . $key ] )
                : $default;
        };

        // Marca: si está fijada por shortcode, usarla; si no, leer de GET
        $marca_query         = $atts['marca_fija'] ?: $get_value( 'marca' );
        $marca_externa_query = $atts['marca_externa_fija'] ?: (array) $get_value( 'marca_externa', array() );
        if ( ! is_array( $marca_externa_query ) ) {
            $marca_externa_query = $marca_externa_query ? array( $marca_externa_query ) : array();
        }

        // Filtros multi-valor (arrays)
        $combustible = (array) $get_value( 'combustible', array() );
        if ( ! is_array( $combustible ) ) $combustible = array( $combustible );

        $carroceria = (array) $get_value( 'carroceria', array() );
        if ( ! is_array( $carroceria ) ) $carroceria = array( $carroceria );

        $cambio = (array) $get_value( 'cambio', array() );
        if ( ! is_array( $cambio ) ) $cambio = array( $cambio );

        $tipo_venta_get = $get_value( 'tipo_venta', 'todos' );
        $orden_get      = $get_value( 'orden', 'recientes' );
        $paged          = isset( $get['welow_paged'] ) ? max( 1, absint( $get['welow_paged'] ) ) : 1;

        // Hacer la query
        $resultado = Welow_Helpers::get_coches_paginado( array(
            'cpt'           => $cpt,
            'marca'         => $marca_query,
            'marca_externa' => $marca_externa_query,
            'tipo_venta'    => $tipo_venta_get,
            'combustible'   => array_filter( $combustible ),
            'carroceria'    => array_filter( $carroceria ),
            'cambio'        => array_filter( $cambio ),
            'precio_min'    => $get_value( 'precio_min' ),
            'precio_max'    => $get_value( 'precio_max' ),
            'km_min'        => $get_value( 'km_min' ),
            'km_max'        => $get_value( 'km_max' ),
            'anio_min'      => $get_value( 'anio_min' ),
            'anio_max'      => $get_value( 'anio_max' ),
            'cv_min'        => $get_value( 'cv_min' ),
            'cv_max'        => $get_value( 'cv_max' ),
            'orden'         => $orden_get,
            'por_pagina'    => intval( $atts['por_pagina'] ),
            'paged'         => $paged,
        ) );

        wp_enqueue_style( 'welow-coches' );
        wp_enqueue_style( 'welow-coches-filtro' );
        wp_enqueue_style( 'welow-buscador' );
        wp_enqueue_script( 'welow-coches-filtro' );

        ob_start();
        Welow_Helpers::get_template( 'coches-filtro.php', array(
            'atts'      => $atts,
            'cpt'       => $cpt,
            'resultado' => $resultado,
            'columnas'  => intval( $atts['columnas'] ),
            'filtros'   => array_map( 'trim', explode( ',', $atts['mostrar_filtros'] ) ),
            // Valores actuales de filtros (para repoblación)
            'valores'   => array(
                'marca'         => $marca_query,
                'marca_externa' => $marca_externa_query,
                'combustible'   => array_filter( $combustible ),
                'carroceria'    => array_filter( $carroceria ),
                'cambio'        => array_filter( $cambio ),
                'tipo_venta'    => $tipo_venta_get,
                'precio_min'    => $get_value( 'precio_min' ),
                'precio_max'    => $get_value( 'precio_max' ),
                'km_min'        => $get_value( 'km_min' ),
                'km_max'        => $get_value( 'km_max' ),
                'anio_min'      => $get_value( 'anio_min' ),
                'anio_max'      => $get_value( 'anio_max' ),
                'cv_min'        => $get_value( 'cv_min' ),
                'cv_max'        => $get_value( 'cv_max' ),
                'orden'         => $orden_get,
            ),
        ) );
        return ob_get_clean();
    }
}
