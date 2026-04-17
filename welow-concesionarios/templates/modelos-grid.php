<?php
/**
 * Template: Grid de modelos de vehículos.
 *
 * @var WP_Post[] $modelos         Array de modelos.
 * @var int       $columnas        Columnas en desktop.
 * @var int       $columnas_tablet Columnas en tablet.
 * @var int       $columnas_movil  Columnas en móvil.
 * @var string    $texto_boton     Texto del CTA.
 *
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="welow-modelos-grid"
     style="--welow-cols: <?php echo esc_attr( $columnas ); ?>;
            --welow-cols-tablet: <?php echo esc_attr( $columnas_tablet ); ?>;
            --welow-cols-movil: <?php echo esc_attr( $columnas_movil ); ?>;">

    <?php foreach ( $modelos as $modelo ) :
        $img_url      = get_the_post_thumbnail_url( $modelo->ID, 'large' );
        $nombre       = get_the_title( $modelo->ID );
        $descripcion  = get_the_excerpt( $modelo->ID );
        $enlace       = Welow_Helpers::get_modelo_meta( $modelo->ID, 'enlace' );
        $texto_enlace = Welow_Helpers::get_modelo_meta( $modelo->ID, 'texto_enlace', $texto_boton );
        $permalink    = $enlace ?: get_permalink( $modelo->ID );
    ?>
        <div class="welow-modelo-card">

            <div class="welow-modelo-card__imagen">
                <?php if ( $img_url ) : ?>
                    <a href="<?php echo esc_url( $permalink ); ?>">
                        <img src="<?php echo esc_url( $img_url ); ?>"
                             alt="<?php echo esc_attr( $nombre ); ?>"
                             loading="lazy" />
                    </a>
                <?php else : ?>
                    <div class="welow-modelo-card__placeholder">
                        <span class="dashicons dashicons-car"></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="welow-modelo-card__info">
                <h3 class="welow-modelo-card__nombre">
                    <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $nombre ); ?></a>
                </h3>

                <?php if ( $descripcion ) : ?>
                    <p class="welow-modelo-card__desc"><?php echo esc_html( $descripcion ); ?></p>
                <?php endif; ?>

                <a href="<?php echo esc_url( $permalink ); ?>" class="welow-btn welow-btn-primary">
                    <?php echo esc_html( $texto_enlace ); ?>
                </a>
            </div>

        </div>
    <?php endforeach; ?>

</div>
