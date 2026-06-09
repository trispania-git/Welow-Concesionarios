<?php
/**
 * Shortcode: [welow_footer]
 * Renderiza el footer global del sitio con los datos configurados en
 * Concesionarios → Configuraciones → Footer.
 *
 * Pensado para usar en el template global de Footer del Theme Builder de Divi.
 *
 * @since 2.45.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Footer {

    public static function init() {
        add_shortcode( 'welow_footer', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts ) {
        if ( ! class_exists( 'Welow_Settings' ) ) return '';
        $options = get_option( Welow_Settings::OPTION_KEY, array() );
        $f = isset( $options['footer'] ) && is_array( $options['footer'] ) ? $options['footer'] : array();

        // Sin nada configurado, mostramos solo el copyright básico
        $vacio = empty( $f['logo_id'] ) && empty( $f['descripcion'] ) && empty( $f['telefono'] )
            && empty( $f['email'] ) && empty( $f['direccion'] )
            && empty( $f['col1_menu_id'] ) && empty( $f['col2_menu_id'] ) && empty( $f['col3_menu_id'] )
            && empty( $f['copyright'] );
        if ( $vacio ) {
            if ( current_user_can( 'manage_options' ) ) {
                return '<div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:12px 16px;margin:12px 0;font-size:13px;color:#78350f;">'
                    . '<strong>[welow_footer]</strong> (visible solo a admins): no hay nada configurado. Ve a '
                    . '<a href="' . esc_url( admin_url( 'admin.php?page=welow_settings&tab=footer' ) ) . '">Concesionarios → Configuraciones → Footer</a>.</div>';
            }
            return '';
        }

        wp_enqueue_style( 'welow-footer' );

        // Estilos inline a partir de la config
        $styles = array();
        if ( ! empty( $f['color_fondo'] ) )   $styles[] = '--welow-f-bg:' . $f['color_fondo'];
        if ( ! empty( $f['color_texto'] ) )   $styles[] = '--welow-f-color:' . $f['color_texto'];
        if ( ! empty( $f['color_titulos'] ) ) $styles[] = '--welow-f-titulo:' . $f['color_titulos'];
        if ( ! empty( $f['color_link'] ) )    $styles[] = '--welow-f-link:' . $f['color_link'];
        $style_attr = $styles ? ' style="' . esc_attr( implode( ';', $styles ) ) . '"' : '';

        // Logo
        $logo_url = ! empty( $f['logo_id'] ) ? wp_get_attachment_image_url( intval( $f['logo_id'] ), 'medium' ) : '';

        // Copyright con {year}
        $copyright = $f['copyright'] ?? '';
        $copyright = str_replace( '{year}', date( 'Y' ), $copyright );

        ob_start();
        ?>
        <footer class="welow-footer"<?php echo $style_attr; ?>>
            <div class="welow-footer__inner">

                <div class="welow-footer__main">

                    <?php // Bloque empresa: logo + descripción + contacto ?>
                    <div class="welow-footer__col welow-footer__col--brand">
                        <?php if ( $logo_url ) : ?>
                            <div class="welow-footer__logo">
                                <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
                            </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $f['descripcion'] ) ) : ?>
                            <p class="welow-footer__desc"><?php echo esc_html( $f['descripcion'] ); ?></p>
                        <?php endif; ?>

                        <?php // Contacto rápido ?>
                        <?php if ( ! empty( $f['telefono'] ) || ! empty( $f['email'] ) || ! empty( $f['direccion'] ) || ! empty( $f['horario'] ) ) : ?>
                            <ul class="welow-footer__contacto">
                                <?php if ( ! empty( $f['direccion'] ) ) : ?>
                                    <li>📍 <?php echo nl2br( esc_html( $f['direccion'] ) ); ?></li>
                                <?php endif; ?>
                                <?php if ( ! empty( $f['telefono'] ) ) : ?>
                                    <li>📞 <a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $f['telefono'] ) ); ?>"><?php echo esc_html( $f['telefono'] ); ?></a></li>
                                <?php endif; ?>
                                <?php if ( ! empty( $f['email'] ) ) : ?>
                                    <li>✉️ <a href="mailto:<?php echo esc_attr( $f['email'] ); ?>"><?php echo esc_html( $f['email'] ); ?></a></li>
                                <?php endif; ?>
                                <?php if ( ! empty( $f['horario'] ) ) : ?>
                                    <li>🕐 <span><?php echo nl2br( esc_html( $f['horario'] ) ); ?></span></li>
                                <?php endif; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <?php // 3 columnas de menús ?>
                    <?php for ( $i = 1; $i <= 3; $i++ ) :
                        $menu_id = intval( $f[ 'col' . $i . '_menu_id' ] ?? 0 );
                        $titulo  = $f[ 'col' . $i . '_titulo' ] ?? '';
                        if ( ! $menu_id ) continue;
                    ?>
                        <div class="welow-footer__col welow-footer__col--menu">
                            <?php if ( $titulo ) : ?>
                                <h3 class="welow-footer__col-titulo"><?php echo esc_html( $titulo ); ?></h3>
                            <?php endif; ?>
                            <?php wp_nav_menu( array(
                                'menu'        => $menu_id,
                                'container'   => false,
                                'menu_class'  => 'welow-footer__menu',
                                'fallback_cb' => false,
                                'depth'       => 1,
                            ) ); ?>
                        </div>
                    <?php endfor; ?>

                </div>

                <?php // Redes sociales (si alguna está rellena) ?>
                <?php
                $redes = array(
                    'facebook'  => array( 'label' => 'Facebook',  'icon' => '📘' ),
                    'instagram' => array( 'label' => 'Instagram', 'icon' => '📷' ),
                    'linkedin'  => array( 'label' => 'LinkedIn',  'icon' => '💼' ),
                    'youtube'   => array( 'label' => 'YouTube',   'icon' => '▶️' ),
                    'tiktok'    => array( 'label' => 'TikTok',    'icon' => '🎵' ),
                    'x'         => array( 'label' => 'X',         'icon' => '𝕏' ),
                );
                $tiene_redes = false;
                foreach ( $redes as $k => $r ) { if ( ! empty( $f[ 'social_' . $k ] ) ) { $tiene_redes = true; break; } }
                ?>
                <?php if ( $tiene_redes ) : ?>
                    <div class="welow-footer__redes">
                        <?php foreach ( $redes as $key => $info ) :
                            $url = $f[ 'social_' . $key ] ?? '';
                            if ( ! $url ) continue;
                        ?>
                            <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $info['label'] ); ?>" title="<?php echo esc_attr( $info['label'] ); ?>">
                                <span aria-hidden="true"><?php echo $info['icon']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php // Pie legal ?>
                <?php
                $legal_links = array();
                if ( ! empty( $f['politica_url'] ) ) $legal_links[] = '<a href="' . esc_url( $f['politica_url'] ) . '">Política de Privacidad</a>';
                if ( ! empty( $f['aviso_url'] ) )    $legal_links[] = '<a href="' . esc_url( $f['aviso_url'] ) . '">Aviso Legal</a>';
                if ( ! empty( $f['cookies_url'] ) )  $legal_links[] = '<a href="' . esc_url( $f['cookies_url'] ) . '">Cookies</a>';
                ?>
                <?php if ( $copyright || ! empty( $legal_links ) ) : ?>
                    <div class="welow-footer__legal">
                        <?php if ( $copyright ) : ?>
                            <p class="welow-footer__copyright"><?php echo esc_html( $copyright ); ?></p>
                        <?php endif; ?>
                        <?php if ( ! empty( $legal_links ) ) : ?>
                            <p class="welow-footer__legal-links"><?php echo implode( ' · ', $legal_links ); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </footer>
        <?php
        return ob_get_clean();
    }
}
