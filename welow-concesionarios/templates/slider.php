<?php
/**
 * Template: Slider de imágenes fullwidth.
 *
 * @var array   $slides    Array de slides (posts).
 * @var string  $slider_id ID único del slider.
 * @var bool    $autoplay  Autoplay activado.
 * @var int     $velocidad Milisegundos entre slides.
 * @var bool    $flechas   Mostrar flechas de navegación.
 * @var bool    $puntos    Mostrar indicadores.
 * @var bool    $es_single Solo un slide (sin navegación).
 *
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="welow-slider<?php echo $es_single ? ' welow-slider--single' : ''; ?>"
     id="<?php echo esc_attr( $slider_id ); ?>"
     data-autoplay="<?php echo $autoplay ? 'true' : 'false'; ?>"
     data-speed="<?php echo esc_attr( $velocidad ); ?>">

    <div class="welow-slider__track">
        <?php foreach ( $slides as $index => $slide ) :
            $img_desktop_id  = get_post_meta( $slide->ID, '_welow_slide_img_desktop', true );
            $img_movil_id    = get_post_meta( $slide->ID, '_welow_slide_img_movil', true );
            $enlace          = get_post_meta( $slide->ID, '_welow_slide_enlace', true );

            $img_desktop_url = $img_desktop_id ? wp_get_attachment_image_url( $img_desktop_id, 'full' ) : '';
            $img_movil_url   = $img_movil_id ? wp_get_attachment_image_url( $img_movil_id, 'large' ) : $img_desktop_url;

            $tag_open  = $enlace ? '<a href="' . esc_url( $enlace ) . '"' : '<div';
            $tag_close = $enlace ? '</a>' : '</div>';
        ?>
            <?php echo $tag_open; ?> class="welow-slider__slide<?php echo 0 === $index ? ' welow-slider__slide--active' : ''; ?>"
                data-index="<?php echo esc_attr( $index ); ?>">

                <?php if ( $img_desktop_url ) : ?>
                    <picture>
                        <?php if ( $img_movil_url && $img_movil_url !== $img_desktop_url ) : ?>
                            <source media="(max-width: 980px)" srcset="<?php echo esc_url( $img_movil_url ); ?>">
                        <?php endif; ?>
                        <img src="<?php echo esc_url( $img_desktop_url ); ?>"
                             alt="<?php echo esc_attr( $slide->post_title ); ?>"
                             loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>"
                             class="welow-slider__img" />
                    </picture>
                <?php endif; ?>

            <?php echo $tag_close; ?>
        <?php endforeach; ?>
    </div>

    <?php if ( ! $es_single && $flechas ) : ?>
        <button class="welow-slider__arrow welow-slider__arrow--prev" aria-label="Anterior">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </button>
        <button class="welow-slider__arrow welow-slider__arrow--next" aria-label="Siguiente">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="9 6 15 12 9 18"></polyline>
            </svg>
        </button>
    <?php endif; ?>

    <?php if ( ! $es_single && $puntos ) : ?>
        <div class="welow-slider__dots">
            <?php foreach ( $slides as $index => $slide ) : ?>
                <button class="welow-slider__dot<?php echo 0 === $index ? ' welow-slider__dot--active' : ''; ?>"
                        data-index="<?php echo esc_attr( $index ); ?>"
                        aria-label="Ir al slide <?php echo esc_attr( $index + 1 ); ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
