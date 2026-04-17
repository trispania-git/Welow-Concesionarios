<?php
/**
 * Template: Sección de contenido flexible.
 *
 * @var string $titulo       Título de la sección.
 * @var string $contenido    HTML del contenido (ya procesado con wpautop + do_shortcode).
 * @var string $img_url      URL de imagen desktop.
 * @var string $img_movil_url URL de imagen móvil (opcional).
 * @var string $layout       imagen-derecha, imagen-izquierda, imagen-arriba, solo-texto.
 * @var string $boton_texto  Texto del botón.
 * @var string $boton_enlace URL del botón.
 * @var string $fondo        Color de fondo CSS.
 *
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$tiene_imagen = ! empty( $img_url ) && 'solo-texto' !== $layout;
$fondo_style  = ( 'transparente' !== $fondo && ! empty( $fondo ) )
    ? 'background-color:' . esc_attr( $fondo ) . ';'
    : '';
?>
<section class="welow-contenido welow-contenido--<?php echo esc_attr( $layout ); ?>"
    <?php echo $fondo_style ? 'style="' . $fondo_style . '"' : ''; ?>>

    <div class="welow-contenido__inner">

        <?php if ( $tiene_imagen ) : ?>
            <div class="welow-contenido__imagen">
                <picture>
                    <?php if ( $img_movil_url && $img_movil_url !== $img_url ) : ?>
                        <source media="(max-width: 980px)" srcset="<?php echo esc_url( $img_movil_url ); ?>">
                    <?php endif; ?>
                    <img src="<?php echo esc_url( $img_url ); ?>"
                         alt="<?php echo esc_attr( $titulo ); ?>"
                         loading="lazy" />
                </picture>
            </div>
        <?php endif; ?>

        <div class="welow-contenido__texto">
            <?php if ( $titulo ) : ?>
                <h2 class="welow-contenido__titulo"><?php echo esc_html( $titulo ); ?></h2>
            <?php endif; ?>

            <?php if ( $contenido ) : ?>
                <div class="welow-contenido__body">
                    <?php echo $contenido; ?>
                </div>
            <?php endif; ?>

            <?php if ( $boton_texto && $boton_enlace ) : ?>
                <div class="welow-contenido__cta">
                    <a href="<?php echo esc_url( $boton_enlace ); ?>" class="welow-btn welow-btn-primary">
                        <?php echo esc_html( $boton_texto ); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </div>

</section>
