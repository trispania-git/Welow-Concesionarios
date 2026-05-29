<?php
/**
 * CPT: welow_lead — Lead enviado desde un formulario.
 *
 * No editable como post normal: solo se ve la información del envío,
 * estado y notas internas. Se crea automáticamente al recibir un envío.
 *
 * @since 2.30.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_CPT_Lead {

    const POST_TYPE = 'welow_lead';
    const META_PREFIX = '_welow_lead_';

    public static $estados = array(
        'nuevo'        => 'Nuevo',
        'contactado'   => 'Contactado',
        'en_negociacion' => 'En negociación',
        'ganado'       => 'Ganado',
        'perdido'      => 'Perdido',
        'descartado'   => 'Descartado',
    );

    public static function init() {
        add_action( 'init', array( __CLASS__, 'registrar_cpt' ) );
        add_action( 'add_meta_boxes', array( __CLASS__, 'registrar_metaboxes' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'guardar_meta' ), 10, 2 );

        // Columnas en el listado
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columnas_admin' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'contenido_columnas' ), 10, 2 );
        add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( __CLASS__, 'columnas_ordenables' ) );

        // Filtro por estado
        add_action( 'restrict_manage_posts', array( __CLASS__, 'filtro_estado' ) );
        add_action( 'pre_get_posts', array( __CLASS__, 'aplicar_filtro_estado' ) );

        // Capability: ocultar "Añadir nuevo" (solo se crean desde el frontend)
        add_filter( 'register_post_type_args', array( __CLASS__, 'ajustar_args' ), 10, 2 );
    }

    public static function registrar_cpt() {
        register_post_type( self::POST_TYPE, array(
            'labels' => array(
                'name'          => 'Leads',
                'singular_name' => 'Lead',
                'edit_item'     => 'Ver lead',
                'all_items'     => 'Leads',
                'menu_name'     => 'Leads',
                'search_items'  => 'Buscar leads',
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false, // se cuelga del menú "Concesionarios"
            'supports'            => array( 'title' ),
            'capabilities'        => array(
                'create_posts' => 'do_not_allow', // no crear manualmente
            ),
            'map_meta_cap'        => true,
            'menu_icon'           => 'dashicons-businessperson',
        ) );
    }

    public static function ajustar_args( $args, $post_type ) {
        if ( self::POST_TYPE === $post_type ) {
            $args['capabilities']['create_posts'] = 'do_not_allow';
        }
        return $args;
    }

    /* =====================================================================
     * Metaboxes
     * ===================================================================== */
    public static function registrar_metaboxes() {
        add_meta_box( 'welow_lead_datos', 'Datos del envío',
            array( __CLASS__, 'render_metabox_datos' ),
            self::POST_TYPE, 'normal', 'high' );

        add_meta_box( 'welow_lead_contexto', 'Contexto de origen',
            array( __CLASS__, 'render_metabox_contexto' ),
            self::POST_TYPE, 'normal', 'default' );

        add_meta_box( 'welow_lead_estado', 'Estado y seguimiento',
            array( __CLASS__, 'render_metabox_estado' ),
            self::POST_TYPE, 'side', 'high' );
    }

    public static function render_metabox_datos( $post ) {
        wp_nonce_field( 'welow_lead_save', 'welow_lead_nonce' );
        $datos_json = get_post_meta( $post->ID, self::META_PREFIX . 'datos', true );
        $datos = $datos_json ? json_decode( $datos_json, true ) : array();
        if ( ! is_array( $datos ) || empty( $datos ) ) {
            echo '<p>Sin datos.</p>';
            return;
        }
        echo '<table class="form-table"><tbody>';
        foreach ( $datos as $key => $val ) {
            $val_html = is_array( $val ) ? esc_html( implode( ', ', $val ) ) : nl2br( esc_html( $val ) );
            echo '<tr>';
            echo '<th style="width:200px;">' . esc_html( $key ) . '</th>';
            echo '<td>' . $val_html . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    public static function render_metabox_contexto( $post ) {
        $id   = $post->ID;
        $form_id = intval( get_post_meta( $id, self::META_PREFIX . 'form_id', true ) );
        $url  = get_post_meta( $id, self::META_PREFIX . 'url_origen', true );
        $referrer = get_post_meta( $id, self::META_PREFIX . 'referrer', true );
        $ip   = get_post_meta( $id, self::META_PREFIX . 'ip', true );
        $ua   = get_post_meta( $id, self::META_PREFIX . 'user_agent', true );
        $contexto_json = get_post_meta( $id, self::META_PREFIX . 'contexto', true );
        $contexto = $contexto_json ? json_decode( $contexto_json, true ) : array();

        $form_post = $form_id ? get_post( $form_id ) : null;
        ?>
        <table class="form-table"><tbody>
            <tr>
                <th>Formulario</th>
                <td>
                    <?php if ( $form_post ) : ?>
                        <a href="<?php echo esc_url( get_edit_post_link( $form_id ) ); ?>"><?php echo esc_html( $form_post->post_title ); ?></a>
                        (<code><?php echo intval( $form_id ); ?></code>)
                    <?php else : ?>
                        Formulario eliminado (ID <?php echo intval( $form_id ); ?>)
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Página de envío</th>
                <td><?php if ( $url ) : ?><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $url ); ?></a><?php else : ?>—<?php endif; ?></td>
            </tr>
            <tr>
                <th>Referrer</th>
                <td><?php echo $referrer ? esc_html( $referrer ) : '<em>directo</em>'; ?></td>
            </tr>
            <?php if ( ! empty( $contexto ) ) :
                $labels = array(
                    'coche_id'         => 'Coche',
                    'modelo_id'        => 'Modelo',
                    'concesionario_id' => 'Concesionario',
                    'marca_id'         => 'Marca',
                    'utm_source'       => 'UTM source',
                    'utm_medium'       => 'UTM medium',
                    'utm_campaign'     => 'UTM campaign',
                    'utm_term'         => 'UTM term',
                    'utm_content'      => 'UTM content',
                );
                foreach ( $labels as $k => $label ) :
                    if ( empty( $contexto[ $k ] ) ) continue;
                    $val = $contexto[ $k ];
                    $html = esc_html( $val );
                    // Si es un ID de post conocido, enlazar
                    if ( in_array( $k, array( 'coche_id', 'modelo_id', 'concesionario_id', 'marca_id' ), true ) && is_numeric( $val ) ) {
                        $p = get_post( intval( $val ) );
                        if ( $p ) $html = '<a href="' . esc_url( get_edit_post_link( $p->ID ) ) . '">' . esc_html( $p->post_title ) . '</a>';
                    }
                    echo '<tr><th>' . esc_html( $label ) . '</th><td>' . $html . '</td></tr>';
                endforeach;
            endif; ?>
            <tr>
                <th>IP</th>
                <td><?php echo $ip ? esc_html( $ip ) : '—'; ?></td>
            </tr>
            <tr>
                <th>User-Agent</th>
                <td style="font-size:11px;color:#64748b;word-break:break-all;"><?php echo $ua ? esc_html( $ua ) : '—'; ?></td>
            </tr>
        </tbody></table>
        <?php
    }

    public static function render_metabox_estado( $post ) {
        $estado = get_post_meta( $post->ID, self::META_PREFIX . 'estado', true ) ?: 'nuevo';
        $notas  = get_post_meta( $post->ID, self::META_PREFIX . 'notas', true );
        ?>
        <p>
            <label for="welow_lead_estado_field"><strong>Estado:</strong></label>
            <select id="welow_lead_estado_field" name="welow_lead_estado" class="widefat">
                <?php foreach ( self::$estados as $val => $label ) : ?>
                    <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $estado, $val ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="welow_lead_notas_field"><strong>Notas internas:</strong></label>
            <textarea id="welow_lead_notas_field" name="welow_lead_notas" rows="5" class="widefat"
                      placeholder="Llamado el X, prefiere whatsapp, presupuesto aprobado..."><?php echo esc_textarea( $notas ); ?></textarea>
        </p>
        <?php
    }

    /* =====================================================================
     * Guardar (estado + notas)
     * ===================================================================== */
    public static function guardar_meta( $post_id, $post ) {
        if ( ! isset( $_POST['welow_lead_nonce'] ) || ! wp_verify_nonce( $_POST['welow_lead_nonce'], 'welow_lead_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        if ( isset( $_POST['welow_lead_estado'] ) ) {
            $estado = sanitize_key( $_POST['welow_lead_estado'] );
            if ( isset( self::$estados[ $estado ] ) ) {
                update_post_meta( $post_id, self::META_PREFIX . 'estado', $estado );
            }
        }
        if ( isset( $_POST['welow_lead_notas'] ) ) {
            update_post_meta( $post_id, self::META_PREFIX . 'notas', sanitize_textarea_field( wp_unslash( $_POST['welow_lead_notas'] ) ) );
        }
    }

    /* =====================================================================
     * Columnas listado admin
     * ===================================================================== */
    public static function columnas_admin( $columns ) {
        return array(
            'cb'         => $columns['cb'] ?? '',
            'title'      => 'Nombre / Asunto',
            'welow_form' => 'Formulario',
            'welow_email'=> 'Email',
            'welow_tel'  => 'Teléfono',
            'welow_ctx'  => 'Contexto',
            'welow_estado' => 'Estado',
            'date'       => 'Fecha',
        );
    }

    public static function contenido_columnas( $column, $post_id ) {
        $datos_json = get_post_meta( $post_id, self::META_PREFIX . 'datos', true );
        $datos = $datos_json ? json_decode( $datos_json, true ) : array();
        switch ( $column ) {
            case 'welow_form':
                $fid = intval( get_post_meta( $post_id, self::META_PREFIX . 'form_id', true ) );
                $f   = $fid ? get_post( $fid ) : null;
                echo $f ? esc_html( $f->post_title ) : '<span style="color:#94a3b8;">—</span>';
                break;
            case 'welow_email':
                $em = $datos['email'] ?? '';
                echo $em ? '<a href="mailto:' . esc_attr( $em ) . '">' . esc_html( $em ) . '</a>' : '—';
                break;
            case 'welow_tel':
                $tel = $datos['telefono'] ?? '';
                echo $tel ? '<a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $tel ) ) . '">' . esc_html( $tel ) . '</a>' : '—';
                break;
            case 'welow_ctx':
                $ctx_json = get_post_meta( $post_id, self::META_PREFIX . 'contexto', true );
                $ctx = $ctx_json ? json_decode( $ctx_json, true ) : array();
                $bits = array();
                foreach ( array( 'coche_id', 'modelo_id', 'concesionario_id' ) as $k ) {
                    if ( empty( $ctx[ $k ] ) ) continue;
                    $p = get_post( intval( $ctx[ $k ] ) );
                    if ( $p ) $bits[] = esc_html( $p->post_title );
                }
                if ( ! empty( $ctx['utm_source'] ) ) $bits[] = '<em>UTM:' . esc_html( $ctx['utm_source'] ) . '</em>';
                echo $bits ? implode( ' · ', $bits ) : '—';
                break;
            case 'welow_estado':
                $estado = get_post_meta( $post_id, self::META_PREFIX . 'estado', true ) ?: 'nuevo';
                $label  = self::$estados[ $estado ] ?? $estado;
                $colores = array(
                    'nuevo'          => '#2563eb',
                    'contactado'     => '#0891b2',
                    'en_negociacion' => '#ca8a04',
                    'ganado'         => '#16a34a',
                    'perdido'        => '#dc2626',
                    'descartado'     => '#64748b',
                );
                $color = $colores[ $estado ] ?? '#64748b';
                echo '<span style="background:' . esc_attr( $color ) . ';color:#fff;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;">' . esc_html( $label ) . '</span>';
                break;
        }
    }

    public static function columnas_ordenables( $columns ) {
        $columns['welow_estado'] = 'welow_estado';
        return $columns;
    }

    public static function filtro_estado() {
        global $typenow;
        if ( self::POST_TYPE !== $typenow ) return;
        $sel = isset( $_GET['welow_lead_estado'] ) ? sanitize_key( $_GET['welow_lead_estado'] ) : '';
        echo '<select name="welow_lead_estado">';
        echo '<option value="">Todos los estados</option>';
        foreach ( self::$estados as $val => $label ) {
            printf( '<option value="%s" %s>%s</option>',
                esc_attr( $val ),
                selected( $sel, $val, false ),
                esc_html( $label )
            );
        }
        echo '</select>';
    }

    public static function aplicar_filtro_estado( $query ) {
        global $pagenow, $typenow;
        if ( ! is_admin() || 'edit.php' !== $pagenow || self::POST_TYPE !== $typenow ) return;
        if ( empty( $_GET['welow_lead_estado'] ) ) return;
        $estado = sanitize_key( $_GET['welow_lead_estado'] );
        $meta = array(
            array( 'key' => self::META_PREFIX . 'estado', 'value' => $estado, 'compare' => '=' ),
        );
        // Caso especial nuevo: incluir leads sin meta (recién creados)
        if ( 'nuevo' === $estado ) {
            $meta = array(
                'relation' => 'OR',
                array( 'key' => self::META_PREFIX . 'estado', 'value' => 'nuevo', 'compare' => '=' ),
                array( 'key' => self::META_PREFIX . 'estado', 'compare' => 'NOT EXISTS' ),
            );
        }
        $query->set( 'meta_query', $meta );
    }
}
