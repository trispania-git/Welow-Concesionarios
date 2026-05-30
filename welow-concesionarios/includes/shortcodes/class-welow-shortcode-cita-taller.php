<?php
/**
 * Shortcode: [welow_cita_taller]
 * Renderiza el formulario de cita previa de taller configurado en
 * Concesionarios → Configuraciones → "Formulario de Cita Taller".
 *
 * Atributos:
 *   titulo  Título introductorio (default: "Pide tu cita de taller")
 *   texto   Texto introductorio (default: "Reserva la fecha y hora que mejor te venga.")
 *
 * @since 2.39.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Cita_Taller {

    public static function init() {
        add_shortcode( 'welow_cita_taller', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'titulo' => 'Pide tu cita de taller',
            'texto'  => 'Reserva la fecha y hora que mejor te venga. Te confirmaremos por teléfono o email en menos de 24h.',
        ), $atts );

        $form_id = 0;
        if ( class_exists( 'Welow_Settings' ) ) {
            $options = get_option( Welow_Settings::OPTION_KEY, array() );
            $form_id = intval( $options['formularios']['cita_taller'] ?? 0 );
        }

        if ( ! $form_id ) {
            return self::msg_admin( 'No hay formulario configurado en <a href="' . esc_url( admin_url( 'admin.php?page=welow_settings' ) ) . '">Configuraciones → Formularios → "Formulario de Cita Taller"</a>.' );
        }

        if ( ! class_exists( 'Welow_Shortcode_Formulario' ) ) {
            return self::msg_admin( 'Sistema de formularios no disponible.' );
        }

        // Reusamos los estilos del shortcode Me Interesa para consistencia visual
        wp_enqueue_style( 'welow-me-interesa' );

        ob_start();
        ?>
        <div class="welow-mi welow-mi--generico welow-cita-taller">
            <div class="welow-mi__intro">
                <?php if ( ! empty( $atts['titulo'] ) ) : ?>
                    <h1 class="welow-mi__intro-titulo"><?php echo esc_html( $atts['titulo'] ); ?></h1>
                <?php endif; ?>
                <?php if ( ! empty( $atts['texto'] ) ) : ?>
                    <p class="welow-mi__intro-texto"><?php echo esc_html( $atts['texto'] ); ?></p>
                <?php endif; ?>
            </div>

            <div class="welow-mi__form">
                <?php echo Welow_Shortcode_Formulario::render( array( 'id' => $form_id ) ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function msg_admin( $html ) {
        if ( current_user_can( 'manage_options' ) ) {
            return '<div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:12px 16px;margin:12px 0;border-radius:4px;font-size:13px;color:#78350f;">'
                . '<strong>[welow_cita_taller]</strong> (visible solo a admins):<br>' . $html . '</div>';
        }
        return '<!-- [welow_cita_taller]: ' . wp_strip_all_tags( $html ) . ' -->';
    }
}
