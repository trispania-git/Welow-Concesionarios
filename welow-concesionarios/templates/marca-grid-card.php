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

        // v2.35.0 — Foto de un modelo al azar de esta marca (preferimos con featured image).
        $foto_modelo_url = '';
        $modelos_marca = get_posts( array(
            'post_type'      => 'welow_modelo',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'rand',
            'meta_query'     => array(
                'relation' => 'AND',
                array( 'key' => '_welow_modelo_marca', 'value' => $marca->ID ),
                array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ),
            ),
        ) );
        if ( ! empty( $modelos_marca ) ) {
            $foto_modelo_url = get_the_post_thumbnail_url( $modelos_marca[0]->ID, 'large' );
        } else {
            // Fallback: cualquier modelo (sin importar thumbnail)
            $any = get_posts( array(
                'post_type'      => 'welow_modelo',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'orderby'        => 'rand',
                'meta_query'     => array( array( 'key' => '_welow_modelo_marca', 'value' => $marca->ID ) ),
            ) );
            if ( ! empty( $any ) ) {
                $foto_modelo_url = get_the_post_thumbnail_url( $any[0]->ID, 'large' );
            }
        }
    ?>
        <div class="welow-marca-card">

            <div class="welow-marca-card-header welow-marca-card-header--split">
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
                <?php if ( $foto_modelo_url ) : ?>
                    <div class="welow-marca-card-foto-modelo">
                        <img src="<?php echo esc_url( $foto_modelo_url ); ?>"
                             alt="<?php echo esc_attr( $nombre ); ?>"
                             loading="lazy" />
                    </div>
                <?php endif; ?>
            </div>

            <div class="welow-marca-card-body">
                <h3 class="welow-marca-card-titulo"><?php echo esc_html( $nombre ); ?></h3>
                <?php if ( $slogan ) : ?>
                    <p class="welow-marca-card-slogan"><?php echo esc_html( $slogan ); ?></p>
                <?php endif; ?>
                <?php if ( $mostrar_descripcion && $desc_corta ) : ?>
                    <p class="welow-marca-card-desc"><?php echo esc_html( $desc_corta ); ?></p>
                <?php endif; ?>
            </div>

            <div class="welow-marca-card-footer">
                <a href="<?php echo esc_url( $link ); ?>" class="welow-marca-card-link">
                    <?php echo esc_html( $texto_boton ); ?>
                    <span aria-hidden="true">→</span>
                </a>
            </div>

        </div>
    <?php endforeach; ?>

</div>
