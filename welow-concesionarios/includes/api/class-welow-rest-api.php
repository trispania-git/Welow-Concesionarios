<?php
/**
 * REST API endpoints públicos para chatbots y consumo externo.
 *
 * Namespace: welow/v1
 *
 * Endpoints:
 *   GET /wp-json/welow/v1/coches/nuevos?max=100
 *   GET /wp-json/welow/v1/coches/ocasion?max=100&tipo=ocasion|km0
 *   GET /wp-json/welow/v1/coches/todos?max=100
 *   GET /wp-json/welow/v1/modelos
 *   GET /wp-json/welow/v1/marcas
 *   GET /wp-json/welow/v1/info               (resumen del sitio)
 *
 * Todos públicos sin autenticación. Si en el futuro se quiere proteger,
 * se puede añadir un parámetro `?api_key=` configurable en Configuraciones.
 *
 * @since 2.4.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Rest_API {

    const NAMESPACE_API = 'welow/v1';

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'registrar_endpoints' ) );
    }

    public static function registrar_endpoints() {
        $args_max = array(
            'max' => array(
                'description' => 'Máximo de resultados (-1 = todos).',
                'type'        => 'integer',
                'default'     => 100,
            ),
            'estado' => array(
                'description' => 'Filtrar por estado: disponible, reservado, vendido, todos.',
                'type'        => 'string',
                'default'     => 'disponible',
            ),
        );

        register_rest_route( self::NAMESPACE_API, '/coches/nuevos', array(
            'methods'  => 'GET',
            'callback' => array( __CLASS__, 'endpoint_coches_nuevos' ),
            'permission_callback' => '__return_true',
            'args' => $args_max,
        ) );

        register_rest_route( self::NAMESPACE_API, '/coches/ocasion', array(
            'methods'  => 'GET',
            'callback' => array( __CLASS__, 'endpoint_coches_ocasion' ),
            'permission_callback' => '__return_true',
            'args' => array_merge( $args_max, array(
                'tipo' => array(
                    'description' => 'Filtrar por tipo: ocasion, km0, todos.',
                    'type'        => 'string',
                    'default'     => 'todos',
                ),
            ) ),
        ) );

        register_rest_route( self::NAMESPACE_API, '/coches/todos', array(
            'methods'  => 'GET',
            'callback' => array( __CLASS__, 'endpoint_coches_todos' ),
            'permission_callback' => '__return_true',
            'args' => $args_max,
        ) );

        register_rest_route( self::NAMESPACE_API, '/coches/(?P<id>\d+)', array(
            'methods'  => 'GET',
            'callback' => array( __CLASS__, 'endpoint_coche_individual' ),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'validate_callback' => function( $v ) { return is_numeric( $v ); },
                ),
            ),
        ) );

        register_rest_route( self::NAMESPACE_API, '/modelos', array(
            'methods'  => 'GET',
            'callback' => array( __CLASS__, 'endpoint_modelos' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( self::NAMESPACE_API, '/marcas', array(
            'methods'  => 'GET',
            'callback' => array( __CLASS__, 'endpoint_marcas' ),
            'permission_callback' => '__return_true',
        ) );

        register_rest_route( self::NAMESPACE_API, '/info', array(
            'methods'  => 'GET',
            'callback' => array( __CLASS__, 'endpoint_info' ),
            'permission_callback' => '__return_true',
        ) );
    }

    /* ========================================================================
       ENDPOINTS
       ======================================================================== */

    public static function endpoint_coches_nuevos( $request ) {
        $coches = Welow_Helpers::get_coches_nuevos( array(
            'max'    => intval( $request['max'] ),
            'estado' => $request['estado'],
        ) );
        return self::respuesta_listado( $coches, 'coche_nuevo' );
    }

    public static function endpoint_coches_ocasion( $request ) {
        $coches = Welow_Helpers::get_coches_ocasion( array(
            'max'        => intval( $request['max'] ),
            'tipo_venta' => $request['tipo'] ?: 'todos',
            'estado'     => $request['estado'],
        ) );
        return self::respuesta_listado( $coches, 'coche_ocasion' );
    }

    public static function endpoint_coches_todos( $request ) {
        $coches = Welow_Helpers::get_coches( array(
            'max'    => intval( $request['max'] ),
            'estado' => $request['estado'],
        ) );
        return self::respuesta_listado( $coches, 'coche' );
    }

    public static function endpoint_coche_individual( $request ) {
        $id = intval( $request['id'] );
        $data = Welow_Helpers::get_coche_completo_data( $id );
        if ( ! $data ) {
            return new WP_Error( 'not_found', 'Coche no encontrado', array( 'status' => 404 ) );
        }
        return rest_ensure_response( $data );
    }

    public static function endpoint_modelos( $request ) {
        $modelos = get_posts( array(
            'post_type'      => 'welow_modelo',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );
        $datos = array_filter( array_map( array( 'Welow_Helpers', 'get_modelo_completo_data' ),
            wp_list_pluck( $modelos, 'ID' ) ) );
        return rest_ensure_response( array(
            'total'    => count( $datos ),
            'modelos'  => array_values( $datos ),
            'generado' => current_time( 'c' ),
        ) );
    }

    public static function endpoint_marcas( $request ) {
        $marcas = Welow_Helpers::get_marcas( array( 'max' => -1 ) );
        $datos  = array_filter( array_map( array( 'Welow_Helpers', 'get_marca_completo_data' ),
            wp_list_pluck( $marcas, 'ID' ) ) );
        return rest_ensure_response( array(
            'total'    => count( $datos ),
            'marcas'   => array_values( $datos ),
            'generado' => current_time( 'c' ),
        ) );
    }

    public static function endpoint_info( $request ) {
        return rest_ensure_response( array(
            'sitio'       => array(
                'nombre' => get_bloginfo( 'name' ),
                'descripcion' => get_bloginfo( 'description' ),
                'url'    => home_url(),
                'idioma' => get_bloginfo( 'language' ),
            ),
            'plugin'      => array(
                'nombre'  => 'Welow Concesionarios',
                'version' => WELOW_CONC_VERSION,
            ),
            'estadisticas' => array(
                'coches_nuevos'    => intval( wp_count_posts( 'welow_coche_nuevo' )->publish ?? 0 ),
                'coches_ocasion'   => intval( wp_count_posts( 'welow_coche_ocasion' )->publish ?? 0 ),
                'marcas_oficiales' => intval( wp_count_posts( 'welow_marca' )->publish ?? 0 ),
                'modelos'          => intval( wp_count_posts( 'welow_modelo' )->publish ?? 0 ),
                'concesionarios'   => intval( wp_count_posts( 'welow_concesionario' )->publish ?? 0 ),
            ),
            'endpoints'  => array(
                'coches_nuevos'    => rest_url( self::NAMESPACE_API . '/coches/nuevos' ),
                'coches_ocasion'   => rest_url( self::NAMESPACE_API . '/coches/ocasion' ),
                'coches_todos'     => rest_url( self::NAMESPACE_API . '/coches/todos' ),
                'coche_individual' => rest_url( self::NAMESPACE_API . '/coches/{id}' ),
                'modelos'          => rest_url( self::NAMESPACE_API . '/modelos' ),
                'marcas'           => rest_url( self::NAMESPACE_API . '/marcas' ),
            ),
            'generado'   => current_time( 'c' ),
        ) );
    }

    /* ========================================================================
       HELPERS
       ======================================================================== */

    private static function respuesta_listado( $coches, $tipo_label ) {
        $datos = array_filter( array_map( array( 'Welow_Helpers', 'get_coche_completo_data' ),
            wp_list_pluck( $coches, 'ID' ) ) );

        return rest_ensure_response( array(
            'total'    => count( $datos ),
            'tipo'     => $tipo_label,
            'coches'   => array_values( $datos ),
            'generado' => current_time( 'c' ),
        ) );
    }
}
