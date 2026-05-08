<?php
/**
 * CPT: Coche NUEVO (unidad concreta del catálogo oficial).
 *
 * Tiene relación obligatoria con welow_modelo (catálogo de marcas oficiales).
 * Hereda toda la lógica común de Welow_CPT_Coche_Base.
 *
 * @since 2.1.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_CPT_Coche_Nuevo extends Welow_CPT_Coche_Base {

    const POST_TYPE = 'welow_coche_nuevo';

    public static function init() {
        parent::init_base( self::POST_TYPE );

        // v2.11.0 — Metabox extra "Destacados" (rótulo + características principales)
        add_action( 'add_meta_boxes', array( __CLASS__, 'registrar_metabox_destacados' ) );
    }

    /**
     * v2.11.0 — Metabox adicional "Destacados" solo para coches nuevos.
     */
    public static function registrar_metabox_destacados() {
        add_meta_box(
            'welow_coche_destacados',
            'I. Destacados (card)',
            array( __CLASS__, 'render_metabox_destacados' ),
            self::POST_TYPE,
            'normal',
            'default'
        );
    }

    /**
     * v2.11.0 — Render del metabox "Destacados".
     *
     * Campos:
     *  - rotulo: frase corta destacada (encima de las características).
     *  - caracteristicas: lista de bullets, una característica por línea.
     */
    public static function render_metabox_destacados( $post ) {
        $rotulo          = get_post_meta( $post->ID, self::META_PREFIX . 'rotulo', true );
        $caracteristicas = get_post_meta( $post->ID, self::META_PREFIX . 'caracteristicas', true );
        ?>
        <p class="description" style="margin-top:0;">
            Aparece en la <strong>card del listado</strong> de coches nuevos para destacar el modelo.
        </p>
        <table class="form-table welow-metabox-table">
            <tr>
                <th><label for="welow_coche_rotulo">Rótulo destacado</label></th>
                <td>
                    <input type="text" id="welow_coche_rotulo" name="welow_coche_rotulo"
                           value="<?php echo esc_attr( $rotulo ); ?>" class="large-text"
                           maxlength="80"
                           placeholder="ej: La innovación tiene nuevo nombre" />
                    <p class="description">Frase corta (máx. 80 caracteres) que se muestra en negrita encima de las características.</p>
                </td>
            </tr>
            <tr>
                <th><label for="welow_coche_caracteristicas">Características principales</label></th>
                <td>
                    <textarea id="welow_coche_caracteristicas" name="welow_coche_caracteristicas"
                              rows="6" class="large-text"
                              placeholder="Una característica por línea, p.ej.:&#10;Sistema de Sonido Sony de 12 Altavoces&#10;Control por Voz de 4 Zonas&#10;Pantalla Central Deslizante de 15,6&#10;19 Asistentes de Conducción (ADAS)"><?php echo esc_textarea( $caracteristicas ); ?></textarea>
                    <p class="description">Una característica por línea. Recomendado: 3-5 líneas.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function get_labels() {
        return array(
            'name'                  => 'Coches nuevos',
            'singular_name'         => 'Coche nuevo',
            'menu_name'             => 'Coches nuevos',
            'add_new'               => 'Añadir coche nuevo',
            'add_new_item'          => 'Añadir nuevo coche',
            'edit_item'             => 'Editar coche nuevo',
            'view_item'             => 'Ver coche nuevo',
            'all_items'             => 'Todos los coches nuevos',
            'search_items'          => 'Buscar coches nuevos',
            'not_found'             => 'No se encontraron coches nuevos',
            'not_found_in_trash'    => 'No hay coches nuevos en la papelera',
            'featured_image'        => 'Imagen principal',
            'set_featured_image'    => 'Establecer imagen principal',
        );
    }

    public static function get_args_extra() {
        return array(
            'rewrite' => array( 'slug' => 'coche-nuevo', 'with_front' => false ),
        );
    }

    /* ========================================================================
       METABOX A: Identificación (con relación a modelo del catálogo)
       ======================================================================== */
    public static function render_metabox_identificacion( $post ) {
        wp_nonce_field( 'welow_coche_save', 'welow_coche_nonce' );

        $modelo_id  = get_post_meta( $post->ID, self::META_PREFIX . 'modelo', true );
        $version    = get_post_meta( $post->ID, self::META_PREFIX . 'version', true );
        $estado     = get_post_meta( $post->ID, self::META_PREFIX . 'estado', true );
        $referencia = get_post_meta( $post->ID, self::META_PREFIX . 'referencia', true );

        if ( '' === $estado ) $estado = 'disponible';

        $modelos = get_posts( array(
            'post_type' => 'welow_modelo', 'post_status' => 'publish',
            'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC',
        ) );
        ?>
        <p style="background:#dbeafe;padding:10px;border-radius:6px;margin:0 0 16px;font-size:13px;">
            <span class="dashicons dashicons-info" style="color:#2563eb;"></span>
            Coches del <strong>catálogo oficial</strong> del concesionario. Selecciona un modelo del catálogo
            (<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=welow_modelo' ) ); ?>">gestionar modelos</a>).
        </p>
        <table class="form-table welow-metabox-table">
            <tr>
                <th><label for="welow_coche_modelo">Modelo *</label></th>
                <td>
                    <select id="welow_coche_modelo" name="welow_coche_modelo" class="widefat" required>
                        <option value="">— Seleccionar modelo —</option>
                        <?php foreach ( $modelos as $m ) :
                            $marca_id = get_post_meta( $m->ID, '_welow_modelo_marca', true );
                            $marca    = $marca_id ? get_post( $marca_id ) : null;
                            $label    = ( $marca ? $marca->post_title . ' — ' : '' ) . $m->post_title;
                        ?>
                            <option value="<?php echo esc_attr( $m->ID ); ?>" <?php selected( $modelo_id, $m->ID ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        El coche heredará carrocería, plazas y datos genéricos del modelo seleccionado.
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="welow_coche_version">Versión / Acabado</label></th>
                <td>
                    <input type="text" id="welow_coche_version" name="welow_coche_version"
                           value="<?php echo esc_attr( $version ); ?>" class="large-text"
                           placeholder="ej: 1.0 VVT-I 72CV Play" />
                </td>
            </tr>
            <tr>
                <th><label>Estado</label></th>
                <td>
                    <select name="welow_coche_estado">
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
        </table>
        <?php
    }

    /**
     * Lógica de guardado específica del CPT nuevo.
     */
    public static function guardar_meta_especifico( $post_id ) {
        // Modelo (obligatorio)
        $modelo_id = isset( $_POST['welow_coche_modelo'] ) ? absint( $_POST['welow_coche_modelo'] ) : 0;
        update_post_meta( $post_id, self::META_PREFIX . 'modelo', $modelo_id );

        // Texto
        $textos = array( 'version', 'estado', 'referencia' );
        foreach ( $textos as $f ) {
            $val = isset( $_POST[ 'welow_coche_' . $f ] ) ? sanitize_text_field( $_POST[ 'welow_coche_' . $f ] ) : '';
            update_post_meta( $post_id, self::META_PREFIX . $f, $val );
        }

        // Para coches NUEVOS no hay tipo_venta (siempre es "nuevo"), ni km, ni anio_matricula
        update_post_meta( $post_id, self::META_PREFIX . 'tipo_venta', 'nuevo' );

        // v2.11.0 — Destacados (rótulo + características principales)
        $rotulo = isset( $_POST['welow_coche_rotulo'] ) ? sanitize_text_field( $_POST['welow_coche_rotulo'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'rotulo', $rotulo );

        $caracteristicas = isset( $_POST['welow_coche_caracteristicas'] ) ? sanitize_textarea_field( $_POST['welow_coche_caracteristicas'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'caracteristicas', $caracteristicas );
    }

    /**
     * Renderiza la columna marca/modelo en el listado admin.
     */
    public static function render_columna_marca_modelo( $post_id ) {
        $modelo_id = get_post_meta( $post_id, self::META_PREFIX . 'modelo', true );
        if ( ! $modelo_id ) {
            echo '<span style="color:#dc3232;">Sin modelo</span>';
            return;
        }
        $modelo   = get_post( $modelo_id );
        $marca_id = get_post_meta( $modelo_id, '_welow_modelo_marca', true );
        $marca    = $marca_id ? get_post( $marca_id ) : null;
        echo '<a href="' . esc_url( get_edit_post_link( $modelo_id ) ) . '">';
        echo esc_html( ( $marca ? $marca->post_title . ' ' : '' ) . ( $modelo ? $modelo->post_title : '' ) );
        echo '</a>';
    }
}
