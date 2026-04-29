<?php
/**
 * CPT: Coche de OCASIÓN / KM0 (cualquier marca, segunda mano).
 *
 * Marca como taxonomía welow_marca_externa, modelo en texto libre.
 * Hereda toda la lógica común de Welow_CPT_Coche_Base.
 *
 * @since 2.1.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_CPT_Coche_Ocasion extends Welow_CPT_Coche_Base {

    const POST_TYPE = 'welow_coche_ocasion';

    /**
     * Tipos de ocasión: ocasión propiamente o KM0.
     */
    public static function get_tipo_options() {
        return array(
            'ocasion' => 'Ocasión',
            'km0'     => 'KM0',
        );
    }

    public static function init() {
        parent::init_base( self::POST_TYPE );
    }

    public static function get_labels() {
        return array(
            'name'                  => 'Coches de ocasión',
            'singular_name'         => 'Coche de ocasión',
            'menu_name'             => 'Coches de ocasión',
            'add_new'               => 'Añadir coche',
            'add_new_item'          => 'Añadir nuevo coche de ocasión',
            'edit_item'             => 'Editar coche de ocasión',
            'view_item'             => 'Ver coche',
            'all_items'             => 'Todos los coches de ocasión',
            'search_items'          => 'Buscar coches de ocasión',
            'not_found'             => 'No se encontraron coches de ocasión',
            'not_found_in_trash'    => 'No hay coches en la papelera',
            'featured_image'        => 'Imagen principal',
            'set_featured_image'    => 'Establecer imagen principal',
        );
    }

    public static function get_args_extra() {
        return array(
            'rewrite'    => array( 'slug' => 'coche-ocasion', 'with_front' => false ),
            'taxonomies' => array( 'welow_combustible', 'welow_categoria_modelo', 'welow_marca_externa' ),
        );
    }

    /* ========================================================================
       METABOX A: Identificación (marca como taxonomía + modelo texto libre)
       ======================================================================== */
    public static function render_metabox_identificacion( $post ) {
        wp_nonce_field( 'welow_coche_save', 'welow_coche_nonce' );

        $modelo_texto = get_post_meta( $post->ID, self::META_PREFIX . 'modelo_texto', true );
        $version      = get_post_meta( $post->ID, self::META_PREFIX . 'version', true );
        $tipo         = get_post_meta( $post->ID, self::META_PREFIX . 'tipo_venta', true );
        $estado       = get_post_meta( $post->ID, self::META_PREFIX . 'estado', true );
        $referencia   = get_post_meta( $post->ID, self::META_PREFIX . 'referencia', true );
        $mes          = get_post_meta( $post->ID, self::META_PREFIX . 'mes_matricula', true );
        $anio         = get_post_meta( $post->ID, self::META_PREFIX . 'anio_matricula', true );
        $km           = get_post_meta( $post->ID, self::META_PREFIX . 'km', true );

        if ( '' === $tipo ) $tipo = 'ocasion';
        if ( '' === $estado ) $estado = 'disponible';
        ?>
        <p style="background:#fef3c7;padding:10px;border-radius:6px;margin:0 0 16px;font-size:13px;">
            <span class="dashicons dashicons-info" style="color:#92400e;"></span>
            Coches de <strong>ocasión / KM0</strong>. La marca se elige en el lateral derecho
            (<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=welow_marca_externa&post_type=welow_coche_ocasion' ) ); ?>">Marcas externas</a>).
        </p>
        <table class="form-table welow-metabox-table">
            <tr>
                <th><label for="welow_coche_modelo_texto">Modelo *</label></th>
                <td>
                    <input type="text" id="welow_coche_modelo_texto" name="welow_coche_modelo_texto"
                           value="<?php echo esc_attr( $modelo_texto ); ?>" class="large-text" required
                           placeholder="ej: Serie 3, A4, Megane, 308..." />
                    <p class="description">Nombre libre del modelo. La marca se elige en el panel lateral.</p>
                </td>
            </tr>
            <tr>
                <th><label for="welow_coche_version">Versión / Acabado</label></th>
                <td>
                    <input type="text" id="welow_coche_version" name="welow_coche_version"
                           value="<?php echo esc_attr( $version ); ?>" class="large-text"
                           placeholder="ej: 320d xDrive Sport" />
                </td>
            </tr>
            <tr>
                <th><label>Tipo / Estado</label></th>
                <td>
                    <select name="welow_coche_tipo_venta">
                        <?php foreach ( self::get_tipo_options() as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $tipo, $key ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="welow_coche_estado" style="margin-left:10px;">
                        <?php foreach ( self::get_estado_options() as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $estado, $key ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="welow_coche_referencia">Referencia interna</label></th>
                <td>
                    <input type="text" id="welow_coche_referencia" name="welow_coche_referencia"
                           value="<?php echo esc_attr( $referencia ); ?>" class="regular-text"
                           placeholder="ej: 7539" />
                </td>
            </tr>
            <tr>
                <th><label>Matriculación</label></th>
                <td>
                    <select name="welow_coche_mes_matricula">
                        <option value="">Mes</option>
                        <?php for ( $i = 1; $i <= 12; $i++ ) : ?>
                            <option value="<?php echo $i; ?>" <?php selected( intval($mes), $i ); ?>>
                                <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <input type="number" name="welow_coche_anio_matricula" value="<?php echo esc_attr( $anio ); ?>"
                           min="1980" max="<?php echo intval( date( 'Y' ) ) + 1; ?>"
                           placeholder="Año" style="width:90px;" />
                </td>
            </tr>
            <tr>
                <th><label for="welow_coche_km">Kilómetros</label></th>
                <td>
                    <input type="number" id="welow_coche_km" name="welow_coche_km"
                           value="<?php echo esc_attr( $km ); ?>" min="0" step="1" style="width:140px;" /> km
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Lógica de guardado específica del CPT ocasión.
     */
    public static function guardar_meta_especifico( $post_id ) {
        // Modelo en texto libre
        $modelo_texto = isset( $_POST['welow_coche_modelo_texto'] ) ? sanitize_text_field( $_POST['welow_coche_modelo_texto'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'modelo_texto', $modelo_texto );

        // Textos
        $textos = array( 'version', 'tipo_venta', 'estado', 'referencia' );
        foreach ( $textos as $f ) {
            $val = isset( $_POST[ 'welow_coche_' . $f ] ) ? sanitize_text_field( $_POST[ 'welow_coche_' . $f ] ) : '';
            update_post_meta( $post_id, self::META_PREFIX . $f, $val );
        }

        // Numéricos: matriculación, km
        $nums = array( 'mes_matricula', 'anio_matricula', 'km' );
        foreach ( $nums as $f ) {
            $val = isset( $_POST[ 'welow_coche_' . $f ] ) && '' !== $_POST[ 'welow_coche_' . $f ]
                ? absint( $_POST[ 'welow_coche_' . $f ] ) : '';
            update_post_meta( $post_id, self::META_PREFIX . $f, $val );
        }
    }

    /**
     * Renderiza la columna marca/modelo en el listado admin.
     */
    public static function render_columna_marca_modelo( $post_id ) {
        $marcas = wp_get_post_terms( $post_id, 'welow_marca_externa' );
        $modelo_texto = get_post_meta( $post_id, self::META_PREFIX . 'modelo_texto', true );

        $marca_label = ( ! empty( $marcas ) && ! is_wp_error( $marcas ) ) ? $marcas[0]->name : '<em style="color:#dc3232;">sin marca</em>';
        $modelo_label = $modelo_texto ?: '<em style="color:#94a3b8;">—</em>';

        echo wp_kses_post( $marca_label . ' <strong>' . $modelo_label . '</strong>' );
    }

    /**
     * Override de columnas: añadir tipo_venta (ocasión/km0) en lugar de heredarlo.
     */
    public static function columnas_admin( $columns ) {
        $columns = parent::columnas_admin( $columns );

        // Insertar 'welow_tipo_venta' después de marca_modelo
        $new = array();
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( 'welow_marca_modelo' === $key ) {
                $new['welow_tipo_venta'] = 'Tipo';
            }
        }
        return $new;
    }

    public static function contenido_columnas( $column, $post_id ) {
        if ( 'welow_tipo_venta' === $column ) {
            $tv = get_post_meta( $post_id, self::META_PREFIX . 'tipo_venta', true );
            $tipos = self::get_tipo_options();
            $colores = array( 'ocasion' => '#3b82f6', 'km0' => '#10b981' );
            $bg = isset( $colores[ $tv ] ) ? $colores[ $tv ] : '#64748b';
            echo $tv ? '<span style="background:' . $bg . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;text-transform:uppercase;">' . esc_html( $tipos[ $tv ] ?? $tv ) . '</span>' : '—';
            return;
        }
        parent::contenido_columnas( $column, $post_id );
    }
}
