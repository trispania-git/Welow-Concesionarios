<?php
/**
 * Template: Formulario buscador de coches.
 *
 * @var string   $accion URL de envío.
 * @var string[] $campos Filtros a mostrar.
 * @var string   $titulo
 *
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Recoger valores actuales de GET para repoblarles
$get = $_GET;
$v = function( $key, $default = '' ) use ( $get ) {
    return isset( $get[ 'welow_' . $key ] ) ? sanitize_text_field( wp_unslash( $get[ 'welow_' . $key ] ) ) : $default;
};

// Datos para selectores
$marcas         = get_posts( array( 'post_type' => 'welow_marca', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
$marcas_externas = get_terms( array( 'taxonomy' => 'welow_marca_externa', 'hide_empty' => false ) );
$combustibles   = get_terms( array( 'taxonomy' => 'welow_combustible', 'hide_empty' => false ) );
$carrocerias    = get_terms( array( 'taxonomy' => 'welow_categoria_modelo', 'hide_empty' => false ) );
$moneda         = class_exists( 'Welow_Settings' ) ? Welow_Settings::get( 'moneda_simbolo', '€' ) : '€';

// Tipo del buscador (nuevos/ocasion/todos) — viene del shortcode
$buscador_tipo = isset( $tipo ) ? $tipo : 'todos';
?>
<form class="welow-buscador welow-buscador--<?php echo esc_attr( $buscador_tipo ); ?>" method="get" action="<?php echo esc_url( $accion ); ?>">

    <?php if ( $titulo ) : ?>
        <h3 class="welow-buscador__titulo"><?php echo esc_html( $titulo ); ?></h3>
    <?php endif; ?>

    <div class="welow-buscador__campos">

        <?php if ( 'ocasion' === $buscador_tipo && in_array( 'tipo', $campos, true ) ) : ?>
            <label>
                <span>Tipo</span>
                <select name="welow_tipo">
                    <option value="">Todos</option>
                    <option value="ocasion" <?php selected( $v( 'tipo' ), 'ocasion' ); ?>>Ocasión</option>
                    <option value="km0" <?php selected( $v( 'tipo' ), 'km0' ); ?>>KM0</option>
                </select>
            </label>
        <?php endif; ?>

        <?php if ( in_array( 'marca', $campos, true ) && 'ocasion' !== $buscador_tipo ) : ?>
            <label>
                <span>Marca (oficiales)</span>
                <select name="welow_marca">
                    <option value="">Cualquiera</option>
                    <?php foreach ( $marcas as $marca ) : ?>
                        <option value="<?php echo esc_attr( $marca->ID ); ?>" <?php selected( $v( 'marca' ), $marca->ID ); ?>>
                            <?php echo esc_html( $marca->post_title ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>

        <?php if ( in_array( 'marca_externa', $campos, true ) && ! empty( $marcas_externas ) && ! is_wp_error( $marcas_externas ) ) : ?>
            <label>
                <span>Marca</span>
                <select name="welow_marca_externa">
                    <option value="">Cualquiera</option>
                    <?php foreach ( $marcas_externas as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $v( 'marca_externa' ), $term->slug ); ?>>
                            <?php echo esc_html( $term->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>

        <?php if ( in_array( 'combustible', $campos ) && ! empty( $combustibles ) && ! is_wp_error( $combustibles ) ) : ?>
            <label>
                <span>Combustible</span>
                <select name="welow_combustible">
                    <option value="">Cualquiera</option>
                    <?php foreach ( $combustibles as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $v( 'combustible' ), $term->slug ); ?>>
                            <?php echo esc_html( $term->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>

        <?php if ( in_array( 'carroceria', $campos ) && ! empty( $carrocerias ) && ! is_wp_error( $carrocerias ) ) : ?>
            <label>
                <span>Carrocería</span>
                <select name="welow_carroceria">
                    <option value="">Cualquiera</option>
                    <?php foreach ( $carrocerias as $term ) : ?>
                        <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $v( 'carroceria' ), $term->slug ); ?>>
                            <?php echo esc_html( $term->name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>

        <?php if ( in_array( 'precio', $campos ) ) : ?>
            <label>
                <span>Precio máximo (<?php echo esc_html( $moneda ); ?>)</span>
                <input type="number" name="welow_precio_max" min="0" step="500"
                       value="<?php echo esc_attr( $v( 'precio_max' ) ); ?>"
                       placeholder="Sin límite" />
            </label>
        <?php endif; ?>

        <?php if ( in_array( 'km', $campos ) ) : ?>
            <label>
                <span>Km máximos</span>
                <input type="number" name="welow_km_max" min="0" step="5000"
                       value="<?php echo esc_attr( $v( 'km_max' ) ); ?>"
                       placeholder="Sin límite" />
            </label>
        <?php endif; ?>

        <?php if ( in_array( 'anio', $campos ) ) : ?>
            <label>
                <span>Año desde</span>
                <input type="number" name="welow_anio_min" min="1990" max="<?php echo intval( date( 'Y' ) ); ?>"
                       value="<?php echo esc_attr( $v( 'anio_min' ) ); ?>" placeholder="Cualquiera" />
            </label>
        <?php endif; ?>

    </div>

    <div class="welow-buscador__acciones">
        <button type="submit" class="welow-btn welow-btn-primary">
            <span class="dashicons dashicons-search"></span> Buscar
        </button>
        <a href="<?php echo esc_url( $accion ); ?>" class="welow-buscador__reset">Limpiar</a>
    </div>

</form>
