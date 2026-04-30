<?php
/**
 * Template: Cabecera del sitio.
 *
 * @var array $config Configuración resuelta del shortcode.
 *
 * @since 2.6.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$logo_url       = $config['logo_id'] ? wp_get_attachment_image_url( $config['logo_id'], 'medium' ) : '';
$logo_movil_url = $config['logo_movil_id'] ? wp_get_attachment_image_url( $config['logo_movil_id'], 'thumbnail' ) : '';
$site_name      = get_bloginfo( 'name' );

// Estilos inline a partir de los colores y tipografía configurados (si los hay)
$inline_styles = array();
if ( $config['color_fondo'] )       $inline_styles[] = '--welow-h-bg:' . $config['color_fondo'];
if ( $config['color_texto'] )       $inline_styles[] = '--welow-h-color:' . $config['color_texto'];
if ( $config['color_boton'] )       $inline_styles[] = '--welow-h-btn-bg:' . $config['color_boton'];
if ( $config['color_boton_texto'] ) $inline_styles[] = '--welow-h-btn-color:' . $config['color_boton_texto'];
$inline_styles[] = '--welow-h-logo-h:' . $config['logo_altura'] . 'px';

// v2.7.0 — Variables CSS de tipografía
if ( ! empty( $config['font_family'] ) ) {
    // Stack con fallbacks para evitar FOUT feo
    $font_stack = '"' . $config['font_family'] . '", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    $inline_styles[] = '--welow-h-font:' . $font_stack;
}
if ( ! empty( $config['font_weight_menu'] ) )    $inline_styles[] = '--welow-h-fw-menu:' . $config['font_weight_menu'];
if ( ! empty( $config['font_weight_boton'] ) )   $inline_styles[] = '--welow-h-fw-boton:' . $config['font_weight_boton'];
if ( ! empty( $config['font_size_menu'] ) )      $inline_styles[] = '--welow-h-fs-menu:' . intval( $config['font_size_menu'] ) . 'px';
if ( ! empty( $config['font_size_boton'] ) )     $inline_styles[] = '--welow-h-fs-boton:' . intval( $config['font_size_boton'] ) . 'px';
if ( ! empty( $config['font_size_telefono'] ) )  $inline_styles[] = '--welow-h-fs-tel:' . intval( $config['font_size_telefono'] ) . 'px';
if ( ! empty( $config['text_transform_menu'] ) && 'none' !== $config['text_transform_menu'] ) {
    $inline_styles[] = '--welow-h-tt-menu:' . $config['text_transform_menu'];
}
if ( ! empty( $config['letter_spacing_menu'] ) ) $inline_styles[] = '--welow-h-ls-menu:' . $config['letter_spacing_menu'];

$style_attr = ! empty( $inline_styles ) ? ' style="' . esc_attr( implode( ';', $inline_styles ) ) . '"' : '';
$class_sticky = $config['sticky'] ? ' welow-header--sticky' : '';
?>
<header class="welow-header<?php echo esc_attr( $class_sticky ); ?>"<?php echo $style_attr; ?> role="banner">
    <div class="welow-header__inner" style="max-width: <?php echo esc_attr( $config['ancho_max'] ); ?>;">

        <!-- ZONA 1: Logo -->
        <div class="welow-header__logo">
            <a href="<?php echo esc_url( $config['logo_url'] ); ?>" rel="home" aria-label="<?php echo esc_attr( $site_name ); ?>">
                <?php if ( $logo_url ) : ?>
                    <img class="welow-header__logo-img welow-header__logo-img--desktop"
                         src="<?php echo esc_url( $logo_url ); ?>"
                         alt="<?php echo esc_attr( $site_name ); ?>" />
                <?php endif; ?>
                <?php if ( $logo_movil_url ) : ?>
                    <img class="welow-header__logo-img welow-header__logo-img--movil"
                         src="<?php echo esc_url( $logo_movil_url ); ?>"
                         alt="<?php echo esc_attr( $site_name ); ?>" />
                <?php endif; ?>
                <?php if ( ! $logo_url && ! $logo_movil_url ) : ?>
                    <span class="welow-header__logo-texto"><?php echo esc_html( $site_name ); ?></span>
                <?php endif; ?>
            </a>
        </div>

        <!-- ZONA 2: Menú (desktop) -->
        <nav class="welow-header__menu welow-header__menu--desktop" aria-label="Menú principal">
            <?php if ( $config['menu_id'] ) :
                wp_nav_menu( array(
                    'menu'        => $config['menu_id'],
                    'container'   => false,
                    'menu_class'  => 'welow-header__menu-list',
                    'fallback_cb' => false,
                    'depth'       => 2,
                ) );
            endif; ?>
        </nav>

        <!-- ZONA 3: CTAs (teléfono + botones) -->
        <div class="welow-header__ctas">
            <?php if ( $config['telefono'] ) :
                $tel_clean = preg_replace( '/[^\d+]/', '', $config['telefono'] );
            ?>
                <a href="tel:<?php echo esc_attr( $tel_clean ); ?>" class="welow-header__telefono" aria-label="Llamar al <?php echo esc_attr( $config['telefono'] ); ?>">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <span><?php echo esc_html( $config['telefono'] ); ?></span>
                </a>
            <?php endif; ?>

            <?php if ( $config['boton2_texto'] && $config['boton2_enlace'] ) : ?>
                <a href="<?php echo esc_url( $config['boton2_enlace'] ); ?>" class="welow-header__boton welow-header__boton--secundario">
                    <?php echo esc_html( $config['boton2_texto'] ); ?>
                </a>
            <?php endif; ?>

            <?php if ( $config['boton_texto'] && $config['boton_enlace'] ) : ?>
                <a href="<?php echo esc_url( $config['boton_enlace'] ); ?>" class="welow-header__boton welow-header__boton--primario">
                    <?php echo esc_html( $config['boton_texto'] ); ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- BOTÓN HAMBURGER (móvil) -->
        <button type="button" class="welow-header__hamburger" aria-label="Abrir menú" aria-expanded="false" aria-controls="welow-header-overlay">
            <span></span>
            <span></span>
            <span></span>
        </button>

    </div>

    <!-- OVERLAY MÓVIL -->
    <div id="welow-header-overlay" class="welow-header__overlay" hidden>
        <div class="welow-header__overlay-inner">
            <?php if ( $config['menu_id'] ) :
                wp_nav_menu( array(
                    'menu'        => $config['menu_id'],
                    'container'   => false,
                    'menu_class'  => 'welow-header__overlay-menu',
                    'fallback_cb' => false,
                    'depth'       => 3,
                ) );
            endif; ?>

            <div class="welow-header__overlay-ctas">
                <?php if ( $config['telefono'] ) : ?>
                    <a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $config['telefono'] ) ); ?>" class="welow-header__overlay-tel">
                        <span class="dashicons dashicons-phone"></span>
                        <?php echo esc_html( $config['telefono'] ); ?>
                    </a>
                <?php endif; ?>
                <?php if ( $config['boton_texto'] && $config['boton_enlace'] ) : ?>
                    <a href="<?php echo esc_url( $config['boton_enlace'] ); ?>" class="welow-header__boton welow-header__boton--primario welow-header__boton--full">
                        <?php echo esc_html( $config['boton_texto'] ); ?>
                    </a>
                <?php endif; ?>
                <?php if ( $config['boton2_texto'] && $config['boton2_enlace'] ) : ?>
                    <a href="<?php echo esc_url( $config['boton2_enlace'] ); ?>" class="welow-header__boton welow-header__boton--secundario welow-header__boton--full">
                        <?php echo esc_html( $config['boton2_texto'] ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
