<?php
/**
 * Trait: añade campo "icono" a una taxonomía.
 * Se usa en welow_combustible y welow_categoria_modelo.
 *
 * @since 2.0.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

trait Welow_Tax_Icon_Trait {

    /**
     * Registra los hooks para el campo de icono.
     * Llamar desde el init() de la taxonomía concreta.
     */
    public static function init_iconos() {
        $tax = self::TAXONOMY;
        add_action( $tax . '_add_form_fields',  array( __CLASS__, 'campo_icono_add' ) );
        add_action( $tax . '_edit_form_fields', array( __CLASS__, 'campo_icono_edit' ) );
        add_action( 'created_' . $tax,          array( __CLASS__, 'guardar_icono' ) );
        add_action( 'edited_' . $tax,           array( __CLASS__, 'guardar_icono' ) );

        // Encolar media uploader en la pantalla de edición de términos
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_media' ) );
    }

    public static function maybe_enqueue_media( $hook ) {
        if ( 'edit-tags.php' === $hook || 'term.php' === $hook ) {
            $tax_actual = isset( $_GET['taxonomy'] ) ? sanitize_key( $_GET['taxonomy'] ) : '';
            if ( $tax_actual === self::TAXONOMY ) {
                wp_enqueue_media();
            }
        }
    }

    /**
     * Campo en formulario "Añadir nuevo término".
     */
    public static function campo_icono_add() {
        ?>
        <div class="form-field welow-term-icono-field">
            <label>Icono</label>
            <input type="hidden" name="welow_term_icono" id="welow_term_icono" value="" />
            <div id="welow-term-icono-preview" class="welow-image-preview"></div>
            <button type="button" class="button welow-upload-btn"
                    data-target="welow_term_icono"
                    data-preview="welow-term-icono-preview">Seleccionar icono</button>
            <p>Imagen PNG/SVG transparente, recomendado 64×64px. Aparecerá en la ficha del coche.</p>
        </div>
        <?php
    }

    /**
     * Campo en formulario "Editar término".
     */
    public static function campo_icono_edit( $term ) {
        $icono_id  = get_term_meta( $term->term_id, 'welow_icono', true );
        $icono_url = $icono_id ? wp_get_attachment_image_url( $icono_id, 'thumbnail' ) : '';
        ?>
        <tr class="form-field welow-term-icono-field">
            <th scope="row"><label>Icono</label></th>
            <td>
                <input type="hidden" name="welow_term_icono" id="welow_term_icono"
                       value="<?php echo esc_attr( $icono_id ); ?>" />
                <div id="welow-term-icono-preview" class="welow-image-preview" style="margin-bottom:8px;">
                    <?php if ( $icono_url ) : ?>
                        <img src="<?php echo esc_url( $icono_url ); ?>" alt="" style="max-width:60px;" />
                    <?php endif; ?>
                </div>
                <button type="button" class="button welow-upload-btn"
                        data-target="welow_term_icono"
                        data-preview="welow-term-icono-preview">
                    <?php echo $icono_id ? 'Cambiar' : 'Seleccionar icono'; ?>
                </button>
                <?php if ( $icono_id ) : ?>
                    <button type="button" class="button welow-remove-btn"
                            data-target="welow_term_icono"
                            data-preview="welow-term-icono-preview">Quitar</button>
                <?php endif; ?>
                <p class="description">Imagen PNG/SVG transparente, recomendado 64×64px.</p>
            </td>
        </tr>
        <?php
    }

    /**
     * Guarda el icono al crear/editar el término.
     */
    public static function guardar_icono( $term_id ) {
        if ( isset( $_POST['welow_term_icono'] ) ) {
            $icono_id = absint( $_POST['welow_term_icono'] );
            if ( $icono_id ) {
                update_term_meta( $term_id, 'welow_icono', $icono_id );
            } else {
                delete_term_meta( $term_id, 'welow_icono' );
            }
        }
    }

    /**
     * Helper: obtiene la URL del icono de un término.
     */
    public static function get_term_icono_url( $term_id, $size = 'thumbnail' ) {
        $icono_id = get_term_meta( $term_id, 'welow_icono', true );
        return $icono_id ? wp_get_attachment_image_url( $icono_id, $size ) : '';
    }
}
