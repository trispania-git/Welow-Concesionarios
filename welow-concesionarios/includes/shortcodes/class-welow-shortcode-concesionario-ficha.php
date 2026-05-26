<?php
/**
 * Shortcode: [welow_concesionario_ficha]
 * Renderiza la ficha pública de un concesionario combinando banner,
 * datos de contacto, marcas que vende, galería y sección Divi.
 *
 * Uso típico (en el Theme Builder de Divi para single de welow_concesionario):
 *   [welow_concesionario_ficha id="auto"]
 *
 * Atributos:
 *   id        Concesionario a renderizar. "auto" = detectar del contexto actual.
 *             También admite slug o ID numérico.
 *   mostrar   Bloques separados por coma. Default: "banner,info,marcas,galeria,divi"
 *             Disponibles: banner, info, marcas, galeria, divi, mapa
 *
 * @since 2.27.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Shortcode_Concesionario_Ficha {

    public static function init() {
        add_shortcode( 'welow_concesionario_ficha', array( __CLASS__, 'render' ) );
    }

    public static function render( $atts ) {
        $atts = shortcode_atts( array(
            'id'      => 'auto',
            'mostrar' => 'banner,info,marcas,galeria,divi',
        ), $atts );

        // Resolver concesionario
        $conc_id = self::resolver_id( $atts['id'] );
        if ( ! $conc_id ) {
            return '<!-- [welow_concesionario_ficha]: no se detectó concesionario -->';
        }

        $post = get_post( $conc_id );
        if ( ! $post || 'welow_concesionario' !== $post->post_type ) {
            return '<!-- [welow_concesionario_ficha]: ID inválido -->';
        }

        $bloques = array_map( 'trim', explode( ',', $atts['mostrar'] ) );

        wp_enqueue_style( 'welow-concesionario-ficha' );
        wp_enqueue_script( 'welow-concesionario-ficha' );

        ob_start();
        ?>
        <article class="welow-conc-ficha" data-id="<?php echo intval( $conc_id ); ?>">

            <?php if ( in_array( 'banner', $bloques, true ) ) :
                self::render_banner( $conc_id, $post );
            endif; ?>

            <header class="welow-conc-ficha__titulo-wrap">
                <h1 class="welow-conc-ficha__titulo"><?php echo esc_html( $post->post_title ); ?></h1>
                <?php
                $ciudad = get_post_meta( $conc_id, '_welow_conc_ciudad', true );
                $prov   = get_post_meta( $conc_id, '_welow_conc_provincia', true );
                if ( $ciudad ) :
                    echo '<p class="welow-conc-ficha__ubicacion">📍 ' . esc_html( trim( $ciudad . ( $prov ? ' · ' . $prov : '' ) ) ) . '</p>';
                endif; ?>
            </header>

            <div class="welow-conc-ficha__cuerpo">

                <?php if ( in_array( 'info', $bloques, true ) ) :
                    self::render_info( $conc_id );
                endif; ?>

                <?php if ( in_array( 'marcas', $bloques, true ) ) :
                    self::render_marcas( $conc_id );
                endif; ?>

                <?php if ( in_array( 'galeria', $bloques, true ) ) :
                    self::render_galeria( $conc_id );
                endif; ?>

                <?php if ( in_array( 'mapa', $bloques, true ) ) :
                    self::render_mapa( $conc_id );
                endif; ?>

                <?php if ( in_array( 'divi', $bloques, true ) ) :
                    self::render_divi( $conc_id );
                endif; ?>

            </div>

        </article>
        <?php
        return ob_get_clean();
    }

    /* =====================================================================
     * Bloques
     * ===================================================================== */

    private static function render_banner( $conc_id, $post ) {
        $img_desktop_id = intval( get_post_meta( $conc_id, '_welow_conc_banner_desktop', true ) );
        $img_movil_id   = intval( get_post_meta( $conc_id, '_welow_conc_banner_movil', true ) );
        if ( ! $img_desktop_id && ! $img_movil_id ) return;

        $url_desktop = $img_desktop_id ? wp_get_attachment_image_url( $img_desktop_id, 'full' )  : '';
        $url_movil   = $img_movil_id   ? wp_get_attachment_image_url( $img_movil_id, 'large' )   : $url_desktop;
        if ( ! $url_desktop ) $url_desktop = $url_movil;

        $titulo    = get_post_meta( $conc_id, '_welow_conc_banner_overlay_titulo', true );
        $subtitulo = get_post_meta( $conc_id, '_welow_conc_banner_overlay_subtitulo', true );
        $btn_txt   = get_post_meta( $conc_id, '_welow_conc_banner_overlay_btn_texto', true );
        $btn_url   = get_post_meta( $conc_id, '_welow_conc_banner_overlay_btn_url', true );
        $posicion  = get_post_meta( $conc_id, '_welow_conc_banner_overlay_posicion', true ) ?: 'middle-center';
        $tiene_overlay = $titulo || $subtitulo || $btn_txt;
        ?>
        <div class="welow-conc-banner<?php echo $tiene_overlay ? ' welow-conc-banner--with-overlay' : ''; ?>">
            <picture>
                <?php if ( $url_movil && $url_movil !== $url_desktop ) : ?>
                    <source media="(max-width: 980px)" srcset="<?php echo esc_url( $url_movil ); ?>">
                <?php endif; ?>
                <img src="<?php echo esc_url( $url_desktop ); ?>"
                     alt="<?php echo esc_attr( $post->post_title ); ?>"
                     class="welow-conc-banner__img" />
            </picture>

            <?php if ( $tiene_overlay ) : ?>
                <div class="welow-conc-banner__overlay welow-pos-<?php echo esc_attr( $posicion ); ?>">
                    <div class="welow-conc-banner__overlay-inner">
                        <?php if ( $titulo ) : ?>
                            <h2 class="welow-conc-banner__overlay-titulo"><?php echo esc_html( $titulo ); ?></h2>
                        <?php endif; ?>
                        <?php if ( $subtitulo ) : ?>
                            <p class="welow-conc-banner__overlay-subtitulo"><?php echo esc_html( $subtitulo ); ?></p>
                        <?php endif; ?>
                        <?php if ( $btn_txt ) : ?>
                            <a class="welow-conc-banner__overlay-boton" href="<?php echo esc_url( $btn_url ?: '#' ); ?>">
                                <?php echo esc_html( $btn_txt ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_info( $conc_id ) {
        $direccion = get_post_meta( $conc_id, '_welow_conc_direccion', true );
        $cp        = get_post_meta( $conc_id, '_welow_conc_cp', true );
        $ciudad    = get_post_meta( $conc_id, '_welow_conc_ciudad', true );
        $telefono  = get_post_meta( $conc_id, '_welow_conc_telefono', true );
        $email     = get_post_meta( $conc_id, '_welow_conc_email', true );
        $horario   = get_post_meta( $conc_id, '_welow_conc_horario', true );

        if ( ! $direccion && ! $telefono && ! $email && ! $horario ) return;

        $direccion_full = trim( $direccion . ( $cp ? ', ' . $cp : '' ) . ( $ciudad ? ' ' . $ciudad : '' ) );
        ?>
        <section class="welow-conc-info">
            <h2 class="welow-conc-section-title">Contacto y horario</h2>
            <ul class="welow-conc-info__lista">
                <?php if ( $direccion_full ) : ?>
                    <li>
                        <span class="welow-conc-info__icon">📍</span>
                        <span><?php echo esc_html( $direccion_full ); ?></span>
                    </li>
                <?php endif; ?>
                <?php if ( $telefono ) : ?>
                    <li>
                        <span class="welow-conc-info__icon">📞</span>
                        <a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $telefono ) ); ?>">
                            <?php echo esc_html( $telefono ); ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ( $email ) : ?>
                    <li>
                        <span class="welow-conc-info__icon">✉️</span>
                        <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
                    </li>
                <?php endif; ?>
                <?php if ( $horario ) : ?>
                    <li>
                        <span class="welow-conc-info__icon">🕐</span>
                        <span class="welow-conc-info__horario"><?php echo nl2br( esc_html( $horario ) ); ?></span>
                    </li>
                <?php endif; ?>
            </ul>
        </section>
        <?php
    }

    private static function render_marcas( $conc_id ) {
        $marca_ids = get_post_meta( $conc_id, '_welow_conc_marcas', true );
        if ( ! is_array( $marca_ids ) || empty( $marca_ids ) ) return;
        ?>
        <section class="welow-conc-marcas">
            <h2 class="welow-conc-section-title">Marcas que representamos</h2>
            <div class="welow-conc-marcas__grid">
                <?php foreach ( $marca_ids as $mid ) :
                    $mid = intval( $mid );
                    $marca = get_post( $mid );
                    if ( ! $marca || 'welow_marca' !== $marca->post_type ) continue;
                    $logo = get_the_post_thumbnail_url( $mid, 'medium' );
                    $url  = get_permalink( $mid );
                ?>
                    <a class="welow-conc-marca-item" href="<?php echo esc_url( $url ); ?>">
                        <?php if ( $logo ) : ?>
                            <img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $marca->post_title ); ?>" />
                        <?php else : ?>
                            <span class="welow-conc-marca-item__nombre"><?php echo esc_html( $marca->post_title ); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    private static function render_galeria( $conc_id ) {
        $ids = get_post_meta( $conc_id, '_welow_conc_galeria', true );
        if ( ! is_array( $ids ) || empty( $ids ) ) return;
        ?>
        <section class="welow-conc-galeria">
            <h2 class="welow-conc-section-title">Galería</h2>
            <div class="welow-conc-galeria__grid" data-welow-conc-galeria>
                <?php foreach ( $ids as $idx => $img_id ) :
                    $img_id = intval( $img_id );
                    $url_thumb = wp_get_attachment_image_url( $img_id, 'medium_large' );
                    $url_full  = wp_get_attachment_image_url( $img_id, 'full' );
                    if ( ! $url_thumb ) continue;
                ?>
                    <button type="button" class="welow-conc-galeria__item"
                            data-full="<?php echo esc_url( $url_full ); ?>"
                            data-index="<?php echo intval( $idx ); ?>"
                            aria-label="Ver foto <?php echo intval( $idx + 1 ); ?>">
                        <img src="<?php echo esc_url( $url_thumb ); ?>"
                             alt="<?php echo esc_attr( 'Foto ' . ( $idx + 1 ) ); ?>"
                             loading="lazy" />
                    </button>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }

    private static function render_mapa( $conc_id ) {
        $lat = get_post_meta( $conc_id, '_welow_conc_lat', true );
        $lng = get_post_meta( $conc_id, '_welow_conc_lng', true );
        if ( ! $lat || ! $lng ) return;
        $q = $lat . ',' . $lng;
        ?>
        <section class="welow-conc-mapa">
            <h2 class="welow-conc-section-title">Cómo llegar</h2>
            <iframe class="welow-conc-mapa__embed"
                    src="https://www.google.com/maps?q=<?php echo esc_attr( $q ); ?>&z=15&output=embed"
                    width="100%" height="380" style="border:0;border-radius:8px;"
                    loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>
            <p style="margin-top:8px;">
                <a class="welow-conc-mapa__link" href="https://www.google.com/maps/dir/?api=1&destination=<?php echo esc_attr( $q ); ?>" target="_blank" rel="noopener">
                    Abrir en Google Maps →
                </a>
            </p>
        </section>
        <?php
    }

    private static function render_divi( $conc_id ) {
        $layout_id = intval( get_post_meta( $conc_id, '_welow_conc_divi_layout_id', true ) );
        if ( ! $layout_id ) return;
        $output = do_shortcode( '[welow_divi id="' . $layout_id . '"]' );
        if ( $output ) {
            echo '<section class="welow-conc-divi">' . $output . '</section>';
        }
    }

    /* =====================================================================
     * Helpers
     * ===================================================================== */

    private static function resolver_id( $id ) {
        if ( 'auto' === $id || '' === $id ) {
            if ( is_singular( 'welow_concesionario' ) ) {
                return get_queried_object_id();
            }
            // Theme Builder context
            global $post;
            if ( $post instanceof WP_Post && 'welow_concesionario' === $post->post_type ) {
                return $post->ID;
            }
            return 0;
        }
        if ( is_numeric( $id ) ) return intval( $id );
        // Slug
        $p = get_page_by_path( sanitize_title( $id ), OBJECT, 'welow_concesionario' );
        return $p ? $p->ID : 0;
    }
}
