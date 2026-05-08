<?php
/**
 * Template parcial: Bloque resaltado (rótulo + características principales).
 *
 * Solo se renderiza si el coche tiene rótulo o características rellenadas
 * en el metabox "I. Destacados (card)" del CPT welow_coche_nuevo.
 *
 * @var array $data
 * @since 2.12.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$coche = $data['post'];
$id    = $coche->ID;

$rotulo          = Welow_Helpers::get_coche_meta( $id, 'rotulo' );
$caracteristicas = Welow_Helpers::get_coche_meta( $id, 'caracteristicas' );

$lineas = array();
if ( $caracteristicas ) {
    $lineas = array_filter( array_map( 'trim', preg_split( "/\r\n|\n|\r/", $caracteristicas ) ) );
}

if ( ! $rotulo && empty( $lineas ) ) {
    return; // nada que mostrar
}
?>
<section class="welow-coche-resaltado">
    <?php if ( $rotulo ) : ?>
        <h2 class="welow-coche-resaltado__rotulo"><?php echo esc_html( $rotulo ); ?></h2>
    <?php endif; ?>

    <?php if ( ! empty( $lineas ) ) : ?>
        <ul class="welow-coche-resaltado__lista">
            <?php foreach ( $lineas as $linea ) : ?>
                <li>
                    <span class="welow-coche-resaltado__check" aria-hidden="true">✓</span>
                    <span class="welow-coche-resaltado__texto"><?php echo esc_html( $linea ); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
