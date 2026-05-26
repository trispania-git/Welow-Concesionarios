<?php
/**
 * Shortcode: [welow_concesionarios]
 * Grid de fichas de los concesionarios publicados.
 *
 * Uso típico (página "Nuestros concesionarios"):
 *   [welow_concesionarios]
 *
 * Atributos:
 *   columnas         Desktop (default 3)
 *   columnas_tablet  Tablet  (default 2)
 *   columnas_movil   Móvil   (default 1)
 *   max              Número máximo de concesionarios (-1 = todos, default -1)
 *   texto_boton      Texto del botón "Ver" (default "Ver concesionario")
 *   orden            Orden: menu_order | title | date (default menu_order)
 *
 * @since 2.28.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Concesionarios {

    public static function init() {
        add_shortcode( 'welow_concesionarios', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'columnas'        => '3',
            'columnas_tablet' => '2',
            'columnas_movil'  => '1',
            'max'             => '-1',
            'texto_boton'     => 'Ver concesionario',
            'orden'           => 'menu_order',
        ), $atts );

        $ordenby = in_array( $atts['orden'], array( 'menu_order', 'title', 'date' ), true )
            ? $atts['orden'] : 'menu_order';

        $concesionarios = get_posts( array(
            'post_type'      => 'welow_concesionario',
            'post_status'    => 'publish',
            'posts_per_page' => intval( $atts['max'] ),
            'orderby'        => $ordenby . ' title',
            'order'          => 'ASC',
        ) );

        if ( empty( $concesionarios ) ) {
            return '<p class="welow-no-results">No hay concesionarios publicados.</p>';
        }

        wp_enqueue_style( 'welow-concesionario-ficha' ); // reusamos su CSS (grid + cards)

        ob_start();
        ?>
        <div class="welow-conc-grid"
             style="--welow-cc-cols: <?php echo intval( $atts['columnas'] ); ?>;
                    --welow-cc-cols-tablet: <?php echo intval( $atts['columnas_tablet'] ); ?>;
                    --welow-cc-cols-movil: <?php echo intval( $atts['columnas_movil'] ); ?>;">

            <?php foreach ( $concesionarios as $c ) :
                $img_url = self::get_imagen_principal( $c->ID );

                $direccion = get_post_meta( $c->ID, '_welow_conc_direccion', true );
                $ciudad    = get_post_meta( $c->ID, '_welow_conc_ciudad', true );
                $cp        = get_post_meta( $c->ID, '_welow_conc_cp', true );

                $linea_principal   = $ciudad ?: $c->post_title;
                $linea_secundaria  = trim( $direccion . ( $cp ? ', ' . $cp : '' ) );

                $marca_ids = get_post_meta( $c->ID, '_welow_conc_marcas', true );
                $marca_ids = is_array( $marca_ids ) ? $marca_ids : array();

                $permalink = get_permalink( $c->ID );
            ?>
                <article class="welow-conc-card">

                    <a class="welow-conc-card__imagen" href="<?php echo esc_url( $permalink ); ?>"
                       aria-label="<?php echo esc_attr( $c->post_title ); ?>">
                        <?php if ( $img_url ) : ?>
                            <img src="<?php echo esc_url( $img_url ); ?>"
                                 alt="<?php echo esc_attr( $c->post_title ); ?>"
                                 loading="lazy" />
                        <?php else : ?>
                            <div class="welow-conc-card__placeholder">
                                <span class="dashicons dashicons-store"></span>
                            </div>
                        <?php endif; ?>
                    </a>

                    <div class="welow-conc-card__info">

                        <h3 class="welow-conc-card__localidad">
                            <a href="<?php echo esc_url( $permalink ); ?>">
                                <?php echo esc_html( $linea_principal ); ?>
                            </a>
                        </h3>

                        <?php if ( $linea_secundaria ) : ?>
                            <p class="welow-conc-card__direccion"><?php echo esc_html( $linea_secundaria ); ?></p>
                        <?php endif; ?>

                        <?php if ( ! empty( $marca_ids ) ) : ?>
                            <div class="welow-conc-card__marcas">
                                <?php foreach ( $marca_ids as $mid ) :
                                    $mid = intval( $mid );
                                    $marca = get_post( $mid );
                                    if ( ! $marca || 'welow_marca' !== $marca->post_type ) continue;
                                    // Usar logo_negro o thumbnail si está disponible
                                    $logo_id  = get_post_meta( $mid, '_welow_marca_logo_negro', true );
                                    $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : get_the_post_thumbnail_url( $mid, 'medium' );
                                ?>
                                    <?php if ( $logo_url ) : ?>
                                        <img class="welow-conc-card__marca-logo"
                                             src="<?php echo esc_url( $logo_url ); ?>"
                                             alt="<?php echo esc_attr( $marca->post_title ); ?>"
                                             title="<?php echo esc_attr( $marca->post_title ); ?>"
                                             loading="lazy" />
                                    <?php else : ?>
                                        <span class="welow-conc-card__marca-text"><?php echo esc_html( $marca->post_title ); ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <a href="<?php echo esc_url( $permalink ); ?>" class="welow-conc-card__btn">
                            <?php echo esc_html( $atts['texto_boton'] ); ?>
                            <span aria-hidden="true">→</span>
                        </a>

                    </div>

                </article>
            <?php endforeach; ?>

        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Imagen principal del concesionario: banner desktop → primera de galería →
     * thumbnail del post → vacío.
     */
    private static function get_imagen_principal( $conc_id ) {
        $banner_id = intval( get_post_meta( $conc_id, '_welow_conc_banner_desktop', true ) );
        if ( $banner_id ) {
            $u = wp_get_attachment_image_url( $banner_id, 'large' );
            if ( $u ) return $u;
        }
        $galeria = get_post_meta( $conc_id, '_welow_conc_galeria', true );
        if ( is_array( $galeria ) && ! empty( $galeria ) ) {
            $u = wp_get_attachment_image_url( intval( $galeria[0] ), 'large' );
            if ( $u ) return $u;
        }
        $thumb_id = get_post_thumbnail_id( $conc_id );
        if ( $thumb_id ) {
            $u = wp_get_attachment_image_url( $thumb_id, 'large' );
            if ( $u ) return $u;
        }
        return '';
    }
}
