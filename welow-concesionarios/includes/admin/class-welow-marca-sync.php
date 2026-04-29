<?php
/**
 * Sincronización Marcas oficiales (welow_marca) ↔ Marcas externas (welow_marca_externa).
 *
 * Cada marca oficial del catálogo tiene automáticamente una "gemela" en la
 * taxonomía de marcas externas. Esto permite que cuando entra un coche de
 * ocasión de una marca que también vendemos nueva, se use el mismo término
 * y no haya duplicados ni typos.
 *
 * Funcionalidades:
 *  - Hook al guardar marca oficial → crea/actualiza término externo
 *  - Hook al borrar marca oficial → desliga el término (no lo borra)
 *  - Detección de typos al crear término externo (similitud > 80%)
 *  - Badge "OFICIAL" en el listado de marcas externas
 *  - Sincronización masiva al activar el plugin
 *
 * Meta key del término externo: `_welow_marca_oficial_id` (post ID de la marca oficial)
 *
 * @since 2.2.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Marca_Sync {

    const META_KEY_OFICIAL = '_welow_marca_oficial_id';
    const TAXONOMY = 'welow_marca_externa';
    const POST_TYPE = 'welow_marca';

    public static function init() {
        // Sincronización al guardar marca oficial
        add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'sync_oficial_a_externa' ), 20, 3 );

        // Desligar al borrar marca oficial
        add_action( 'before_delete_post', array( __CLASS__, 'desligar_al_borrar' ), 10, 2 );

        // Detección de typos al crear término externo
        add_action( self::TAXONOMY . '_pre_add_form', array( __CLASS__, 'mostrar_warning_duplicados' ) );

        // Columna "OFICIAL" en el listado de marcas externas
        add_filter( 'manage_edit-' . self::TAXONOMY . '_columns', array( __CLASS__, 'columna_oficial' ) );
        add_filter( 'manage_' . self::TAXONOMY . '_custom_column', array( __CLASS__, 'render_columna_oficial' ), 10, 3 );

        // Aviso de typo al guardar término nuevo (después del insert)
        add_action( 'created_' . self::TAXONOMY, array( __CLASS__, 'check_similar_terms' ), 10, 2 );

        // Bloquear edición del nombre/slug en términos sincronizados
        add_filter( self::TAXONOMY . '_row_actions', array( __CLASS__, 'row_actions_oficiales' ), 10, 2 );
    }

    /* ========================================================================
       SINCRONIZACIÓN OFICIAL → EXTERNA
       ======================================================================== */

    /**
     * Al guardar una marca oficial, asegura que existe el término externo gemelo.
     */
    public static function sync_oficial_a_externa( $post_id, $post, $update ) {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) return;
        if ( 'publish' !== $post->post_status ) return;

        $slug = $post->post_name;
        $name = $post->post_title;

        if ( empty( $slug ) || empty( $name ) ) return;

        // ¿Existe ya un término con ese slug?
        $term = get_term_by( 'slug', $slug, self::TAXONOMY );

        if ( ! $term ) {
            // Crear nuevo término
            $created = wp_insert_term( $name, self::TAXONOMY, array( 'slug' => $slug ) );
            if ( is_wp_error( $created ) ) return;
            $term_id = $created['term_id'];
        } else {
            $term_id = $term->term_id;
            // Si el nombre cambió, actualizarlo
            if ( $term->name !== $name ) {
                wp_update_term( $term_id, self::TAXONOMY, array( 'name' => $name ) );
            }
        }

        // Marcar como sincronizado con esta marca oficial
        update_term_meta( $term_id, self::META_KEY_OFICIAL, $post_id );
    }

    /**
     * Al borrar una marca oficial, desligar el término externo (sin borrarlo).
     */
    public static function desligar_al_borrar( $post_id, $post = null ) {
        // before_delete_post puede no recibir el segundo argumento en versiones antiguas
        if ( ! $post ) $post = get_post( $post_id );
        if ( ! $post || self::POST_TYPE !== $post->post_type ) return;

        // Buscar el término que apuntaba a este post
        $terms = get_terms( array(
            'taxonomy'   => self::TAXONOMY,
            'meta_key'   => self::META_KEY_OFICIAL,
            'meta_value' => $post_id,
            'hide_empty' => false,
        ) );

        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                delete_term_meta( $term->term_id, self::META_KEY_OFICIAL );
            }
        }
    }

    /**
     * Sincronización masiva: recorre todas las marcas oficiales y crea/actualiza
     * sus términos externos. Útil al activar el plugin.
     *
     * @return array Resumen: {creados: int, actualizados: int}
     */
    public static function sincronizar_todas() {
        $marcas = get_posts( array(
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        ) );

        $creados      = 0;
        $actualizados = 0;

        foreach ( $marcas as $marca ) {
            $slug = $marca->post_name;
            $name = $marca->post_title;
            if ( ! $slug || ! $name ) continue;

            $term = get_term_by( 'slug', $slug, self::TAXONOMY );
            if ( ! $term ) {
                $created = wp_insert_term( $name, self::TAXONOMY, array( 'slug' => $slug ) );
                if ( ! is_wp_error( $created ) ) {
                    update_term_meta( $created['term_id'], self::META_KEY_OFICIAL, $marca->ID );
                    $creados++;
                }
            } else {
                if ( $term->name !== $name ) {
                    wp_update_term( $term->term_id, self::TAXONOMY, array( 'name' => $name ) );
                }
                update_term_meta( $term->term_id, self::META_KEY_OFICIAL, $marca->ID );
                $actualizados++;
            }
        }

        return array( 'creados' => $creados, 'actualizados' => $actualizados );
    }

    /* ========================================================================
       DETECCIÓN DE TYPOS
       ======================================================================== */

    /**
     * Compara un nombre con todas las marcas (oficiales + externas) usando
     * similitud y devuelve la mejor coincidencia si supera el umbral.
     *
     * @param string $nombre  Nombre a comparar.
     * @param int    $exclude_term_id Excluir este término (al editar).
     * @param int    $umbral  Umbral % (0-100, default 80).
     * @return array|null  null si no hay coincidencias relevantes, o {match, similitud, fuente}
     */
    public static function detectar_similar( $nombre, $exclude_term_id = 0, $umbral = 80 ) {
        $nombre_norm = self::normalizar( $nombre );

        $candidatos = array();

        // Marcas oficiales
        $oficiales = get_posts( array(
            'post_type' => self::POST_TYPE, 'post_status' => 'publish',
            'posts_per_page' => -1,
        ) );
        foreach ( $oficiales as $oficial ) {
            $candidatos[] = array(
                'name'   => $oficial->post_title,
                'fuente' => 'oficial',
                'id'     => $oficial->ID,
            );
        }

        // Marcas externas
        $externas = get_terms( array(
            'taxonomy' => self::TAXONOMY, 'hide_empty' => false,
            'exclude'  => $exclude_term_id ? array( $exclude_term_id ) : array(),
        ) );
        if ( ! is_wp_error( $externas ) ) {
            foreach ( $externas as $term ) {
                $candidatos[] = array(
                    'name'   => $term->name,
                    'fuente' => 'externa',
                    'id'     => $term->term_id,
                );
            }
        }

        // Calcular similitudes
        $mejor = null;
        $mejor_sim = 0;
        foreach ( $candidatos as $cand ) {
            $cand_norm = self::normalizar( $cand['name'] );
            if ( $cand_norm === $nombre_norm ) {
                // Coincidencia exacta normalizada
                return array_merge( $cand, array( 'similitud' => 100, 'exacta' => true ) );
            }
            similar_text( $nombre_norm, $cand_norm, $sim );
            if ( $sim > $mejor_sim ) {
                $mejor_sim = $sim;
                $mejor = array_merge( $cand, array( 'similitud' => round( $sim ), 'exacta' => false ) );
            }
        }

        return ( $mejor && $mejor_sim >= $umbral ) ? $mejor : null;
    }

    /**
     * Normaliza un texto para comparación: minúsculas, sin tildes, sin espacios extras.
     */
    private static function normalizar( $texto ) {
        $texto = strtolower( $texto );
        $texto = remove_accents( $texto );
        $texto = preg_replace( '/[^a-z0-9]+/', '', $texto );
        return $texto;
    }

    /**
     * Tras crear un término externo, comprueba similitud con otros y registra
     * un transient con el aviso (que aparecerá tras el redirect del admin).
     */
    public static function check_similar_terms( $term_id, $tt_id ) {
        $term = get_term( $term_id, self::TAXONOMY );
        if ( ! $term || is_wp_error( $term ) ) return;

        $similar = self::detectar_similar( $term->name, $term_id, 75 );

        if ( $similar && ! $similar['exacta'] ) {
            $msg = sprintf(
                '⚠️ <strong>Posible duplicado:</strong> "%s" se parece mucho a "<strong>%s</strong>" (%s, %d%% similar). Si querías referirte a la misma marca, considera borrar este término y usar el existente.',
                esc_html( $term->name ),
                esc_html( $similar['name'] ),
                $similar['fuente'] === 'oficial' ? 'marca oficial' : 'marca externa existente',
                $similar['similitud']
            );
            set_transient( 'welow_marca_sync_warning_' . get_current_user_id(), $msg, 60 );
        }
    }

    /**
     * Muestra el warning de duplicado tras crear el término.
     */
    public static function mostrar_warning_duplicados() {
        $key = 'welow_marca_sync_warning_' . get_current_user_id();
        $msg = get_transient( $key );
        if ( $msg ) {
            delete_transient( $key );
            echo '<div class="notice notice-warning is-dismissible"><p>' . wp_kses_post( $msg ) . '</p></div>';
        }

        // Listado de marcas oficiales como ayuda
        $oficiales = get_posts( array(
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        if ( ! empty( $oficiales ) ) {
            $titulos = array_map( function( $id ) { return get_the_title( $id ); }, $oficiales );
            echo '<div class="notice notice-info"><p>';
            echo '<strong>Marcas oficiales del concesionario</strong> (sincronizadas automáticamente, no las repitas aquí): ';
            echo esc_html( implode( ', ', $titulos ) );
            echo '</p></div>';
        }
    }

    /* ========================================================================
       UI: COLUMNAS
       ======================================================================== */

    public static function columna_oficial( $columns ) {
        // Insertar después de la columna 'name'
        $new = array();
        foreach ( $columns as $key => $value ) {
            $new[ $key ] = $value;
            if ( 'name' === $key ) {
                $new['welow_es_oficial'] = 'Tipo';
            }
        }
        return $new;
    }

    public static function render_columna_oficial( $content, $column_name, $term_id ) {
        if ( 'welow_es_oficial' !== $column_name ) return $content;

        $oficial_id = get_term_meta( $term_id, self::META_KEY_OFICIAL, true );
        if ( $oficial_id ) {
            $edit_link = get_edit_post_link( $oficial_id );
            return '<span style="background:#10b981;color:#fff;padding:3px 10px;border-radius:4px;font-size:11px;font-weight:700;text-transform:uppercase;">Oficial</span>'
                . ' <a href="' . esc_url( $edit_link ) . '" style="margin-left:6px;text-decoration:none;font-size:11px;">↗ editar marca oficial</a>';
        }

        return '<span style="background:#e5e7eb;color:#374151;padding:3px 10px;border-radius:4px;font-size:11px;font-weight:600;">Externa</span>';
    }

    /**
     * Quita la opción "Eliminar" en marcas externas que están sincronizadas
     * con una oficial (para evitar que el editor las borre por accidente).
     */
    public static function row_actions_oficiales( $actions, $term ) {
        $oficial_id = get_term_meta( $term->term_id, self::META_KEY_OFICIAL, true );
        if ( $oficial_id ) {
            unset( $actions['delete'] );
            // Aviso visual
            $actions['oficial_info'] = '<em style="color:#059669;">Sincronizada con marca oficial</em>';
        }
        return $actions;
    }
}
