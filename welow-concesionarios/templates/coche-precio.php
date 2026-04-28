<?php
/**
 * Template parcial: Bloque de precio.
 *
 * @var array $data
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$id = $data['post']->ID;
$contado    = Welow_Helpers::get_coche_meta( $id, 'precio_contado' );
$financiado = Welow_Helpers::get_coche_meta( $id, 'precio_financiado' );
$anterior   = Welow_Helpers::get_coche_meta( $id, 'precio_anterior' );
$cuota      = Welow_Helpers::get_coche_meta( $id, 'cuota' );
$disclaimer = $data['disclaimer'];

$moneda = class_exists( 'Welow_Settings' ) ? Welow_Settings::get( 'moneda_simbolo', '€' ) : '€';
$icono_disc_id = class_exists( 'Welow_Settings' ) ? intval( Welow_Settings::get( 'disclaimer_icono', 0 ) ) : 0;
$icono_disc_url = $icono_disc_id ? wp_get_attachment_image_url( $icono_disc_id, 'thumbnail' ) : '';

if ( ! $contado && ! $financiado && ! $cuota ) return;
?>
<aside class="welow-coche-precio">

    <?php if ( $anterior && $contado && $anterior > $contado ) : ?>
        <div class="welow-coche-precio__anterior">
            Antes <span><?php echo esc_html( number_format_i18n( floatval( $anterior ), 0 ) . ' ' . $moneda ); ?></span>
        </div>
    <?php endif; ?>

    <?php if ( $contado ) : ?>
        <div class="welow-coche-precio__contado">
            <span class="welow-coche-precio__label">Precio al contado</span>
            <span class="welow-coche-precio__valor">
                <?php echo esc_html( number_format_i18n( floatval( $contado ), 0 ) ); ?>
                <small><?php echo esc_html( $moneda ); ?></small>
            </span>
        </div>
    <?php endif; ?>

    <?php if ( $financiado && $financiado != $contado ) : ?>
        <div class="welow-coche-precio__financiado">
            <span class="welow-coche-precio__label">Precio financiado</span>
            <span class="welow-coche-precio__valor-sec">
                <?php echo esc_html( number_format_i18n( floatval( $financiado ), 0 ) . ' ' . $moneda ); ?>
            </span>
        </div>
    <?php endif; ?>

    <?php if ( $cuota ) : ?>
        <div class="welow-coche-precio__cuota">
            Desde <strong><?php echo esc_html( number_format_i18n( floatval( $cuota ), 0 ) . ' ' . $moneda ); ?></strong>/mes
        </div>
    <?php endif; ?>

    <?php if ( $disclaimer ) : ?>
        <div class="welow-coche-precio__disclaimer">
            <span class="welow-coche-precio__disclaimer-icono">
                <?php if ( $icono_disc_url ) : ?>
                    <img src="<?php echo esc_url( $icono_disc_url ); ?>" alt="i" />
                <?php else : ?>
                    <span class="dashicons dashicons-info-outline"></span>
                <?php endif; ?>
            </span>
            <small><?php echo esc_html( $disclaimer ); ?></small>
        </div>
    <?php endif; ?>

</aside>
