<?php
/**
 * Template parcial: Garantías del coche.
 *
 * @var array $data
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$garantias = Welow_Helpers::get_coche_meta( $data['post']->ID, 'garantias' );
$programa  = Welow_Helpers::get_coche_meta( $data['post']->ID, 'programa' );
if ( empty( $garantias ) && empty( $programa ) ) return;
?>
<section class="welow-coche-garantias">
    <h2>Garantías y servicios</h2>

    <?php if ( $programa ) : ?>
        <div class="welow-coche-garantias__programa">
            <span class="dashicons dashicons-awards"></span>
            <strong><?php echo esc_html( $programa ); ?></strong>
        </div>
    <?php endif; ?>

    <?php if ( $garantias ) : ?>
        <div class="welow-coche-garantias__contenido">
            <?php echo wp_kses_post( wpautop( $garantias ) ); ?>
        </div>
    <?php endif; ?>
</section>
