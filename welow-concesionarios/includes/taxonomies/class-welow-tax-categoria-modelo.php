<?php
/**
 * Taxonomía: welow_categoria_modelo
 * Categoriza modelos por carrocería: Berlina, SUV, Monovolumen, Coupé, etc.
 *
 * @since 1.2.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Tax_Categoria_Modelo {

    const TAXONOMY = 'welow_categoria_modelo';

    public static function init() {
        add_action( 'init', array( __CLASS__, 'registrar_taxonomia' ), 5 );
    }

    public static function registrar_taxonomia() {
        $labels = array(
            'name'              => 'Categorías de modelo',
            'singular_name'     => 'Categoría de modelo',
            'search_items'      => 'Buscar categorías',
            'all_items'         => 'Todas las categorías',
            'parent_item'       => 'Categoría padre',
            'parent_item_colon' => 'Categoría padre:',
            'edit_item'         => 'Editar categoría',
            'update_item'       => 'Actualizar categoría',
            'add_new_item'      => 'Añadir nueva categoría',
            'new_item_name'     => 'Nueva categoría',
            'menu_name'         => 'Categorías',
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
            'rewrite'           => array( 'slug' => 'categoria-modelo' ),
        );

        register_taxonomy( self::TAXONOMY, array( 'welow_modelo' ), $args );
    }

    /**
     * Términos por defecto (se crean al activar el plugin).
     */
    public static function crear_terminos_defecto() {
        $terminos_defecto = array(
            'berlina'      => 'Berlina',
            'suv'          => 'SUV / Crossover',
            'monovolumen'  => 'Monovolumen',
            'familiar'     => 'Familiar',
            'coupe'        => 'Coupé',
            'cabrio'       => 'Cabrio / Descapotable',
            'pickup'       => 'Pick-up',
            'deportivo'    => 'Deportivo',
            'compacto'     => 'Compacto',
            'urbano'       => 'Urbano',
            'comercial'    => 'Comercial',
        );

        foreach ( $terminos_defecto as $slug => $nombre ) {
            if ( ! term_exists( $slug, self::TAXONOMY ) ) {
                wp_insert_term( $nombre, self::TAXONOMY, array( 'slug' => $slug ) );
            }
        }
    }
}
