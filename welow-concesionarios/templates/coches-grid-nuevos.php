<?php
/**
 * Template: Grid de coches NUEVOS.
 *
 * @var WP_Post[] $coches
 * @var int       $columnas, $columnas_tablet, $columnas_movil
 *
 * @since 2.1.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$moneda = class_exists( 'Welow_Settings' ) ? Welow_Settings::get( 'moneda_simbolo', '€' ) : '€';
?>
<div class="welow-coches-grid"
     style="--welow-cols: <?php echo esc_attr( $columnas ); ?>;
            --welow-cols-tablet: <?php echo esc_attr( $columnas_tablet ); ?>;
            --welow-cols-movil: <?php echo esc_attr( $columnas_movil ); ?>;">

    <?php foreach ( $coches as $coche ) :
        $marca_nom  = Welow_Helpers::get_coche_marca_nombre( $coche->ID );
        $modelo_nom = Welow_Helpers::get_coche_modelo_nombre( $coche->ID );
        $version    = Welow_Helpers::get_coche_meta( $coche->ID, 'version' );
        $cv         = Welow_Helpers::get_coche_meta( $coche->ID, 'cv' );
        $cambio     = Welow_Helpers::get_coche_meta( $coche->ID, 'cambio' );
        $precio     = Welow_Helpers::get_coche_meta( $coche->ID, 'precio_contado' );
        $precio_ant = Welow_Helpers::get_coche_meta( $coche->ID, 'precio_anterior' );

        $combustibles = wp_get_post_terms( $coche->ID, 'welow_combustible' );
        $combustible_label = ! empty( $combustibles ) && ! is_wp_error( $combustibles ) ? $combustibles[0]->name : '';

        $img       = get_the_post_thumbnail_url( $coche->ID, 'large' );
        $permalink = get_permalink( $coche->ID );
        $titulo    = trim( $marca_nom . ' ' . $modelo_nom );
    ?>
        <article class="welow-coche-card">

            <a href="<?php echo esc_url( $permalink ); ?>" class="welow-coche-card__imagen">
                <?php if ( $img ) : ?>
                    <img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $titulo ); ?>" loading="lazy" />
                <?php else : ?>
                    <span class="welow-coche-card__placeholder dashicons dashicons-car"></span>
                <?php endif; ?>
                <span class="welow-coche-tipo welow-tipo-nuevo">Nuevo</span>
            </a>

            <div class="welow-coche-card__body">
                <h3 class="welow-coche-card__titulo">
                    <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $titulo ); ?></a>
                </h3>

                <?php if ( $version ) : ?>
                    <p class="welow-coche-card__version"><?php echo esc_html( $version ); ?></p>
                <?php endif; ?>

                <ul class="welow-coche-card__datos">
                    <?php if ( $combustible_label ) : ?>
                        <li><?php echo esc_html( $combustible_label ); ?></li>
                    <?php endif; ?>
                    <?php if ( $cambio ) :
                        $cambios = Welow_CPT_Coche_Base::get_cambio_options();
                    ?>
                        <li><?php echo esc_html( $cambios[ $cambio ] ?? $cambio ); ?></li>
                    <?php endif; ?>
                    <?php if ( $cv ) : ?>
                        <li><?php echo esc_html( $cv ); ?> CV</li>
                    <?php endif; ?>
                </ul>

                <div class="welow-coche-card__footer">
                    <div class="welow-coche-card__precio">
                        <?php if ( $precio_ant && $precio_ant > $precio ) : ?>
                            <span class="welow-coche-card__precio-anterior">
                                <?php echo esc_html( number_format_i18n( floatval( $precio_ant ), 0 ) . ' ' . $moneda ); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ( $precio ) : ?>
                            <strong><?php echo esc_html( number_format_i18n( floatval( $precio ), 0 ) . ' ' . $moneda ); ?></strong>
                        <?php else : ?>
                            <em>Consultar</em>
                        <?php endif; ?>
                    </div>
                    <a href="<?php echo esc_url( $permalink ); ?>" class="welow-btn welow-btn-primary">Ver ficha</a>
                </div>
            </div>

        </article>
    <?php endforeach; ?>
</div>
