<?php
/**
 * Template parcial: Galería de la ficha del coche.
 *
 * @var array $data
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$coche       = $data['post'];
$principal   = get_post_thumbnail_id( $coche->ID );
$galeria_ids = $data['galeria'];

// Combinar imagen principal + galería
$todas_ids = array();
if ( $principal ) $todas_ids[] = $principal;
foreach ( $galeria_ids as $id ) {
    if ( $id != $principal ) $todas_ids[] = $id;
}

if ( empty( $todas_ids ) ) {
    return;
}
?>
<div class="welow-galeria-ficha">

    <div class="welow-galeria-ficha__main">
        <?php foreach ( $todas_ids as $i => $img_id ) :
            $url_full = wp_get_attachment_image_url( $img_id, 'full' );
            $url_lg   = wp_get_attachment_image_url( $img_id, 'large' );
            $alt      = get_post_meta( $img_id, '_wp_attachment_image_alt', true ) ?: $coche->post_title;
        ?>
            <div class="welow-galeria-ficha__slide <?php echo $i === 0 ? 'is-active' : ''; ?>" data-index="<?php echo $i; ?>">
                <a href="<?php echo esc_url( $url_full ); ?>" data-lightbox="welow-galeria">
                    <img src="<?php echo esc_url( $url_lg ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="<?php echo $i === 0 ? 'eager' : 'lazy'; ?>" />
                </a>
            </div>
        <?php endforeach; ?>

        <?php if ( count( $todas_ids ) > 1 ) : ?>
            <button class="welow-galeria-ficha__arrow welow-galeria-ficha__arrow--prev" aria-label="Anterior">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <button class="welow-galeria-ficha__arrow welow-galeria-ficha__arrow--next" aria-label="Siguiente">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="9 6 15 12 9 18"></polyline>
                </svg>
            </button>
            <span class="welow-galeria-ficha__counter">
                <span class="welow-galeria-ficha__current">1</span>
                /
                <span class="welow-galeria-ficha__total"><?php echo count( $todas_ids ); ?></span>
            </span>
        <?php endif; ?>
    </div>

    <?php if ( count( $todas_ids ) > 1 ) : ?>
        <div class="welow-galeria-ficha__thumbs">
            <?php foreach ( $todas_ids as $i => $img_id ) :
                $url_thumb = wp_get_attachment_image_url( $img_id, 'thumbnail' );
            ?>
                <button class="welow-galeria-ficha__thumb <?php echo $i === 0 ? 'is-active' : ''; ?>" data-index="<?php echo $i; ?>" type="button">
                    <img src="<?php echo esc_url( $url_thumb ); ?>" alt="" loading="lazy" />
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
