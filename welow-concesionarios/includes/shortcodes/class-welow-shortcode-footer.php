<?php
/**
 * Shortcode: [welow_footer]
 * Renderiza el footer global del sitio con los datos configurados en
 * Concesionarios → Configuraciones → Footer.
 *
 * Estructura visual:
 *   FILA 1: Logo empresa (grande) + logos de todas las marcas (más pequeños).
 *   FILA 2: Ubicaciones (concesionarios auto) | 3 columnas de menús WP.
 *   FILA 3: Copyright + redes sociales + enlaces legales.
 *
 * Pensado para usar en el template global de Footer del Theme Builder de Divi.
 *
 * @since 2.45.0
 * @version 2.46.0 — Rediseño en 3 filas con separadores horizontales.
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

        // Sin nada configurado, mostrar aviso solo a admins
        if ( empty( $f ) || ( empty( $f['logo_id'] ) && empty( $f['copyright'] ) && empty( $f['col1_menu_id'] ) ) ) {
            if ( current_user_can( 'manage_options' ) ) {
                return '<div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:12px 16px;margin:12px 0;font-size:13px;color:#78350f;">'
                    . '<strong>[welow_footer]</strong> (visible solo a admins): no hay nada configurado. Ve a '
                    . '<a href="' . esc_url( admin_url( 'admin.php?page=welow_settings&tab=footer' ) ) . '">Concesionarios → Configuraciones → Footer</a>.</div>';
            }
            return '';
        }

        wp_enqueue_style( 'welow-footer' );

        // Estilos inline desde la config
        $styles = array();
        if ( ! empty( $f['color_fondo'] ) )   $styles[] = '--welow-f-bg:' . $f['color_fondo'];
        if ( ! empty( $f['color_texto'] ) )   $styles[] = '--welow-f-color:' . $f['color_texto'];
        if ( ! empty( $f['color_titulos'] ) ) $styles[] = '--welow-f-titulo:' . $f['color_titulos'];
        if ( ! empty( $f['color_link'] ) )    $styles[] = '--welow-f-link:' . $f['color_link'];
        $style_attr = $styles ? ' style="' . esc_attr( implode( ';', $styles ) ) . '"' : '';

        // Datos para FILA 1
        $logo_url = ! empty( $f['logo_id'] ) ? wp_get_attachment_image_url( intval( $f['logo_id'] ), 'large' ) : '';
        $variante = $f['logos_marca_variante'] ?? 'blanco';

        $marcas = class_exists( 'Welow_Helpers' ) ? Welow_Helpers::get_marcas( array( 'max' => -1 ) ) : array();

        // Datos para FILA 2
        $ubicaciones = get_posts( array(
            'post_type'      => 'welow_concesionario',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        ) );
        // v2.48.0 — Si el título está vacío, no se renderiza ningún h3 (antes
        // caía a "Nuestras ubicaciones"). Da más libertad de diseño.
        $ubic_titulo = trim( $f['ubicaciones_titulo'] ?? '' );

        // v2.47.0 — Si las claves no existen aún (instalación previa al toggle), mostramos todo.
        $mostrar_dir = array_key_exists( 'ubicaciones_mostrar_direccion', $f ) ? ! empty( $f['ubicaciones_mostrar_direccion'] ) : true;
        $mostrar_tel = array_key_exists( 'ubicaciones_mostrar_telefono', $f )  ? ! empty( $f['ubicaciones_mostrar_telefono'] )  : true;

        // Datos para FILA 3
        $copyright = str_replace( '{year}', date( 'Y' ), $f['copyright'] ?? '' );

        $redes = array(
            'facebook'  => array( 'label' => 'Facebook',  'icon' => '📘' ),
            'instagram' => array( 'label' => 'Instagram', 'icon' => '📷' ),
            'linkedin'  => array( 'label' => 'LinkedIn',  'icon' => '💼' ),
            'youtube'   => array( 'label' => 'YouTube',   'icon' => '▶️' ),
            'tiktok'    => array( 'label' => 'TikTok',    'icon' => '🎵' ),
            'x'         => array( 'label' => 'X',         'icon' => '𝕏' ),
        );
        $tiene_redes = false;
        foreach ( $redes as $k => $_r ) { if ( ! empty( $f[ 'social_' . $k ] ) ) { $tiene_redes = true; break; } }

        $legal_links = array();
        if ( ! empty( $f['politica_url'] ) ) $legal_links[] = '<a href="' . esc_url( $f['politica_url'] ) . '">Política de Privacidad</a>';
        if ( ! empty( $f['aviso_url'] ) )    $legal_links[] = '<a href="' . esc_url( $f['aviso_url'] ) . '">Aviso Legal</a>';
        if ( ! empty( $f['cookies_url'] ) )  $legal_links[] = '<a href="' . esc_url( $f['cookies_url'] ) . '">Cookies</a>';

        ob_start();
        ?>
        <footer class="welow-footer"<?php echo $style_attr; ?>>
            <div class="welow-footer__inner">

                <?php // ============== FILA 1: Logo empresa + marcas ============== ?>
                <div class="welow-footer__row welow-footer__row--logos">
                    <?php if ( $logo_url ) : ?>
                        <div class="welow-footer__empresa-logo">
                            <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $marcas ) ) : ?>
                        <div class="welow-footer__marcas-logos">
                            <?php foreach ( $marcas as $marca ) :
                                $murl = method_exists( 'Welow_Helpers', 'get_logo_url' )
                                    ? Welow_Helpers::get_logo_url( $marca->ID, $variante, 'medium' )
                                    : get_the_post_thumbnail_url( $marca->ID, 'medium' );
                                if ( ! $murl ) continue;
                            ?>
                                <a href="<?php echo esc_url( get_permalink( $marca->ID ) ); ?>"
                                   class="welow-footer__marca-logo"
                                   title="<?php echo esc_attr( $marca->post_title ); ?>"
                                   aria-label="<?php echo esc_attr( $marca->post_title ); ?>">
                                    <img src="<?php echo esc_url( $murl ); ?>"
                                         alt="<?php echo esc_attr( $marca->post_title ); ?>"
                                         loading="lazy" />
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $f['descripcion'] ) ) : ?>
                        <p class="welow-footer__empresa-desc"><?php echo esc_html( $f['descripcion'] ); ?></p>
                    <?php endif; ?>
                </div>

                <hr class="welow-footer__sep" />

                <?php // ============== FILA 2: Ubicaciones | Menús ============== ?>
                <div class="welow-footer__row welow-footer__row--main">

                    <?php if ( ! empty( $ubicaciones ) ) : ?>
                        <div class="welow-footer__ubicaciones">
                            <?php if ( $ubic_titulo ) : ?>
                                <h3 class="welow-footer__col-titulo"><?php echo esc_html( $ubic_titulo ); ?></h3>
                            <?php endif; ?>
                            <ul class="welow-footer__ubicaciones-lista">
                                <?php foreach ( $ubicaciones as $conc ) :
                                    $cid       = $conc->ID;
                                    $direccion = get_post_meta( $cid, '_welow_conc_direccion', true );
                                    $ciudad    = get_post_meta( $cid, '_welow_conc_ciudad', true );
                                    $cp        = get_post_meta( $cid, '_welow_conc_cp', true );
                                    $telefono  = get_post_meta( $cid, '_welow_conc_telefono', true );
                                    $url_conc  = get_permalink( $cid );
                                ?>
                                    <li>
                                        <a class="welow-footer__ubicacion-nombre" href="<?php echo esc_url( $url_conc ); ?>">
                                            <?php // v2.48.0 — SVG pin estilo línea (sustituye al emoji 📍) ?>
                                            <svg class="welow-footer__ubicacion-icon" aria-hidden="true" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 22s8-8.5 8-14a8 8 0 1 0-16 0c0 5.5 8 14 8 14z"/>
                                                <circle cx="12" cy="10" r="3"/>
                                            </svg>
                                            <span class="welow-footer__ubicacion-texto"><?php echo esc_html( $conc->post_title ); ?></span>
                                        </a>
                                        <?php if ( $mostrar_dir && ( $direccion || $ciudad ) ) : ?>
                                            <span class="welow-footer__ubicacion-dir">
                                                <?php echo esc_html( trim( $direccion . ( $cp ? ', ' . $cp : '' ) . ( $ciudad ? ' ' . $ciudad : '' ) ) ); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ( $mostrar_tel && $telefono ) : ?>
                                            <a class="welow-footer__ubicacion-tel" href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $telefono ) ); ?>">
                                                📞 <?php echo esc_html( $telefono ); ?>
                                            </a>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php // Separador vertical entre ubicaciones y menús ?>
                    <?php if ( ! empty( $ubicaciones ) ) : ?>
                        <div class="welow-footer__vsep" aria-hidden="true"></div>
                    <?php endif; ?>

                    <div class="welow-footer__menus">
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

                </div>

                <hr class="welow-footer__sep" />

                <?php // ============== FILA 3: Copyright | redes | legal ============== ?>
                <div class="welow-footer__row welow-footer__row--legal">
                    <?php if ( $copyright ) : ?>
                        <p class="welow-footer__copyright"><?php echo esc_html( $copyright ); ?></p>
                    <?php endif; ?>

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

                    <?php if ( ! empty( $legal_links ) ) : ?>
                        <p class="welow-footer__legal-links"><?php echo implode( ' · ', $legal_links ); ?></p>
                    <?php endif; ?>
                </div>

            </div>
        </footer>
        <?php
        return ob_get_clean();
    }
}
