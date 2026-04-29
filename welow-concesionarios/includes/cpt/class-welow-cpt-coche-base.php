<?php
/**
 * Clase abstracta base para CPTs de coches (nuevos y ocasión).
 *
 * Contiene la lógica común: precio, datos técnicos, galería, equipamiento,
 * garantías, comercial, datos privados. Las clases hijas deben definir
 * POST_TYPE, las labels y el metabox de identificación.
 *
 * Uso: late static binding (`static::POST_TYPE`).
 *
 * @since 2.1.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Welow_CPT_Coche_Base {

    const META_PREFIX = '_welow_coche_';
    const GALERIA_MAX = 30;

    /* Las clases hijas deben definir:
       const POST_TYPE
       static function get_labels()
       static function get_args_extra()         (opcional, args extra para register_post_type)
       static function render_metabox_identificacion( $post )
    */

    /* ========================================================================
       OPCIONES DE SELECTS (compartidas)
       ======================================================================== */

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

    /* ========================================================================
       INIT (debe llamarse desde init() de la clase hija con su POST_TYPE)
       ======================================================================== */

    /**
     * Registra todos los hooks comunes. Debe ser invocado desde la clase hija.
     *
     * @param string $post_type CPT de la clase hija (no se puede usar static::POST_TYPE
     *                          aquí porque ::class al pasar callback estático no resuelve
     *                          late static binding en algunos contextos).
     */
    protected static function init_base( $post_type ) {
        add_action( 'init', array( static::class, 'registrar_cpt' ) );
        add_action( 'add_meta_boxes', array( static::class, 'registrar_metaboxes' ) );
        add_action( 'save_post_' . $post_type, array( static::class, 'guardar_meta' ), 10, 2 );

        add_filter( 'manage_' . $post_type . '_posts_columns', array( static::class, 'columnas_admin' ) );
        add_action( 'manage_' . $post_type . '_posts_custom_column', array( static::class, 'contenido_columnas' ), 10, 2 );
        add_filter( 'manage_edit-' . $post_type . '_sortable_columns', array( static::class, 'columnas_ordenables' ) );
    }

    /* ========================================================================
       REGISTRAR CPT (la clase hija provee labels y args_extra)
       ======================================================================== */

    public static function registrar_cpt() {
        $args = array_merge( array(
            'labels'              => static::get_labels(),
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => 'welow_concesionarios',
            'show_in_rest'        => true,
            'query_var'           => true,
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_icon'           => 'dashicons-car',
            'supports'            => array( 'title', 'thumbnail', 'excerpt' ),
            'taxonomies'          => array( 'welow_combustible', 'welow_categoria_modelo' ),
        ), static::get_args_extra() );

        register_post_type( static::POST_TYPE, $args );
    }

    /**
     * Por defecto sin args extra. Las clases hijas pueden override.
     */
    public static function get_args_extra() {
        return array();
    }

    /* ========================================================================
       METABOXES — REGISTRO COMÚN
       ======================================================================== */

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
                array( static::class, $box['callback'] ),
                static::POST_TYPE, $box['context'], $box['priority'] );
        }
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
       METABOX C: Datos técnicos básicos (con cilindrada)
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
            Combustible y carrocería se gestionan en el lateral derecho (taxonomías).
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
                           min="0" step="0.1" placeholder="CV" style="width:90px;" /> CV
                    <input type="number" name="welow_coche_kw" value="<?php echo esc_attr( $kw ); ?>"
                           min="0" step="0.1" placeholder="kW" style="width:90px;margin-left:10px;" /> kW
                    <p class="description">Si solo rellenas uno, el otro se calcula automáticamente (kW = CV × 0.7355). Acepta decimales.</p>
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
            La <strong>imagen principal</strong> se establece como Imagen Destacada (panel lateral).
            Aquí añade hasta <strong><?php echo self::GALERIA_MAX; ?> imágenes adicionales</strong>.
        </p>

        <?php // Marcador para que el guardado sepa que el metabox se procesó (galería vacía es válida) ?>
        <input type="hidden" name="welow_coche_galeria_present" value="1" />

        <div class="welow-galeria-coche">
            <div id="welow-galeria-thumbs" class="welow-galeria-thumbs">
                <?php foreach ( $galeria as $img_id ) :
                    $img_id = intval( $img_id );
                    if ( ! $img_id ) continue;
                    $url = wp_get_attachment_image_url( $img_id, 'thumbnail' );
                    if ( ! $url ) continue;
                ?>
                    <div class="welow-galeria-thumb" data-id="<?php echo esc_attr( $img_id ); ?>">
                        <input type="hidden" name="welow_coche_galeria[]" value="<?php echo esc_attr( $img_id ); ?>" />
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
                margin-left: 15px; color: #64748b; font-size: 13px;
            }
        </style>

        <script>
        jQuery(function($){
            var maxImages = <?php echo self::GALERIA_MAX; ?>;
            var $thumbs   = $('#welow-galeria-thumbs');
            var $count    = $('#welow-galeria-current');

            function actualizarContador() {
                $count.text( $thumbs.find('.welow-galeria-thumb').length );
            }
            function existeId( id ) {
                return $thumbs.find('.welow-galeria-thumb[data-id="' + id + '"]').length > 0;
            }

            $('#welow-galeria-add').on('click', function(e){
                e.preventDefault();

                if ( typeof wp === 'undefined' || ! wp.media ) {
                    alert('Error: la API de medios de WordPress no está disponible. Recarga la página.');
                    return;
                }
                if ( $thumbs.find('.welow-galeria-thumb').length >= maxImages ) {
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
                    selection.each(function(att){
                        if ( $thumbs.find('.welow-galeria-thumb').length >= maxImages ) return;
                        var id = att.id.toString();
                        if ( existeId( id ) ) return;

                        var thumbUrl = att.attributes.sizes && att.attributes.sizes.thumbnail
                            ? att.attributes.sizes.thumbnail.url : att.attributes.url;

                        // Cada thumb lleva su propio input[] - así el orden DOM == orden POST
                        $thumbs.append(
                            '<div class="welow-galeria-thumb" data-id="' + id + '">' +
                            '<input type="hidden" name="welow_coche_galeria[]" value="' + id + '" />' +
                            '<img src="' + thumbUrl + '" alt="" />' +
                            '<button type="button" class="welow-galeria-remove" title="Quitar">×</button>' +
                            '</div>'
                        );
                    });
                    actualizarContador();
                });
                frame.open();
            });

            $thumbs.on('click', '.welow-galeria-remove', function(e){
                e.preventDefault();
                $(this).closest('.welow-galeria-thumb').remove();
                actualizarContador();
            });

            // jQuery UI sortable: con inputs[] en cada thumb, el orden DOM = orden POST
            // No hace falta mantener un array sincronizado.
            if ( $.fn.sortable ) {
                $thumbs.sortable({
                    placeholder: 'welow-galeria-thumb',
                    forcePlaceholderSize: true,
                    tolerance: 'pointer'
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
       METABOX H: Datos privados
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
       GUARDADO COMÚN
       ======================================================================== */
    public static function guardar_meta( $post_id, $post ) {
        if ( ! isset( $_POST['welow_coche_nonce'] ) || ! wp_verify_nonce( $_POST['welow_coche_nonce'], 'welow_coche_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // 1. Guardar metadatos comunes
        self::guardar_meta_comunes( $post_id );

        // 2. Permitir a la clase hija guardar su lógica específica
        if ( method_exists( static::class, 'guardar_meta_especifico' ) ) {
            static::guardar_meta_especifico( $post_id );
        }
    }

    /**
     * Guarda los metadatos comunes (precio, técnicos, galería, etc.).
     */
    protected static function guardar_meta_comunes( $post_id ) {
        // B: Precio
        $precios = array( 'precio_contado', 'precio_financiado', 'precio_anterior', 'cuota' );
        foreach ( $precios as $f ) {
            $val = isset( $_POST[ 'welow_coche_' . $f ] ) ? sanitize_text_field( $_POST[ 'welow_coche_' . $f ] ) : '';
            update_post_meta( $post_id, self::META_PREFIX . $f, $val );
        }
        $disclaimer = isset( $_POST['welow_coche_disclaimer'] ) ? sanitize_textarea_field( $_POST['welow_coche_disclaimer'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'disclaimer', $disclaimer );

        // C: Datos técnicos texto
        $tecn_text = array( 'cambio', 'color', 'tipo_pintura', 'etiqueta_dgt' );
        foreach ( $tecn_text as $f ) {
            $val = isset( $_POST[ 'welow_coche_' . $f ] ) ? sanitize_text_field( $_POST[ 'welow_coche_' . $f ] ) : '';
            update_post_meta( $post_id, self::META_PREFIX . $f, $val );
        }

        // CV/kW con auto-cálculo
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

        // D: Galería (v2.3.2 - lee array nativo de inputs[] en lugar de CSV string)
        // Solo se procesa si el metabox estuvo presente en el form (evita borrar
        // la galería si el metabox se oculta o si se guarda desde otro contexto).
        if ( isset( $_POST['welow_coche_galeria_present'] ) ) {
            $galeria_raw = isset( $_POST['welow_coche_galeria'] ) ? (array) $_POST['welow_coche_galeria'] : array();
            $galeria_ids = array_values( array_filter( array_map( 'absint', $galeria_raw ) ) );
            $galeria_ids = array_slice( $galeria_ids, 0, self::GALERIA_MAX );
            update_post_meta( $post_id, self::META_PREFIX . 'galeria', $galeria_ids );
        }

        $video = isset( $_POST['welow_coche_video'] ) ? esc_url_raw( $_POST['welow_coche_video'] ) : '';
        update_post_meta( $post_id, self::META_PREFIX . 'video', $video );

        // E/F: WYSIWYG
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
       COLUMNAS ADMIN — base, las clases hijas pueden override
       ======================================================================== */
    public static function columnas_admin( $columns ) {
        $new = array();
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( 'title' === $key ) {
                $new['welow_imagen']     = 'Imagen';
                $new['welow_marca_modelo'] = 'Marca / Modelo';
                $new['welow_referencia'] = 'Ref.';
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
                // v2.3.3 — usa imagen destacada o primera de galería como fallback
                $img_id = class_exists( 'Welow_Helpers' )
                    ? Welow_Helpers::get_coche_imagen_principal_id( $post_id )
                    : get_post_thumbnail_id( $post_id );
                if ( $img_id ) {
                    echo wp_get_attachment_image( $img_id, array( 60, 45 ), false, array( 'style' => 'border-radius:4px;object-fit:cover;width:60px;height:45px;' ) );
                } else {
                    echo '<span style="color:#ccc;">—</span>';
                }
                break;
            case 'welow_marca_modelo':
                // Cada CPT decide cómo mostrar la marca/modelo
                if ( method_exists( static::class, 'render_columna_marca_modelo' ) ) {
                    static::render_columna_marca_modelo( $post_id );
                } else {
                    echo '—';
                }
                break;
            case 'welow_referencia':
                echo esc_html( get_post_meta( $post_id, self::META_PREFIX . 'referencia', true ) ?: '—' );
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
