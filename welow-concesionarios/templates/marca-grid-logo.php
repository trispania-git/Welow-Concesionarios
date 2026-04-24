<?php
/**
 * Template: Grid compacto de logos de marcas.
 *
 * Variables disponibles:
 * @var WP_Post[] $marcas          Array de marcas.
 * @var int       $columnas        Columnas en desktop.
 * @var int       $columnas_tablet Columnas en tablet.
 * @var int       $columnas_movil  Columnas en móvil.
 *
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="welow-marcas-grid welow-marcas-logos"
     style="--welow-cols: <?php echo esc_attr( $columnas ); ?>;
            --welow-cols-tablet: <?php echo esc_attr( $columnas_tablet ); ?>;
            --welow-cols-movil: <?php echo esc_attr( $columnas_movil ); ?>;">

    <?php foreach ( $marcas as $marca ) :
        // v1.1.0 — Soporte para variante de logo (original | negro | blanco)
        $logo_url = Welow_Helpers::get_logo_url( $marca->ID, isset( $variante_logo ) ? $variante_logo : 'original', 'medium' );
        $link     = get_permalink( $marca->ID );
        $nombre   = get_the_title( $marca->ID );
        $slogan   = Welow_Helpers::get_marca_meta( $marca->ID, 'slogan' );
    ?>
        <a href="<?php echo esc_url( $link ); ?>"
           class="welow-marca-logo-item"
           title="<?php echo esc_attr( $nombre ); ?>">

            <div class="welow-marca-logo-wrap">
                <?php if ( $logo_url ) : ?>
                    <img src="<?php echo esc_url( $logo_url ); ?>"
                         alt="<?php echo esc_attr( $nombre ); ?>"
                         loading="lazy"
                         width="200"
                         height="200" />
                <?php else : ?>
                    <span class="welow-marca-logo-placeholder"><?php echo esc_html( mb_substr( $nombre, 0, 2 ) ); ?></span>
                <?php endif; ?>
            </div>

            <span class="welow-marca-logo-nombre"><?php echo esc_html( $nombre ); ?></span>

        </a>
    <?php endforeach; ?>

</div>
