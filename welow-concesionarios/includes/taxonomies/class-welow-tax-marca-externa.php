<?php
/**
 * Taxonomía: welow_marca_externa
 *
 * Marcas que NO son del catálogo oficial del concesionario (welow_marca).
 * Se usan en coches de ocasión para identificar marcas que entran como traída
 * pero no se venden como nuevos: BMW, Audi, Renault, Citroën, etc.
 *
 * @since 2.1.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Tax_Marca_Externa {
    use Welow_Tax_Icon_Trait;

    const TAXONOMY = 'welow_marca_externa';

    public static function init() {
        add_action( 'init', array( __CLASS__, 'registrar_taxonomia' ), 5 );
        self::init_iconos();
    }

    public static function registrar_taxonomia() {
        $labels = array(
            'name'              => 'Marcas externas',
            'singular_name'     => 'Marca externa',
            'search_items'      => 'Buscar marcas',
            'all_items'         => 'Todas las marcas',
            'edit_item'         => 'Editar marca',
            'update_item'       => 'Actualizar marca',
            'add_new_item'      => 'Añadir nueva marca',
            'new_item_name'     => 'Nueva marca',
            'menu_name'         => 'Marcas externas',
            'not_found'         => 'No hay marcas externas todavía.',
        );

        $args = array(
            'labels'            => $labels,
            'public'            => true,
            'hierarchical'      => false,  // etiquetas planas
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'show_in_menu'      => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'marca-externa' ),
            'meta_box_cb'       => 'post_categories_meta_box',  // estilo checkbox para que solo se elija una
        );

        register_taxonomy( self::TAXONOMY, array( 'welow_coche_ocasion' ), $args );
    }

    /**
     * Términos por defecto: marcas comunes en mercado de ocasión.
     */
    public static function crear_terminos_defecto() {
        $terminos_defecto = array(
            'bmw'           => 'BMW',
            'audi'          => 'Audi',
            'mercedes-benz' => 'Mercedes-Benz',
            'volkswagen'    => 'Volkswagen',
            'seat'          => 'SEAT',
            'renault'       => 'Renault',
            'citroen'       => 'Citroën',
            'peugeot'       => 'Peugeot',
            'ford'          => 'Ford',
            'opel'          => 'Opel',
            'fiat'          => 'Fiat',
            'skoda'         => 'Škoda',
            'mini'          => 'MINI',
            'kia'           => 'Kia',
            'mazda'         => 'Mazda',
            'honda'         => 'Honda',
            'volvo'         => 'Volvo',
            'dacia'         => 'Dacia',
            'jeep'          => 'Jeep',
            'land-rover'    => 'Land Rover',
        );

        foreach ( $terminos_defecto as $slug => $nombre ) {
            if ( ! term_exists( $slug, self::TAXONOMY ) ) {
                wp_insert_term( $nombre, self::TAXONOMY, array( 'slug' => $slug ) );
            }
        }
    }
}
