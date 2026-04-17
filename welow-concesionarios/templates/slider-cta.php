<?php
/**
 * Template: Slider CTA — Hero con imagen de fondo, texto y botón.
 *
 * @var string $cta_id          ID único.
 * @var string $img_desktop_url URL imagen desktop.
 * @var string $img_movil_url   URL imagen móvil.
 * @var string $titulo          Título principal.
 * @var string $texto           Texto descriptivo.
 * @var string $boton_texto     Texto del botón.
 * @var string $boton_enlace    URL del botón.
 * @var string $overlay         Color overlay CSS.
 * @var string $alineacion      centro, izquierda, derecha.
 * @var string $altura          Altura desktop (CSS value).
 * @var string $altura_movil    Altura móvil (CSS value).
 *
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$align_class = 'welow-cta--' . $alineacion;
?>
<section class="welow-cta <?php echo esc_attr( $align_class ); ?>"
         id="<?php echo esc_attr( $cta_id ); ?>"
         style="--welow-cta-altura: <?php echo esc_attr( $altura ); ?>;
                --welow-cta-altura-movil: <?php echo esc_attr( $altura_movil ); ?>;">

    <!-- Imagen de fondo responsive -->
    <picture class="welow-cta__bg">
        <?php if ( $img_movil_url && $img_movil_url !== $img_desktop_url ) : ?>
            <source media="(max-width: 980px)" srcset="<?php echo esc_url( $img_movil_url ); ?>">
        <?php endif; ?>
        <img src="<?php echo esc_url( $img_desktop_url ); ?>"
             alt="<?php echo esc_attr( $titulo ); ?>"
             loading="lazy"
             class="welow-cta__bg-img" />
    </picture>

    <!-- Overlay -->
    <div class="welow-cta__overlay" style="background: <?php echo esc_attr( $overlay ); ?>;"></div>

    <!-- Contenido -->
    <div class="welow-cta__content">
        <?php if ( $titulo ) : ?>
            <h2 class="welow-cta__titulo"><?php echo esc_html( $titulo ); ?></h2>
        <?php endif; ?>

        <?php if ( $texto ) : ?>
            <p class="welow-cta__texto"><?php echo esc_html( $texto ); ?></p>
        <?php endif; ?>

        <?php if ( $boton_texto && $boton_enlace ) : ?>
            <a href="<?php echo esc_url( $boton_enlace ); ?>" class="welow-btn welow-btn-cta">
                <?php echo esc_html( $boton_texto ); ?>
            </a>
        <?php endif; ?>
    </div>

</section>
