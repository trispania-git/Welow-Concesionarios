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
     * Obtiene marcas activas, ordenadas.
     *
     * @since 1.0.0
     * @version 1.2.0 — Eliminado filtro por `tipo` (la clasificación se gestiona a nivel modelo).
     *
     * @param array $args Argumentos de filtro.
     * @return WP_Post[] Array de posts de tipo marca.
     */
    public static function get_marcas( $args = array() ) {
        $defaults = array(
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
     * Detecta la marca actual del contexto.
     *
     * Devuelve el ID si:
     * 1. Estamos en un single de welow_marca → la marca actual
     * 2. Estamos en un single de welow_modelo → la marca asociada al modelo
     * 3. Estamos en un archive/taxonomía relacionada con marca (futuro)
     *
     * Útil para shortcodes con `marca="auto"` o sin parámetro marca.
     *
     * @since 1.3.0
     * @return int|false ID de la marca actual, o false si no se detecta.
     */
    public static function get_current_marca_id() {
        // Permitir override por filtro (útil para Theme Builder de Divi)
        $forced = apply_filters( 'welow_current_marca_id', null );
        if ( $forced ) {
            return intval( $forced );
        }

        // Detectar desde el contexto del loop
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            // Fallback: intentar con queried_object
            $obj = get_queried_object();
            if ( $obj instanceof WP_Post ) {
                $post_id = $obj->ID;
            }
        }

        if ( ! $post_id ) {
            return false;
        }

        $post_type = get_post_type( $post_id );

        // Si estamos en una marca, esa es la actual
        if ( Welow_CPT_Marca::POST_TYPE === $post_type ) {
            return $post_id;
        }

        // Si estamos en un modelo, devolver su marca asociada
        if ( Welow_CPT_Modelo::POST_TYPE === $post_type ) {
            $marca_id = get_post_meta( $post_id, '_welow_modelo_marca', true );
            return $marca_id ? intval( $marca_id ) : false;
        }

        return false;
    }

    /**
     * Obtiene el slug de la marca actual del contexto.
     *
     * @since 1.3.0
     * @return string|false Slug o false.
     */
    public static function get_current_marca_slug() {
        $id = self::get_current_marca_id();
        return $id ? get_post_field( 'post_name', $id ) : false;
    }

    /**
     * Obtiene la URL del logo de una marca según la variante.
     *
     * @since 1.1.0
     * @param int    $marca_id ID de la marca.
     * @param string $variante original | negro | blanco.
     * @param string $size     Tamaño de imagen.
     * @return string URL del logo (o de la imagen destacada como fallback).
     */
    public static function get_logo_url( $marca_id, $variante = 'original', $size = 'medium' ) {
        if ( 'original' === $variante ) {
            return get_the_post_thumbnail_url( $marca_id, $size );
        }

        $meta_key = 'negro' === $variante ? '_welow_marca_logo_negro' : '_welow_marca_logo_blanco';
        $logo_id  = get_post_meta( $marca_id, $meta_key, true );

        if ( $logo_id ) {
            return wp_get_attachment_image_url( $logo_id, $size );
        }

        // Fallback: logo original
        return get_the_post_thumbnail_url( $marca_id, $size );
    }

    /**
     * Obtiene los banners de una marca.
     *
     * @since 1.1.0
     * @param int    $marca_id
     * @param string $tipo portada | media
     * @return array{desktop: string, movil: string}
     */
    public static function get_marca_banners( $marca_id, $tipo = 'portada' ) {
        $id_desktop = get_post_meta( $marca_id, '_welow_marca_banner_' . $tipo . '_desktop', true );
        $id_movil   = get_post_meta( $marca_id, '_welow_marca_banner_' . $tipo . '_movil', true );

        return array(
            'desktop' => $id_desktop ? wp_get_attachment_image_url( $id_desktop, 'full' ) : '',
            'movil'   => $id_movil ? wp_get_attachment_image_url( $id_movil, 'large' ) : '',
        );
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

    /**
     * Obtiene las etiquetas asignadas a un modelo (objetos WP_Post).
     *
     * @since 1.1.0
     * @param int $modelo_id
     * @return WP_Post[] Array de posts de tipo welow_etiqueta.
     */
    public static function get_etiquetas_modelo( $modelo_id ) {
        $ids = get_post_meta( $modelo_id, '_welow_modelo_etiquetas', true );
        if ( ! is_array( $ids ) || empty( $ids ) ) return array();

        $etiquetas = get_posts( array(
            'post_type'      => 'welow_etiqueta',
            'post__in'       => $ids,
            'posts_per_page' => -1,
            'orderby'        => 'post__in',
            'meta_query'     => array(
                array(
                    'relation' => 'OR',
                    array( 'key' => '_welow_etiqueta_activa', 'value' => '1' ),
                    array( 'key' => '_welow_etiqueta_activa', 'compare' => 'NOT EXISTS' ),
                ),
            ),
        ) );

        return $etiquetas;
    }

    /**
     * Obtiene el disclaimer efectivo de un modelo (override o global).
     *
     * @since 1.1.0
     * @param int $modelo_id
     * @return string
     */
    public static function get_modelo_disclaimer( $modelo_id ) {
        $override = get_post_meta( $modelo_id, '_welow_modelo_disclaimer', true );
        if ( ! empty( $override ) ) {
            return $override;
        }

        if ( class_exists( 'Welow_Settings' ) ) {
            return Welow_Settings::get( 'disclaimer_global', '' );
        }
        return '';
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
