<?php
/**
 * Shortcode: [welow_divi] — Inserta layouts de la Biblioteca Divi.
 *
 * Permite insertar secciones, filas, módulos o layouts completos guardados
 * en la Biblioteca Divi (post type `et_pb_layout`) en cualquier contexto:
 * páginas normales, plantillas del Theme Builder, otros shortcodes, etc.
 *
 * Casos de uso:
 *   [welow_divi id="123"]
 *   [welow_divi slug="hero-marca"]
 *   [welow_divi nombre="Hero Marca"]
 *   [welow_divi id="123" envolver="si"]   ← envuelve en un div con clase
 *
 * @since 1.4.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Divi {

    const DIVI_CPT = 'et_pb_layout';

    public static function init() {
        add_shortcode( 'welow_divi', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'id'       => '',
            'slug'     => '',
            'nombre'   => '',
            'envolver' => 'no',     // si | no  (envuelve en <div class="welow-divi-layout">)
            'clase'    => '',       // clase CSS adicional al wrapper
        ), $atts );

        // Verificar que Divi está activo
        if ( ! post_type_exists( self::DIVI_CPT ) ) {
            return '<!-- [welow_divi]: el CPT et_pb_layout no existe. ¿Está Divi instalado y activo? -->';
        }

        // Resolver el ID del layout
        $layout_id = self::resolver_layout_id( $atts );

        if ( ! $layout_id ) {
            return '<!-- [welow_divi]: layout no encontrado -->';
        }

        // Verificar estado del post
        $post = get_post( $layout_id );
        if ( ! $post || self::DIVI_CPT !== $post->post_type ) {
            return '<!-- [welow_divi]: el ID ' . esc_html( $layout_id ) . ' no es un layout de Divi -->';
        }
        if ( 'publish' !== $post->post_status && ! current_user_can( 'edit_post', $layout_id ) ) {
            return '<!-- [welow_divi]: layout no publicado -->';
        }

        // Obtener el contenido y procesarlo
        $contenido = $post->post_content;

        if ( empty( trim( $contenido ) ) ) {
            return '<!-- [welow_divi]: layout vacío -->';
        }

        // Procesar shortcodes (incluidos los anidados de Divi)
        $contenido_procesado = do_shortcode( $contenido );

        // Aplicar autop si Divi no lo hizo
        if ( false === strpos( $contenido_procesado, 'et_pb_' ) && false === strpos( $contenido_procesado, '<p>' ) ) {
            $contenido_procesado = wpautop( $contenido_procesado );
        }

        // Wrapper opcional
        if ( 'si' === $atts['envolver'] ) {
            $clase = trim( 'welow-divi-layout welow-divi-layout--' . $layout_id . ' ' . sanitize_html_class( $atts['clase'] ) );
            $contenido_procesado = '<div class="' . esc_attr( $clase ) . '" data-layout-id="' . esc_attr( $layout_id ) . '">'
                . $contenido_procesado
                . '</div>';
        }

        return $contenido_procesado;
    }

    /**
     * Resuelve el ID del layout a partir de id/slug/nombre.
     */
    private static function resolver_layout_id( $atts ) {
        // Por ID directo
        if ( ! empty( $atts['id'] ) ) {
            return absint( $atts['id'] );
        }

        // Por slug
        if ( ! empty( $atts['slug'] ) ) {
            $post = get_page_by_path( sanitize_title( $atts['slug'] ), OBJECT, self::DIVI_CPT );
            if ( $post ) {
                return $post->ID;
            }

            // Fallback: buscar como nombre (post_name) sin restricción de tipo
            $posts = get_posts( array(
                'post_type'      => self::DIVI_CPT,
                'name'           => sanitize_title( $atts['slug'] ),
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ) );
            return ! empty( $posts ) ? $posts[0] : 0;
        }

        // Por título
        if ( ! empty( $atts['nombre'] ) ) {
            $posts = get_posts( array(
                'post_type'      => self::DIVI_CPT,
                'title'          => $atts['nombre'],
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ) );
            if ( ! empty( $posts ) ) {
                return $posts[0];
            }

            // Fallback con get_page_by_title (deprecated en 6.2+ pero aún funciona)
            if ( function_exists( 'get_page_by_title' ) ) {
                $post = get_page_by_title( $atts['nombre'], OBJECT, self::DIVI_CPT );
                if ( $post ) {
                    return $post->ID;
                }
            }
        }

        return 0;
    }

    /**
     * Lista todos los layouts disponibles en la Biblioteca Divi.
     *
     * @param string $tipo Filtro opcional: 'layout', 'section', 'row', 'module' o '' para todos.
     * @return WP_Post[]
     */
    public static function listar_layouts( $tipo = '' ) {
        if ( ! post_type_exists( self::DIVI_CPT ) ) {
            return array();
        }

        $args = array(
            'post_type'      => self::DIVI_CPT,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        if ( $tipo ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'layout_type',
                    'field'    => 'slug',
                    'terms'    => $tipo,
                ),
            );
        }

        return get_posts( $args );
    }
}
