<?php
/**
 * URLs personalizadas para CPTs de coches.
 *
 * Estructura:
 *   - welow_coche_nuevo:   /coches/{marca}/{modelo}/{slug}/
 *   - welow_coche_ocasion: /coches/segunda-mano/{marca}/{modelo}/{slug}/
 *
 * Estrategia: rewrite rules custom + filtro post_type_link para construir
 * el permalink. Si la marca/modelo no se pueden resolver, fallback al
 * slug del CPT por defecto.
 *
 * @since 2.5.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Coche_Permalinks {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'registrar_rewrites' ), 20 );
        add_filter( 'post_type_link', array( __CLASS__, 'filtrar_permalink' ), 10, 2 );

        // Forzar flush de rewrite rules tras cambios de versión
        add_action( 'init', array( __CLASS__, 'maybe_flush_rules' ), 99 );
    }

    /**
     * Registra las rewrite rules personalizadas.
     */
    public static function registrar_rewrites() {
        // Coches NUEVOS: /coches/{marca}/{modelo}/{slug}/
        // Pero con cuidado: 'coches/segunda-mano/...' es subset de 'coches/...'
        // Por eso registramos primero la de ocasión (más específica).

        // Ocasión - debe ir ANTES por especificidad
        add_rewrite_rule(
            '^coches/segunda-mano/([^/]+)/([^/]+)/([^/]+)/?$',
            'index.php?welow_coche_ocasion=$matches[3]',
            'top'
        );

        // Nuevos - DESPUÉS de la regla de ocasión
        // Excluye explícitamente "segunda-mano" como primer segmento
        add_rewrite_rule(
            '^coches/(?!segunda-mano/)([^/]+)/([^/]+)/([^/]+)/?$',
            'index.php?welow_coche_nuevo=$matches[3]',
            'top'
        );
    }

    /**
     * Construye el permalink personalizado para los CPTs.
     */
    public static function filtrar_permalink( $url, $post ) {
        if ( ! $post instanceof WP_Post ) return $url;

        $tipo = $post->post_type;
        if ( ! in_array( $tipo, array( 'welow_coche_nuevo', 'welow_coche_ocasion' ), true ) ) {
            return $url;
        }

        // Resolver slug de marca y modelo según el CPT
        $marca_slug  = self::get_marca_slug( $post->ID, $tipo );
        $modelo_slug = self::get_modelo_slug( $post->ID, $tipo );

        if ( ! $marca_slug || ! $modelo_slug ) {
            return $url; // Fallback al permalink por defecto
        }

        $base = home_url( '/' );

        if ( 'welow_coche_ocasion' === $tipo ) {
            return $base . 'coches/segunda-mano/' . $marca_slug . '/' . $modelo_slug . '/' . $post->post_name . '/';
        }

        if ( 'welow_coche_nuevo' === $tipo ) {
            return $base . 'coches/' . $marca_slug . '/' . $modelo_slug . '/' . $post->post_name . '/';
        }

        return $url;
    }

    /**
     * Devuelve el slug de la marca asociada al coche.
     */
    private static function get_marca_slug( $coche_id, $tipo ) {
        if ( 'welow_coche_nuevo' === $tipo ) {
            // Vía relación con welow_modelo → welow_marca
            $modelo_id = get_post_meta( $coche_id, '_welow_coche_modelo', true );
            if ( ! $modelo_id ) return '';
            $marca_id = get_post_meta( $modelo_id, '_welow_modelo_marca', true );
            if ( ! $marca_id ) return '';
            return get_post_field( 'post_name', $marca_id );
        }

        if ( 'welow_coche_ocasion' === $tipo ) {
            // Vía taxonomía welow_marca_externa
            $terms = wp_get_post_terms( $coche_id, 'welow_marca_externa', array( 'fields' => 'slugs' ) );
            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                return $terms[0];
            }
        }
        return '';
    }

    /**
     * Devuelve el slug del modelo asociado al coche.
     */
    private static function get_modelo_slug( $coche_id, $tipo ) {
        if ( 'welow_coche_nuevo' === $tipo ) {
            $modelo_id = get_post_meta( $coche_id, '_welow_coche_modelo', true );
            return $modelo_id ? get_post_field( 'post_name', $modelo_id ) : '';
        }

        if ( 'welow_coche_ocasion' === $tipo ) {
            // Modelo es texto libre — slugify
            $modelo_texto = get_post_meta( $coche_id, '_welow_coche_modelo_texto', true );
            return $modelo_texto ? sanitize_title( $modelo_texto ) : '';
        }
        return '';
    }

    /**
     * Flush rewrite rules una vez tras instalación/upgrade de v2.5.0.
     */
    public static function maybe_flush_rules() {
        if ( get_option( 'welow_rewrite_rules_v2_5_0' ) ) return;
        flush_rewrite_rules();
        update_option( 'welow_rewrite_rules_v2_5_0', '1' );
    }
}
