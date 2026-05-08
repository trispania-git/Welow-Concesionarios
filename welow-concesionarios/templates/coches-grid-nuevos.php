<?php
/**
 * Template: Grid de coches NUEVOS.
 *
 * @var WP_Post[] $coches
 * @var int       $columnas, $columnas_tablet, $columnas_movil
 *
 * @since 2.1.0
 * @version 2.11.0 — Card rediseñada:
 *   - Nuevo bloque "Destacados" con rótulo + lista de características principales
 *     (campos editables en el CPT, metabox "I. Destacados").
 *   - Si no hay datos destacados, la card mantiene el layout clásico
 *     (combustible / cambio / CV).
 *
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

        $img       = Welow_Helpers::get_coche_imagen_principal_url( $coche->ID, 'large' );
        $permalink = get_permalink( $coche->ID );
        $titulo    = trim( $marca_nom . ' ' . $modelo_nom );

        // v2.11.0 — Destacados
        $rotulo          = Welow_Helpers::get_coche_meta( $coche->ID, 'rotulo' );
        $caracteristicas = Welow_Helpers::get_coche_meta( $coche->ID, 'caracteristicas' );
        $caract_lineas   = array();
        if ( $caracteristicas ) {
            $caract_lineas = array_filter( array_map( 'trim', preg_split( "/\r\n|\n|\r/", $caracteristicas ) ) );
        }
        $tiene_destacados = $rotulo || ! empty( $caract_lineas );
    ?>
        <article class="welow-coche-card<?php echo $tiene_destacados ? ' welow-coche-card--con-destacados' : ''; ?>">

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

                <?php if ( $tiene_destacados ) : ?>
                    <div class="welow-coche-card__destacados">
                        <?php if ( $rotulo ) : ?>
                            <p class="welow-coche-card__rotulo"><?php echo esc_html( $rotulo ); ?></p>
                        <?php endif; ?>
                        <?php if ( ! empty( $caract_lineas ) ) : ?>
                            <ul class="welow-coche-card__caracteristicas">
                                <?php foreach ( $caract_lineas as $linea ) : ?>
                                    <li><?php echo esc_html( $linea ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php else : ?>
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
                <?php endif; ?>

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
