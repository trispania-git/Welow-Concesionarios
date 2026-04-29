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
     * Listado completo de marcas conocidas en el mercado.
     *
     * Idempotente: las que ya existen no se tocan.
     *
     * @since 2.1.0
     * @version 2.3.0 — Ampliado a 99 marcas (catálogo completo).
     * @return array Marcas pre-cargadas: ['slug' => 'Nombre', ...]
     */
    public static function get_marcas_catalogo() {
        return array(
            'abarth'            => 'Abarth',
            'acura'             => 'Acura',
            'aeolus'            => 'Aeolus',
            'aion'              => 'Aion',
            'aiways'            => 'Aiways',
            'alfa-romeo'        => 'Alfa Romeo',
            'alpina'            => 'Alpina',
            'alpine'            => 'Alpine',
            'aston-martin'      => 'Aston Martin',
            'audi'              => 'Audi',
            'aurus'             => 'Aurus',
            'baic'              => 'BAIC',
            'bentley'           => 'Bentley',
            'bmw'               => 'BMW',
            'bugatti'           => 'Bugatti',
            'buick'             => 'Buick',
            'byd'               => 'BYD',
            'cadillac'          => 'Cadillac',
            'caterham'          => 'Caterham',
            'changan'           => 'Changan',
            'chery'             => 'Chery',
            'chevrolet'         => 'Chevrolet',
            'chrysler'          => 'Chrysler',
            'citroen'           => 'Citroën',
            'cupra'             => 'Cupra',
            'dacia'             => 'Dacia',
            'daihatsu'          => 'Daihatsu',
            'dfsk'              => 'DFSK',
            'dodge'             => 'Dodge',
            'ds'                => 'DS',
            'ferrari'           => 'Ferrari',
            'fiat'              => 'Fiat',
            'ford'              => 'Ford',
            'gac'               => 'GAC',
            'genesis'           => 'Genesis',
            'gmc'               => 'GMC',
            'great-wall'        => 'Great Wall',
            'haval'             => 'Haval',
            'honda'             => 'Honda',
            'hongqi'            => 'Hongqi',
            'hummer'            => 'Hummer',
            'hyundai'           => 'Hyundai',
            'ineos'             => 'Ineos',
            'infiniti'          => 'Infiniti',
            'isuzu'             => 'Isuzu',
            'iveco'             => 'Iveco',
            'jac'               => 'JAC',
            'jaguar'            => 'Jaguar',
            'jeep'              => 'Jeep',
            'jetta'             => 'Jetta',
            'kia'               => 'Kia',
            'koenigsegg'        => 'Koenigsegg',
            'lada'              => 'Lada',
            'lamborghini'       => 'Lamborghini',
            'lancia'            => 'Lancia',
            'land-rover'        => 'Land Rover',
            'lexus'             => 'Lexus',
            'lincoln'           => 'Lincoln',
            'lotus'             => 'Lotus',
            'lucid'             => 'Lucid',
            'lynk-co'           => 'Lynk & Co',
            'mahindra'          => 'Mahindra',
            'maserati'          => 'Maserati',
            'mazda'             => 'Mazda',
            'mclaren'           => 'McLaren',
            'mercedes-benz'     => 'Mercedes-Benz',
            'mg'                => 'MG',
            'mini'              => 'Mini',
            'mitsubishi'        => 'Mitsubishi',
            'morgan'            => 'Morgan',
            'nio'               => 'Nio',
            'nissan'            => 'Nissan',
            'omoda'             => 'Omoda',
            'opel'              => 'Opel',
            'pagani'            => 'Pagani',
            'peugeot'           => 'Peugeot',
            'polestar'          => 'Polestar',
            'porsche'           => 'Porsche',
            'ram'               => 'RAM',
            'renault'           => 'Renault',
            'rivian'            => 'Rivian',
            'rolls-royce'       => 'Rolls-Royce',
            'saab'              => 'Saab',
            'seat'              => 'SEAT',
            'skoda'             => 'Škoda',
            'smart'             => 'Smart',
            'ssangyong-kgm'     => 'SsangYong (KGM)',
            'subaru'            => 'Subaru',
            'suzuki'            => 'Suzuki',
            'tata'              => 'Tata',
            'tesla'             => 'Tesla',
            'toyota'            => 'Toyota',
            'vauxhall'          => 'Vauxhall',
            'vinfast'           => 'VinFast',
            'volkswagen'        => 'Volkswagen',
            'volvo'             => 'Volvo',
            'wuling'            => 'Wuling',
            'xpeng'             => 'XPeng',
            'zeekr'             => 'Zeekr',
        );
    }

    /**
     * Términos por defecto: marcas conocidas en el mercado.
     *
     * Idempotente: solo añade las que NO existen. Las que ya están (creadas
     * por el editor o sincronizadas con marcas oficiales) no se tocan.
     *
     * @since 2.1.0
     * @version 2.3.0 — Refactor para usar get_marcas_catalogo().
     * @return array Resumen: ['creadas' => int, 'existentes' => int]
     */
    public static function crear_terminos_defecto() {
        $marcas = self::get_marcas_catalogo();
        $creadas = 0;
        $existentes = 0;

        foreach ( $marcas as $slug => $nombre ) {
            if ( term_exists( $slug, self::TAXONOMY ) ) {
                $existentes++;
                continue;
            }
            $r = wp_insert_term( $nombre, self::TAXONOMY, array( 'slug' => $slug ) );
            if ( ! is_wp_error( $r ) ) {
                $creadas++;
            }
        }

        return array( 'creadas' => $creadas, 'existentes' => $existentes );
    }
}
