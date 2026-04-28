<?php
/**
 * Template orquestador de la ficha del coche.
 *
 * @var array $data    Datos completos (de get_coche_ficha_data).
 * @var array $bloques Bloques a mostrar.
 *
 * @since 2.0.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$coche  = $data['post'];
$modelo = $data['modelo'];
$marca  = $data['marca'];

$titulo = ( $marca ? $marca->post_title . ' ' : '' ) . ( $modelo ? $modelo->post_title : '' );
$version = Welow_Helpers::get_coche_meta( $coche->ID, 'version' );
$tipo_venta = Welow_Helpers::get_coche_meta( $coche->ID, 'tipo_venta' );
$tipos_venta = Welow_CPT_Coche::get_tipo_venta_options();
?>
<article class="welow-coche-ficha">

    <header class="welow-coche-ficha__header">
        <div class="welow-coche-ficha__header-titulos">
            <h1 class="welow-coche-ficha__titulo"><?php echo esc_html( $titulo ); ?></h1>
            <?php if ( $version ) : ?>
                <p class="welow-coche-ficha__version"><?php echo esc_html( $version ); ?></p>
            <?php endif; ?>
        </div>
        <?php if ( $tipo_venta && isset( $tipos_venta[ $tipo_venta ] ) ) : ?>
            <span class="welow-coche-tipo welow-tipo-<?php echo esc_attr( $tipo_venta ); ?>">
                <?php echo esc_html( $tipos_venta[ $tipo_venta ] ); ?>
            </span>
        <?php endif; ?>
    </header>

    <div class="welow-coche-ficha__layout">

        <div class="welow-coche-ficha__main">
            <?php if ( in_array( 'galeria', $bloques ) ) : ?>
                <?php Welow_Helpers::get_template( 'coche-galeria.php', array( 'data' => $data ) ); ?>
            <?php endif; ?>

            <?php if ( in_array( 'destacados', $bloques ) ) : ?>
                <?php Welow_Helpers::get_template( 'coche-destacados.php', array( 'data' => $data ) ); ?>
            <?php endif; ?>

            <?php if ( in_array( 'equipamiento', $bloques ) ) : ?>
                <?php Welow_Helpers::get_template( 'coche-equipamiento.php', array( 'data' => $data ) ); ?>
            <?php endif; ?>

            <?php if ( in_array( 'garantias', $bloques ) ) : ?>
                <?php Welow_Helpers::get_template( 'coche-garantias.php', array( 'data' => $data ) ); ?>
            <?php endif; ?>
        </div>

        <aside class="welow-coche-ficha__aside">
            <?php if ( in_array( 'precio', $bloques ) ) : ?>
                <?php Welow_Helpers::get_template( 'coche-precio.php', array( 'data' => $data ) ); ?>
            <?php endif; ?>

            <?php if ( in_array( 'concesionario', $bloques ) && $data['concesionario_id'] ) : ?>
                <?php Welow_Helpers::get_template( 'coche-concesionario.php', array( 'data' => $data ) ); ?>
            <?php endif; ?>
        </aside>

    </div>

</article>
