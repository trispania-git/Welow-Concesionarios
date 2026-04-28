<?php
/**
 * Taxonomía: welow_combustible
 * Categoriza modelos por tipo de motorización: Gasoil, Gasolina, Híbrido, etc.
 *
 * @since 1.1.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Tax_Combustible {
    use Welow_Tax_Icon_Trait;  // v2.0.0 — soporte de icono por término

    const TAXONOMY = 'welow_combustible';

    public static function init() {
        add_action( 'init', array( __CLASS__, 'registrar_taxonomia' ), 5 );
        self::init_iconos();  // v2.0.0
    }

    public static function registrar_taxonomia() {
        $labels = array(
            'name'              => 'Combustibles',
            'singular_name'     => 'Combustible',
            'search_items'      => 'Buscar combustibles',
            'all_items'         => 'Todos los combustibles',
            'parent_item'       => 'Combustible padre',
            'parent_item_colon' => 'Combustible padre:',
            'edit_item'         => 'Editar combustible',
            'update_item'       => 'Actualizar combustible',
            'add_new_item'      => 'Añadir nuevo combustible',
            'new_item_name'     => 'Nuevo combustible',
            'menu_name'         => 'Combustibles',
        );

        $args = array(
            'labels'            => $labels,
            'public'            => true,
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'show_in_menu'      => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'combustible' ),
        );

        register_taxonomy( self::TAXONOMY, array( 'welow_modelo' ), $args );
    }

    /**
     * Términos por defecto (se crean al activar el plugin).
     */
    public static function crear_terminos_defecto() {
        $terminos_defecto = array(
            'gasolina'          => 'Gasolina',
            'gasoil'            => 'Gasoil / Diésel',
            'hibrido'           => 'Híbrido',
            'hibrido-enchufable'=> 'Híbrido enchufable',
            'electrico'         => 'Eléctrico',
            'glp'               => 'GLP',
            'gnc'               => 'GNC',
        );

        foreach ( $terminos_defecto as $slug => $nombre ) {
            if ( ! term_exists( $slug, self::TAXONOMY ) ) {
                wp_insert_term( $nombre, self::TAXONOMY, array( 'slug' => $slug ) );
            }
        }
    }
}
