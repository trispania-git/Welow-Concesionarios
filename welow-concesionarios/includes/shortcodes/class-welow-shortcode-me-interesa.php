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
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'modelo'         => '',
            'form_id'        => '',
            'mostrar_marca'  => 'si',
            'titulo_generico' => '¿En qué podemos ayudarte?',
            'texto_generico'  => 'Déjanos tus datos y te contactaremos en breve.',
        ), $atts );

        // Resolver modelo: atributo del shortcode > ?modelo= de query
        $slug = $atts['modelo'] ?: ( isset( $_GET['modelo'] ) ? sanitize_title( wp_unslash( $_GET['modelo'] ) ) : '' );

        $modelo = null;
        if ( $slug ) {
            $modelo = get_page_by_path( $slug, OBJECT, 'welow_modelo' );
        }

        // Resolver formulario: atributo > config "coche_nuevo"
        $form_id = intval( $atts['form_id'] );
        if ( ! $form_id && class_exists( 'Welow_Settings' ) ) {
            $options = get_option( Welow_Settings::OPTION_KEY, array() );
            $form_id = intval( $options['formularios']['coche_nuevo'] ?? 0 );
        }

        wp_enqueue_style( 'welow-me-interesa' );

        // v2.34.0 — Si no hay modelo, renderizar versión genérica (sin hero)
        if ( ! $modelo ) {
            return self::render_generico( $form_id, $atts );
        }

        // Versión específica con hero del modelo
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

    /**
     * v2.34.0 — Versión genérica del shortcode cuando no hay modelo en la URL.
     * Sin hero, solo título introductorio + formulario.
     */
    private static function render_generico( $form_id, $atts ) {
        ob_start();
        ?>
        <div class="welow-mi welow-mi--generico">
            <div class="welow-mi__intro">
                <?php if ( ! empty( $atts['titulo_generico'] ) ) : ?>
                    <h1 class="welow-mi__intro-titulo"><?php echo esc_html( $atts['titulo_generico'] ); ?></h1>
                <?php endif; ?>
                <?php if ( ! empty( $atts['texto_generico'] ) ) : ?>
                    <p class="welow-mi__intro-texto"><?php echo esc_html( $atts['texto_generico'] ); ?></p>
                <?php endif; ?>
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
