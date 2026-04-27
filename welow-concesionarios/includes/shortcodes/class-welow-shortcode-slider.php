<?php
/**
 * Shortcode: [welow_slider] — Slider de imágenes fullwidth.
 * Intercambia imagen desktop/móvil automáticamente.
 *
 * @since 1.0.0
 * @version 1.3.0 — Soporte de `grupo="auto"` o `grupo="{marca}-home"` dinámico:
 *                   en single de marca/modelo, se busca el grupo `{slug-marca}-home`.
 *                   También se admite `grupo="{marca}-{nombre}"` con plantillas.
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Slider {

    private static $instance_count = 0;

    public static function init() {
        add_shortcode( 'welow_slider', array( __CLASS__, 'render' ) );
    }

    /**
     * Resuelve el nombre del grupo a partir del parámetro y el contexto.
     *
     * Casos soportados:
     * - `grupo=""` o `grupo="auto"` → usa `{marca-actual}-{sufijo}` (ej: `toyota-home`)
     * - `grupo="{marca}-home"`      → reemplaza `{marca}` por el slug de marca actual
     * - `grupo="toyota-home"`       → uso literal (sin cambios)
     *
     * @since 1.3.0
     */
    private static function resolver_grupo( $grupo_raw, $sufijo = 'home' ) {
        $grupo_raw = trim( (string) $grupo_raw );

        // Auto: usar marca actual + sufijo
        if ( '' === $grupo_raw || 'auto' === $grupo_raw ) {
            $slug = Welow_Helpers::get_current_marca_slug();
            return $slug ? sanitize_title( $slug . '-' . $sufijo ) : '';
        }

        // Reemplazo de placeholder {marca}
        if ( false !== strpos( $grupo_raw, '{marca}' ) ) {
            $slug = Welow_Helpers::get_current_marca_slug();
            if ( ! $slug ) return '';
            return sanitize_title( str_replace( '{marca}', $slug, $grupo_raw ) );
        }

        return sanitize_title( $grupo_raw );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'grupo'    => '',
            'sufijo'   => 'home',     // v1.3.0: sufijo para grupo="auto" → "{slug}-{sufijo}"
            'autoplay' => 'si',
            'velocidad'=> '5000',
            'flechas'  => 'si',
            'puntos'   => 'si',
        ), $atts );

        // v1.3.0 — Resolver grupo dinámicamente
        $grupo = self::resolver_grupo( $atts['grupo'], $atts['sufijo'] );

        if ( empty( $grupo ) ) {
            return '<!-- [welow_slider]: no se pudo determinar el grupo -->';
        }

        $slides = Welow_Helpers::get_slides( $grupo );

        if ( empty( $slides ) ) {
            return '<!-- [welow_slider]: no hay slides para grupo "' . esc_html( $grupo ) . '" -->';
        }

        // Encolar assets
        wp_enqueue_style( 'welow-slider' );
        wp_enqueue_script( 'welow-slider' );

        self::$instance_count++;

        ob_start();
        Welow_Helpers::get_template( 'slider.php', array(
            'slides'     => $slides,
            'slider_id'  => 'welow-slider-' . self::$instance_count,
            'autoplay'   => ( 'si' === $atts['autoplay'] ),
            'velocidad'  => intval( $atts['velocidad'] ),
            'flechas'    => ( 'si' === $atts['flechas'] ),
            'puntos'     => ( 'si' === $atts['puntos'] ),
            'es_single'  => ( count( $slides ) === 1 ),
        ) );
        return ob_get_clean();
    }
}
