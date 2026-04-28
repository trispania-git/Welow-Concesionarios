<?php
/**
 * CPT: Concesionario físico (ubicación de venta).
 *
 * @since 2.0.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_CPT_Concesionario {

    const POST_TYPE   = 'welow_concesionario';
    const META_PREFIX = '_welow_conc_';

    public static function init() {
        add_action( 'init', array( __CLASS__, 'registrar_cpt' ) );
        add_action( 'add_meta_boxes', array( __CLASS__, 'registrar_metaboxes' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'guardar_meta' ), 10, 2 );

        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columnas_admin' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'contenido_columnas' ), 10, 2 );
    }

    public static function registrar_cpt() {
        $labels = array(
            'name'                  => 'Concesionarios físicos',
            'singular_name'        => 'Concesionario',
            'menu_name'            => 'Concesionarios',
            'add_new'              => 'Añadir concesionario',
            'add_new_item'         => 'Añadir nuevo concesionario',
            'edit_item'            => 'Editar concesionario',
            'view_item'            => 'Ver concesionario',
            'all_items'            => 'Todos los concesionarios',
            'search_items'         => 'Buscar concesionarios',
            'not_found'            => 'No se encontraron concesionarios',
            'not_found_in_trash'   => 'No hay concesionarios en la papelera',
            'featured_image'       => 'Logo / imagen',
            'set_featured_image'   => 'Establecer imagen',
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => 'welow_concesionarios',
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => array( 'slug' => 'concesionario', 'with_front' => false ),
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_icon'           => 'dashicons-store',
            'supports'            => array( 'title', 'editor', 'thumbnail' ),
        );

        register_post_type( self::POST_TYPE, $args );
    }

    public static function registrar_metaboxes() {
        add_meta_box( 'welow_conc_contacto', 'Contacto y ubicación',
            array( __CLASS__, 'render_metabox_contacto' ),
            self::POST_TYPE, 'normal', 'high' );

        add_meta_box( 'welow_conc_marcas', 'Marcas representadas',
            array( __CLASS__, 'render_metabox_marcas' ),
            self::POST_TYPE, 'side', 'default' );

        add_meta_box( 'welow_conc_config', 'Configuración',
            array( __CLASS__, 'render_metabox_config' ),
            self::POST_TYPE, 'side', 'default' );
    }

    public static function render_metabox_contacto( $post ) {
        wp_nonce_field( 'welow_conc_save', 'welow_conc_nonce' );

        $direccion = get_post_meta( $post->ID, self::META_PREFIX . 'direccion', true );
        $cp        = get_post_meta( $post->ID, self::META_PREFIX . 'cp', true );
        $ciudad    = get_post_meta( $post->ID, self::META_PREFIX . 'ciudad', true );
        $provincia = get_post_meta( $post->ID, self::META_PREFIX . 'provincia', true );
        $telefono  = get_post_meta( $post->ID, self::META_PREFIX . 'telefono', true );
        $email     = get_post_meta( $post->ID, self::META_PREFIX . 'email', true );
        $horario   = get_post_meta( $post->ID, self::META_PREFIX . 'horario', true );
        $lat       = get_post_meta( $post->ID, self::META_PREFIX . 'lat', true );
        $lng       = get_post_meta( $post->ID, self::META_PREFIX . 'lng', true );
        ?>
        <table class="form-table welow-metabox-table">
            <tr>
                <th><label for="welow_conc_direccion">Dirección</label></th>
                <td><input type="text" id="welow_conc_direccion" name="welow_conc_direccion"
                           value="<?php echo esc_attr( $direccion ); ?>" class="large-text" /></td>
            </tr>
            <tr>
                <th><label>Código postal / Ciudad / Provincia</label></th>
                <td>
                    <input type="text" name="welow_conc_cp" value="<?php echo esc_attr( $cp ); ?>"
                           placeholder="C.P." style="width: 100px;" />
                    <input type="text" name="welow_conc_ciudad" value="<?php echo esc_attr( $ciudad ); ?>"
                           placeholder="Ciudad" style="width: 200px;" />
                    <input type="text" name="welow_conc_provincia" value="<?php echo esc_attr( $provincia ); ?>"
                           placeholder="Provincia" style="width: 200px;" />
                </td>
            </tr>
            <tr>
                <th><label>Teléfono / Email</label></th>
                <td>
                    <input type="text" name="welow_conc_telefono" value="<?php echo esc_attr( $telefono ); ?>"
                           placeholder="Teléfono" style="width: 180px;" />
                    <input type="email" name="welow_conc_email" value="<?php echo esc_attr( $email ); ?>"
                           placeholder="Email" style="width: 280px;" />
                </td>
            </tr>
            <tr>
                <th><label for="welow_conc_horario">Horario</label></th>
                <td><textarea id="welow_conc_horario" name="welow_conc_horario" rows="3"
                              class="large-text" placeholder="Lunes a Viernes 9:00–14:00 / 16:30–20:00..."><?php echo esc_textarea( $horario ); ?></textarea></td>
            </tr>
            <tr>
                <th><label>Coordenadas mapa</label></th>
                <td>
                    <input type="text" name="welow_conc_lat" value="<?php echo esc_attr( $lat ); ?>"
                           placeholder="Latitud" style="width: 160px;" />
                    <input type="text" name="welow_conc_lng" value="<?php echo esc_attr( $lng ); ?>"
                           placeholder="Longitud" style="width: 160px;" />
                    <p class="description">Para mostrar el mapa en la ficha del coche.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function render_metabox_marcas( $post ) {
        $marcas_guardadas = get_post_meta( $post->ID, self::META_PREFIX . 'marcas', true );
        if ( ! is_array( $marcas_guardadas ) ) $marcas_guardadas = array();

        $marcas = get_posts( array(
            'post_type' => 'welow_marca', 'post_status' => 'publish',
            'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC',
        ) );

        if ( empty( $marcas ) ) {
            echo '<p><em>Aún no hay marcas creadas.</em></p>';
            return;
        }
        ?>
        <p class="description" style="margin-top:0;">Marcas que vende este concesionario:</p>
        <?php foreach ( $marcas as $marca ) : ?>
            <label class="welow-checkbox-label" style="display:block;">
                <input type="checkbox" name="welow_conc_marcas[]"
                       value="<?php echo esc_attr( $marca->ID ); ?>"
                       <?php checked( in_array( $marca->ID, $marcas_guardadas ) ); ?> />
                <?php echo esc_html( $marca->post_title ); ?>
            </label>
        <?php endforeach;
    }

    public static function render_metabox_config( $post ) {
        $orden  = get_post_meta( $post->ID, self::META_PREFIX . 'orden', true );
        $activo = get_post_meta( $post->ID, self::META_PREFIX . 'activo', true );
        if ( '' === $activo ) $activo = '1';
        ?>
        <p>
            <label><strong>Orden:</strong></label><br>
            <input type="number" name="welow_conc_orden" value="<?php echo esc_attr( $orden ); ?>"
                   min="0" step="1" style="width: 80px;" />
        </p>
        <p>
            <label class="welow-checkbox-label">
                <input type="checkbox" name="welow_conc_activo" value="1" <?php checked( $activo, '1' ); ?> />
                <strong>Activo</strong>
            </label>
        </p>
        <?php
    }

    public static function guardar_meta( $post_id, $post ) {
        if ( ! isset( $_POST['welow_conc_nonce'] ) || ! wp_verify_nonce( $_POST['welow_conc_nonce'], 'welow_conc_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $campos_texto = array(
            'welow_conc_direccion' => 'direccion',
            'welow_conc_cp'        => 'cp',
            'welow_conc_ciudad'    => 'ciudad',
            'welow_conc_provincia' => 'provincia',
            'welow_conc_telefono'  => 'telefono',
            'welow_conc_lat'       => 'lat',
            'welow_conc_lng'       => 'lng',
        );
        foreach ( $campos_texto as $post_key => $meta_key ) {
            $val = isset( $_POST[ $post_key ] ) ? sanitize_text_field( $_POST[ $post_key ] ) : '';
            update_post_meta( $post_id, self::META_PREFIX . $meta_key, $val );
        }

        $email = isset( $_POST['welow_conc_email'] ) ? sanitize_email( $_POST['welow_conc_email'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'email', $email );

        $horario = isset( $_POST['welow_conc_horario'] ) ? sanitize_textarea_field( $_POST['welow_conc_horario'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'horario', $horario );

        $marcas = isset( $_POST['welow_conc_marcas'] ) ? array_map( 'absint', $_POST['welow_conc_marcas'] ) : array();
        update_post_meta( $post_id, self::META_PREFIX . 'marcas', $marcas );

        $orden = isset( $_POST['welow_conc_orden'] ) ? absint( $_POST['welow_conc_orden'] ) : 0;
        update_post_meta( $post_id, self::META_PREFIX . 'orden', $orden );

        $activo = isset( $_POST['welow_conc_activo'] ) ? '1' : '0';
        update_post_meta( $post_id, self::META_PREFIX . 'activo', $activo );
    }

    public static function columnas_admin( $columns ) {
        $new = array();
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( 'title' === $key ) {
                $new['welow_logo']    = 'Logo';
                $new['welow_ciudad']  = 'Ciudad';
                $new['welow_telefono'] = 'Teléfono';
                $new['welow_activo']  = 'Activo';
            }
        }
        unset( $new['date'] );
        return $new;
    }

    public static function contenido_columnas( $column, $post_id ) {
        switch ( $column ) {
            case 'welow_logo':
                $thumb = get_the_post_thumbnail( $post_id, array( 60, 60 ) );
                echo $thumb ? $thumb : '<span style="color:#ccc;">—</span>';
                break;
            case 'welow_ciudad':
                $ciudad = get_post_meta( $post_id, self::META_PREFIX . 'ciudad', true );
                $prov   = get_post_meta( $post_id, self::META_PREFIX . 'provincia', true );
                echo esc_html( trim( $ciudad . ( $prov ? " ($prov)" : '' ) ) ?: '—' );
                break;
            case 'welow_telefono':
                echo esc_html( get_post_meta( $post_id, self::META_PREFIX . 'telefono', true ) ?: '—' );
                break;
            case 'welow_activo':
                $activo = get_post_meta( $post_id, self::META_PREFIX . 'activo', true );
                echo ( '1' === $activo || '' === $activo )
                    ? '<span class="dashicons dashicons-yes-alt" style="color:#46b450;"></span>'
                    : '<span class="dashicons dashicons-dismiss" style="color:#dc3232;"></span>';
                break;
        }
    }
}
