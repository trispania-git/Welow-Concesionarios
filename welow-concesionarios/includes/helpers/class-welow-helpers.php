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
    // COCHES (v2.1.0 - separados nuevos / ocasión)
    // =========================================================================

    /**
     * Obtiene coches con filtros. Se puede limitar a un CPT concreto.
     *
     * @since 2.0.0
     * @version 2.1.0 — Soporte de dos CPTs (welow_coche_nuevo, welow_coche_ocasion).
     * @param array $args Argumentos de filtro. Clave 'cpt' acepta:
     *               'welow_coche_nuevo', 'welow_coche_ocasion' o array de ambos.
     * @return WP_Post[]
     */
    public static function get_coches( $args = array() ) {
        $defaults = array(
            'cpt'             => array( 'welow_coche_nuevo', 'welow_coche_ocasion' ),
            'marca'           => '',
            'modelo'          => '',
            'marca_externa'   => '',
            'tipo_venta'      => 'todos',
            'combustible'     => '',
            'carroceria'      => '',
            'concesionario'   => '',
            'precio_min'      => '',
            'precio_max'      => '',
            'km_max'          => '',
            'anio_min'        => '',
            'estado'          => 'disponible',
            'orden'           => 'recientes',
            'max'             => -1,
            'paged'           => 1,
        );
        $args = wp_parse_args( $args, $defaults );

        $query_args = array(
            'post_type'      => $args['cpt'],
            'post_status'    => 'publish',
            'posts_per_page' => intval( $args['max'] ),
            'paged'          => max( 1, intval( $args['paged'] ) ),
            'meta_query'     => array( 'relation' => 'AND' ),
            'tax_query'      => array( 'relation' => 'AND' ),
        );

        if ( $args['estado'] && 'todos' !== $args['estado'] ) {
            $query_args['meta_query'][] = array(
                'key' => '_welow_coche_estado', 'value' => $args['estado'], 'compare' => '=',
            );
        }

        if ( 'todos' !== $args['tipo_venta'] && $args['tipo_venta'] ) {
            $query_args['meta_query'][] = array(
                'key' => '_welow_coche_tipo_venta', 'value' => $args['tipo_venta'], 'compare' => '=',
            );
        }

        // Modelo (solo aplica a coches nuevos)
        if ( $args['modelo'] ) {
            $modelo_id = is_numeric( $args['modelo'] ) ? intval( $args['modelo'] )
                : self::resolver_post_id_by_slug( $args['modelo'], 'welow_modelo' );
            if ( $modelo_id ) {
                $query_args['meta_query'][] = array(
                    'key' => '_welow_coche_modelo', 'value' => $modelo_id, 'compare' => '=',
                );
            }
        }

        // Marca oficial (catálogo) → encuentra modelos y filtra (solo nuevos)
        if ( $args['marca'] ) {
            $marca_id = self::resolver_marca_id( $args['marca'] );
            if ( $marca_id ) {
                $modelos_de_marca = get_posts( array(
                    'post_type'      => 'welow_modelo',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                    'meta_query'     => array(
                        array( 'key' => '_welow_modelo_marca', 'value' => $marca_id ),
                    ),
                ) );
                if ( ! empty( $modelos_de_marca ) ) {
                    $query_args['meta_query'][] = array(
                        'key' => '_welow_coche_modelo', 'value' => $modelos_de_marca, 'compare' => 'IN',
                    );
                } else {
                    return array();
                }
            }
        }

        // Marca externa (taxonomía, solo aplica a ocasión)
        if ( $args['marca_externa'] ) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'welow_marca_externa', 'field' => 'slug', 'terms' => $args['marca_externa'],
            );
        }

        if ( $args['concesionario'] ) {
            $conc_id = is_numeric( $args['concesionario'] ) ? intval( $args['concesionario'] )
                : self::resolver_post_id_by_slug( $args['concesionario'], 'welow_concesionario' );
            if ( $conc_id ) {
                $query_args['meta_query'][] = array(
                    'key' => '_welow_coche_concesionario', 'value' => $conc_id, 'compare' => '=',
                );
            }
        }

        if ( $args['precio_min'] !== '' && $args['precio_min'] > 0 ) {
            $query_args['meta_query'][] = array(
                'key' => '_welow_coche_precio_contado', 'value' => floatval( $args['precio_min'] ),
                'compare' => '>=', 'type' => 'NUMERIC',
            );
        }
        if ( $args['precio_max'] !== '' && $args['precio_max'] > 0 ) {
            $query_args['meta_query'][] = array(
                'key' => '_welow_coche_precio_contado', 'value' => floatval( $args['precio_max'] ),
                'compare' => '<=', 'type' => 'NUMERIC',
            );
        }
        if ( $args['km_max'] !== '' && $args['km_max'] > 0 ) {
            $query_args['meta_query'][] = array(
                'key' => '_welow_coche_km', 'value' => intval( $args['km_max'] ),
                'compare' => '<=', 'type' => 'NUMERIC',
            );
        }
        if ( $args['anio_min'] !== '' && $args['anio_min'] > 0 ) {
            $query_args['meta_query'][] = array(
                'key' => '_welow_coche_anio_matricula', 'value' => intval( $args['anio_min'] ),
                'compare' => '>=', 'type' => 'NUMERIC',
            );
        }

        if ( $args['combustible'] ) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'welow_combustible', 'field' => 'slug', 'terms' => $args['combustible'],
            );
        }
        if ( $args['carroceria'] ) {
            $query_args['tax_query'][] = array(
                'taxonomy' => 'welow_categoria_modelo', 'field' => 'slug', 'terms' => $args['carroceria'],
            );
        }

        switch ( $args['orden'] ) {
            case 'precio_asc':
                $query_args['meta_key'] = '_welow_coche_precio_contado';
                $query_args['orderby']  = 'meta_value_num';
                $query_args['order']    = 'ASC';
                break;
            case 'precio_desc':
                $query_args['meta_key'] = '_welow_coche_precio_contado';
                $query_args['orderby']  = 'meta_value_num';
                $query_args['order']    = 'DESC';
                break;
            case 'km_asc':
                $query_args['meta_key'] = '_welow_coche_km';
                $query_args['orderby']  = 'meta_value_num';
                $query_args['order']    = 'ASC';
                break;
            case 'anio_desc':
                $query_args['meta_key'] = '_welow_coche_anio_matricula';
                $query_args['orderby']  = 'meta_value_num';
                $query_args['order']    = 'DESC';
                break;
            case 'recientes':
            default:
                $query_args['orderby'] = 'date';
                $query_args['order']   = 'DESC';
        }

        return get_posts( $query_args );
    }

    /**
     * Atajo: solo coches nuevos.
     * @since 2.1.0
     */
    public static function get_coches_nuevos( $args = array() ) {
        $args['cpt'] = 'welow_coche_nuevo';
        return self::get_coches( $args );
    }

    /**
     * Atajo: solo coches de ocasión / KM0.
     * @since 2.1.0
     */
    public static function get_coches_ocasion( $args = array() ) {
        $args['cpt'] = 'welow_coche_ocasion';
        return self::get_coches( $args );
    }

    /**
     * Devuelve true si el coche es nuevo, false si es de ocasión, null si no es coche.
     */
    public static function es_coche_nuevo( $coche_id ) {
        $type = get_post_type( $coche_id );
        if ( 'welow_coche_nuevo' === $type ) return true;
        if ( 'welow_coche_ocasion' === $type ) return false;
        return null;
    }

    /**
     * @since 2.0.0
     */
    public static function get_coche_meta( $coche_id, $key, $default = '' ) {
        $value = get_post_meta( $coche_id, '_welow_coche_' . $key, true );
        return ( '' !== $value && false !== $value ) ? $value : $default;
    }

    /**
     * Devuelve el WP_Post del modelo asociado (solo coches nuevos).
     */
    public static function get_coche_modelo( $coche_id ) {
        if ( ! self::es_coche_nuevo( $coche_id ) ) return null;
        $modelo_id = self::get_coche_meta( $coche_id, 'modelo' );
        return $modelo_id ? get_post( $modelo_id ) : null;
    }

    /**
     * Devuelve el WP_Post de la marca asociada (vía modelo, solo coches nuevos).
     */
    public static function get_coche_marca( $coche_id ) {
        $modelo = self::get_coche_modelo( $coche_id );
        if ( ! $modelo ) return null;
        $marca_id = get_post_meta( $modelo->ID, '_welow_modelo_marca', true );
        return $marca_id ? get_post( $marca_id ) : null;
    }

    /**
     * Devuelve el nombre legible de la marca según el CPT del coche.
     *
     * @since 2.1.0
     * @return string
     */
    public static function get_coche_marca_nombre( $coche_id ) {
        $es_nuevo = self::es_coche_nuevo( $coche_id );
        if ( null === $es_nuevo ) return '';

        if ( $es_nuevo ) {
            $marca = self::get_coche_marca( $coche_id );
            return $marca ? $marca->post_title : '';
        }

        // Ocasión: marca desde taxonomía welow_marca_externa
        $terms = wp_get_post_terms( $coche_id, 'welow_marca_externa' );
        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            return $terms[0]->name;
        }
        return '';
    }

    /**
     * Devuelve el nombre legible del modelo según el CPT del coche.
     *
     * @since 2.1.0
     * @return string
     */
    public static function get_coche_modelo_nombre( $coche_id ) {
        $es_nuevo = self::es_coche_nuevo( $coche_id );
        if ( null === $es_nuevo ) return '';

        if ( $es_nuevo ) {
            $modelo = self::get_coche_modelo( $coche_id );
            return $modelo ? $modelo->post_title : '';
        }

        // Ocasión: texto libre
        return self::get_coche_meta( $coche_id, 'modelo_texto', '' );
    }

    /**
     * Devuelve los IDs de imágenes de la galería del coche.
     */
    public static function get_coche_galeria( $coche_id ) {
        $ids = self::get_coche_meta( $coche_id, 'galeria', array() );
        return is_array( $ids ) ? $ids : array();
    }

    /**
     * Devuelve el ID de la imagen principal del coche.
     *
     * Prioridad:
     *  1. Imagen destacada (post thumbnail)
     *  2. Primera imagen de la galería (fallback)
     *  3. 0 si no hay ninguna
     *
     * @since 2.3.3
     * @param int $coche_id
     * @return int Attachment ID o 0.
     */
    public static function get_coche_imagen_principal_id( $coche_id ) {
        $thumb_id = get_post_thumbnail_id( $coche_id );
        if ( $thumb_id ) return intval( $thumb_id );

        $galeria = self::get_coche_galeria( $coche_id );
        if ( ! empty( $galeria ) ) {
            foreach ( $galeria as $id ) {
                $id = intval( $id );
                if ( $id ) return $id;
            }
        }
        return 0;
    }

    /**
     * Devuelve la URL de la imagen principal del coche con fallback a galería.
     *
     * @since 2.3.3
     * @param int    $coche_id
     * @param string $size 'thumbnail', 'medium', 'large', 'full' o array.
     * @return string URL o ''.
     */
    public static function get_coche_imagen_principal_url( $coche_id, $size = 'large' ) {
        $id = self::get_coche_imagen_principal_id( $coche_id );
        return $id ? wp_get_attachment_image_url( $id, $size ) : '';
    }

    /**
     * Disclaimer efectivo del coche (override o global).
     */
    public static function get_coche_disclaimer( $coche_id ) {
        $override = self::get_coche_meta( $coche_id, 'disclaimer' );
        if ( ! empty( $override ) ) return $override;

        if ( class_exists( 'Welow_Settings' ) ) {
            return Welow_Settings::get( 'disclaimer_global', '' );
        }
        return '';
    }

    /**
     * Datos completos para la ficha del coche. Detecta el CPT automáticamente.
     *
     * @since 2.0.0
     * @version 2.1.0 — Soporte para ambos CPTs.
     */
    public static function get_coche_ficha_data( $coche_id ) {
        $coche = get_post( $coche_id );
        if ( ! $coche ) return null;

        $es_nuevo = self::es_coche_nuevo( $coche_id );
        if ( null === $es_nuevo ) return null;

        // Marca y modelo (texto legible) según CPT
        $marca_nombre  = self::get_coche_marca_nombre( $coche_id );
        $modelo_nombre = self::get_coche_modelo_nombre( $coche_id );

        // Para nuevos: WP_Posts de marca y modelo (si existen). Para ocasión: null.
        $modelo = $es_nuevo ? self::get_coche_modelo( $coche_id ) : null;
        $marca  = $es_nuevo ? self::get_coche_marca( $coche_id ) : null;

        $combustibles = wp_get_post_terms( $coche_id, 'welow_combustible' );
        $carrocerias  = wp_get_post_terms( $coche_id, 'welow_categoria_modelo' );

        // Si nuevo y no tiene combustible/carrocería propios, hereda del modelo del catálogo
        if ( $es_nuevo && $modelo ) {
            if ( empty( $combustibles ) && ! is_wp_error( $combustibles ) ) {
                $combustibles = wp_get_post_terms( $modelo->ID, 'welow_combustible' );
            }
            if ( empty( $carrocerias ) && ! is_wp_error( $carrocerias ) ) {
                $carrocerias = wp_get_post_terms( $modelo->ID, 'welow_categoria_modelo' );
            }
        }

        // Plazas: si nuevo y no tiene, hereda del modelo
        $plazas = self::get_coche_meta( $coche_id, 'plazas' );
        if ( '' === $plazas && $es_nuevo && $modelo ) {
            $plazas = get_post_meta( $modelo->ID, '_welow_modelo_plazas', true );
        }

        return array(
            'id'               => $coche_id,
            'post'             => $coche,
            'es_nuevo'         => $es_nuevo,
            'cpt'              => $coche->post_type,
            'modelo'           => $modelo,                // WP_Post o null
            'marca'            => $marca,                 // WP_Post o null
            'marca_nombre'     => $marca_nombre,
            'modelo_nombre'    => $modelo_nombre,
            'combustibles'     => is_wp_error( $combustibles ) ? array() : $combustibles,
            'carrocerias'      => is_wp_error( $carrocerias ) ? array() : $carrocerias,
            'plazas'           => $plazas,
            'galeria'          => self::get_coche_galeria( $coche_id ),
            'disclaimer'       => self::get_coche_disclaimer( $coche_id ),
            'concesionario_id' => self::get_coche_meta( $coche_id, 'concesionario' ),
        );
    }

    // =========================================================================
    // CONCESIONARIOS (v2.0.0)
    // =========================================================================

    public static function get_concesionario_meta( $id, $key, $default = '' ) {
        $value = get_post_meta( $id, '_welow_conc_' . $key, true );
        return ( '' !== $value && false !== $value ) ? $value : $default;
    }

    public static function get_concesionario_data( $id ) {
        $post = get_post( $id );
        if ( ! $post || 'welow_concesionario' !== $post->post_type ) return null;

        return array(
            'id'        => $id,
            'nombre'    => $post->post_title,
            'logo'      => get_the_post_thumbnail_url( $id, 'medium' ),
            'direccion' => self::get_concesionario_meta( $id, 'direccion' ),
            'cp'        => self::get_concesionario_meta( $id, 'cp' ),
            'ciudad'    => self::get_concesionario_meta( $id, 'ciudad' ),
            'provincia' => self::get_concesionario_meta( $id, 'provincia' ),
            'telefono'  => self::get_concesionario_meta( $id, 'telefono' ),
            'email'     => self::get_concesionario_meta( $id, 'email' ),
            'horario'   => self::get_concesionario_meta( $id, 'horario' ),
            'lat'       => self::get_concesionario_meta( $id, 'lat' ),
            'lng'       => self::get_concesionario_meta( $id, 'lng' ),
        );
    }

    public static function get_concesionarios_activos() {
        return get_posts( array(
            'post_type'      => 'welow_concesionario',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'relation' => 'OR',
                    array( 'key' => '_welow_conc_activo', 'value' => '1' ),
                    array( 'key' => '_welow_conc_activo', 'compare' => 'NOT EXISTS' ),
                ),
            ),
            'meta_key' => '_welow_conc_orden',
            'orderby'  => array( 'meta_value_num' => 'ASC', 'title' => 'ASC' ),
        ) );
    }

    /**
     * Resuelve un slug a ID para cualquier post type.
     */
    public static function resolver_post_id_by_slug( $slug, $post_type ) {
        $posts = get_posts( array(
            'post_type'      => $post_type,
            'name'           => sanitize_title( $slug ),
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ) );
        return ! empty( $posts ) ? $posts[0] : 0;
    }

    /**
     * Detecta el coche actual del contexto (single de welow_coche_nuevo o _ocasion).
     *
     * @since 2.0.0
     * @version 2.1.0 — Detecta los dos CPTs.
     */
    public static function get_current_coche_id() {
        $forced = apply_filters( 'welow_current_coche_id', null );
        if ( $forced ) return intval( $forced );

        $post_id = get_the_ID();
        if ( ! $post_id ) {
            $obj = get_queried_object();
            if ( $obj instanceof WP_Post ) $post_id = $obj->ID;
        }
        if ( ! $post_id ) return false;

        $type = get_post_type( $post_id );
        return ( in_array( $type, array( 'welow_coche_nuevo', 'welow_coche_ocasion' ), true ) ) ? $post_id : false;
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
