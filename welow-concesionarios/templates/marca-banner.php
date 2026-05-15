<?php
/**
 * Template: Banner de marca (portada o zona media).
 *
 * @var string $url_desktop
 * @var string $url_movil
 * @var string $enlace
 * @var string $altura
 * @var string $tipo        portada | media
 * @var string $alt
 * @var array|null $overlay_desktop  Datos del overlay para desktop (o null)
 * @var array|null $overlay_movil    Datos del overlay para móvil (o null)
 *
 * @since 1.1.0
 * @version 2.19.0 — Soporte de texto superpuesto (overlay) por viewport.
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$style = $altura ? 'style="--welow-banner-altura: ' . esc_attr( $altura ) . ';"' : '';

// Si hay overlay, el banner es <div> con position relative; el enlace global del shortcode
// se aplica solo si NO hay botón propio en el overlay (para no romper accesibilidad).
$tiene_overlay = ! empty( $overlay_desktop ) || ! empty( $overlay_movil );

$tag_open  = ( $enlace && ! $tiene_overlay ) ? '<a href="' . esc_url( $enlace ) . '"' : '<div';
$tag_close = ( $enlace && ! $tiene_overlay ) ? '</a>' : '</div>';

$render_overlay = function( $data, $viewport_class ) {
    if ( empty( $data ) ) return;
    $pos = $data['posicion'] ?? 'middle-center';
    ?>
    <div class="welow-marca-banner__overlay welow-marca-banner__overlay--<?php echo esc_attr( $viewport_class ); ?> welow-pos-<?php echo esc_attr( $pos ); ?>">
        <div class="welow-marca-banner__overlay-inner">
            <?php if ( ! empty( $data['titulo'] ) ) : ?>
                <h2 class="welow-marca-banner__overlay-titulo"><?php echo esc_html( $data['titulo'] ); ?></h2>
            <?php endif; ?>
            <?php if ( ! empty( $data['subtitulo'] ) ) : ?>
                <p class="welow-marca-banner__overlay-subtitulo"><?php echo esc_html( $data['subtitulo'] ); ?></p>
            <?php endif; ?>
            <?php if ( ! empty( $data['btn_texto'] ) ) : ?>
                <a class="welow-marca-banner__overlay-boton"
                   href="<?php echo esc_url( $data['btn_url'] ?: '#' ); ?>">
                    <?php echo esc_html( $data['btn_texto'] ); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
};
?>
<?php echo $tag_open; ?> class="welow-marca-banner welow-marca-banner--<?php echo esc_attr( $tipo ); ?><?php echo $tiene_overlay ? ' welow-marca-banner--with-overlay' : ''; ?>" <?php echo $style; ?>>

    <picture>
        <?php if ( $url_movil && $url_movil !== $url_desktop ) : ?>
            <source media="(max-width: 980px)" srcset="<?php echo esc_url( $url_movil ); ?>">
        <?php endif; ?>
        <img src="<?php echo esc_url( $url_desktop ); ?>"
             alt="<?php echo esc_attr( $alt ); ?>"
             class="welow-marca-banner__img"
             loading="lazy" />
    </picture>

    <?php $render_overlay( $overlay_desktop, 'desktop' ); ?>
    <?php $render_overlay( $overlay_movil, 'movil' ); ?>

<?php echo $tag_close; ?>
