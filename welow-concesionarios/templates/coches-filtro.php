<?php
/**
 * Template: Página de filtros + listado de coches.
 *
 * @var array $atts      Atributos del shortcode
 * @var mixed $cpt       CPT(s) usados
 * @var array $resultado Datos de paginación (posts, total, paginas_total, pagina_actual, por_pagina)
 * @var int   $columnas  Columnas en desktop
 * @var array $filtros   Filtros a mostrar
 * @var array $valores   Valores actuales de los filtros (para repoblar)
 *
 * @since 2.8.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Datos para selectores
$marcas_oficiales = get_posts( array( 'post_type' => 'welow_marca', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
$marcas_externas  = get_terms( array( 'taxonomy' => 'welow_marca_externa', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ) );
$combustibles     = get_terms( array( 'taxonomy' => 'welow_combustible', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ) );
$carrocerias      = get_terms( array( 'taxonomy' => 'welow_categoria_modelo', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ) );

$cambios = array(
    'manual'         => 'Manual',
    'automatico'     => 'Automático',
    'semiautomatico' => 'Semiautomático',
);

$moneda = class_exists( 'Welow_Settings' ) ? Welow_Settings::get( 'moneda_simbolo', '€' ) : '€';

// Determinar el tipo según CPT
$es_solo_ocasion = ( 'welow_coche_ocasion' === $cpt );
$es_solo_nuevos  = ( 'welow_coche_nuevo' === $cpt );

// Form action: la URL de la página actual SIN params (para resetear paginación al filtrar)
$form_action = strtok( $_SERVER['REQUEST_URI'] ?? '', '?' );

// Helpers de UI
$is_checked = function( $array, $value ) {
    return is_array( $array ) && in_array( $value, $array, true );
};
?>
<div class="welow-coches-filtro">

    <?php if ( $atts['titulo'] || $atts['subtitulo'] ) : ?>
        <header class="welow-cf__cabecera">
            <?php if ( $atts['titulo'] ) : ?><h1 class="welow-cf__titulo"><?php echo esc_html( $atts['titulo'] ); ?></h1><?php endif; ?>
            <?php if ( $atts['subtitulo'] ) : ?><p class="welow-cf__subtitulo"><?php echo esc_html( $atts['subtitulo'] ); ?></p><?php endif; ?>
        </header>
    <?php endif; ?>

    <!-- Botón abrir filtros (móvil) -->
    <button type="button" class="welow-cf__movil-toggle" aria-controls="welow-cf-sidebar" aria-expanded="false">
        <span class="dashicons dashicons-filter"></span>
        <span>Filtros</span>
        <?php
        // Contador de filtros activos (excluyendo orden)
        $activos = 0;
        foreach ( array( 'marca', 'tipo_venta', 'precio_min', 'precio_max', 'km_min', 'km_max', 'anio_min', 'anio_max', 'cv_min', 'cv_max' ) as $k ) {
            if ( ! empty( $valores[ $k ] ) && 'todos' !== $valores[ $k ] ) $activos++;
        }
        foreach ( array( 'marca_externa', 'combustible', 'carroceria', 'cambio' ) as $k ) {
            if ( ! empty( $valores[ $k ] ) ) $activos += count( $valores[ $k ] );
        }
        if ( $activos > 0 ) :
        ?>
            <span class="welow-cf__filtros-badge"><?php echo esc_html( $activos ); ?></span>
        <?php endif; ?>
    </button>

    <div class="welow-cf__layout">

        <!-- ===================== SIDEBAR DE FILTROS ===================== -->
        <aside class="welow-cf__sidebar" id="welow-cf-sidebar">

            <div class="welow-cf__sidebar-header">
                <h2>Filtrar por</h2>
                <button type="button" class="welow-cf__cerrar" aria-label="Cerrar filtros">×</button>
            </div>

            <form method="get" action="<?php echo esc_url( $form_action ); ?>" class="welow-cf__form">

                <?php // Mantener marca fija si la hay (sino el form la perdería) ?>
                <?php if ( ! empty( $atts['marca_fija'] ) ) : ?>
                    <input type="hidden" name="welow_marca" value="<?php echo esc_attr( $atts['marca_fija'] ); ?>" />
                <?php endif; ?>

                <?php // ============= MARCA OFICIAL ============= ?>
                <?php if ( in_array( 'marca', $filtros, true ) && empty( $atts['marca_fija'] ) && ! empty( $marcas_oficiales ) && ! $es_solo_ocasion ) : ?>
                    <fieldset class="welow-cf__grupo">
                        <legend>Marca <small>(catálogo)</small></legend>
                        <select name="welow_marca" class="welow-cf__select">
                            <option value="">Cualquiera</option>
                            <?php foreach ( $marcas_oficiales as $m ) : ?>
                                <option value="<?php echo esc_attr( $m->post_name ); ?>" <?php selected( $valores['marca'], $m->post_name ); ?>>
                                    <?php echo esc_html( $m->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </fieldset>
                <?php endif; ?>

                <?php // ============= MARCA EXTERNA ============= ?>
                <?php if ( in_array( 'marca', $filtros, true ) && empty( $atts['marca_externa_fija'] ) && ! empty( $marcas_externas ) && ! is_wp_error( $marcas_externas ) && ! $es_solo_nuevos ) : ?>
                    <fieldset class="welow-cf__grupo">
                        <legend>Marca</legend>
                        <div class="welow-cf__checks welow-cf__checks--scroll">
                            <?php foreach ( $marcas_externas as $term ) : ?>
                                <label class="welow-cf__check">
                                    <input type="checkbox" name="welow_marca_externa[]"
                                           value="<?php echo esc_attr( $term->slug ); ?>"
                                           <?php checked( $is_checked( $valores['marca_externa'], $term->slug ) ); ?> />
                                    <span><?php echo esc_html( $term->name ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endif; ?>

                <?php // ============= TIPO (ocasion/km0) — solo si CPT ocasión ============= ?>
                <?php if ( in_array( 'tipo', $filtros, true ) && $es_solo_ocasion ) : ?>
                    <fieldset class="welow-cf__grupo">
                        <legend>Tipo</legend>
                        <div class="welow-cf__checks">
                            <label class="welow-cf__check">
                                <input type="radio" name="welow_tipo_venta" value="todos" <?php checked( $valores['tipo_venta'], 'todos' ); ?> />
                                <span>Todos</span>
                            </label>
                            <label class="welow-cf__check">
                                <input type="radio" name="welow_tipo_venta" value="ocasion" <?php checked( $valores['tipo_venta'], 'ocasion' ); ?> />
                                <span>Ocasión</span>
                            </label>
                            <label class="welow-cf__check">
                                <input type="radio" name="welow_tipo_venta" value="km0" <?php checked( $valores['tipo_venta'], 'km0' ); ?> />
                                <span>KM0</span>
                            </label>
                        </div>
                    </fieldset>
                <?php endif; ?>

                <?php // ============= PRECIO ============= ?>
                <?php if ( in_array( 'precio', $filtros, true ) ) : ?>
                    <fieldset class="welow-cf__grupo">
                        <legend>Precio (<?php echo esc_html( $moneda ); ?>)</legend>
                        <div class="welow-cf__rango">
                            <input type="number" name="welow_precio_min" placeholder="Desde"
                                   value="<?php echo esc_attr( $valores['precio_min'] ); ?>" min="0" step="500" />
                            <span class="welow-cf__rango-sep">—</span>
                            <input type="number" name="welow_precio_max" placeholder="Hasta"
                                   value="<?php echo esc_attr( $valores['precio_max'] ); ?>" min="0" step="500" />
                        </div>
                    </fieldset>
                <?php endif; ?>

                <?php // ============= CARROCERÍA ============= ?>
                <?php if ( in_array( 'carroceria', $filtros, true ) && ! empty( $carrocerias ) && ! is_wp_error( $carrocerias ) ) : ?>
                    <fieldset class="welow-cf__grupo">
                        <legend>Carrocería</legend>
                        <div class="welow-cf__checks">
                            <?php foreach ( $carrocerias as $term ) : ?>
                                <label class="welow-cf__check">
                                    <input type="checkbox" name="welow_carroceria[]"
                                           value="<?php echo esc_attr( $term->slug ); ?>"
                                           <?php checked( $is_checked( $valores['carroceria'], $term->slug ) ); ?> />
                                    <span><?php echo esc_html( $term->name ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endif; ?>

                <?php // ============= COMBUSTIBLE ============= ?>
                <?php if ( in_array( 'combustible', $filtros, true ) && ! empty( $combustibles ) && ! is_wp_error( $combustibles ) ) : ?>
                    <fieldset class="welow-cf__grupo">
                        <legend>Combustible</legend>
                        <div class="welow-cf__checks">
                            <?php foreach ( $combustibles as $term ) : ?>
                                <label class="welow-cf__check">
                                    <input type="checkbox" name="welow_combustible[]"
                                           value="<?php echo esc_attr( $term->slug ); ?>"
                                           <?php checked( $is_checked( $valores['combustible'], $term->slug ) ); ?> />
                                    <span><?php echo esc_html( $term->name ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endif; ?>

                <?php // ============= CAMBIO ============= ?>
                <?php if ( in_array( 'cambio', $filtros, true ) ) : ?>
                    <fieldset class="welow-cf__grupo">
                        <legend>Cambio</legend>
                        <div class="welow-cf__checks">
                            <?php foreach ( $cambios as $key => $label ) : ?>
                                <label class="welow-cf__check">
                                    <input type="checkbox" name="welow_cambio[]"
                                           value="<?php echo esc_attr( $key ); ?>"
                                           <?php checked( $is_checked( $valores['cambio'], $key ) ); ?> />
                                    <span><?php echo esc_html( $label ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endif; ?>

                <?php // ============= AÑO ============= ?>
                <?php if ( in_array( 'anio', $filtros, true ) && $es_solo_ocasion ) : ?>
                    <fieldset class="welow-cf__grupo">
                        <legend>Año</legend>
                        <div class="welow-cf__rango">
                            <input type="number" name="welow_anio_min" placeholder="Desde"
                                   value="<?php echo esc_attr( $valores['anio_min'] ); ?>"
                                   min="1990" max="<?php echo intval( date( 'Y' ) ); ?>" />
                            <span class="welow-cf__rango-sep">—</span>
                            <input type="number" name="welow_anio_max" placeholder="Hasta"
                                   value="<?php echo esc_attr( $valores['anio_max'] ); ?>"
                                   min="1990" max="<?php echo intval( date( 'Y' ) ); ?>" />
                        </div>
                    </fieldset>
                <?php endif; ?>

                <?php // ============= KM ============= ?>
                <?php if ( in_array( 'km', $filtros, true ) && $es_solo_ocasion ) : ?>
                    <fieldset class="welow-cf__grupo">
                        <legend>Kilómetros</legend>
                        <div class="welow-cf__rango">
                            <input type="number" name="welow_km_min" placeholder="Desde"
                                   value="<?php echo esc_attr( $valores['km_min'] ); ?>" min="0" step="5000" />
                            <span class="welow-cf__rango-sep">—</span>
                            <input type="number" name="welow_km_max" placeholder="Hasta"
                                   value="<?php echo esc_attr( $valores['km_max'] ); ?>" min="0" step="5000" />
                        </div>
                    </fieldset>
                <?php endif; ?>

                <?php // ============= POTENCIA (CV) ============= ?>
                <?php if ( in_array( 'cv', $filtros, true ) ) : ?>
                    <fieldset class="welow-cf__grupo">
                        <legend>Potencia (CV)</legend>
                        <div class="welow-cf__rango">
                            <input type="number" name="welow_cv_min" placeholder="Desde"
                                   value="<?php echo esc_attr( $valores['cv_min'] ); ?>" min="0" step="10" />
                            <span class="welow-cf__rango-sep">—</span>
                            <input type="number" name="welow_cv_max" placeholder="Hasta"
                                   value="<?php echo esc_attr( $valores['cv_max'] ); ?>" min="0" step="10" />
                        </div>
                    </fieldset>
                <?php endif; ?>

                <div class="welow-cf__acciones">
                    <button type="submit" class="welow-btn welow-btn-primary welow-btn-grande">Aplicar filtros</button>
                    <a href="<?php echo esc_url( $form_action ); ?>" class="welow-cf__limpiar">Limpiar filtros</a>
                </div>

            </form>
        </aside>

        <!-- ===================== RESULTADOS ===================== -->
        <main class="welow-cf__main">

            <div class="welow-cf__main-header">
                <div class="welow-cf__total">
                    <strong><?php echo esc_html( $resultado['total'] ); ?></strong>
                    <?php echo esc_html( 1 === $resultado['total'] ? 'resultado' : 'resultados' ); ?>
                </div>

                <div class="welow-cf__orden">
                    <label for="welow-orden">Ordenar:</label>
                    <select id="welow-orden" name="welow_orden" data-welow-autosubmit>
                        <option value="recientes"   <?php selected( $valores['orden'], 'recientes' ); ?>>Más recientes</option>
                        <option value="precio_asc"  <?php selected( $valores['orden'], 'precio_asc' ); ?>>Precio: más bajo</option>
                        <option value="precio_desc" <?php selected( $valores['orden'], 'precio_desc' ); ?>>Precio: más alto</option>
                        <?php if ( $es_solo_ocasion || ! $es_solo_nuevos ) : ?>
                            <option value="km_asc"      <?php selected( $valores['orden'], 'km_asc' ); ?>>Menos kilómetros</option>
                            <option value="anio_desc"   <?php selected( $valores['orden'], 'anio_desc' ); ?>>Año más reciente</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <?php if ( empty( $resultado['posts'] ) ) : ?>
                <div class="welow-cf__sin-resultados">
                    <p>No se encontraron coches con los filtros seleccionados.</p>
                    <a href="<?php echo esc_url( $form_action ); ?>" class="welow-btn welow-btn-primary">Limpiar filtros</a>
                </div>
            <?php else : ?>
                <?php
                // Renderizar el grid según el CPT (nuevos / ocasión / mixto)
                $template = $es_solo_nuevos ? 'coches-grid-nuevos.php' : 'coches-grid-ocasion.php';
                Welow_Helpers::get_template( $template, array(
                    'coches'          => $resultado['posts'],
                    'columnas'        => $columnas,
                    'columnas_tablet' => max( 1, min( 2, $columnas ) ),
                    'columnas_movil'  => 1,
                ) );
                ?>

                <?php // ============= PAGINACIÓN ============= ?>
                <?php if ( $resultado['paginas_total'] > 1 ) :
                    $current = $resultado['pagina_actual'];
                    $total_p = $resultado['paginas_total'];

                    // Generar URL de cada página manteniendo los filtros actuales
                    $base_url = remove_query_arg( 'welow_paged' );
                    $page_url = function( $n ) use ( $base_url ) {
                        return add_query_arg( 'welow_paged', $n, $base_url );
                    };

                    // Rango de páginas a mostrar (ej: 1 ... 4 5 6 ... 12)
                    $rango = 2;
                    $paginas = array();
                    for ( $i = 1; $i <= $total_p; $i++ ) {
                        if ( $i === 1 || $i === $total_p || ( $i >= $current - $rango && $i <= $current + $rango ) ) {
                            $paginas[] = $i;
                        }
                    }
                ?>
                    <nav class="welow-cf__paginacion" aria-label="Paginación">
                        <?php if ( $current > 1 ) : ?>
                            <a class="welow-cf__pag welow-cf__pag--prev" href="<?php echo esc_url( $page_url( $current - 1 ) ); ?>" aria-label="Página anterior">‹</a>
                        <?php endif; ?>

                        <?php
                        $prev_pag = 0;
                        foreach ( $paginas as $p ) :
                            if ( $prev_pag && $p > $prev_pag + 1 ) :
                        ?>
                                <span class="welow-cf__pag-sep">…</span>
                        <?php
                            endif;
                            if ( $p === $current ) :
                        ?>
                                <span class="welow-cf__pag welow-cf__pag--actual" aria-current="page"><?php echo esc_html( $p ); ?></span>
                        <?php
                            else :
                        ?>
                                <a class="welow-cf__pag" href="<?php echo esc_url( $page_url( $p ) ); ?>"><?php echo esc_html( $p ); ?></a>
                        <?php
                            endif;
                            $prev_pag = $p;
                        endforeach;
                        ?>

                        <?php if ( $current < $total_p ) : ?>
                            <a class="welow-cf__pag welow-cf__pag--next" href="<?php echo esc_url( $page_url( $current + 1 ) ); ?>" aria-label="Página siguiente">›</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>

            <?php endif; ?>

        </main>
    </div>

    <!-- Backdrop del drawer móvil -->
    <div class="welow-cf__backdrop" aria-hidden="true"></div>
</div>
