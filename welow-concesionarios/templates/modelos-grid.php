<?php
/**
 * Template: Grid de modelos de vehículos.
 *
 * Variables disponibles:
 * @var WP_Post[] $modelos         Array de modelos.
 * @var int       $columnas        Columnas en desktop.
 * @var int       $columnas_tablet Columnas en tablet.
 * @var int       $columnas_movil  Columnas en móvil.
 * @var string    $texto_boton     Texto del CTA.
 *
 * @since 1.1.0
 * @version 2.10.0 — Card rediseñada:
 *   - Eliminadas badges de carrocería (solo se muestra combustible)
 *   - Etiquetas visuales (welow_etiqueta) movidas DEBAJO del nombre del modelo (ya no flotan sobre la imagen)
 *   - Título del modelo MÁS GRANDE y destacado (h2 + tipografía aumentada)
 *   - Botón "Ver modelo" reposicionado a esquina inferior DERECHA, más pequeño y discreto
 *   - Nuevo campo "rótulo" destacado (texto opcional encima del título)
 *
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$moneda               = class_exists( 'Welow_Settings' ) ? Welow_Settings::get( 'moneda_simbolo', '€' ) : '€';
$disclaimer_global    = class_exists( 'Welow_Settings' ) ? Welow_Settings::get( 'disclaimer_global', '' ) : '';
$icono_disclaimer_id  = class_exists( 'Welow_Settings' ) ? intval( Welow_Settings::get( 'disclaimer_icono', 0 ) ) : 0;
$icono_disclaimer_url = $icono_disclaimer_id ? wp_get_attachment_image_url( $icono_disclaimer_id, 'thumbnail' ) : '';
?>
<div class="welow-modelos-grid"
     style="--welow-cols: <?php echo esc_attr( $columnas ); ?>;
            --welow-cols-tablet: <?php echo esc_attr( $columnas_tablet ); ?>;
            --welow-cols-movil: <?php echo esc_attr( $columnas_movil ); ?>;">

    <?php foreach ( $modelos as $modelo ) :
        $img_url      = get_the_post_thumbnail_url( $modelo->ID, 'large' );
        $nombre       = get_the_title( $modelo->ID );
        $descripcion  = get_the_excerpt( $modelo->ID );
        // v2.22.0 — "Ver modelo" SOLO se renderiza si hay enlace propio definido
        // (antes caía a get_permalink() y abría una ficha vacía del CPT).
        $enlace       = Welow_Helpers::get_modelo_meta( $modelo->ID, 'enlace' );
        $texto_enlace = Welow_Helpers::get_modelo_meta( $modelo->ID, 'texto_enlace', $texto_boton );
        // v2.33.0 — Botón "¡Me interesa!" — siempre usa la página configurada en
        // Configuraciones → Formularios → "Página del botón Me Interesa"
        // con ?modelo=slug. Si no hay página configurada, no se muestra el botón.
        $interesa_url = '';
        if ( class_exists( 'Welow_Settings' ) ) {
            $_opt = get_option( Welow_Settings::OPTION_KEY, array() );
            $_page_id = intval( $_opt['formularios']['me_interesa_page'] ?? 0 );
            if ( $_page_id ) {
                $_page_url = get_permalink( $_page_id );
                if ( $_page_url ) {
                    $interesa_url = add_query_arg( 'modelo', $modelo->post_name, $_page_url );
                }
            }
        }

        $precio_desde = Welow_Helpers::get_modelo_meta( $modelo->ID, 'precio_desde' );
        $disclaimer   = Welow_Helpers::get_modelo_disclaimer( $modelo->ID );
        $etiquetas    = Welow_Helpers::get_etiquetas_modelo( $modelo->ID );

        // Combustible (taxonomía)
        $combustibles = wp_get_post_terms( $modelo->ID, 'welow_combustible' );

        // v2.10.0 — Rótulo destacado opcional
        $rotulo       = Welow_Helpers::get_modelo_meta( $modelo->ID, 'rotulo' );
        $rotulo_color = Welow_Helpers::get_modelo_meta( $modelo->ID, 'rotulo_color' );

        // v2.13.0 — Características principales (lista de bullets)
        $caracteristicas = Welow_Helpers::get_modelo_meta( $modelo->ID, 'caracteristicas' );
        $caract_lineas   = array();
        if ( $caracteristicas ) {
            $caract_lineas = array_filter( array_map( 'trim', preg_split( "/\r\n|\n|\r/", $caracteristicas ) ) );
        }

        // Estilo inline para el color del rótulo (si está definido)
        $rotulo_style = $rotulo_color ? ' style="background:' . esc_attr( $rotulo_color ) . ';"' : '';
    ?>
        <article class="welow-modelo-card<?php echo $rotulo ? ' welow-modelo-card--con-rotulo' : ''; ?>">

            <?php
            // v2.24.0 — Galería de hasta 5 imágenes (destacada + img_2..img_5) como slider scroll-snap
            $galeria_urls = array();
            if ( $img_url ) $galeria_urls[] = $img_url;
            for ( $n = 2; $n <= 5; $n++ ) {
                $img_id_n = get_post_meta( $modelo->ID, '_welow_modelo_img_' . $n, true );
                if ( $img_id_n ) {
                    $u = wp_get_attachment_image_url( $img_id_n, 'large' );
                    if ( $u ) $galeria_urls[] = $u;
                }
            }
            $hay_galeria = count( $galeria_urls ) > 1;
            ?>
            <div class="welow-modelo-card__imagen<?php echo $hay_galeria ? ' welow-modelo-card__imagen--slider' : ''; ?>">
                <?php if ( ! empty( $galeria_urls ) ) : ?>
                    <?php if ( $hay_galeria ) : ?>
                        <div class="welow-modelo-slider" data-welow-slider>
                            <div class="welow-modelo-slider__track">
                                <?php foreach ( $galeria_urls as $idx => $u ) : ?>
                                    <div class="welow-modelo-slider__slide">
                                        <img src="<?php echo esc_url( $u ); ?>"
                                             alt="<?php echo esc_attr( $nombre . ' — imagen ' . ( $idx + 1 ) ); ?>"
                                             loading="<?php echo $idx === 0 ? 'eager' : 'lazy'; ?>" />
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="welow-modelo-slider__nav welow-modelo-slider__nav--prev" aria-label="Anterior">‹</button>
                            <button type="button" class="welow-modelo-slider__nav welow-modelo-slider__nav--next" aria-label="Siguiente">›</button>
                            <div class="welow-modelo-slider__dots" role="tablist">
                                <?php foreach ( $galeria_urls as $idx => $u ) : ?>
                                    <button type="button"
                                            class="welow-modelo-slider__dot<?php echo $idx === 0 ? ' is-active' : ''; ?>"
                                            data-index="<?php echo intval( $idx ); ?>"
                                            aria-label="Imagen <?php echo intval( $idx + 1 ); ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else : ?>
                        <img src="<?php echo esc_url( $galeria_urls[0] ); ?>"
                             alt="<?php echo esc_attr( $nombre ); ?>"
                             loading="lazy" />
                    <?php endif; ?>
                <?php else : ?>
                    <div class="welow-modelo-card__placeholder">
                        <span class="dashicons dashicons-car"></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="welow-modelo-card__info">

                <?php // v2.10.0 — Rótulo destacado (opcional, encima del título) ?>
                <?php if ( $rotulo ) : ?>
                    <span class="welow-modelo-card__rotulo"<?php echo $rotulo_style; ?>>
                        <?php echo esc_html( $rotulo ); ?>
                    </span>
                <?php endif; ?>

                <?php // v2.10.0 — Título grande (h2). v2.22.0 — solo enlazado si hay $enlace propio ?>
                <h2 class="welow-modelo-card__nombre">
                    <?php if ( $enlace ) : ?>
                        <a href="<?php echo esc_url( $enlace ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $nombre ); ?></a>
                    <?php else : ?>
                        <?php echo esc_html( $nombre ); ?>
                    <?php endif; ?>
                </h2>

                <?php // v2.14.0 — Etiquetas DGT (70% tamaño) + combustible en MISMA fila (etiqueta primero) ?>
                <?php
                    $hay_etiquetas    = ! empty( $etiquetas );
                    $hay_combustibles = ! empty( $combustibles ) && ! is_wp_error( $combustibles );
                ?>
                <?php if ( $hay_etiquetas || $hay_combustibles ) : ?>
                    <div class="welow-modelo-card__etiquetas-row">
                        <?php if ( $hay_etiquetas ) : ?>
                            <?php foreach ( $etiquetas as $et ) :
                                $et_img_id = get_post_meta( $et->ID, '_welow_etiqueta_imagen', true );
                                if ( ! $et_img_id ) continue;
                                $et_img_url = wp_get_attachment_image_url( $et_img_id, 'medium' );
                            ?>
                                <img src="<?php echo esc_url( $et_img_url ); ?>"
                                     alt="<?php echo esc_attr( $et->post_title ); ?>"
                                     title="<?php echo esc_attr( $et->post_title ); ?>"
                                     class="welow-modelo-etiqueta"
                                     loading="lazy" />
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if ( $hay_combustibles ) : ?>
                            <?php foreach ( $combustibles as $c ) : ?>
                                <span class="welow-badge welow-badge--combustible"><?php echo esc_html( $c->name ); ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ( $descripcion ) : ?>
                    <p class="welow-modelo-card__desc"><?php echo esc_html( $descripcion ); ?></p>
                <?php endif; ?>

                <?php // v2.13.0 — Características principales (lista bullets) ?>
                <?php if ( ! empty( $caract_lineas ) ) : ?>
                    <ul class="welow-modelo-card__caracteristicas">
                        <?php foreach ( $caract_lineas as $linea ) : ?>
                            <li><?php echo esc_html( $linea ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php // v2.10.0 — Footer: precio (izq) + "Ver modelo" pequeño (der) ?>
                <div class="welow-modelo-card__footer">

                    <?php if ( '' !== $precio_desde && null !== $precio_desde ) : ?>
                        <div class="welow-modelo-card__precio">
                            <span class="welow-precio-label">Desde</span>
                            <span class="welow-precio-valor">
                                <?php echo esc_html( number_format_i18n( floatval( $precio_desde ), 0 ) ); ?> <?php echo esc_html( $moneda ); ?>
                            </span>

                            <?php if ( $disclaimer ) : ?>
                                <span class="welow-precio-disclaimer"
                                      tabindex="0"
                                      data-tooltip="<?php echo esc_attr( wp_strip_all_tags( $disclaimer ) ); ?>">
                                    <?php if ( $icono_disclaimer_url ) : ?>
                                        <img src="<?php echo esc_url( $icono_disclaimer_url ); ?>" alt="Aviso legal" width="16" height="16" />
                                    <?php else : ?>
                                        <span class="dashicons dashicons-info-outline"></span>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                        <span class="welow-modelo-card__precio-vacio"></span>
                    <?php endif; ?>

                    <?php // v2.22.0 — "Ver modelo" solo si hay enlace propio. Abre en pestaña nueva. ?>
                    <?php if ( $enlace ) : ?>
                        <a href="<?php echo esc_url( $enlace ); ?>"
                           class="welow-modelo-card__cta"
                           target="_blank" rel="noopener">
                            <?php echo esc_html( $texto_enlace ); ?>
                            <span aria-hidden="true">→</span>
                        </a>
                    <?php endif; ?>
                </div>

                <?php // v2.22.0 — Botón "¡Me interesa!" en parte inferior izquierda (solo si hay URL) ?>
                <?php if ( $interesa_url ) : ?>
                    <div class="welow-modelo-card__interesa-wrap">
                        <a href="<?php echo esc_url( $interesa_url ); ?>" class="welow-modelo-card__interesa">
                            ¡Me interesa!
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </article>
    <?php endforeach; ?>

</div>
