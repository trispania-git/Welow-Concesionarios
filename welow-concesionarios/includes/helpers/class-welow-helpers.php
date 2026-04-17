<?php
/**
 * Funciones auxiliares reutilizables.
 *
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Helpers {

    /**
     * Obtiene marcas filtradas y ordenadas.
     *
     * @param array $args Argumentos de filtro.
     * @return WP_Post[] Array de posts de tipo marca.
     */
    public static function get_marcas( $args = array() ) {
        $defaults = array(
            'tipo'  => 'todos',
            'orden' => 'personalizado',
            'max'   => -1,
        );
        $args = wp_parse_args( $args, $defaults );

        $query_args = array(
            'post_type'      => Welow_CPT_Marca::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => intval( $args['max'] ),
            'meta_query'     => array(
                // Solo marcas activas
                array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_welow_marca_activa',
                        'value'   => '1',
                        'compare' => '=',
                    ),
                    array(
                        'key'     => '_welow_marca_activa',
                        'compare' => 'NOT EXISTS',
                    ),
                ),
            ),
        );

        // Filtrar por tipo de venta
        if ( 'todos' !== $args['tipo'] ) {
            $query_args['meta_query'][] = array(
                'key'     => '_welow_marca_tipo_venta',
                'value'   => $args['tipo'],
                'compare' => 'LIKE',
            );
        }

        // Ordenación
        if ( 'nombre' === $args['orden'] ) {
            $query_args['orderby'] = 'title';
            $query_args['order']   = 'ASC';
        } else {
            $query_args['meta_key'] = '_welow_marca_orden';
            $query_args['orderby']  = array(
                'meta_value_num' => 'ASC',
                'title'          => 'ASC',
            );
        }

        return get_posts( $query_args );
    }

    /**
     * Obtiene un meta field de una marca con prefijo automático.
     *
     * @param int    $post_id ID del post.
     * @param string $key     Nombre del campo (sin prefijo).
     * @param mixed  $default Valor por defecto.
     * @return mixed
     */
    public static function get_marca_meta( $post_id, $key, $default = '' ) {
        $value = get_post_meta( $post_id, '_welow_marca_' . $key, true );
        return ( '' !== $value && false !== $value ) ? $value : $default;
    }

    /**
     * Obtiene las etiquetas legibles de los tipos de venta de una marca.
     *
     * @param int $post_id ID del post.
     * @return string[] Array de labels.
     */
    public static function get_tipos_venta_labels( $post_id ) {
        $tipos  = self::get_marca_meta( $post_id, 'tipo_venta', array() );
        $todos  = Welow_CPT_Marca::get_tipos_venta();
        $labels = array();

        if ( is_array( $tipos ) ) {
            foreach ( $tipos as $tipo ) {
                if ( isset( $todos[ $tipo ] ) ) {
                    $labels[] = $todos[ $tipo ];
                }
            }
        }
        return $labels;
    }

    /**
     * Obtiene las etiquetas legibles de las categorías de una marca.
     *
     * @param int $post_id ID del post.
     * @return string[] Array de labels.
     */
    public static function get_categorias_labels( $post_id ) {
        $cats   = self::get_marca_meta( $post_id, 'categorias', array() );
        $todos  = Welow_CPT_Marca::get_categorias_disponibles();
        $labels = array();

        if ( is_array( $cats ) ) {
            foreach ( $cats as $cat ) {
                if ( isset( $todos[ $cat ] ) ) {
                    $labels[] = $todos[ $cat ];
                }
            }
        }
        return $labels;
    }

    // =========================================================================
    // SLIDES
    // =========================================================================

    /**
     * Obtiene los slides de un grupo, ordenados y activos.
     *
     * @param string $grupo Identificador del grupo.
     * @return WP_Post[] Array de slides.
     */
    public static function get_slides( $grupo ) {
        return get_posts( array(
            'post_type'      => Welow_CPT_Slide::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => '_welow_slide_grupo',
                    'value' => sanitize_title( $grupo ),
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key'   => '_welow_slide_activo',
                        'value' => '1',
                    ),
                    array(
                        'key'     => '_welow_slide_activo',
                        'compare' => 'NOT EXISTS',
                    ),
                ),
            ),
            'meta_key' => '_welow_slide_orden',
            'orderby'  => 'meta_value_num',
            'order'    => 'ASC',
        ) );
    }

    // =========================================================================
    // MODELOS
    // =========================================================================

    /**
     * Obtiene modelos de una marca (por slug o ID), ordenados y activos.
     *
     * @param string|int $marca Slug o ID de la marca.
     * @param int        $max   Máximo de resultados (-1 = todos).
     * @return WP_Post[] Array de modelos.
     */
    public static function get_modelos( $marca, $max = -1 ) {
        // Resolver ID de la marca si se pasa un slug
        $marca_id = self::resolver_marca_id( $marca );

        if ( ! $marca_id ) {
            return array();
        }

        return get_posts( array(
            'post_type'      => Welow_CPT_Modelo::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => $max,
            'meta_query'     => array(
                array(
                    'key'   => '_welow_modelo_marca',
                    'value' => $marca_id,
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key'   => '_welow_modelo_activo',
                        'value' => '1',
                    ),
                    array(
                        'key'     => '_welow_modelo_activo',
                        'compare' => 'NOT EXISTS',
                    ),
                ),
            ),
            'meta_key' => '_welow_modelo_orden',
            'orderby'  => 'meta_value_num',
            'order'    => 'ASC',
        ) );
    }

    /**
     * Obtiene un meta field de un modelo con prefijo automático.
     *
     * @param int    $post_id ID del post.
     * @param string $key     Nombre del campo (sin prefijo).
     * @param mixed  $default Valor por defecto.
     * @return mixed
     */
    public static function get_modelo_meta( $post_id, $key, $default = '' ) {
        $value = get_post_meta( $post_id, '_welow_modelo_' . $key, true );
        return ( '' !== $value && false !== $value ) ? $value : $default;
    }

    /**
     * Resuelve un slug o ID de marca a su ID numérico.
     *
     * @param string|int $marca Slug o ID.
     * @return int|false ID de la marca o false.
     */
    public static function resolver_marca_id( $marca ) {
        if ( is_numeric( $marca ) ) {
            return intval( $marca );
        }

        // Buscar por slug
        $posts = get_posts( array(
            'post_type'      => Welow_CPT_Marca::POST_TYPE,
            'name'           => sanitize_title( $marca ),
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ) );

        return ! empty( $posts ) ? $posts[0] : false;
    }

    // =========================================================================
    // TEMPLATES
    // =========================================================================

    /**
     * Carga un template del plugin permitiendo override desde el tema.
     *
     * Busca primero en: tema/welow-concesionarios/{template}
     * Si no existe, usa: plugin/templates/{template}
     *
     * @param string $template Nombre del archivo template.
     * @param array  $args     Variables a pasar al template.
     */
    public static function get_template( $template, $args = array() ) {
        // Permitir override desde el tema
        $theme_file = locate_template( 'welow-concesionarios/' . $template );

        if ( $theme_file ) {
            $file = $theme_file;
        } else {
            $file = WELOW_CONC_PATH . 'templates/' . $template;
        }

        if ( file_exists( $file ) ) {
            // Extraer variables para el template
            if ( ! empty( $args ) ) {
                extract( $args, EXTR_SKIP );
            }
            include $file;
        }
    }
}
