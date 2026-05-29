<?php
/**
 * Shortcode: [welow_me_interesa]
 * Página destino del botón "¡Me interesa!" de la card de modelo.
 *
 * Detecta el modelo desde ?modelo=slug en la URL, renderiza:
 *   - Foto destacada del modelo + marca/nombre
 *   - Formulario configurado en Configuraciones para "coches nuevos"
 *
 * Atributos:
 *   modelo         Slug del modelo (override del query string)
 *   form_id        ID del formulario a usar (override del configurado)
 *   mostrar_marca  si | no (default: si)
 *
 * @since 2.32.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Me_Interesa {

    public static function init() {
        add_shortcode( 'welow_me_interesa', array( __CLASS__, 'render' ) );
        // v2.33.0 — Redirección a /contacto/ si visitan la página sin ?modelo=
        add_action( 'template_redirect', array( __CLASS__, 'redirigir_si_sin_modelo' ) );
    }

    /**
     * v2.33.0 — Si la página actual es la "página de Me Interesa" configurada
     * en Configuraciones y NO viene con ?modelo=slug, redirige a /contacto/
     * (excepto a admins logueados, para que puedan testear con ?modelo=).
     */
    public static function redirigir_si_sin_modelo() {
        if ( is_admin() ) return;
        if ( current_user_can( 'manage_options' ) ) return; // admins ven el aviso, no redirigen
        if ( ! class_exists( 'Welow_Settings' ) ) return;

        $options = get_option( Welow_Settings::OPTION_KEY, array() );
        $page_id = intval( $options['formularios']['me_interesa_page'] ?? 0 );
        if ( ! $page_id ) return;
        if ( ! is_page( $page_id ) ) return;

        if ( ! empty( $_GET['modelo'] ) ) return; // sí tiene modelo, no redirigir

        // Permitir cambiar la URL de fallback vía filtro
        $url = apply_filters( 'welow_me_interesa_fallback_url', home_url( '/contacto/' ) );
        wp_safe_redirect( $url, 302 );
        exit;
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'modelo'        => '',
            'form_id'       => '',
            'mostrar_marca' => 'si',
        ), $atts );

        // Resolver modelo: atributo del shortcode > ?modelo= de query
        $slug = $atts['modelo'] ?: ( isset( $_GET['modelo'] ) ? sanitize_title( wp_unslash( $_GET['modelo'] ) ) : '' );

        $modelo = null;
        if ( $slug ) {
            $modelo = get_page_by_path( $slug, OBJECT, 'welow_modelo' );
        }

        if ( ! $modelo ) {
            return self::msg_admin( 'No se ha encontrado el modelo. Llega aquí desde un botón "¡Me interesa!" de una card de modelo, o pasa <code>?modelo=slug</code> en la URL.' );
        }

        // Resolver formulario: atributo > config "coche_nuevo"
        $form_id = intval( $atts['form_id'] );
        if ( ! $form_id && class_exists( 'Welow_Settings' ) ) {
            $options = get_option( Welow_Settings::OPTION_KEY, array() );
            $form_id = intval( $options['formularios']['coche_nuevo'] ?? 0 );
        }

        // Datos del modelo
        $img_url = get_the_post_thumbnail_url( $modelo->ID, 'large' );
        $nombre  = $modelo->post_title;

        $marca_html = '';
        if ( 'si' === $atts['mostrar_marca'] ) {
            $marca_id = get_post_meta( $modelo->ID, '_welow_modelo_marca', true );
            if ( $marca_id ) {
                $marca = get_post( intval( $marca_id ) );
                if ( $marca ) {
                    $marca_html = esc_html( $marca->post_title );
                }
            }
        }

        wp_enqueue_style( 'welow-me-interesa' );

        ob_start();
        ?>
        <div class="welow-mi">

            <div class="welow-mi__hero">
                <?php if ( $img_url ) : ?>
                    <div class="welow-mi__hero-img">
                        <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $nombre ); ?>" />
                    </div>
                <?php endif; ?>

                <div class="welow-mi__hero-text">
                    <?php if ( $marca_html ) : ?>
                        <p class="welow-mi__marca"><?php echo $marca_html; ?></p>
                    <?php endif; ?>
                    <h1 class="welow-mi__nombre"><?php echo esc_html( $nombre ); ?></h1>
                </div>
            </div>

            <div class="welow-mi__form">
                <?php if ( $form_id && class_exists( 'Welow_Shortcode_Formulario' ) ) : ?>
                    <?php echo Welow_Shortcode_Formulario::render( array( 'id' => $form_id ) ); ?>
                <?php else : ?>
                    <?php echo self::msg_admin( 'No hay formulario configurado en <a href="' . esc_url( admin_url( 'admin.php?page=welow_settings' ) ) . '">Configuraciones → Formularios → "Formulario para coches NUEVOS"</a>.' ); ?>
                <?php endif; ?>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }

    private static function msg_admin( $html ) {
        if ( current_user_can( 'manage_options' ) ) {
            return '<div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:12px 16px;margin:12px 0;border-radius:4px;font-size:13px;color:#78350f;">'
                . '<strong>[welow_me_interesa]</strong> (visible solo a admins):<br>' . $html . '</div>';
        }
        return '<!-- [welow_me_interesa]: ' . wp_strip_all_tags( $html ) . ' -->';
    }
}
