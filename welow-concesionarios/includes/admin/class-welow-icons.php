<?php
/**
 * Sistema de iconos: gestión y rendering.
 *
 * Permite asignar iconos personalizados (imágenes de la mediateca) a:
 * - Campos clave de la ficha (km, año, combustible, etc.)
 * - Valores de selects (manual/automático, etiqueta DGT, etc.)
 *
 * Almacenamiento en `welow_conc_settings`:
 *   iconos.campos.{campo} = attachment_id
 *   iconos.valores.{campo}.{valor_slug} = attachment_id
 *
 * @since 2.0.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Icons {

    /* Definición de qué campos pueden tener icono */
    public static function get_campos_con_icono() {
        return array(
            'km'            => array( 'label' => 'Kilómetros',     'dashicon' => 'dashicons-location' ),
            'anio'          => array( 'label' => 'Año',            'dashicon' => 'dashicons-calendar-alt' ),
            'combustible'   => array( 'label' => 'Combustible',    'dashicon' => 'dashicons-admin-tools' ),
            'cambio'        => array( 'label' => 'Cambio',         'dashicon' => 'dashicons-controls-repeat' ),
            'marchas'       => array( 'label' => 'Marchas',        'dashicon' => 'dashicons-update' ),
            'cv'            => array( 'label' => 'Potencia (CV)',  'dashicon' => 'dashicons-performance' ),
            'kw'            => array( 'label' => 'Potencia (kW)',  'dashicon' => 'dashicons-performance' ),
            'cilindrada'    => array( 'label' => 'Cilindrada',     'dashicon' => 'dashicons-database' ),
            'plazas'        => array( 'label' => 'Plazas',         'dashicon' => 'dashicons-groups' ),
            'puertas'       => array( 'label' => 'Puertas',        'dashicon' => 'dashicons-exit' ),
            'color'         => array( 'label' => 'Color',          'dashicon' => 'dashicons-art' ),
            'etiqueta_dgt'  => array( 'label' => 'Etiqueta DGT',   'dashicon' => 'dashicons-shield' ),
            'tipo_pintura'  => array( 'label' => 'Tipo de pintura','dashicon' => 'dashicons-format-image' ),
            'carroceria'    => array( 'label' => 'Carrocería',     'dashicon' => 'dashicons-car' ),
        );
    }

    /* Definición de qué valores de select pueden tener icono */
    public static function get_valores_con_icono() {
        return array(
            'cambio' => array(
                'manual'         => 'Manual',
                'automatico'     => 'Automático',
                'semiautomatico' => 'Semiautomático',
            ),
            'etiqueta_dgt' => array(
                '0'   => 'Cero (0 azul)',
                'eco' => 'ECO',
                'c'   => 'C (verde)',
                'b'   => 'B (amarilla)',
                'sin' => 'Sin distintivo',
            ),
            'tipo_pintura' => array(
                'solida'     => 'Sólida',
                'metalizada' => 'Metalizada',
                'mate'       => 'Mate',
                'perlada'    => 'Perlada',
            ),
            'tipo_venta' => array(
                'ocasion' => 'Ocasión',
                'km0'     => 'KM0',
                'nuevo'   => 'Nuevo',
            ),
        );
    }

    /* ========================================================================
       GETTERS PARA FRONTEND
       ======================================================================== */

    /**
     * Obtiene el icono de un campo.
     *
     * @param string $campo Slug del campo (km, anio, combustible, etc.).
     * @return array{type: string, value: string, dashicon: string}
     */
    public static function get_field_icon( $campo ) {
        $options = get_option( 'welow_conc_settings', array() );
        $id = isset( $options['iconos']['campos'][ $campo ] ) ? intval( $options['iconos']['campos'][ $campo ] ) : 0;

        if ( $id ) {
            $url = wp_get_attachment_image_url( $id, 'thumbnail' );
            if ( $url ) {
                return array( 'type' => 'image', 'value' => $url, 'dashicon' => '' );
            }
        }

        $campos = self::get_campos_con_icono();
        $dashicon = isset( $campos[ $campo ]['dashicon'] ) ? $campos[ $campo ]['dashicon'] : 'dashicons-marker';

        return array( 'type' => 'dashicon', 'value' => '', 'dashicon' => $dashicon );
    }

    /**
     * Obtiene el icono de un valor de select.
     */
    public static function get_value_icon( $campo, $valor ) {
        $options = get_option( 'welow_conc_settings', array() );
        $id = isset( $options['iconos']['valores'][ $campo ][ $valor ] )
            ? intval( $options['iconos']['valores'][ $campo ][ $valor ] )
            : 0;

        if ( $id ) {
            $url = wp_get_attachment_image_url( $id, 'thumbnail' );
            if ( $url ) {
                return array( 'type' => 'image', 'value' => $url );
            }
        }
        return array( 'type' => 'none', 'value' => '' );
    }

    /**
     * Renderiza un icono (img o dashicon).
     *
     * @param array  $icon  Array de get_field_icon o get_value_icon.
     * @param string $alt   Texto alternativo.
     * @return string HTML.
     */
    public static function render( $icon, $alt = '' ) {
        if ( empty( $icon ) || 'none' === ( $icon['type'] ?? '' ) ) {
            return '';
        }
        if ( 'image' === $icon['type'] ) {
            return '<img class="welow-icon welow-icon--img" src="' . esc_url( $icon['value'] ) . '" alt="' . esc_attr( $alt ) . '" />';
        }
        if ( 'dashicon' === $icon['type'] ) {
            return '<span class="welow-icon welow-icon--dashicon dashicons ' . esc_attr( $icon['dashicon'] ) . '" aria-hidden="true"></span>';
        }
        return '';
    }

    /* ========================================================================
       UI EN CONFIGURACIONES
       ======================================================================== */

    /**
     * Renderiza la sección "Iconos" en la página Configuraciones.
     */
    public static function render_section() {
        $options = get_option( 'welow_conc_settings', array() );
        $iconos_campos  = isset( $options['iconos']['campos'] ) ? $options['iconos']['campos'] : array();
        $iconos_valores = isset( $options['iconos']['valores'] ) ? $options['iconos']['valores'] : array();
        $option_key = 'welow_conc_settings';
        ?>
        <h2 class="title">Iconos de la ficha</h2>
        <p>Asigna iconos personalizados (imágenes PNG/SVG transparentes) a los datos clave de la ficha de coche.
           Si no asignas ninguno, se usará un icono Dashicon por defecto.</p>

        <h3>Iconos por campo</h3>
        <p class="description">Para los datos clave que aparecen en la ficha de coche (km, año, etc.).</p>

        <table class="widefat welow-icons-table">
            <thead>
                <tr><th>Campo</th><th>Icono</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ( self::get_campos_con_icono() as $key => $info ) :
                    $id = isset( $iconos_campos[ $key ] ) ? intval( $iconos_campos[ $key ] ) : 0;
                    $url = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : '';
                    $field_name = $option_key . '[iconos][campos][' . $key . ']';
                    $preview_id = 'welow-icon-campo-' . $key;
                ?>
                    <tr>
                        <td><strong><?php echo esc_html( $info['label'] ); ?></strong>
                            <br><small style="color:#94a3b8;">por defecto: <span class="dashicons <?php echo esc_attr( $info['dashicon'] ); ?>"></span></small>
                        </td>
                        <td>
                            <input type="hidden" id="<?php echo esc_attr( $preview_id ); ?>"
                                   name="<?php echo esc_attr( $field_name ); ?>"
                                   value="<?php echo esc_attr( $id ); ?>" />
                            <div class="welow-icon-preview" id="<?php echo esc_attr( $preview_id ); ?>-preview">
                                <?php if ( $url ) : ?>
                                    <img src="<?php echo esc_url( $url ); ?>" alt="" />
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <button type="button" class="button welow-upload-btn"
                                    data-target="<?php echo esc_attr( $preview_id ); ?>"
                                    data-preview="<?php echo esc_attr( $preview_id ); ?>-preview">
                                <?php echo $id ? 'Cambiar' : 'Seleccionar'; ?>
                            </button>
                            <?php if ( $id ) : ?>
                                <button type="button" class="button welow-remove-btn"
                                        data-target="<?php echo esc_attr( $preview_id ); ?>"
                                        data-preview="<?php echo esc_attr( $preview_id ); ?>-preview">Quitar</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3 style="margin-top:30px;">Iconos por valor (selects)</h3>
        <p class="description">Por ejemplo, un icono distinto para cada tipo de cambio (manual/automático), o cada etiqueta DGT.</p>

        <?php foreach ( self::get_valores_con_icono() as $campo => $valores ) :
            $campos_info = self::get_campos_con_icono();
            $campo_label = isset( $campos_info[ $campo ]['label'] ) ? $campos_info[ $campo ]['label'] : ucfirst( $campo );
        ?>
            <h4><?php echo esc_html( $campo_label ); ?></h4>
            <table class="widefat welow-icons-table" style="margin-bottom:20px;">
                <thead><tr><th>Valor</th><th>Icono</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ( $valores as $valor_key => $valor_label ) :
                        $id = isset( $iconos_valores[ $campo ][ $valor_key ] ) ? intval( $iconos_valores[ $campo ][ $valor_key ] ) : 0;
                        $url = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : '';
                        $field_name = $option_key . '[iconos][valores][' . $campo . '][' . $valor_key . ']';
                        $preview_id = 'welow-icon-' . $campo . '-' . $valor_key;
                    ?>
                        <tr>
                            <td><?php echo esc_html( $valor_label ); ?></td>
                            <td>
                                <input type="hidden" id="<?php echo esc_attr( $preview_id ); ?>"
                                       name="<?php echo esc_attr( $field_name ); ?>"
                                       value="<?php echo esc_attr( $id ); ?>" />
                                <div class="welow-icon-preview" id="<?php echo esc_attr( $preview_id ); ?>-preview">
                                    <?php if ( $url ) : ?>
                                        <img src="<?php echo esc_url( $url ); ?>" alt="" />
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="button welow-upload-btn"
                                        data-target="<?php echo esc_attr( $preview_id ); ?>"
                                        data-preview="<?php echo esc_attr( $preview_id ); ?>-preview">
                                    <?php echo $id ? 'Cambiar' : 'Seleccionar'; ?>
                                </button>
                                <?php if ( $id ) : ?>
                                    <button type="button" class="button welow-remove-btn"
                                            data-target="<?php echo esc_attr( $preview_id ); ?>"
                                            data-preview="<?php echo esc_attr( $preview_id ); ?>-preview">Quitar</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>

        <style>
            .welow-icons-table th, .welow-icons-table td { padding: 8px 12px; vertical-align: middle; }
            .welow-icon-preview { width: 50px; height: 50px; background: #f5f5f5; border: 1px dashed #cbd5e1;
                                  border-radius: 4px; display: flex; align-items: center; justify-content: center; }
            .welow-icon-preview img { max-width: 40px; max-height: 40px; object-fit: contain; }
            .welow-icon-preview:empty::after { content: "—"; color: #cbd5e1; }
        </style>
        <?php
    }

    /**
     * Sanitiza la sección iconos antes de guardar.
     */
    public static function sanitize( $iconos_input ) {
        $output = array( 'campos' => array(), 'valores' => array() );

        if ( isset( $iconos_input['campos'] ) && is_array( $iconos_input['campos'] ) ) {
            foreach ( $iconos_input['campos'] as $campo => $id ) {
                $output['campos'][ sanitize_key( $campo ) ] = absint( $id );
            }
        }

        if ( isset( $iconos_input['valores'] ) && is_array( $iconos_input['valores'] ) ) {
            foreach ( $iconos_input['valores'] as $campo => $valores ) {
                if ( ! is_array( $valores ) ) continue;
                $campo_key = sanitize_key( $campo );
                foreach ( $valores as $valor => $id ) {
                    $output['valores'][ $campo_key ][ sanitize_key( $valor ) ] = absint( $id );
                }
            }
        }
        return $output;
    }
}
