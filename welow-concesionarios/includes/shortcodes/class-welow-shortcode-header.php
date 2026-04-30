<?php
/**
 * Shortcode: [welow_header] — Cabecera del sitio responsive.
 *
 * Estructura:
 *  - Desktop: 3 zonas: Logo | Menú | Teléfono + Botones CTA
 *  - Móvil:   Logo + Hamburger → menú overlay
 *
 * Configurable desde Concesionarios → Configuraciones → "Cabecera",
 * o por params del shortcode (override de defaults).
 *
 * Uso típico: en Divi Theme Builder, "Build Custom Header" con un único
 * módulo Texto que contenga [welow_header].
 *
 * @since 2.6.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Header {

    public static function init() {
        add_shortcode( 'welow_header', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts ) {
        // Defaults globales
        $defaults_globales = class_exists( 'Welow_Settings' )
            ? Welow_Settings::get_header_defaults()
            : array();

        // Atributos del shortcode (override de defaults)
        $atts = shortcode_atts( array(
            'logo'              => '',          // ID o URL imagen
            'logo_movil'        => '',          // ID o URL imagen móvil
            'logo_altura'       => '',          // px (default 50)
            'logo_url'          => '',          // URL del enlace del logo (default home)
            'menu'              => '',          // ID o slug del menú
            'telefono'          => '',
            'boton_texto'       => '',
            'boton_enlace'      => '',
            'boton2_texto'      => '',
            'boton2_enlace'     => '',
            'color_fondo'       => '',
            'color_texto'       => '',
            'color_boton'       => '',
            'color_boton_texto' => '',
            'sticky'            => '',          // si | no | (vacío = usa default)
            'ancho_max'         => '1280px',    // max-width del contenedor interior
        ), $atts );

        // Combinar: shortcode atts > defaults globales
        $config = array(
            'logo_id'           => self::resolver_imagen_id( $atts['logo'], $defaults_globales['logo_id'] ?? 0 ),
            'logo_movil_id'     => self::resolver_imagen_id( $atts['logo_movil'], $defaults_globales['logo_movil_id'] ?? 0 ),
            'logo_altura'       => intval( $atts['logo_altura'] ?: ( $defaults_globales['logo_altura'] ?? 50 ) ),
            'logo_url'          => $atts['logo_url'] ?: home_url( '/' ),
            'menu_id'           => self::resolver_menu_id( $atts['menu'], $defaults_globales['menu_id'] ?? 0 ),
            'telefono'          => $atts['telefono'] ?: ( $defaults_globales['telefono'] ?? '' ),
            'boton_texto'       => $atts['boton_texto'] ?: ( $defaults_globales['boton_texto'] ?? '' ),
            'boton_enlace'      => $atts['boton_enlace'] ?: ( $defaults_globales['boton_enlace'] ?? '' ),
            'boton2_texto'      => $atts['boton2_texto'] ?: ( $defaults_globales['boton2_texto'] ?? '' ),
            'boton2_enlace'     => $atts['boton2_enlace'] ?: ( $defaults_globales['boton2_enlace'] ?? '' ),
            'color_fondo'       => $atts['color_fondo'] ?: ( $defaults_globales['color_fondo'] ?? '' ),
            'color_texto'       => $atts['color_texto'] ?: ( $defaults_globales['color_texto'] ?? '' ),
            'color_boton'       => $atts['color_boton'] ?: ( $defaults_globales['color_boton'] ?? '' ),
            'color_boton_texto' => $atts['color_boton_texto'] ?: ( $defaults_globales['color_boton_texto'] ?? '' ),
            'sticky'            => self::resolver_bool( $atts['sticky'], ! empty( $defaults_globales['sticky'] ) ),
            'ancho_max'         => sanitize_text_field( $atts['ancho_max'] ),
        );

        wp_enqueue_style( 'welow-header' );
        wp_enqueue_script( 'welow-header' );

        ob_start();
        Welow_Helpers::get_template( 'header.php', array( 'config' => $config ) );
        return ob_get_clean();
    }

    /**
     * Resuelve un valor de imagen (puede ser ID, URL o vacío) a un ID.
     */
    private static function resolver_imagen_id( $valor, $fallback = 0 ) {
        if ( empty( $valor ) ) return intval( $fallback );
        if ( is_numeric( $valor ) ) return intval( $valor );
        // Si es URL, intentar obtener el attachment ID
        $id = attachment_url_to_postid( $valor );
        return $id ?: intval( $fallback );
    }

    /**
     * Resuelve un menú: acepta ID, slug o nombre. Devuelve term_id.
     */
    private static function resolver_menu_id( $valor, $fallback = 0 ) {
        if ( empty( $valor ) ) return intval( $fallback );
        if ( is_numeric( $valor ) ) return intval( $valor );

        // Buscar por slug o nombre
        $menu = wp_get_nav_menu_object( $valor );
        if ( $menu && ! is_wp_error( $menu ) ) {
            return intval( $menu->term_id );
        }
        return intval( $fallback );
    }

    /**
     * Resuelve "si"/"no"/"" a boolean.
     */
    private static function resolver_bool( $valor, $default = false ) {
        if ( '' === $valor || null === $valor ) return $default;
        return in_array( strtolower( $valor ), array( 'si', 'sí', '1', 'true', 'yes' ), true );
    }
}
