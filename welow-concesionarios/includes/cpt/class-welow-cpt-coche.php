<?php
/**
 * CPT: Coche (unidad concreta en venta).
 *
 * Cada post = un vehículo físico individual con su km, año, fotos y precio reales.
 * Se vincula a un welow_modelo y hereda sus datos genéricos (combustible, plazas, etc.).
 *
 * @since 2.0.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_CPT_Coche {

    const POST_TYPE   = 'welow_coche';
    const META_PREFIX = '_welow_coche_';
    const GALERIA_MAX = 30;

    /* Opciones de selects */

    public static function get_tipo_venta_options() {
        return array(
            'ocasion' => 'Ocasión',
            'km0'     => 'KM0',
            'nuevo'   => 'Nuevo',
        );
    }

    public static function get_estado_options() {
        return array(
            'disponible' => 'Disponible',
            'reservado'  => 'Reservado',
            'vendido'    => 'Vendido',
        );
    }

    public static function get_cambio_options() {
        return array(
            'manual'         => 'Manual',
            'automatico'     => 'Automático',
            'semiautomatico' => 'Semiautomático',
        );
    }

    public static function get_tipo_pintura_options() {
        return array(
            'solida'     => 'Sólida',
            'metalizada' => 'Metalizada',
            'mate'       => 'Mate',
            'perlada'    => 'Perlada',
        );
    }

    public static function get_etiqueta_dgt_options() {
        return array(
            '0'   => 'Cero (0 azul)',
            'eco' => 'ECO',
            'c'   => 'C (verde)',
            'b'   => 'B (amarilla)',
            'sin' => 'Sin distintivo',
        );
    }

    public static function init() {
        add_action( 'init', array( __CLASS__, 'registrar_cpt' ) );
        add_action( 'add_meta_boxes', array( __CLASS__, 'registrar_metaboxes' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'guardar_meta' ), 10, 2 );

        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columnas_admin' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'contenido_columnas' ), 10, 2 );
        add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( __CLASS__, 'columnas_ordenables' ) );
    }

    public static function registrar_cpt() {
        $labels = array(
            'name'                  => 'Coches',
            'singular_name'        => 'Coche',
            'menu_name'            => 'Coches',
            'add_new'              => 'Añadir coche',
            'add_new_item'         => 'Añadir nuevo coche',
            'edit_item'            => 'Editar coche',
            'view_item'            => 'Ver coche',
            'all_items'            => 'Todos los coches',
            'search_items'         => 'Buscar coches',
            'not_found'            => 'No se encontraron coches',
            'not_found_in_trash'   => 'No hay coches en la papelera',
            'featured_image'       => 'Imagen principal',
            'set_featured_image'   => 'Establecer imagen principal',
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => 'welow_concesionarios',
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => array( 'slug' => 'coche', 'with_front' => false ),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_icon'           => 'dashicons-car',
            'supports'            => array( 'title', 'thumbnail', 'excerpt' ),
            'taxonomies'          => array( 'welow_combustible', 'welow_categoria_modelo' ),
        );

        register_post_type( self::POST_TYPE, $args );
    }

    public static function registrar_metaboxes() {
        $boxes = array(
            array( 'id' => 'welow_coche_identificacion', 'titulo' => 'A. Identificación',     'callback' => 'render_metabox_identificacion', 'context' => 'normal',  'priority' => 'high' ),
            array( 'id' => 'welow_coche_precio',         'titulo' => 'B. Precio',              'callback' => 'render_metabox_precio',         'context' => 'normal',  'priority' => 'high' ),
            array( 'id' => 'welow_coche_tecnico',        'titulo' => 'C. Datos técnicos',      'callback' => 'render_metabox_tecnico',        'context' => 'normal',  'priority' => 'default' ),
            array( 'id' => 'welow_coche_galeria',        'titulo' => 'D. Galería de imágenes', 'callback' => 'render_metabox_galeria',        'context' => 'normal',  'priority' => 'default' ),
            array( 'id' => 'welow_coche_equipamiento',   'titulo' => 'E. Equipamiento',        'callback' => 'render_metabox_equipamiento',   'context' => 'normal',  'priority' => 'default' ),
            array( 'id' => 'welow_coche_garantias',      'titulo' => 'F. Garantías',           'callback' => 'render_metabox_garantias',      'context' => 'normal',  'priority' => 'default' ),
            array( 'id' => 'welow_coche_comercial',      'titulo' => 'G. Comercial',           'callback' => 'render_metabox_comercial',      'context' => 'side',    'priority' => 'high' ),
            array( 'id' => 'welow_coche_privados',       'titulo' => 'H. Datos privados',      'callback' => 'render_metabox_privados',       'context' => 'side',    'priority' => 'low' ),
        );

        foreach ( $boxes as $box ) {
            add_meta_box( $box['id'], $box['titulo'],
                array( __CLASS__, $box['callback'] ),
                self::POST_TYPE, $box['context'], $box['priority'] );
        }
    }

    /* ========================================================================
       METABOX A: Identificación
       ======================================================================== */
    public static function render_metabox_identificacion( $post ) {
        wp_nonce_field( 'welow_coche_save', 'welow_coche_nonce' );

        $modelo_id    = get_post_meta( $post->ID, self::META_PREFIX . 'modelo', true );
        $version      = get_post_meta( $post->ID, self::META_PREFIX . 'version', true );
        $tipo_venta   = get_post_meta( $post->ID, self::META_PREFIX . 'tipo_venta', true );
        $estado       = get_post_meta( $post->ID, self::META_PREFIX . 'estado', true );
        $referencia   = get_post_meta( $post->ID, self::META_PREFIX . 'referencia', true );
        $mes          = get_post_meta( $post->ID, self::META_PREFIX . 'mes_matricula', true );
        $anio         = get_post_meta( $post->ID, self::META_PREFIX . 'anio_matricula', true );
        $km           = get_post_meta( $post->ID, self::META_PREFIX . 'km', true );

        if ( '' === $tipo_venta ) $tipo_venta = 'ocasion';
        if ( '' === $estado ) $estado = 'disponible';

        // Modelos para el selector, agrupados por marca
        $modelos = get_posts( array(
            'post_type' => 'welow_modelo', 'post_status' => 'publish',
            'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC',
        ) );
        ?>
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
                    <p class="description">El coche heredará carrocería, plazas y otros datos del modelo.</p>
                </td>
            </tr>
            <tr>
                <th><label for="welow_coche_version">Versión / Acabado</label></th>
                <td><input type="text" id="welow_coche_version" name="welow_coche_version"
                           value="<?php echo esc_attr( $version ); ?>" class="large-text"
                           placeholder="ej: 1.0 VVT-I 72CV Play" /></td>
            </tr>
            <tr>
                <th><label>Tipo de venta / Estado</label></th>
                <td>
                    <select name="welow_coche_tipo_venta">
                        <?php foreach ( self::get_tipo_venta_options() as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $tipo_venta, $key ); ?>>
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
                <th><label for="welow_coche_referencia">Referencia</label></th>
                <td><input type="text" id="welow_coche_referencia" name="welow_coche_referencia"
                           value="<?php echo esc_attr( $referencia ); ?>" class="regular-text"
                           placeholder="ej: 7539" /></td>
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
                <td><input type="number" id="welow_coche_km" name="welow_coche_km"
                           value="<?php echo esc_attr( $km ); ?>" min="0" step="1" style="width:140px;" /> km</td>
            </tr>
        </table>
        <?php
    }

    /* ========================================================================
       METABOX B: Precio
       ======================================================================== */
    public static function render_metabox_precio( $post ) {
        $contado    = get_post_meta( $post->ID, self::META_PREFIX . 'precio_contado', true );
        $financiado = get_post_meta( $post->ID, self::META_PREFIX . 'precio_financiado', true );
        $anterior   = get_post_meta( $post->ID, self::META_PREFIX . 'precio_anterior', true );
        $cuota      = get_post_meta( $post->ID, self::META_PREFIX . 'cuota', true );
        $disclaimer = get_post_meta( $post->ID, self::META_PREFIX . 'disclaimer', true );
        $moneda     = class_exists( 'Welow_Settings' ) ? Welow_Settings::get( 'moneda_simbolo', '€' ) : '€';
        ?>
        <table class="form-table welow-metabox-table">
            <tr>
                <th><label>Precios</label></th>
                <td>
                    <label style="display:inline-block;margin-right:18px;">
                        Al contado:<br>
                        <input type="number" name="welow_coche_precio_contado" value="<?php echo esc_attr( $contado ); ?>"
                               min="0" step="0.01" style="width:130px;" /> <?php echo esc_html( $moneda ); ?>
                    </label>
                    <label style="display:inline-block;margin-right:18px;">
                        Financiado:<br>
                        <input type="number" name="welow_coche_precio_financiado" value="<?php echo esc_attr( $financiado ); ?>"
                               min="0" step="0.01" style="width:130px;" /> <?php echo esc_html( $moneda ); ?>
                    </label>
                    <label style="display:inline-block;margin-right:18px;">
                        Anterior (tachado):<br>
                        <input type="number" name="welow_coche_precio_anterior" value="<?php echo esc_attr( $anterior ); ?>"
                               min="0" step="0.01" style="width:130px;" /> <?php echo esc_html( $moneda ); ?>
                    </label>
                    <label style="display:inline-block;">
                        Cuota mensual:<br>
                        <input type="number" name="welow_coche_cuota" value="<?php echo esc_attr( $cuota ); ?>"
                               min="0" step="0.01" style="width:130px;" /> <?php echo esc_html( $moneda ); ?>/mes
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="welow_coche_disclaimer">Disclaimer (override)</label></th>
                <td>
                    <textarea id="welow_coche_disclaimer" name="welow_coche_disclaimer"
                              rows="4" class="large-text"
                              placeholder="Si lo dejas vacío, se usa el global de Configuraciones."><?php echo esc_textarea( $disclaimer ); ?></textarea>
                </td>
            </tr>
        </table>
        <?php
    }

    /* ========================================================================
       METABOX C: Datos técnicos básicos
       ======================================================================== */
    public static function render_metabox_tecnico( $post ) {
        $cambio       = get_post_meta( $post->ID, self::META_PREFIX . 'cambio', true );
        $marchas      = get_post_meta( $post->ID, self::META_PREFIX . 'marchas', true );
        $cv           = get_post_meta( $post->ID, self::META_PREFIX . 'cv', true );
        $kw           = get_post_meta( $post->ID, self::META_PREFIX . 'kw', true );
        $cilindrada   = get_post_meta( $post->ID, self::META_PREFIX . 'cilindrada', true );
        $plazas       = get_post_meta( $post->ID, self::META_PREFIX . 'plazas', true );
        $puertas      = get_post_meta( $post->ID, self::META_PREFIX . 'puertas', true );
        $color        = get_post_meta( $post->ID, self::META_PREFIX . 'color', true );
        $tipo_pintura = get_post_meta( $post->ID, self::META_PREFIX . 'tipo_pintura', true );
        $etiqueta_dgt = get_post_meta( $post->ID, self::META_PREFIX . 'etiqueta_dgt', true );
        ?>
        <p class="description" style="margin-top:0;">
            Combustible y carrocería se gestionan en el lateral derecho (taxonomías nativas).
        </p>
        <table class="form-table welow-metabox-table">
            <tr>
                <th><label>Cambio / Marchas</label></th>
                <td>
                    <select name="welow_coche_cambio">
                        <option value="">— Seleccionar —</option>
                        <?php foreach ( self::get_cambio_options() as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $cambio, $key ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="welow_coche_marchas" value="<?php echo esc_attr( $marchas ); ?>"
                           min="1" max="10" placeholder="N° marchas" style="width:110px;margin-left:10px;" />
                </td>
            </tr>
            <tr>
                <th><label>Potencia</label></th>
                <td>
                    <input type="number" name="welow_coche_cv" value="<?php echo esc_attr( $cv ); ?>"
                           min="0" step="1" placeholder="CV" style="width:90px;" /> CV
                    <input type="number" name="welow_coche_kw" value="<?php echo esc_attr( $kw ); ?>"
                           min="0" step="1" placeholder="kW" style="width:90px;margin-left:10px;" /> kW
                    <p class="description">Si solo rellenas uno de los dos, el otro se calcula automáticamente (kW = CV × 0.7355).</p>
                </td>
            </tr>
            <tr>
                <th><label for="welow_coche_cilindrada">Cilindrada (cc)</label></th>
                <td>
                    <input type="number" id="welow_coche_cilindrada" name="welow_coche_cilindrada"
                           value="<?php echo esc_attr( $cilindrada ); ?>" min="0" step="1" style="width:120px;" /> cc
                </td>
            </tr>
            <tr>
                <th><label>Plazas / Puertas</label></th>
                <td>
                    <input type="number" name="welow_coche_plazas" value="<?php echo esc_attr( $plazas ); ?>"
                           min="1" max="20" placeholder="Plazas" style="width:90px;" />
                    <input type="number" name="welow_coche_puertas" value="<?php echo esc_attr( $puertas ); ?>"
                           min="1" max="6" placeholder="Puertas" style="width:90px;margin-left:10px;" />
                </td>
            </tr>
            <tr>
                <th><label for="welow_coche_color">Color</label></th>
                <td>
                    <input type="text" id="welow_coche_color" name="welow_coche_color"
                           value="<?php echo esc_attr( $color ); ?>" class="regular-text" />
                    <select name="welow_coche_tipo_pintura" style="margin-left:10px;">
                        <option value="">Tipo pintura</option>
                        <?php foreach ( self::get_tipo_pintura_options() as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $tipo_pintura, $key ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="welow_coche_etiqueta_dgt">Etiqueta DGT</label></th>
                <td>
                    <select id="welow_coche_etiqueta_dgt" name="welow_coche_etiqueta_dgt">
                        <option value="">— Seleccionar —</option>
                        <?php foreach ( self::get_etiqueta_dgt_options() as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $etiqueta_dgt, $key ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /* ========================================================================
       METABOX D: Galería
       ======================================================================== */
    public static function render_metabox_galeria( $post ) {
        $galeria = get_post_meta( $post->ID, self::META_PREFIX . 'galeria', true );
        if ( ! is_array( $galeria ) ) $galeria = array();
        $video = get_post_meta( $post->ID, self::META_PREFIX . 'video', true );
        ?>
        <p class="description" style="margin-top:0;">
            La <strong>imagen principal</strong> se establece como Imagen Destacada (panel lateral derecho).
            Aquí añade hasta <strong><?php echo self::GALERIA_MAX; ?> imágenes adicionales</strong>.
        </p>

        <input type="hidden" id="welow_coche_galeria" name="welow_coche_galeria"
               value="<?php echo esc_attr( implode( ',', $galeria ) ); ?>" />

        <div class="welow-galeria-coche">
            <div id="welow-galeria-thumbs" class="welow-galeria-thumbs">
                <?php foreach ( $galeria as $img_id ) :
                    $url = wp_get_attachment_image_url( $img_id, 'thumbnail' );
                    if ( ! $url ) continue;
                ?>
                    <div class="welow-galeria-thumb" data-id="<?php echo esc_attr( $img_id ); ?>">
                        <img src="<?php echo esc_url( $url ); ?>" alt="" />
                        <button type="button" class="welow-galeria-remove" title="Quitar">×</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" class="button button-primary" id="welow-galeria-add">
                <span class="dashicons dashicons-format-gallery" style="margin-top:4px;"></span>
                Añadir imágenes
            </button>
            <span class="welow-galeria-count">
                <span id="welow-galeria-current"><?php echo count( $galeria ); ?></span>
                / <?php echo self::GALERIA_MAX; ?> imágenes
            </span>
        </div>

        <table class="form-table welow-metabox-table" style="margin-top:20px;">
            <tr>
                <th><label for="welow_coche_video">URL de vídeo</label></th>
                <td>
                    <input type="url" id="welow_coche_video" name="welow_coche_video"
                           value="<?php echo esc_url( $video ); ?>" class="large-text"
                           placeholder="https://www.youtube.com/watch?v=..." />
                    <p class="description">Opcional. Soporta YouTube y Vimeo.</p>
                </td>
            </tr>
        </table>

        <style>
            .welow-galeria-coche { margin-bottom: 15px; }
            .welow-galeria-thumbs {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 10px;
                margin-bottom: 15px;
                min-height: 50px;
            }
            .welow-galeria-thumb {
                position: relative;
                border: 2px solid #e2e8f0;
                border-radius: 6px;
                overflow: hidden;
                aspect-ratio: 4/3;
                cursor: move;
            }
            .welow-galeria-thumb img {
                width: 100%; height: 100%; object-fit: cover; display: block;
            }
            .welow-galeria-remove {
                position: absolute; top: 4px; right: 4px;
                width: 24px; height: 24px; border-radius: 50%;
                background: rgba(220, 38, 38, 0.9); color: white;
                border: none; cursor: pointer; font-size: 16px;
                line-height: 1; padding: 0;
            }
            .welow-galeria-remove:hover { background: #dc2626; }
            .welow-galeria-count {
                margin-left: 15px;
                color: #64748b;
                font-size: 13px;
            }
        </style>

        <script>
        jQuery(function($){
            var maxImages = <?php echo self::GALERIA_MAX; ?>;
            var $hidden   = $('#welow_coche_galeria');
            var $thumbs   = $('#welow-galeria-thumbs');
            var $count    = $('#welow-galeria-current');

            function getIds() {
                return $hidden.val() ? $hidden.val().split(',').filter(Boolean) : [];
            }
            function setIds(ids) {
                $hidden.val(ids.join(','));
                $count.text(ids.length);
            }

            $('#welow-galeria-add').on('click', function(e){
                e.preventDefault();
                var current = getIds();
                if (current.length >= maxImages) {
                    alert('Máximo ' + maxImages + ' imágenes.');
                    return;
                }

                var frame = wp.media({
                    title: 'Seleccionar imágenes para la galería',
                    button: { text: 'Añadir a galería' },
                    multiple: true,
                    library: { type: 'image' }
                });

                frame.on('select', function(){
                    var selection = frame.state().get('selection');
                    var current = getIds();
                    selection.each(function(att){
                        if (current.length >= maxImages) return;
                        var id = att.id.toString();
                        if (current.indexOf(id) !== -1) return;
                        current.push(id);
                        var thumbUrl = att.attributes.sizes && att.attributes.sizes.thumbnail
                            ? att.attributes.sizes.thumbnail.url
                            : att.attributes.url;
                        $thumbs.append(
                            '<div class="welow-galeria-thumb" data-id="' + id + '">' +
                            '<img src="' + thumbUrl + '" alt="" />' +
                            '<button type="button" class="welow-galeria-remove" title="Quitar">×</button>' +
                            '</div>'
                        );
                    });
                    setIds(current);
                });
                frame.open();
            });

            $thumbs.on('click', '.welow-galeria-remove', function(){
                var $thumb = $(this).closest('.welow-galeria-thumb');
                var id = $thumb.data('id').toString();
                $thumb.remove();
                var current = getIds().filter(function(x){ return x !== id; });
                setIds(current);
            });

            // Reordenamiento por drag
            if ($.fn.sortable) {
                $thumbs.sortable({
                    update: function(){
                        var ids = $thumbs.find('.welow-galeria-thumb').map(function(){
                            return $(this).data('id').toString();
                        }).get();
                        setIds(ids);
                    }
                });
            }
        });
        </script>
        <?php
    }

    /* ========================================================================
       METABOX E: Equipamiento (WYSIWYG)
       ======================================================================== */
    public static function render_metabox_equipamiento( $post ) {
        $equipamiento = get_post_meta( $post->ID, self::META_PREFIX . 'equipamiento', true );
        wp_editor( $equipamiento, 'welow_coche_equipamiento', array(
            'textarea_name' => 'welow_coche_equipamiento',
            'media_buttons' => false,
            'textarea_rows' => 12,
            'teeny'         => false,
        ) );
    }

    /* ========================================================================
       METABOX F: Garantías (WYSIWYG)
       ======================================================================== */
    public static function render_metabox_garantias( $post ) {
        $garantias = get_post_meta( $post->ID, self::META_PREFIX . 'garantias', true );
        wp_editor( $garantias, 'welow_coche_garantias', array(
            'textarea_name' => 'welow_coche_garantias',
            'media_buttons' => false,
            'textarea_rows' => 8,
            'teeny'         => true,
        ) );
    }

    /* ========================================================================
       METABOX G: Comercial (sidebar)
       ======================================================================== */
    public static function render_metabox_comercial( $post ) {
        $concesionario_id = get_post_meta( $post->ID, self::META_PREFIX . 'concesionario', true );
        $programa         = get_post_meta( $post->ID, self::META_PREFIX . 'programa', true );

        $concesionarios = get_posts( array(
            'post_type' => 'welow_concesionario', 'post_status' => 'publish',
            'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC',
        ) );
        ?>
        <p>
            <label for="welow_coche_concesionario"><strong>Concesionario:</strong></label>
            <select id="welow_coche_concesionario" name="welow_coche_concesionario" class="widefat">
                <option value="">— Sin asignar —</option>
                <?php foreach ( $concesionarios as $c ) : ?>
                    <option value="<?php echo esc_attr( $c->ID ); ?>" <?php selected( $concesionario_id, $c->ID ); ?>>
                        <?php echo esc_html( $c->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="welow_coche_programa"><strong>Programa especial:</strong></label>
            <input type="text" id="welow_coche_programa" name="welow_coche_programa"
                   value="<?php echo esc_attr( $programa ); ?>" class="widefat"
                   placeholder="ej: Toyota Ocasión" />
        </p>
        <?php
    }

    /* ========================================================================
       METABOX H: Datos privados (no se muestran en frontend)
       ======================================================================== */
    public static function render_metabox_privados( $post ) {
        $matricula = get_post_meta( $post->ID, self::META_PREFIX . 'matricula', true );
        $vin       = get_post_meta( $post->ID, self::META_PREFIX . 'vin', true );
        ?>
        <p style="background:#fef3c7;padding:8px;border-radius:4px;margin:0 0 12px;font-size:12px;">
            ⚠️ Estos datos NO se muestran en frontend. Solo gestión interna.
        </p>
        <p>
            <label><strong>Matrícula:</strong></label><br>
            <input type="text" name="welow_coche_matricula" value="<?php echo esc_attr( $matricula ); ?>"
                   class="widefat" maxlength="10" />
        </p>
        <p>
            <label><strong>Bastidor (VIN):</strong></label><br>
            <input type="text" name="welow_coche_vin" value="<?php echo esc_attr( $vin ); ?>"
                   class="widefat" maxlength="17" />
        </p>
        <?php
    }

    /* ========================================================================
       GUARDADO
       ======================================================================== */
    public static function guardar_meta( $post_id, $post ) {
        if ( ! isset( $_POST['welow_coche_nonce'] ) || ! wp_verify_nonce( $_POST['welow_coche_nonce'], 'welow_coche_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // A: Identificación
        $intfields = array( 'modelo', 'mes_matricula', 'anio_matricula', 'km' );
        foreach ( $intfields as $f ) {
            $val = isset( $_POST[ 'welow_coche_' . $f ] ) ? absint( $_POST[ 'welow_coche_' . $f ] ) : '';
            update_post_meta( $post_id, self::META_PREFIX . $f, $val );
        }
        $textfields = array( 'version', 'tipo_venta', 'estado', 'referencia' );
        foreach ( $textfields as $f ) {
            $val = isset( $_POST[ 'welow_coche_' . $f ] ) ? sanitize_text_field( $_POST[ 'welow_coche_' . $f ] ) : '';
            update_post_meta( $post_id, self::META_PREFIX . $f, $val );
        }

        // B: Precio
        $precios = array( 'precio_contado', 'precio_financiado', 'precio_anterior', 'cuota' );
        foreach ( $precios as $f ) {
            $val = isset( $_POST[ 'welow_coche_' . $f ] ) ? sanitize_text_field( $_POST[ 'welow_coche_' . $f ] ) : '';
            update_post_meta( $post_id, self::META_PREFIX . $f, $val );
        }
        $disclaimer = isset( $_POST['welow_coche_disclaimer'] ) ? sanitize_textarea_field( $_POST['welow_coche_disclaimer'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'disclaimer', $disclaimer );

        // C: Datos técnicos
        $tecn_text = array( 'cambio', 'color', 'tipo_pintura', 'etiqueta_dgt' );
        foreach ( $tecn_text as $f ) {
            $val = isset( $_POST[ 'welow_coche_' . $f ] ) ? sanitize_text_field( $_POST[ 'welow_coche_' . $f ] ) : '';
            update_post_meta( $post_id, self::META_PREFIX . $f, $val );
        }

        // CV/kW con cálculo automático si solo viene uno
        $cv = isset( $_POST['welow_coche_cv'] ) ? floatval( $_POST['welow_coche_cv'] ) : 0;
        $kw = isset( $_POST['welow_coche_kw'] ) ? floatval( $_POST['welow_coche_kw'] ) : 0;
        if ( $cv > 0 && $kw == 0 )       $kw = round( $cv * 0.7355, 1 );
        elseif ( $kw > 0 && $cv == 0 )   $cv = round( $kw / 0.7355 );
        update_post_meta( $post_id, self::META_PREFIX . 'cv', $cv ?: '' );
        update_post_meta( $post_id, self::META_PREFIX . 'kw', $kw ?: '' );

        $tecn_int = array( 'marchas', 'cilindrada', 'plazas', 'puertas' );
        foreach ( $tecn_int as $f ) {
            $val = isset( $_POST[ 'welow_coche_' . $f ] ) && '' !== $_POST[ 'welow_coche_' . $f ]
                ? absint( $_POST[ 'welow_coche_' . $f ] ) : '';
            update_post_meta( $post_id, self::META_PREFIX . $f, $val );
        }

        // D: Galería
        $galeria_raw = isset( $_POST['welow_coche_galeria'] ) ? $_POST['welow_coche_galeria'] : '';
        $galeria_ids = array_filter( array_map( 'absint', explode( ',', $galeria_raw ) ) );
        $galeria_ids = array_slice( $galeria_ids, 0, self::GALERIA_MAX );
        update_post_meta( $post_id, self::META_PREFIX . 'galeria', $galeria_ids );

        $video = isset( $_POST['welow_coche_video'] ) ? esc_url_raw( $_POST['welow_coche_video'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'video', $video );

        // E/F: WYSIWYG (permitir HTML básico)
        $equipamiento = isset( $_POST['welow_coche_equipamiento'] ) ? wp_kses_post( $_POST['welow_coche_equipamiento'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'equipamiento', $equipamiento );

        $garantias = isset( $_POST['welow_coche_garantias'] ) ? wp_kses_post( $_POST['welow_coche_garantias'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'garantias', $garantias );

        // G: Comercial
        $conc = isset( $_POST['welow_coche_concesionario'] ) ? absint( $_POST['welow_coche_concesionario'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'concesionario', $conc );

        $programa = isset( $_POST['welow_coche_programa'] ) ? sanitize_text_field( $_POST['welow_coche_programa'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'programa', $programa );

        // H: Privados
        $matricula = isset( $_POST['welow_coche_matricula'] ) ? sanitize_text_field( $_POST['welow_coche_matricula'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'matricula', strtoupper( $matricula ) );

        $vin = isset( $_POST['welow_coche_vin'] ) ? sanitize_text_field( $_POST['welow_coche_vin'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'vin', strtoupper( $vin ) );
    }

    /* ========================================================================
       COLUMNAS ADMIN
       ======================================================================== */
    public static function columnas_admin( $columns ) {
        $new = array();
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( 'title' === $key ) {
                $new['welow_imagen']     = 'Imagen';
                $new['welow_modelo']     = 'Modelo';
                $new['welow_referencia'] = 'Ref.';
                $new['welow_tipo_venta'] = 'Tipo';
                $new['welow_km']         = 'Km';
                $new['welow_precio']     = 'Precio';
                $new['welow_estado']     = 'Estado';
            }
        }
        unset( $new['date'] );
        return $new;
    }

    public static function contenido_columnas( $column, $post_id ) {
        switch ( $column ) {
            case 'welow_imagen':
                $thumb = get_the_post_thumbnail( $post_id, array( 60, 45 ) );
                echo $thumb ?: '<span style="color:#ccc;">—</span>';
                break;
            case 'welow_modelo':
                $modelo_id = get_post_meta( $post_id, self::META_PREFIX . 'modelo', true );
                if ( $modelo_id ) {
                    $modelo   = get_post( $modelo_id );
                    $marca_id = get_post_meta( $modelo_id, '_welow_modelo_marca', true );
                    $marca    = $marca_id ? get_post( $marca_id ) : null;
                    echo '<a href="' . esc_url( get_edit_post_link( $modelo_id ) ) . '">';
                    echo esc_html( ( $marca ? $marca->post_title . ' ' : '' ) . ( $modelo ? $modelo->post_title : '' ) );
                    echo '</a>';
                } else {
                    echo '<span style="color:#dc3232;">Sin modelo</span>';
                }
                break;
            case 'welow_referencia':
                echo esc_html( get_post_meta( $post_id, self::META_PREFIX . 'referencia', true ) ?: '—' );
                break;
            case 'welow_tipo_venta':
                $tv = get_post_meta( $post_id, self::META_PREFIX . 'tipo_venta', true );
                $tvs = self::get_tipo_venta_options();
                $color_map = array( 'ocasion' => '#3b82f6', 'km0' => '#10b981', 'nuevo' => '#f59e0b' );
                $bg = isset( $color_map[ $tv ] ) ? $color_map[ $tv ] : '#64748b';
                echo $tv ? '<span style="background:' . $bg . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;text-transform:uppercase;">' . esc_html( $tvs[ $tv ] ?? $tv ) . '</span>' : '—';
                break;
            case 'welow_km':
                $km = get_post_meta( $post_id, self::META_PREFIX . 'km', true );
                echo $km !== '' ? esc_html( number_format_i18n( $km ) ) . ' km' : '—';
                break;
            case 'welow_precio':
                $precio = get_post_meta( $post_id, self::META_PREFIX . 'precio_contado', true );
                $moneda = class_exists( 'Welow_Settings' ) ? Welow_Settings::get( 'moneda_simbolo', '€' ) : '€';
                echo $precio ? '<strong>' . esc_html( number_format_i18n( floatval( $precio ), 0 ) ) . ' ' . esc_html( $moneda ) . '</strong>' : '—';
                break;
            case 'welow_estado':
                $estado = get_post_meta( $post_id, self::META_PREFIX . 'estado', true );
                $colores = array( 'disponible' => '#10b981', 'reservado' => '#f59e0b', 'vendido' => '#ef4444' );
                $estados = self::get_estado_options();
                $bg = isset( $colores[ $estado ] ) ? $colores[ $estado ] : '#64748b';
                echo $estado ? '<span style="background:' . $bg . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;">' . esc_html( $estados[ $estado ] ?? $estado ) . '</span>' : '—';
                break;
        }
    }

    public static function columnas_ordenables( $columns ) {
        $columns['welow_referencia'] = 'welow_referencia';
        $columns['welow_km']         = 'welow_km';
        $columns['welow_precio']     = 'welow_precio';
        return $columns;
    }
}
