<?php
/**
 * Template: Grid de tarjetas de marcas.
 *
 * Variables disponibles:
 * @var WP_Post[] $marcas              Array de marcas.
 * @var int       $columnas            Columnas en desktop.
 * @var int       $columnas_tablet     Columnas en tablet.
 * @var int       $columnas_movil      Columnas en móvil.
 * @var bool      $mostrar_descripcion Mostrar descripción corta.
 * @var string    $texto_boton         Texto del botón CTA.
 * @var string    $variante_logo       Variante del logo (original | negro | blanco).
 *
 * @since 1.0.0
 * @version 1.2.0 — Eliminadas categorías y tipos de venta (movidos a nivel modelo).
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="welow-marcas-grid welow-marcas-cards"
     style="--welow-cols: <?php echo esc_attr( $columnas ); ?>;
            --welow-cols-tablet: <?php echo esc_attr( $columnas_tablet ); ?>;
            --welow-cols-movil: <?php echo esc_attr( $columnas_movil ); ?>;">

    <?php foreach ( $marcas as $marca ) :
        $logo_url   = Welow_Helpers::get_logo_url( $marca->ID, isset( $variante_logo ) ? $variante_logo : 'original', 'medium' );
        $link       = get_permalink( $marca->ID );
        $nombre     = get_the_title( $marca->ID );
        $desc_corta = Welow_Helpers::get_marca_meta( $marca->ID, 'desc_corta' );
        $slogan     = Welow_Helpers::get_marca_meta( $marca->ID, 'slogan' );
    ?>
        <div class="welow-marca-card">

            <div class="welow-marca-card-header">
                <div class="welow-marca-card-logo">
                    <?php if ( $logo_url ) : ?>
                        <img src="<?php echo esc_url( $logo_url ); ?>"
                             alt="<?php echo esc_attr( $nombre ); ?>"
                             loading="lazy"
                             width="120"
                             height="120" />
                    <?php else : ?>
                        <span class="welow-marca-logo-placeholder"><?php echo esc_html( mb_substr( $nombre, 0, 2 ) ); ?></span>
                    <?php endif; ?>
                </div>
                <h3 class="welow-marca-card-titulo"><?php echo esc_html( $nombre ); ?></h3>
                <?php if ( $slogan ) : ?>
                    <p class="welow-marca-card-slogan"><?php echo esc_html( $slogan ); ?></p>
                <?php endif; ?>
            </div>

            <div class="welow-marca-card-body">

                <?php if ( $mostrar_descripcion && $desc_corta ) : ?>
                    <p class="welow-marca-card-desc"><?php echo esc_html( $desc_corta ); ?></p>
                <?php endif; ?>

            </div>

            <div class="welow-marca-card-footer">
                <a href="<?php echo esc_url( $link ); ?>" class="welow-btn welow-btn-primary">
                    <?php echo esc_html( $texto_boton ); ?>
                </a>
            </div>

        </div>
    <?php endforeach; ?>

</div>
