<?php
/**
 * Shortcode: [welow_marca_banner]
 * Muestra el banner (portada o zona media) de una marca con responsive desktop/móvil.
 *
 * @since 1.1.0
 * @version 1.3.0 — Soporte de auto-detección: si no se pasa `marca` o se pasa "auto",
 *                   se usa la marca del contexto actual (single de marca o modelo).
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Marca_Banner {

    public static function init() {
        add_shortcode( 'welow_marca_banner', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'marca'  => 'auto',      // auto | slug | ID
            'tipo'   => 'portada',   // portada | media
            'enlace' => '',
            'altura' => '',          // opcional: CSS value (ej: 500px)
        ), $atts );

        // v1.3.0 — Auto-detección
        if ( '' === $atts['marca'] || 'auto' === $atts['marca'] ) {
            $marca_id = Welow_Helpers::get_current_marca_id();
            if ( ! $marca_id ) {
                return '<!-- [welow_marca_banner]: no se detectó marca actual -->';
            }
        } else {
            $marca_id = Welow_Helpers::resolver_marca_id( $atts['marca'] );
            if ( ! $marca_id ) {
                return '<p class="welow-no-results">Marca no encontrada: "' . esc_html( $atts['marca'] ) . '".</p>';
            }
        }

        // Validar tipo
        $tipo = in_array( $atts['tipo'], array( 'portada', 'media' ), true ) ? $atts['tipo'] : 'portada';

        // Obtener IDs de imágenes
        $id_desktop = get_post_meta( $marca_id, '_welow_marca_banner_' . $tipo . '_desktop', true );
        $id_movil   = get_post_meta( $marca_id, '_welow_marca_banner_' . $tipo . '_movil', true );

        if ( ! $id_desktop && ! $id_movil ) {
            return '<p class="welow-no-results">No hay banner "' . esc_html( $tipo ) . '" configurado para esta marca.</p>';
        }

        $url_desktop = $id_desktop ? wp_get_attachment_image_url( $id_desktop, 'full' ) : '';
        $url_movil   = $id_movil ? wp_get_attachment_image_url( $id_movil, 'large' ) : $url_desktop;

        // Fallback
        if ( ! $url_desktop ) $url_desktop = $url_movil;

        wp_enqueue_style( 'welow-secciones' );

        $marca_title = get_the_title( $marca_id );

        ob_start();
        Welow_Helpers::get_template( 'marca-banner.php', array(
            'url_desktop'  => $url_desktop,
            'url_movil'    => $url_movil,
            'enlace'       => esc_url( $atts['enlace'] ),
            'altura'       => sanitize_text_field( $atts['altura'] ),
            'tipo'         => $tipo,
            'alt'          => $marca_title,
        ) );
        return ob_get_clean();
    }
}
