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
        Welow_Shortcode_Cita_Taller::init();         // v2.39.0
        Welow_Shortcode_Footer::init();              // v2.45.0

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

        // v2.52.0 — Selectores ampliados para que TODO el plugin respete los estilos
        // configurados (incluidos formularios, footer, Me Interesa, etc).
        // !important garantiza victoria sobre los hardcoded y el CSS de Divi/tema.

        // Convertimos color_principal a rgba para sombras/focus box-shadow
        $rgba_focus = '';
        if ( $color_principal && preg_match( '/^#([a-fA-F0-9]{6})$/', $color_principal, $m ) ) {
            $r = hexdec( substr( $m[1], 0, 2 ) );
            $g = hexdec( substr( $m[1], 2, 2 ) );
            $b = hexdec( substr( $m[1], 4, 2 ) );
            $rgba_focus = "rgba($r,$g,$b,0.15)";
        }

        // ===========================================================
        // BACKGROUND con color_principal → botones primarios
        // ===========================================================
        if ( $color_principal ) {
            $css .= "
            .welow-modelo-card__interesa,
            .welow-conc-card__btn,
            .welow-conc-banner__overlay-boton:hover,
            .welow-form__submit,
            .welow-coche-formulario__submit,
            .welow-conc-galeria__lightbox .welow-conc-galeria__close:hover,
            .welow-coche-extras__compartir-btn:hover {
                background: $color_principal !important;
            }
            ";

            // COLOR (texto) — enlaces, CTA texto, hover de títulos
            $css .= "
            .welow-modelo-card__cta,
            .welow-conc-info a,
            .welow-conc-info__lista a,
            .welow-conc-mapa__link,
            .welow-modelo-card__nombre a:hover,
            .welow-conc-card__localidad a:hover,
            .welow-marca-card-link,
            .welow-form a,
            .welow-form__campo--consent a,
            .welow-footer__menu a:hover,
            .welow-footer__ubicacion-nombre:hover,
            .welow-footer__ubicacion-tel:hover,
            .welow-footer__legal-links a:hover,
            .welow-coche-formulario a {
                color: $color_principal !important;
            }
            ";

            // BORDER-COLOR → hover de cards, focus de inputs, separadores destacados
            $css .= "
            .welow-conc-marca-item:hover,
            .welow-conc-card:hover,
            .welow-modelo-card:hover {
                border-color: $color_principal !important;
            }
            .welow-modelo-card__caracteristicas,
            .welow-form__campo--consent {
                border-left-color: $color_principal !important;
            }
            .welow-form input:focus,
            .welow-form textarea:focus,
            .welow-form select:focus,
            .welow-coche-formulario input:focus,
            .welow-coche-formulario textarea:focus {
                border-color: $color_principal !important;
                ";
            if ( $rgba_focus ) {
                $css .= "box-shadow: 0 0 0 3px $rgba_focus !important;";
            }
            $css .= "
            }
            ";

            // ACCENT-COLOR para checkboxes/radios nativos (HTML5)
            $css .= "
            .welow-form,
            .welow-form input[type=checkbox],
            .welow-form input[type=radio],
            .welow-form__opciones input,
            .welow-form__campo--consent input[type=checkbox] {
                accent-color: $color_principal !important;
            }
            ";

            // FOOTER — color del link hover (variable CSS)
            $css .= "
            .welow-footer { --welow-f-link: $color_principal !important; }
            ";

            // Asterisco campo obligatorio
            $css .= "
            .welow-form__req { color: $color_principal !important; }
            ";
        }

        // ===========================================================
        // HOVER del color principal
        // ===========================================================
        if ( $color_principal_hover ) {
            $css .= "
            .welow-modelo-card__interesa:hover,
            .welow-modelo-card__interesa:focus,
            .welow-conc-card__btn:hover,
            .welow-form__submit:hover:not(:disabled),
            .welow-coche-formulario__submit:hover {
                background: $color_principal_hover !important;
            }
            ";
        }

        // ===========================================================
        // COLOR DE TÍTULOS — h2/h3 de cards y fichas
        // ===========================================================
        if ( $color_titulos ) {
            $css .= "
            .welow-modelo-card__nombre,
            .welow-modelo-card__nombre a,
            .welow-conc-card__localidad,
            .welow-conc-card__localidad a,
            .welow-conc-section-title,
            .welow-coche-resaltado__rotulo,
            .welow-form__titulo,
            .welow-mi__nombre,
            .welow-mi__intro-titulo,
            .welow-conc-ficha__titulo,
            .welow-marca-banner__overlay-titulo,
            .welow-conc-banner__overlay-titulo {
                color: $color_titulos !important;
            }
            ";
        }

        // ===========================================================
        // COLOR TEXTO DE BOTONES — texto dentro de botones primarios
        // ===========================================================
        if ( $color_boton_texto ) {
            $css .= "
            .welow-modelo-card__interesa,
            .welow-conc-card__btn,
            .welow-conc-banner__overlay-boton:hover,
            .welow-form__submit,
            .welow-form__submit:hover,
            .welow-coche-formulario__submit,
            .welow-coche-formulario__submit:hover {
                color: $color_boton_texto !important;
            }
            ";
        }

        // ===========================================================
        // COLOR DE RÓTULOS — píldoras destacadas
        // ===========================================================
        if ( $rotulo_efectivo ) {
            $css .= "
            .welow-modelo-card__rotulo,
            .welow-mi__marca,
            .welow-conc-banner__overlay-rotulo {
                background: $rotulo_efectivo !important;
            }
            ";
        }

        // ===========================================================
        // TIPOGRAFÍA — aplicada a todos los componentes del plugin
        // ===========================================================
        if ( $font_family ) {
            $family_css = strpos( $font_family, ' ' ) !== false && strpos( $font_family, "'" ) === false && strpos( $font_family, '"' ) === false
                ? '\"' . $font_family . '\"'
                : $font_family;
            $css .= "
            .welow-modelo-card,
            .welow-coche-card,
            .welow-conc-card,
            .welow-conc-ficha,
            .welow-coche-ficha,
            .welow-header,
            .welow-footer,
            .welow-form,
            .welow-mi,
            .welow-marca-card,
            .welow-conc-banner__overlay-inner,
            .welow-marca-banner__overlay-inner {
                font-family: $family_css, system-ui, sans-serif !important;
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

        // v2.45.0 — Footer
        wp_register_style(
            'welow-footer',
            WELOW_CONC_URL . 'assets/css/footer.css',
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
