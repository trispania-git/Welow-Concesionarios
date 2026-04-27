<?php
/**
 * Template: Grid de modelos de vehículos.
 *
 * Variables disponibles:
 * @var WP_Post[] $modelos         Array de modelos.
 * @var int       $columnas        Columnas en desktop.
 * @var int       $columnas_tablet Columnas en tablet.
 * @var int       $columnas_movil  Columnas en móvil.
 * @var string    $texto_boton     Texto del CTA.
 *
 * @since 1.1.0 — Añadidos: etiquetas visuales, precio desde, disclaimer, combustible.
 * @version 1.2.0 — Añadidos: categoría de modelo (taxonomía) y plazas.
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$moneda = class_exists( 'Welow_Settings' ) ? Welow_Settings::get( 'moneda_simbolo', '€' ) : '€';
$disclaimer_global = class_exists( 'Welow_Settings' ) ? Welow_Settings::get( 'disclaimer_global', '' ) : '';
$icono_disclaimer_id = class_exists( 'Welow_Settings' ) ? intval( Welow_Settings::get( 'disclaimer_icono', 0 ) ) : 0;
$icono_disclaimer_url = $icono_disclaimer_id ? wp_get_attachment_image_url( $icono_disclaimer_id, 'thumbnail' ) : '';
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

        // v1.1.0 — Nuevos datos
        $precio_desde = Welow_Helpers::get_modelo_meta( $modelo->ID, 'precio_desde' );
        $disclaimer   = Welow_Helpers::get_modelo_disclaimer( $modelo->ID );
        $etiquetas    = Welow_Helpers::get_etiquetas_modelo( $modelo->ID );

        // Combustible (taxonomía)
        $combustibles = wp_get_post_terms( $modelo->ID, 'welow_combustible' );

        // v1.2.0 — Categoría de modelo (taxonomía) y plazas
        $categorias_modelo = wp_get_post_terms( $modelo->ID, 'welow_categoria_modelo' );
        $plazas            = Welow_Helpers::get_modelo_meta( $modelo->ID, 'plazas' );
    ?>
        <div class="welow-modelo-card">

            <div class="welow-modelo-card__imagen">
                <?php // Etiquetas visuales superpuestas ?>
                <?php if ( ! empty( $etiquetas ) ) : ?>
                    <div class="welow-modelo-card__etiquetas">
                        <?php foreach ( $etiquetas as $et ) :
                            $et_img_id = get_post_meta( $et->ID, '_welow_etiqueta_imagen', true );
                            if ( ! $et_img_id ) continue;
                            $et_img_url = wp_get_attachment_image_url( $et_img_id, 'medium' );
                        ?>
                            <img src="<?php echo esc_url( $et_img_url ); ?>"
                                 alt="<?php echo esc_attr( $et->post_title ); ?>"
                                 title="<?php echo esc_attr( $et->post_title ); ?>"
                                 class="welow-modelo-etiqueta"
                                 loading="lazy" />
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

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

                <?php // v1.2.0 — Badges de categoría + combustible + plazas (meta-info) ?>
                <div class="welow-modelo-card__meta">

                    <?php if ( ! empty( $categorias_modelo ) && ! is_wp_error( $categorias_modelo ) ) : ?>
                        <?php foreach ( $categorias_modelo as $cat ) : ?>
                            <span class="welow-badge welow-badge--categoria"><?php echo esc_html( $cat->name ); ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if ( ! empty( $combustibles ) && ! is_wp_error( $combustibles ) ) : ?>
                        <?php foreach ( $combustibles as $c ) : ?>
                            <span class="welow-badge welow-badge--combustible"><?php echo esc_html( $c->name ); ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if ( '' !== $plazas && null !== $plazas && intval( $plazas ) > 0 ) : ?>
                        <span class="welow-badge welow-badge--plazas" title="Número de plazas">
                            <span class="dashicons dashicons-groups"></span>
                            <?php echo intval( $plazas ); ?>
                        </span>
                    <?php endif; ?>

                </div>

                <h3 class="welow-modelo-card__nombre">
                    <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $nombre ); ?></a>
                </h3>

                <?php if ( $descripcion ) : ?>
                    <p class="welow-modelo-card__desc"><?php echo esc_html( $descripcion ); ?></p>
                <?php endif; ?>

                <?php if ( '' !== $precio_desde && null !== $precio_desde ) : ?>
                    <div class="welow-modelo-card__precio">
                        <span class="welow-precio-label">Desde</span>
                        <span class="welow-precio-valor">
                            <?php echo esc_html( number_format_i18n( floatval( $precio_desde ), 0 ) ); ?> <?php echo esc_html( $moneda ); ?>
                        </span>

                        <?php if ( $disclaimer ) : ?>
                            <span class="welow-precio-disclaimer"
                                  tabindex="0"
                                  data-tooltip="<?php echo esc_attr( wp_strip_all_tags( $disclaimer ) ); ?>">
                                <?php if ( $icono_disclaimer_url ) : ?>
                                    <img src="<?php echo esc_url( $icono_disclaimer_url ); ?>" alt="Aviso legal" width="16" height="16" />
                                <?php else : ?>
                                    <span class="dashicons dashicons-info-outline"></span>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <a href="<?php echo esc_url( $permalink ); ?>" class="welow-btn welow-btn-primary">
                    <?php echo esc_html( $texto_enlace ); ?>
                </a>
            </div>

        </div>
    <?php endforeach; ?>

</div>
