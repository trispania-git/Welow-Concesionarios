<?php
/**
 * Shortcode: [welow_listado_completo] — Vuelca TODOS los datos en HTML
 * estructurado para consumo de chatbots y crawlers.
 *
 * Uso típico:
 *  - Crear página WP "/datos-bot/nuevos/" con [welow_listado_completo tipo="nuevos"]
 *  - Crear página WP "/datos-bot/ocasion/" con [welow_listado_completo tipo="ocasion"]
 *  - NO enlazar las páginas desde el menú — solo el chatbot conoce la URL
 *
 * El plugin añade automáticamente noindex/nofollow al detectar el shortcode.
 *
 * @since 2.4.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Listado_Completo {

    const SHORTCODE = 'welow_listado_completo';

    public static function init() {
        add_shortcode( self::SHORTCODE, array( __CLASS__, 'render' ) );

        // Auto-noindex y exclusión de sitemap
        add_action( 'wp_head', array( __CLASS__, 'auto_noindex' ), 1 );
        add_filter( 'wp_robots', array( __CLASS__, 'wp_robots_filter' ) );
        add_filter( 'wpseo_robots', array( __CLASS__, 'yoast_robots_filter' ) );  // Yoast SEO
        add_filter( 'rank_math/frontend/robots', array( __CLASS__, 'rankmath_robots_filter' ) );  // Rank Math

        // Excluir del sitemap WP nativo
        add_filter( 'wp_sitemaps_posts_query_args', array( __CLASS__, 'excluir_sitemap' ), 10, 2 );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'tipo'      => 'todos',         // nuevos | ocasion | todos | modelos | marcas
            'max'       => '-1',            // -1 = todos
            'estado'    => 'disponible',    // solo aplica a coches
            'sin_html'  => 'no',            // si="si" devuelve solo texto plano
        ), $atts );

        $tipo = in_array( $atts['tipo'], array( 'nuevos', 'ocasion', 'todos', 'modelos', 'marcas' ), true )
            ? $atts['tipo'] : 'todos';

        $datos = self::recoger_datos( $tipo, intval( $atts['max'] ), $atts['estado'] );

        ob_start();
        Welow_Helpers::get_template( 'listado-completo.php', array(
            'datos' => $datos,
            'tipo'  => $tipo,
            'sin_html' => 'si' === $atts['sin_html'],
            'site_name' => get_bloginfo( 'name' ),
            'site_url'  => home_url(),
        ) );
        return ob_get_clean();
    }

    /**
     * Recoge los datos según el tipo solicitado.
     */
    public static function recoger_datos( $tipo, $max = -1, $estado = 'disponible' ) {
        switch ( $tipo ) {
            case 'nuevos':
                $coches = Welow_Helpers::get_coches_nuevos( array( 'max' => $max, 'estado' => $estado ) );
                return array_filter( array_map( array( 'Welow_Helpers', 'get_coche_completo_data' ),
                    wp_list_pluck( $coches, 'ID' ) ) );

            case 'ocasion':
                $coches = Welow_Helpers::get_coches_ocasion( array( 'max' => $max, 'estado' => $estado ) );
                return array_filter( array_map( array( 'Welow_Helpers', 'get_coche_completo_data' ),
                    wp_list_pluck( $coches, 'ID' ) ) );

            case 'todos':
                $coches = Welow_Helpers::get_coches( array( 'max' => $max, 'estado' => $estado ) );
                return array_filter( array_map( array( 'Welow_Helpers', 'get_coche_completo_data' ),
                    wp_list_pluck( $coches, 'ID' ) ) );

            case 'modelos':
                $modelos = get_posts( array(
                    'post_type'      => 'welow_modelo',
                    'post_status'    => 'publish',
                    'posts_per_page' => $max,
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                ) );
                return array_filter( array_map( array( 'Welow_Helpers', 'get_modelo_completo_data' ),
                    wp_list_pluck( $modelos, 'ID' ) ) );

            case 'marcas':
                $marcas = Welow_Helpers::get_marcas( array( 'max' => $max ) );
                return array_filter( array_map( array( 'Welow_Helpers', 'get_marca_completo_data' ),
                    wp_list_pluck( $marcas, 'ID' ) ) );
        }
        return array();
    }

    /* ========================================================================
       AUTO-NOINDEX
       ======================================================================== */

    /**
     * Detecta si la página actual contiene nuestro shortcode.
     */
    public static function pagina_actual_tiene_shortcode() {
        if ( ! is_singular() ) return false;
        global $post;
        if ( ! $post instanceof WP_Post ) return false;
        return has_shortcode( $post->post_content, self::SHORTCODE );
    }

    /**
     * Mete <meta name="robots" content="noindex, nofollow"> en el head si toca.
     */
    public static function auto_noindex() {
        if ( self::pagina_actual_tiene_shortcode() ) {
            echo "\n" . '<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">' . "\n";
        }
    }

    /**
     * Hook moderno wp_robots() — sustituye la directiva.
     */
    public static function wp_robots_filter( $robots ) {
        if ( self::pagina_actual_tiene_shortcode() ) {
            $robots = array(
                'noindex'   => true,
                'nofollow'  => true,
                'noarchive' => true,
                'nosnippet' => true,
            );
        }
        return $robots;
    }

    /**
     * Yoast SEO override.
     */
    public static function yoast_robots_filter( $string ) {
        if ( self::pagina_actual_tiene_shortcode() ) {
            return 'noindex,nofollow,noarchive,nosnippet';
        }
        return $string;
    }

    /**
     * Rank Math override.
     */
    public static function rankmath_robots_filter( $robots ) {
        if ( self::pagina_actual_tiene_shortcode() ) {
            $robots['index']    = 'noindex';
            $robots['follow']   = 'nofollow';
            $robots['archive']  = 'noarchive';
            $robots['snippet']  = 'nosnippet';
        }
        return $robots;
    }

    /**
     * Excluye del sitemap WordPress nativo las páginas con el shortcode.
     */
    public static function excluir_sitemap( $args, $post_type ) {
        if ( 'page' !== $post_type && 'post' !== $post_type ) return $args;

        // Buscar páginas con el shortcode (cacheable)
        $excluded = get_transient( 'welow_listado_completo_pages_excluded' );
        if ( false === $excluded ) {
            $excluded = array();
            $pages = get_posts( array(
                'post_type'      => array( 'page', 'post' ),
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                's'              => '[' . self::SHORTCODE,
                'fields'         => 'ids',
            ) );
            foreach ( $pages as $pid ) {
                if ( has_shortcode( get_post_field( 'post_content', $pid ), self::SHORTCODE ) ) {
                    $excluded[] = $pid;
                }
            }
            set_transient( 'welow_listado_completo_pages_excluded', $excluded, HOUR_IN_SECONDS );
        }

        if ( ! empty( $excluded ) ) {
            $args['post__not_in'] = isset( $args['post__not_in'] )
                ? array_merge( $args['post__not_in'], $excluded )
                : $excluded;
        }
        return $args;
    }
}
