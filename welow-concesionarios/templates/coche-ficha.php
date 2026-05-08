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

$coche       = $data['post'];
$es_nuevo    = $data['es_nuevo'];
$marca_nom   = $data['marca_nombre'];
$modelo_nom  = $data['modelo_nombre'];

$titulo  = trim( $marca_nom . ' ' . $modelo_nom );
$version = Welow_Helpers::get_coche_meta( $coche->ID, 'version' );
$tipo_venta = Welow_Helpers::get_coche_meta( $coche->ID, 'tipo_venta' );

// Etiqueta de tipo según CPT
if ( $es_nuevo ) {
    $tipo_label = 'Nuevo';
    $tipo_class = 'nuevo';
} else {
    $tipos_ocasion = array( 'ocasion' => 'Ocasión', 'km0' => 'KM0' );
    $tipo_label = $tipos_ocasion[ $tipo_venta ] ?? 'Ocasión';
    $tipo_class = $tipo_venta ?: 'ocasion';
}
?>
<article class="welow-coche-ficha">

    <header class="welow-coche-ficha__header">
        <div class="welow-coche-ficha__header-titulos">
            <h1 class="welow-coche-ficha__titulo"><?php echo esc_html( $titulo ); ?></h1>
            <?php if ( $version ) : ?>
                <p class="welow-coche-ficha__version"><?php echo esc_html( $version ); ?></p>
            <?php endif; ?>
        </div>
        <span class="welow-coche-tipo welow-tipo-<?php echo esc_attr( $tipo_class ); ?>">
            <?php echo esc_html( $tipo_label ); ?>
        </span>
    </header>

    <div class="welow-coche-ficha__layout">

        <div class="welow-coche-ficha__main">
            <?php if ( in_array( 'galeria', $bloques ) ) : ?>
                <?php Welow_Helpers::get_template( 'coche-galeria.php', array( 'data' => $data ) ); ?>
            <?php endif; ?>

            <?php // v2.12.0 — Bloque resaltado (rótulo + características principales) ?>
            <?php if ( in_array( 'resaltado', $bloques ) ) : ?>
                <?php Welow_Helpers::get_template( 'coche-resaltado.php', array( 'data' => $data ) ); ?>
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

            <?php // v2.5.1 — Formulario integrado justo debajo del precio (en el aside, más compacto) ?>
            <?php if ( in_array( 'formulario', $bloques ) && class_exists( 'Welow_Shortcode_Coche_Extras' ) ) : ?>
                <div class="welow-coche-ficha__formulario-wrap">
                    <?php echo Welow_Shortcode_Coche_Extras::render_formulario( array( 'titulo' => '¿Te interesa?', 'mostrar_ref' => 'no' ) ); ?>
                </div>
            <?php endif; ?>

            <?php if ( in_array( 'concesionario', $bloques ) && $data['concesionario_id'] ) : ?>
                <?php Welow_Helpers::get_template( 'coche-concesionario.php', array( 'data' => $data ) ); ?>
            <?php endif; ?>
        </aside>

    </div>

</article>
