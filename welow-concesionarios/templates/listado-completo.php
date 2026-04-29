<?php
/**
 * Template: Listado completo de datos para chatbots/crawlers.
 *
 * Diseñado para LLMs y crawlers: HTML mínimo, sin estilos, con
 * estructura semántica clara (<article>, <dl>, <dt>, <dd>) y atributos
 * data-* para fácil parseo.
 *
 * @var array  $datos      Lista de items
 * @var string $tipo       nuevos | ocasion | todos | modelos | marcas
 * @var bool   $sin_html   true para texto plano, false para HTML
 * @var string $site_name
 * @var string $site_url
 *
 * @since 2.4.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$total = count( $datos );

// Helpers locales para formateo
$fmt_precio = function( $valor ) {
    return $valor !== null ? number_format_i18n( $valor, 0 ) . ' €' : '';
};
$fmt_km = function( $valor ) {
    return $valor !== null ? number_format_i18n( $valor ) . ' km' : '';
};
$fmt_potencia = function( $cv, $kw ) {
    $parts = array();
    if ( $cv !== null ) $parts[] = $cv . ' CV';
    if ( $kw !== null ) $parts[] = round( $kw, 2 ) . ' kW';
    return implode( ' / ', $parts );
};

if ( $sin_html ) {
    // ================== MODO TEXTO PLANO ==================
    echo "Datos de {$tipo} en {$site_name} ({$site_url})\n";
    echo "Total: {$total} elementos\n";
    echo "Generado: " . current_time( 'Y-m-d H:i:s' ) . "\n";
    echo str_repeat( '=', 60 ) . "\n\n";

    foreach ( $datos as $item ) {
        if ( in_array( $tipo, array( 'nuevos', 'ocasion', 'todos' ), true ) ) {
            echo "[{$item['tipo_label']}] {$item['marca']} {$item['modelo']}";
            if ( $item['version'] ) echo " — {$item['version']}";
            echo "\n";
            if ( $item['anio'] ) echo "  Año: {$item['matriculacion_str']}\n";
            if ( $item['km'] !== null ) echo "  Km: " . $fmt_km( $item['km'] ) . "\n";
            if ( $item['combustible'] ) echo "  Combustible: {$item['combustible']}\n";
            if ( $item['cambio_label'] ) echo "  Cambio: {$item['cambio_label']}" . ( $item['marchas'] ? " ({$item['marchas']} marchas)" : '' ) . "\n";
            $pot = $fmt_potencia( $item['cv'], $item['kw'] );
            if ( $pot ) echo "  Potencia: {$pot}\n";
            if ( $item['cilindrada_cc'] ) echo "  Cilindrada: " . number_format_i18n( $item['cilindrada_cc'] ) . " cc\n";
            if ( $item['plazas'] ) echo "  Plazas: {$item['plazas']}\n";
            if ( $item['carroceria'] ) echo "  Carrocería: {$item['carroceria']}\n";
            if ( $item['color'] ) echo "  Color: {$item['color']}" . ( $item['tipo_pintura_label'] ? " ({$item['tipo_pintura_label']})" : '' ) . "\n";
            if ( $item['etiqueta_dgt_label'] ) echo "  Etiqueta DGT: {$item['etiqueta_dgt_label']}\n";
            if ( $item['precio_contado'] !== null ) echo "  Precio: " . $fmt_precio( $item['precio_contado'] ) . "\n";
            if ( $item['cuota_mensual'] !== null ) echo "  Cuota: " . $fmt_precio( $item['cuota_mensual'] ) . "/mes\n";
            if ( $item['equipamiento_texto'] ) echo "  Equipamiento: {$item['equipamiento_texto']}\n";
            if ( $item['concesionario'] ) {
                echo "  Concesionario: {$item['concesionario']['nombre']}\n";
                if ( $item['concesionario']['direccion'] ) echo "    Dirección: {$item['concesionario']['direccion']}, {$item['concesionario']['cp']} {$item['concesionario']['ciudad']}\n";
                if ( $item['concesionario']['telefono'] ) echo "    Teléfono: {$item['concesionario']['telefono']}\n";
                if ( $item['concesionario']['email'] ) echo "    Email: {$item['concesionario']['email']}\n";
            }
            echo "  URL: {$item['url']}\n";
            echo "\n";
        }
    }
    return;
}
?>

<div class="welow-listado-completo" data-tipo="<?php echo esc_attr( $tipo ); ?>" data-total="<?php echo esc_attr( $total ); ?>">

    <header class="welow-listado-header">
        <h1>Listado de <?php
            echo esc_html( ucfirst( $tipo === 'todos' ? 'todos los coches' : ( $tipo === 'modelos' ? 'modelos del catálogo' : ( $tipo === 'marcas' ? 'marcas oficiales' : 'coches ' . $tipo ) ) ) );
        ?> — <?php echo esc_html( $site_name ); ?></h1>
        <p>Total: <strong data-count><?php echo esc_html( $total ); ?></strong> elementos. Datos actualizados: <?php echo esc_html( current_time( 'Y-m-d H:i' ) ); ?>.</p>
        <p>Sitio: <a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_url ); ?></a></p>
    </header>

    <?php if ( in_array( $tipo, array( 'nuevos', 'ocasion', 'todos' ), true ) ) : ?>

        <?php foreach ( $datos as $item ) : ?>
            <article class="welow-coche-data"
                     data-coche-id="<?php echo esc_attr( $item['id'] ); ?>"
                     data-tipo="<?php echo esc_attr( $item['tipo'] ); ?>"
                     data-marca="<?php echo esc_attr( $item['marca'] ); ?>"
                     data-modelo="<?php echo esc_attr( $item['modelo'] ); ?>">

                <h2><?php echo esc_html( trim( $item['marca'] . ' ' . $item['modelo'] ) ); ?>
                    <?php if ( $item['anio'] ) : ?>(<?php echo esc_html( $item['anio'] ); ?>)<?php endif; ?>
                </h2>

                <p class="welow-coche-data__resumen">
                    <strong><?php echo esc_html( $item['tipo_label'] ); ?></strong>
                    <?php if ( $item['version'] ) : ?> — <?php echo esc_html( $item['version'] ); endif; ?>
                </p>

                <dl class="welow-coche-data__especificaciones">
                    <?php if ( $item['referencia'] ) : ?>
                        <dt>Referencia</dt><dd><?php echo esc_html( $item['referencia'] ); ?></dd>
                    <?php endif; ?>

                    <dt>Marca</dt><dd><?php echo esc_html( $item['marca'] ); ?></dd>
                    <dt>Modelo</dt><dd><?php echo esc_html( $item['modelo'] ); ?></dd>

                    <?php if ( $item['version'] ) : ?>
                        <dt>Versión</dt><dd><?php echo esc_html( $item['version'] ); ?></dd>
                    <?php endif; ?>

                    <?php if ( $item['matriculacion_str'] ) : ?>
                        <dt>Matriculación</dt><dd><?php echo esc_html( $item['matriculacion_str'] ); ?></dd>
                    <?php endif; ?>

                    <?php if ( $item['km'] !== null ) : ?>
                        <dt>Kilómetros</dt><dd><?php echo esc_html( $fmt_km( $item['km'] ) ); ?></dd>
                    <?php endif; ?>

                    <?php if ( $item['combustible'] ) : ?>
                        <dt>Combustible</dt><dd><?php echo esc_html( $item['combustible'] ); ?></dd>
                    <?php endif; ?>

                    <?php if ( $item['cambio_label'] ) : ?>
                        <dt>Cambio</dt>
                        <dd><?php echo esc_html( $item['cambio_label'] ); ?>
                            <?php if ( $item['marchas'] ) : ?> (<?php echo esc_html( $item['marchas'] ); ?> marchas)<?php endif; ?>
                        </dd>
                    <?php endif; ?>

                    <?php if ( $item['cv'] !== null || $item['kw'] !== null ) : ?>
                        <dt>Potencia</dt><dd><?php echo esc_html( $fmt_potencia( $item['cv'], $item['kw'] ) ); ?></dd>
                    <?php endif; ?>

                    <?php if ( $item['cilindrada_cc'] !== null ) : ?>
                        <dt>Cilindrada</dt><dd><?php echo esc_html( number_format_i18n( $item['cilindrada_cc'] ) . ' cc' ); ?></dd>
                    <?php endif; ?>

                    <?php if ( $item['plazas'] !== null ) : ?>
                        <dt>Plazas</dt><dd><?php echo esc_html( $item['plazas'] ); ?></dd>
                    <?php endif; ?>

                    <?php if ( $item['puertas'] !== null ) : ?>
                        <dt>Puertas</dt><dd><?php echo esc_html( $item['puertas'] ); ?></dd>
                    <?php endif; ?>

                    <?php if ( $item['carroceria'] ) : ?>
                        <dt>Carrocería</dt><dd><?php echo esc_html( $item['carroceria'] ); ?></dd>
                    <?php endif; ?>

                    <?php if ( $item['color'] ) : ?>
                        <dt>Color</dt>
                        <dd><?php echo esc_html( $item['color'] ); ?>
                            <?php if ( $item['tipo_pintura_label'] ) : ?> (<?php echo esc_html( $item['tipo_pintura_label'] ); ?>)<?php endif; ?>
                        </dd>
                    <?php endif; ?>

                    <?php if ( $item['etiqueta_dgt_label'] ) : ?>
                        <dt>Etiqueta DGT</dt><dd><?php echo esc_html( $item['etiqueta_dgt_label'] ); ?></dd>
                    <?php endif; ?>

                    <?php if ( $item['precio_contado'] !== null ) : ?>
                        <dt>Precio al contado</dt><dd><?php echo esc_html( $fmt_precio( $item['precio_contado'] ) ); ?></dd>
                    <?php endif; ?>

                    <?php if ( $item['precio_financiado'] !== null && $item['precio_financiado'] != $item['precio_contado'] ) : ?>
                        <dt>Precio financiado</dt><dd><?php echo esc_html( $fmt_precio( $item['precio_financiado'] ) ); ?></dd>
                    <?php endif; ?>

                    <?php if ( $item['precio_anterior'] !== null ) : ?>
                        <dt>Precio anterior</dt><dd><?php echo esc_html( $fmt_precio( $item['precio_anterior'] ) ); ?></dd>
                    <?php endif; ?>

                    <?php if ( $item['cuota_mensual'] !== null ) : ?>
                        <dt>Cuota mensual</dt><dd><?php echo esc_html( $fmt_precio( $item['cuota_mensual'] ) ); ?></dd>
                    <?php endif; ?>

                    <?php if ( $item['programa'] ) : ?>
                        <dt>Programa</dt><dd><?php echo esc_html( $item['programa'] ); ?></dd>
                    <?php endif; ?>

                    <dt>Estado</dt><dd><?php echo esc_html( $item['estado'] ); ?></dd>

                    <dt>Ficha completa</dt>
                    <dd><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['url'] ); ?></a></dd>
                </dl>

                <?php if ( $item['equipamiento_texto'] ) : ?>
                    <div class="welow-coche-data__equipamiento">
                        <h3>Equipamiento</h3>
                        <?php echo wp_kses_post( $item['equipamiento_html'] ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( $item['garantias_texto'] ) : ?>
                    <div class="welow-coche-data__garantias">
                        <h3>Garantías</h3>
                        <?php echo wp_kses_post( $item['garantias_html'] ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( $item['concesionario'] ) :
                    $c = $item['concesionario'];
                ?>
                    <div class="welow-coche-data__concesionario">
                        <h3>Concesionario</h3>
                        <dl>
                            <dt>Nombre</dt><dd><?php echo esc_html( $c['nombre'] ); ?></dd>
                            <?php if ( $c['direccion'] ) : ?>
                                <dt>Dirección</dt>
                                <dd><?php echo esc_html( $c['direccion'] ); ?>
                                    <?php if ( $c['cp'] || $c['ciudad'] ) : ?>, <?php echo esc_html( trim( $c['cp'] . ' ' . $c['ciudad'] ) ); endif; ?>
                                    <?php if ( $c['provincia'] ) : ?> (<?php echo esc_html( $c['provincia'] ); ?>)<?php endif; ?>
                                </dd>
                            <?php endif; ?>
                            <?php if ( $c['telefono'] ) : ?>
                                <dt>Teléfono</dt><dd><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $c['telefono'] ) ); ?>"><?php echo esc_html( $c['telefono'] ); ?></a></dd>
                            <?php endif; ?>
                            <?php if ( $c['email'] ) : ?>
                                <dt>Email</dt><dd><a href="mailto:<?php echo esc_attr( $c['email'] ); ?>"><?php echo esc_html( $c['email'] ); ?></a></dd>
                            <?php endif; ?>
                            <?php if ( $c['horario'] ) : ?>
                                <dt>Horario</dt><dd><?php echo nl2br( esc_html( $c['horario'] ) ); ?></dd>
                            <?php endif; ?>
                            <?php if ( $c['lat'] && $c['lng'] ) : ?>
                                <dt>Mapa</dt>
                                <dd><a href="https://www.google.com/maps/search/?api=1&query=<?php echo esc_attr( $c['lat'] . ',' . $c['lng'] ); ?>" target="_blank">Ver en Google Maps</a></dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                <?php endif; ?>

                <?php if ( $item['imagen_principal'] ) : ?>
                    <p class="welow-coche-data__imagen"><img src="<?php echo esc_url( $item['imagen_principal'] ); ?>" alt="<?php echo esc_attr( trim( $item['marca'] . ' ' . $item['modelo'] ) ); ?>" loading="lazy" width="600" /></p>
                <?php endif; ?>

            </article>
        <?php endforeach; ?>

    <?php elseif ( 'modelos' === $tipo ) : ?>

        <?php foreach ( $datos as $modelo ) : ?>
            <article class="welow-modelo-data" data-modelo-id="<?php echo esc_attr( $modelo['id'] ); ?>" data-marca="<?php echo esc_attr( $modelo['marca'] ); ?>">
                <h2><?php echo esc_html( $modelo['marca'] . ' ' . $modelo['titulo'] ); ?></h2>
                <dl>
                    <dt>Marca</dt><dd><?php echo esc_html( $modelo['marca'] ); ?></dd>
                    <dt>Modelo</dt><dd><?php echo esc_html( $modelo['titulo'] ); ?></dd>
                    <?php if ( $modelo['extracto'] ) : ?><dt>Resumen</dt><dd><?php echo esc_html( $modelo['extracto'] ); ?></dd><?php endif; ?>
                    <?php if ( $modelo['descripcion'] ) : ?><dt>Descripción</dt><dd><?php echo esc_html( $modelo['descripcion'] ); ?></dd><?php endif; ?>
                    <?php if ( $modelo['precio_desde'] ) : ?><dt>Precio desde</dt><dd><?php echo esc_html( $fmt_precio( $modelo['precio_desde'] ) ); ?></dd><?php endif; ?>
                    <?php if ( $modelo['plazas'] ) : ?><dt>Plazas</dt><dd><?php echo esc_html( $modelo['plazas'] ); ?></dd><?php endif; ?>
                    <?php if ( ! empty( $modelo['combustibles'] ) ) : ?><dt>Combustibles disponibles</dt><dd><?php echo esc_html( implode( ', ', $modelo['combustibles'] ) ); ?></dd><?php endif; ?>
                    <?php if ( ! empty( $modelo['carrocerias'] ) ) : ?><dt>Carrocería</dt><dd><?php echo esc_html( implode( ', ', $modelo['carrocerias'] ) ); ?></dd><?php endif; ?>
                    <dt>URL</dt><dd><a href="<?php echo esc_url( $modelo['url'] ); ?>"><?php echo esc_html( $modelo['url'] ); ?></a></dd>
                </dl>
            </article>
        <?php endforeach; ?>

    <?php elseif ( 'marcas' === $tipo ) : ?>

        <?php foreach ( $datos as $marca ) : ?>
            <article class="welow-marca-data" data-marca-id="<?php echo esc_attr( $marca['id'] ); ?>">
                <h2><?php echo esc_html( $marca['titulo'] ); ?></h2>
                <dl>
                    <?php if ( $marca['slogan'] ) : ?><dt>Slogan</dt><dd><?php echo esc_html( $marca['slogan'] ); ?></dd><?php endif; ?>
                    <?php if ( $marca['desc_corta'] ) : ?><dt>Descripción corta</dt><dd><?php echo esc_html( $marca['desc_corta'] ); ?></dd><?php endif; ?>
                    <?php if ( $marca['descripcion'] ) : ?><dt>Descripción</dt><dd><?php echo esc_html( $marca['descripcion'] ); ?></dd><?php endif; ?>
                    <?php if ( $marca['web'] ) : ?><dt>Web oficial</dt><dd><a href="<?php echo esc_url( $marca['web'] ); ?>"><?php echo esc_html( $marca['web'] ); ?></a></dd><?php endif; ?>
                    <dt>Modelos disponibles (<?php echo esc_html( $marca['modelos_count'] ); ?>)</dt>
                    <dd><?php echo esc_html( implode( ', ', $marca['modelos'] ) ?: '—' ); ?></dd>
                    <dt>URL página marca</dt><dd><a href="<?php echo esc_url( $marca['url'] ); ?>"><?php echo esc_html( $marca['url'] ); ?></a></dd>
                </dl>
            </article>
        <?php endforeach; ?>

    <?php endif; ?>

    <footer>
        <p><small>Listado generado por Welow Concesionarios v<?php echo esc_html( WELOW_CONC_VERSION ); ?>. <?php echo esc_html( current_time( 'Y-m-d H:i:s' ) ); ?>.</small></p>
    </footer>

</div>
