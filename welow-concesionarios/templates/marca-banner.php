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
 *
 * @since 1.1.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$style = $altura ? 'style="--welow-banner-altura: ' . esc_attr( $altura ) . ';"' : '';
$tag_open  = $enlace ? '<a href="' . esc_url( $enlace ) . '"' : '<div';
$tag_close = $enlace ? '</a>' : '</div>';
?>
<?php echo $tag_open; ?> class="welow-marca-banner welow-marca-banner--<?php echo esc_attr( $tipo ); ?>" <?php echo $style; ?>>

    <picture>
        <?php if ( $url_movil && $url_movil !== $url_desktop ) : ?>
            <source media="(max-width: 980px)" srcset="<?php echo esc_url( $url_movil ); ?>">
        <?php endif; ?>
        <img src="<?php echo esc_url( $url_desktop ); ?>"
             alt="<?php echo esc_attr( $alt ); ?>"
             class="welow-marca-banner__img"
             loading="lazy" />
    </picture>

<?php echo $tag_close; ?>
