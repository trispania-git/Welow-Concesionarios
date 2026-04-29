<?php
/**
 * Template parcial: Datos clave destacados (con iconos).
 *
 * @var array $data
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$coche = $data['post'];
$id    = $coche->ID;

// Recoger valores
$km           = Welow_Helpers::get_coche_meta( $id, 'km' );
$anio         = Welow_Helpers::get_coche_meta( $id, 'anio_matricula' );
$mes          = Welow_Helpers::get_coche_meta( $id, 'mes_matricula' );
$cambio       = Welow_Helpers::get_coche_meta( $id, 'cambio' );
$marchas      = Welow_Helpers::get_coche_meta( $id, 'marchas' );
$cv           = Welow_Helpers::get_coche_meta( $id, 'cv' );
$cilindrada   = Welow_Helpers::get_coche_meta( $id, 'cilindrada' );
$plazas       = $data['plazas'];
$puertas      = Welow_Helpers::get_coche_meta( $id, 'puertas' );
$color        = Welow_Helpers::get_coche_meta( $id, 'color' );
$etiqueta_dgt = Welow_Helpers::get_coche_meta( $id, 'etiqueta_dgt' );

$combustibles = $data['combustibles'];
$carrocerias  = $data['carrocerias'];

$cambio_options = Welow_CPT_Coche_Base::get_cambio_options();
$dgt_options    = Welow_CPT_Coche_Base::get_etiqueta_dgt_options();

// Helper inline para construir el item
$render_item = function( $campo, $valor, $sub = '' ) {
    if ( $valor === '' || $valor === null ) return '';
    $icon_html = '';
    if ( class_exists( 'Welow_Icons' ) ) {
        $icon_html = Welow_Icons::render( Welow_Icons::get_field_icon( $campo ), $campo );
    }
    $out = '<li class="welow-destacado welow-destacado--' . esc_attr( $campo ) . '">';
    $out .= '<span class="welow-destacado__icono">' . $icon_html . '</span>';
    $out .= '<span class="welow-destacado__valor">' . wp_kses_post( $valor );
    if ( $sub ) $out .= ' <small>' . esc_html( $sub ) . '</small>';
    $out .= '</span></li>';
    return $out;
};
?>
<section class="welow-coche-destacados">
    <ul class="welow-destacados-grid">

        <?php if ( $km !== '' ) : ?>
            <?php echo $render_item( 'km', number_format_i18n( intval( $km ) ) . ' km' ); ?>
        <?php endif; ?>

        <?php if ( $anio ) :
            $valor = $mes ? str_pad( $mes, 2, '0', STR_PAD_LEFT ) . '/' . $anio : $anio;
            echo $render_item( 'anio', $valor );
        endif; ?>

        <?php if ( ! empty( $combustibles ) ) :
            $term = $combustibles[0];
            // Icono específico del término si existe
            $term_icon_url = method_exists( 'Welow_Tax_Combustible', 'get_term_icono_url' )
                ? Welow_Tax_Combustible::get_term_icono_url( $term->term_id ) : '';
            ?>
            <li class="welow-destacado welow-destacado--combustible">
                <span class="welow-destacado__icono">
                    <?php if ( $term_icon_url ) : ?>
                        <img class="welow-icon welow-icon--img" src="<?php echo esc_url( $term_icon_url ); ?>" alt="" />
                    <?php else :
                        echo class_exists( 'Welow_Icons' ) ? Welow_Icons::render( Welow_Icons::get_field_icon( 'combustible' ), 'combustible' ) : '';
                    endif; ?>
                </span>
                <span class="welow-destacado__valor"><?php echo esc_html( $term->name ); ?></span>
            </li>
        <?php endif; ?>

        <?php if ( $cambio ) :
            // Icono específico del valor
            $valor_icon = class_exists( 'Welow_Icons' ) ? Welow_Icons::get_value_icon( 'cambio', $cambio ) : array();
            $valor_icon_html = ( $valor_icon && 'image' === ($valor_icon['type'] ?? '') )
                ? '<img class="welow-icon welow-icon--img" src="' . esc_url( $valor_icon['value'] ) . '" alt="" />'
                : ( class_exists( 'Welow_Icons' ) ? Welow_Icons::render( Welow_Icons::get_field_icon( 'cambio' ), 'cambio' ) : '' );

            $valor_label = $cambio_options[ $cambio ] ?? $cambio;
            if ( $marchas ) $valor_label .= ' (' . $marchas . ')';
        ?>
            <li class="welow-destacado welow-destacado--cambio">
                <span class="welow-destacado__icono"><?php echo $valor_icon_html; ?></span>
                <span class="welow-destacado__valor"><?php echo esc_html( $valor_label ); ?></span>
            </li>
        <?php endif; ?>

        <?php if ( $cv ) echo $render_item( 'cv', $cv . ' CV' ); ?>
        <?php if ( $cilindrada ) echo $render_item( 'cilindrada', number_format_i18n( intval( $cilindrada ) ) . ' cc' ); ?>
        <?php if ( $plazas !== '' ) echo $render_item( 'plazas', $plazas . ' plazas' ); ?>
        <?php if ( $puertas ) echo $render_item( 'puertas', $puertas . ' puertas' ); ?>

        <?php if ( ! empty( $carrocerias ) ) :
            $term = $carrocerias[0];
            $term_icon_url = method_exists( 'Welow_Tax_Categoria_Modelo', 'get_term_icono_url' )
                ? Welow_Tax_Categoria_Modelo::get_term_icono_url( $term->term_id ) : '';
            ?>
            <li class="welow-destacado welow-destacado--carroceria">
                <span class="welow-destacado__icono">
                    <?php if ( $term_icon_url ) : ?>
                        <img class="welow-icon welow-icon--img" src="<?php echo esc_url( $term_icon_url ); ?>" alt="" />
                    <?php else :
                        echo class_exists( 'Welow_Icons' ) ? Welow_Icons::render( Welow_Icons::get_field_icon( 'carroceria' ), 'carroceria' ) : '';
                    endif; ?>
                </span>
                <span class="welow-destacado__valor"><?php echo esc_html( $term->name ); ?></span>
            </li>
        <?php endif; ?>

        <?php if ( $color ) echo $render_item( 'color', $color ); ?>

        <?php if ( $etiqueta_dgt ) :
            $valor_icon = class_exists( 'Welow_Icons' ) ? Welow_Icons::get_value_icon( 'etiqueta_dgt', $etiqueta_dgt ) : array();
            $valor_icon_html = ( $valor_icon && 'image' === ($valor_icon['type'] ?? '') )
                ? '<img class="welow-icon welow-icon--img" src="' . esc_url( $valor_icon['value'] ) . '" alt="" />'
                : ( class_exists( 'Welow_Icons' ) ? Welow_Icons::render( Welow_Icons::get_field_icon( 'etiqueta_dgt' ), 'DGT' ) : '' );
        ?>
            <li class="welow-destacado welow-destacado--etiqueta-dgt">
                <span class="welow-destacado__icono"><?php echo $valor_icon_html; ?></span>
                <span class="welow-destacado__valor">DGT <?php echo esc_html( $dgt_options[ $etiqueta_dgt ] ?? $etiqueta_dgt ); ?></span>
            </li>
        <?php endif; ?>

    </ul>
</section>
