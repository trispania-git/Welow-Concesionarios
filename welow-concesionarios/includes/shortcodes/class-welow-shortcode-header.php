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

    /** Para acumular Google Fonts a cargar en el head (puede haber varios shortcodes) */
    private static $google_fonts_to_load = array();

    public static function init() {
        add_shortcode( 'welow_header', array( __CLASS__, 'render' ) );
        add_action( 'wp_head', array( __CLASS__, 'imprimir_google_fonts' ), 5 );
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
            'ancho_max'         => '100%',      // v2.7.1: default sin límite (era 1280px)
            // v2.7.0 — Tipografía
            'font_family'       => '',
            'font_google'       => '',          // si | no
            'font_weight_menu'  => '',
            'font_weight_boton' => '',
            'font_size_menu'    => '',
            'font_size_boton'   => '',
            'font_size_telefono'=> '',
            'text_transform_menu' => '',
            'letter_spacing_menu' => '',
            // v2.9.0 — Logo de la marca actual al lado del logo principal
            'logo_marca'           => '',     // '' | 'auto' | slug de marca
            'logo_marca_variante'  => 'negro', // original | negro | blanco
            'logo_marca_altura'    => '',     // px (default: igual que logo_altura)
            'logo_marca_separador' => 'si',   // si | no — línea vertical separadora
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
            // v2.7.0 — Tipografía
            'font_family'        => $atts['font_family'] ?: ( $defaults_globales['font_family'] ?? '' ),
            'font_google'        => self::resolver_bool( $atts['font_google'], ! empty( $defaults_globales['font_google'] ) ),
            'font_weight_menu'   => $atts['font_weight_menu'] ?: ( $defaults_globales['font_weight_menu'] ?? '600' ),
            'font_weight_boton'  => $atts['font_weight_boton'] ?: ( $defaults_globales['font_weight_boton'] ?? '700' ),
            'font_size_menu'     => intval( $atts['font_size_menu'] ?: ( $defaults_globales['font_size_menu'] ?? 14 ) ),
            'font_size_boton'    => intval( $atts['font_size_boton'] ?: ( $defaults_globales['font_size_boton'] ?? 14 ) ),
            'font_size_telefono' => intval( $atts['font_size_telefono'] ?: ( $defaults_globales['font_size_telefono'] ?? 14 ) ),
            'text_transform_menu' => $atts['text_transform_menu'] ?: ( $defaults_globales['text_transform_menu'] ?? 'none' ),
            'letter_spacing_menu' => $atts['letter_spacing_menu'] ?: ( $defaults_globales['letter_spacing_menu'] ?? '' ),

            // v2.9.0 — Logo de marca al lado del principal
            'logo_marca'           => $atts['logo_marca'],
            'logo_marca_variante'  => sanitize_text_field( $atts['logo_marca_variante'] ),
            'logo_marca_altura'    => intval( $atts['logo_marca_altura'] ),
            'logo_marca_separador' => self::resolver_bool( $atts['logo_marca_separador'], true ),
        );

        // Resolver logo de la marca según el contexto
        $config['logo_marca_data'] = null;
        if ( ! empty( $config['logo_marca'] ) ) {
            if ( 'auto' === $config['logo_marca'] ) {
                $config['logo_marca_data'] = Welow_Helpers::get_current_marca_logo_data( $config['logo_marca_variante'], 'medium' );
            } else {
                // Slug específico
                $marca_id = Welow_Helpers::resolver_marca_id( $config['logo_marca'] );
                if ( $marca_id ) {
                    $config['logo_marca_data'] = array(
                        'tipo'     => 'oficial',
                        'id'       => $marca_id,
                        'nombre'   => get_the_title( $marca_id ),
                        'url_logo' => Welow_Helpers::get_logo_url( $marca_id, $config['logo_marca_variante'], 'medium' ),
                        'url_link' => get_permalink( $marca_id ),
                    );
                }
            }
        }

        // Si la fuente debe cargarse desde Google Fonts, registrarla
        if ( $config['font_google'] && $config['font_family'] ) {
            self::registrar_google_font(
                $config['font_family'],
                array( $config['font_weight_menu'], $config['font_weight_boton'] )
            );
        }

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

    /**
     * v2.7.0 — Registra una Google Font para imprimirla en wp_head.
     */
    public static function registrar_google_font( $family, $weights = array() ) {
        if ( ! $family ) return;
        $key = sanitize_title( $family );
        if ( ! isset( self::$google_fonts_to_load[ $key ] ) ) {
            self::$google_fonts_to_load[ $key ] = array(
                'family'  => $family,
                'weights' => array(),
            );
        }
        foreach ( (array) $weights as $w ) {
            $w = preg_replace( '/[^0-9]/', '', $w );
            if ( $w ) self::$google_fonts_to_load[ $key ]['weights'][] = $w;
        }
        self::$google_fonts_to_load[ $key ]['weights'] = array_unique( self::$google_fonts_to_load[ $key ]['weights'] );
    }

    /**
     * v2.7.0 — Imprime los <link> de Google Fonts en el head.
     */
    public static function imprimir_google_fonts() {
        if ( empty( self::$google_fonts_to_load ) ) return;

        // Preconnect (mejor performance)
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";

        $families = array();
        foreach ( self::$google_fonts_to_load as $f ) {
            // Reemplazar espacios por +
            $family_url = str_replace( ' ', '+', $f['family'] );
            $weights = ! empty( $f['weights'] ) ? $f['weights'] : array( '400', '600', '700' );
            sort( $weights );
            $families[] = $family_url . ':wght@' . implode( ';', $weights );
        }

        $url = 'https://fonts.googleapis.com/css2?family=' . implode( '&family=', $families ) . '&display=swap';
        echo '<link rel="stylesheet" href="' . esc_url( $url ) . '">' . "\n";
    }
}
