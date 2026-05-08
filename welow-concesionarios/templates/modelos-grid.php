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
        $enlace       = Welow_Helpers::get_modelo_meta( $modelo->ID, 'enlace' );
        $texto_enlace = Welow_Helpers::get_modelo_meta( $modelo->ID, 'texto_enlace', $texto_boton );
        $permalink    = $enlace ?: get_permalink( $modelo->ID );

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

            <div class="welow-modelo-card__imagen">
                <?php if ( $img_url ) : ?>
                    <a href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $nombre ); ?>">
                        <img src="<?php echo esc_url( $img_url ); ?>"
                             alt="<?php echo esc_attr( $nombre ); ?>"
                             loading="lazy" />
                    </a>
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

                <?php // v2.10.0 — Título grande (h2) ?>
                <h2 class="welow-modelo-card__nombre">
                    <a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $nombre ); ?></a>
                </h2>

                <?php // v2.10.0 — Etiquetas visuales DEBAJO del nombre (antes flotaban sobre la imagen) ?>
                <?php if ( ! empty( $etiquetas ) ) : ?>
                    <div class="welow-modelo-card__etiquetas">
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
                    </div>
                <?php endif; ?>

                <?php // v2.10.0 — Solo combustible (carrocería ya no aparece). Plazas tampoco. ?>
                <?php if ( ! empty( $combustibles ) && ! is_wp_error( $combustibles ) ) : ?>
                    <div class="welow-modelo-card__meta">
                        <?php foreach ( $combustibles as $c ) : ?>
                            <span class="welow-badge welow-badge--combustible"><?php echo esc_html( $c->name ); ?></span>
                        <?php endforeach; ?>
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

                    <a href="<?php echo esc_url( $permalink ); ?>" class="welow-modelo-card__cta">
                        <?php echo esc_html( $texto_enlace ); ?>
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>

        </article>
    <?php endforeach; ?>

</div>
