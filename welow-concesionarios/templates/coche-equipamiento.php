<?php
/**
 * Template parcial: Equipamiento del coche.
 *
 * @var array $data
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$equipamiento = Welow_Helpers::get_coche_meta( $data['post']->ID, 'equipamiento' );
if ( empty( $equipamiento ) ) return;
?>
<section class="welow-coche-equipamiento">
    <h2>Equipamiento</h2>
    <div class="welow-coche-equipamiento__contenido">
        <?php echo wp_kses_post( wpautop( $equipamiento ) ); ?>
    </div>
</section>
