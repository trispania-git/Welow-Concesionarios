<?php
/**
 * Template parcial: Información del concesionario.
 *
 * @var array $data
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( empty( $data['concesionario_id'] ) ) return;
$conc = Welow_Helpers::get_concesionario_data( $data['concesionario_id'] );
if ( ! $conc ) return;
?>
<section class="welow-coche-concesionario">
    <h2>Concesionario</h2>

    <?php if ( $conc['logo'] ) : ?>
        <div class="welow-coche-concesionario__logo">
            <img src="<?php echo esc_url( $conc['logo'] ); ?>" alt="<?php echo esc_attr( $conc['nombre'] ); ?>" />
        </div>
    <?php endif; ?>

    <h3 class="welow-coche-concesionario__nombre"><?php echo esc_html( $conc['nombre'] ); ?></h3>

    <ul class="welow-coche-concesionario__datos">
        <?php if ( $conc['direccion'] || $conc['ciudad'] ) : ?>
            <li>
                <span class="dashicons dashicons-location"></span>
                <?php
                $direccion_completa = trim( $conc['direccion'] );
                if ( $conc['cp'] || $conc['ciudad'] ) {
                    $direccion_completa .= '<br>' . trim( $conc['cp'] . ' ' . $conc['ciudad'] );
                    if ( $conc['provincia'] ) $direccion_completa .= ' (' . $conc['provincia'] . ')';
                }
                echo wp_kses_post( $direccion_completa );
                ?>
            </li>
        <?php endif; ?>

        <?php if ( $conc['telefono'] ) : ?>
            <li>
                <span class="dashicons dashicons-phone"></span>
                <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $conc['telefono'] ) ); ?>">
                    <?php echo esc_html( $conc['telefono'] ); ?>
                </a>
            </li>
        <?php endif; ?>

        <?php if ( $conc['email'] ) : ?>
            <li>
                <span class="dashicons dashicons-email"></span>
                <a href="mailto:<?php echo esc_attr( $conc['email'] ); ?>">
                    <?php echo esc_html( $conc['email'] ); ?>
                </a>
            </li>
        <?php endif; ?>

        <?php if ( $conc['horario'] ) : ?>
            <li>
                <span class="dashicons dashicons-clock"></span>
                <?php echo nl2br( esc_html( $conc['horario'] ) ); ?>
            </li>
        <?php endif; ?>
    </ul>

    <?php if ( $conc['lat'] && $conc['lng'] ) : ?>
        <a class="welow-btn welow-btn-primary" target="_blank"
           href="https://www.google.com/maps/search/?api=1&query=<?php echo esc_attr( $conc['lat'] . ',' . $conc['lng'] ); ?>">
            Ver en mapa
        </a>
    <?php endif; ?>
</section>
