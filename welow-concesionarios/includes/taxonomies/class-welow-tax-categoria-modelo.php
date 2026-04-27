<?php
/**
 * Taxonomía: welow_categoria_modelo
 * Carrocería del modelo: Berlina, SUV, Monovolumen, Coupé, etc.
 *
 * NOTA: El slug interno se mantiene como `welow_categoria_modelo` para
 * no romper datos existentes; las labels visibles son "Carrocería".
 *
 * @since 1.2.0
 * @version 1.2.1 — Renombradas labels a "Carrocería" + alta en Configuraciones.
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
            'name'              => 'Carrocerías',
            'singular_name'     => 'Carrocería',
            'search_items'      => 'Buscar carrocerías',
            'all_items'         => 'Todas las carrocerías',
            'parent_item'       => 'Carrocería padre',
            'parent_item_colon' => 'Carrocería padre:',
            'edit_item'         => 'Editar carrocería',
            'update_item'       => 'Actualizar carrocería',
            'add_new_item'      => 'Añadir nueva carrocería',
            'new_item_name'     => 'Nueva carrocería',
            'menu_name'         => 'Carrocerías',
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
            'rewrite'           => array( 'slug' => 'carroceria' ),
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
