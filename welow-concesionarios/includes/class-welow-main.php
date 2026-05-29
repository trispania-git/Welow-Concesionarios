<?php
/**
 * Clase principal del plugin Welow Concesionarios.
 *
 * @package Welow_Concesionarios
 * @since 1.0.0
 * @version 2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Main {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Admin
        Welow_Admin_Menu::init();              // v1.1.0
        Welow_Settings::init();                // v1.1.0
        Welow_Importer::init();                // v1.1.0
        Welow_SuperExcel::init();              // v2.17.0
        Welow_Divi_Library_Admin::init();      // v1.4.0
        Welow_Marca_Sync::init();              // v2.2.0
        Welow_Help::init();                    // v2.4.0
        Welow_Rest_API::init();                // v2.4.0

        // Taxonomías
        Welow_Tax_Combustible::init();         // v1.1.0
        Welow_Tax_Categoria_Modelo::init();    // v1.2.0
        Welow_Tax_Marca_Externa::init();       // v2.1.0

        // CPTs
        Welow_CPT_Marca::init();
        Welow_CPT_Slide::init();
        Welow_CPT_Modelo::init();
        Welow_CPT_Etiqueta::init();
        Welow_CPT_Concesionario::init();       // v2.0.0
        Welow_CPT_Coche_Nuevo::init();         // v2.1.0
        Welow_CPT_Coche_Ocasion::init();       // v2.1.0

        // Shortcodes
        Welow_Shortcode_Marcas::init();
        Welow_Shortcode_Slider::init();
        Welow_Shortcode_Modelos::init();
        Welow_Shortcode_Slider_CTA::init();
        Welow_Shortcode_Contenido::init();
        Welow_Shortcode_Marca_Banner::init();
        Welow_Shortcode_Divi::init();              // v1.4.0
        Welow_Shortcode_Coches_Nuevos::init();     // v2.1.0
        Welow_Shortcode_Coches_Ocasion::init();    // v2.1.0
        Welow_Shortcode_Coche_Ficha::init();       // v2.0.0
        Welow_Shortcode_Buscador_Coches::init();   // v2.0.0
        Welow_Shortcode_Listado_Completo::init();  // v2.4.0
        Welow_Shortcode_Coche_Extras::init();      // v2.5.0
        Welow_Shortcode_Header::init();            // v2.6.0
        Welow_Shortcode_Coches_Filtro::init();     // v2.8.0
        Welow_Shortcode_Concesionario_Ficha::init(); // v2.27.0
        Welow_Shortcode_Concesionarios::init();      // v2.28.0
        Welow_CPT_Formulario::init();                // v2.30.0
        Welow_CPT_Lead::init();                      // v2.30.0
        Welow_Shortcode_Formulario::init();          // v2.30.0
        Welow_Shortcode_Me_Interesa::init();         // v2.32.0

        // Permalinks personalizados de coches
        Welow_Coche_Permalinks::init();            // v2.5.0

        // Enqueue assets
        add_action( 'wp_enqueue_scripts', array( $this, 'registrar_assets' ) );
        // v2.29.0 — Estilos globales (overrides de color/tipografía) en <head>
        add_action( 'wp_head', array( __CLASS__, 'inyectar_estilos_globales' ), 99 );
        add_action( 'admin_enqueue_scripts', array( $this, 'registrar_admin_assets' ) );
    }

    /**
     * v2.29.0 — Inyecta los estilos globales del plugin (overrides de color +
     * tipografía configurables desde Configuraciones → "Estilos generales").
     * Solo escribe lo que tenga valor en Configuraciones; sin configurar = nada.
     */
    public static function inyectar_estilos_globales() {
        if ( ! class_exists( 'Welow_Settings' ) ) return;
        // v2.29.1 — Fix: usar la OPTION_KEY real (welow_conc_settings)
        $options = get_option( Welow_Settings::OPTION_KEY, array() );
        $e = isset( $options['estilos'] ) && is_array( $options['estilos'] ) ? $options['estilos'] : array();

        $color_principal       = $e['color_principal']       ?? '';
        $color_principal_hover = $e['color_principal_hover'] ?? '';
        $color_titulos         = $e['color_titulos']         ?? '';
        $color_boton_texto     = $e['color_boton_texto']     ?? '';
        $color_rotulo          = $e['color_rotulo']          ?? '';
        $font_family           = $e['font_family']           ?? '';
        $font_google           = ! empty( $e['font_google'] );

        if ( ! $color_principal && ! $color_principal_hover && ! $color_titulos
            && ! $color_boton_texto && ! $color_rotulo && ! $font_family ) {
            return; // Nada que inyectar.
        }

        // Cargar Google Font si procede
        if ( $font_family && $font_google ) {
            $family = str_replace( ' ', '+', $font_family );
            echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
            echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
            echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=' . esc_attr( $family ) . ':wght@400;500;600;700;800&display=swap">' . "\n";
        }

        // Rótulo usa color_principal si no se define explícitamente
        $rotulo_efectivo = $color_rotulo ?: $color_principal;

        $css = '';

        // v2.29.1 — Usar !important para ganar al shorthand "background:" de los
        // CSS originales y al posible CSS de Divi/tema.

        // Color principal → fondos de botones primarios
        if ( $color_principal ) {
            $css .= "
            .welow-modelo-card__interesa,
            .welow-conc-card__btn,
            .welow-conc-banner__overlay-boton:hover {
                background: $color_principal !important;
            }
            .welow-modelo-card__cta,
            .welow-conc-info a,
            .welow-conc-mapa__link,
            .welow-modelo-card__nombre a:hover,
            .welow-conc-card__localidad a:hover {
                color: $color_principal !important;
            }
            .welow-conc-marca-item:hover,
            .welow-conc-card:hover {
                border-color: $color_principal !important;
            }
            .welow-modelo-card__caracteristicas {
                border-left-color: $color_principal !important;
            }
            ";
        }

        if ( $color_principal_hover ) {
            $css .= "
            .welow-modelo-card__interesa:hover,
            .welow-modelo-card__interesa:focus,
            .welow-conc-card__btn:hover {
                background: $color_principal_hover !important;
            }
            ";
        }

        if ( $color_titulos ) {
            $css .= "
            .welow-modelo-card__nombre,
            .welow-modelo-card__nombre a,
            .welow-conc-card__localidad,
            .welow-conc-card__localidad a,
            .welow-conc-section-title,
            .welow-coche-resaltado__rotulo {
                color: $color_titulos !important;
            }
            ";
        }

        if ( $color_boton_texto ) {
            $css .= "
            .welow-modelo-card__interesa,
            .welow-conc-card__btn,
            .welow-conc-banner__overlay-boton:hover {
                color: $color_boton_texto !important;
            }
            ";
        }

        if ( $rotulo_efectivo ) {
            $css .= "
            .welow-modelo-card__rotulo {
                background: $rotulo_efectivo !important;
            }
            ";
        }

        if ( $font_family ) {
            // Comillas para nombres con espacios
            $family_css = strpos( $font_family, ' ' ) !== false && strpos( $font_family, "'" ) === false && strpos( $font_family, '"' ) === false
                ? '"' . $font_family . '"'
                : $font_family;
            $css .= "
            .welow-modelo-card,
            .welow-coche-card,
            .welow-conc-card,
            .welow-conc-ficha,
            .welow-coche-ficha,
            .welow-header,
            .welow-conc-banner__overlay-inner,
            .welow-marca-banner__overlay-inner {
                font-family: $family_css, system-ui, sans-serif;
            }
            ";
        }

        if ( $css ) {
            // Compactar espacios para html limpio
            $css = preg_replace( '/\s+/', ' ', trim( $css ) );
            echo "<style id=\"welow-estilos-globales\">$css</style>\n";
        }
    }

    /**
     * Registra los assets del frontend (se encolan bajo demanda en los shortcodes).
     */
    public function registrar_assets() {
        // CSS
        wp_register_style(
            'welow-marcas',
            WELOW_CONC_URL . 'assets/css/marcas.css',
            array(),
            WELOW_CONC_VERSION
        );

        wp_register_style(
            'welow-slider',
            WELOW_CONC_URL . 'assets/css/slider.css',
            array(),
            WELOW_CONC_VERSION
        );

        wp_register_style(
            'welow-secciones',
            WELOW_CONC_URL . 'assets/css/secciones.css',
            array(),
            WELOW_CONC_VERSION
        );

        // === v2.0.0: Coches ===
        wp_register_style(
            'welow-coches',
            WELOW_CONC_URL . 'assets/css/coches.css',
            array(),
            WELOW_CONC_VERSION
        );
        wp_register_style(
            'welow-coche-ficha',
            WELOW_CONC_URL . 'assets/css/coche-ficha.css',
            array(),
            WELOW_CONC_VERSION
        );
        wp_register_style(
            'welow-buscador',
            WELOW_CONC_URL . 'assets/css/buscador.css',
            array(),
            WELOW_CONC_VERSION
        );

        wp_register_style(
            'welow-coche-extras',
            WELOW_CONC_URL . 'assets/css/coche-extras.css',
            array(),
            WELOW_CONC_VERSION
        );

        // v2.6.0 — Header
        wp_register_style(
            'welow-header',
            WELOW_CONC_URL . 'assets/css/header.css',
            array(),
            WELOW_CONC_VERSION
        );

        // v2.8.0 — Filtro de coches
        wp_register_style(
            'welow-coches-filtro',
            WELOW_CONC_URL . 'assets/css/coches-filtro.css',
            array(),
            WELOW_CONC_VERSION
        );

        // v2.27.0 — Ficha de concesionario
        wp_register_style(
            'welow-concesionario-ficha',
            WELOW_CONC_URL . 'assets/css/concesionario-ficha.css',
            array(),
            WELOW_CONC_VERSION
        );

        // v2.30.0 — Formularios
        wp_register_style(
            'welow-formulario',
            WELOW_CONC_URL . 'assets/css/formulario.css',
            array(),
            WELOW_CONC_VERSION
        );

        // v2.32.0 — Página "Me interesa"
        wp_register_style(
            'welow-me-interesa',
            WELOW_CONC_URL . 'assets/css/me-interesa.css',
            array(),
            WELOW_CONC_VERSION
        );

        // JS
        wp_register_script(
            'welow-slider',
            WELOW_CONC_URL . 'assets/js/slider.js',
            array(),
            WELOW_CONC_VERSION,
            true
        );
        // v2.24.0 — Mini-slider de galería de modelos
        wp_register_script(
            'welow-modelos-slider',
            WELOW_CONC_URL . 'assets/js/modelos-slider.js',
            array(),
            WELOW_CONC_VERSION,
            true
        );
        wp_register_script(
            'welow-coche-galeria',
            WELOW_CONC_URL . 'assets/js/coche-galeria.js',
            array(),
            WELOW_CONC_VERSION,
            true
        );

        // v2.6.0 — Header (toggle hamburger)
        wp_register_script(
            'welow-header',
            WELOW_CONC_URL . 'assets/js/header.js',
            array(),
            WELOW_CONC_VERSION,
            true
        );

        // v2.8.0 — Filtro coches (drawer móvil + autosubmit orden)
        wp_register_script(
            'welow-coches-filtro',
            WELOW_CONC_URL . 'assets/js/coches-filtro.js',
            array(),
            WELOW_CONC_VERSION,
            true
        );

        // v2.27.0 — Ficha de concesionario (lightbox de galería)
        wp_register_script(
            'welow-concesionario-ficha',
            WELOW_CONC_URL . 'assets/js/concesionario-ficha.js',
            array(),
            WELOW_CONC_VERSION,
            true
        );

        // v2.30.0 — Formularios (validación + AJAX submit)
        wp_register_script(
            'welow-formulario',
            WELOW_CONC_URL . 'assets/js/formulario.js',
            array(),
            WELOW_CONC_VERSION,
            true
        );
    }

    /**
     * Assets para el admin.
     *
     * @since 1.1.0 — Añadido soporte para welow_etiqueta, welow_modelo y páginas del plugin.
     */
    public function registrar_admin_assets( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen ) return;

        // CPTs y páginas que usan el media uploader
        $cpts_con_media = array(
            'welow_marca', 'welow_slide', 'welow_modelo', 'welow_etiqueta',
            'welow_concesionario',                              // v2.0.0
            'welow_coche_nuevo', 'welow_coche_ocasion',         // v2.1.0
        );
        $paginas_con_media = array(
            'concesionarios_page_welow_settings',
            'welow-concesionarios_page_welow_settings',
        );

        $necesita_media = in_array( $screen->post_type, $cpts_con_media, true )
            || in_array( $hook, $paginas_con_media, true )
            || ( isset( $_GET['page'] ) && 'welow_settings' === $_GET['page'] )
            || ( 'edit-tags.php' === $hook || 'term.php' === $hook );

        if ( $necesita_media ) {
            wp_enqueue_media();
            // jQuery UI sortable para galería del coche/concesionario y builder de formulario
            if ( in_array( $screen->post_type, array( 'welow_coche_nuevo', 'welow_coche_ocasion', 'welow_concesionario', 'welow_formulario' ), true ) ) {
                wp_enqueue_script( 'jquery-ui-sortable' );
            }
            // v2.10.0 — Color picker nativo de WP en pantalla de modelo
            // v2.29.0 — También en la pantalla de Configuraciones (estilos generales)
            $en_pantalla_settings = ( isset( $_GET['page'] ) && 'welow_settings' === $_GET['page'] );
            if ( 'welow_modelo' === $screen->post_type || $en_pantalla_settings ) {
                wp_enqueue_style( 'wp-color-picker' );
                wp_enqueue_script( 'wp-color-picker' );
                add_action( 'admin_print_footer_scripts', function() {
                    echo '<script>jQuery(function($){ if($.fn.wpColorPicker){ $(".welow-color-field").wpColorPicker(); } });</script>';
                } );
            }
            wp_enqueue_style(
                'welow-admin-marca',
                WELOW_CONC_URL . 'assets/css/admin-marca.css',
                array(),
                WELOW_CONC_VERSION
            );
            wp_enqueue_script(
                'welow-admin-marca',
                WELOW_CONC_URL . 'assets/js/admin-marca.js',
                array( 'jquery' ),
                WELOW_CONC_VERSION,
                true
            );
        }
    }
}
