<?php
/**
 * Shortcodes de marcas:
 * - [welow_marcas]       → Grid compacto de logos
 * - [welow_marcas_cards] → Tarjetas con información
 *
 * @package Welow_Concesionarios
 * @since 1.0.0
 * @version 1.2.0 — Eliminados parámetros `tipo` y `mostrar_categorias`
 *                   (la clasificación se gestiona ahora a nivel modelo).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Marcas {

    public static function init() {
        add_shortcode( 'welow_marcas', array( __CLASS__, 'render_grid_logos' ) );
        add_shortcode( 'welow_marcas_cards', array( __CLASS__, 'render_grid_cards' ) );
    }

    /**
     * Parámetros comunes para ambos shortcodes.
     */
    private static function parse_atts_comunes( $atts ) {
        return shortcode_atts( array(
            'columnas'        => '4',
            'columnas_tablet' => '3',
            'columnas_movil'  => '2',
            'orden'           => 'personalizado',
            'max'             => '-1',
            'variante_logo'   => 'original', // original | negro | blanco
        ), $atts );
    }

    /**
     * Shortcode [welow_marcas] — Grid compacto de logos.
     */
    public static function render_grid_logos( $atts ) {
        $atts = self::parse_atts_comunes( $atts );

        $marcas = Welow_Helpers::get_marcas( array(
            'orden' => $atts['orden'],
            'max'   => $atts['max'],
        ) );

        if ( empty( $marcas ) ) {
            return '<p class="welow-no-results">No se encontraron marcas.</p>';
        }

        wp_enqueue_style( 'welow-marcas' );

        ob_start();
        Welow_Helpers::get_template( 'marca-grid-logo.php', array(
            'marcas'          => $marcas,
            'columnas'        => intval( $atts['columnas'] ),
            'columnas_tablet' => intval( $atts['columnas_tablet'] ),
            'columnas_movil'  => intval( $atts['columnas_movil'] ),
            'variante_logo'   => sanitize_text_field( $atts['variante_logo'] ),
        ) );
        return ob_get_clean();
    }

    /**
     * Shortcode [welow_marcas_cards] — Tarjetas con info.
     */
    public static function render_grid_cards( $atts ) {
        $atts = shortcode_atts( array(
            'columnas'            => '3',
            'columnas_tablet'     => '2',
            'columnas_movil'      => '1',
            'orden'               => 'personalizado',
            'max'                 => '-1',
            'mostrar_descripcion' => 'si',
            'texto_boton'         => 'Ver marca',
            'variante_logo'       => 'original',
        ), $atts );

        $marcas = Welow_Helpers::get_marcas( array(
            'orden' => $atts['orden'],
            'max'   => $atts['max'],
        ) );

        if ( empty( $marcas ) ) {
            return '<p class="welow-no-results">No se encontraron marcas.</p>';
        }

        wp_enqueue_style( 'welow-marcas' );

        ob_start();
        Welow_Helpers::get_template( 'marca-grid-card.php', array(
            'marcas'              => $marcas,
            'columnas'            => intval( $atts['columnas'] ),
            'columnas_tablet'     => intval( $atts['columnas_tablet'] ),
            'columnas_movil'      => intval( $atts['columnas_movil'] ),
            'mostrar_descripcion' => ( 'si' === $atts['mostrar_descripcion'] ),
            'texto_boton'         => sanitize_text_field( $atts['texto_boton'] ),
            'variante_logo'       => sanitize_text_field( $atts['variante_logo'] ),
        ) );
        return ob_get_clean();
    }
}
