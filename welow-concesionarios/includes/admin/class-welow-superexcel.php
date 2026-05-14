<?php
/**
 * SuperExcel: generador y procesador de un único XLSX que combina todas
 * las importaciones del plugin con hojas de referencia (dropdowns).
 *
 * FASE 1 (v2.17.0): Modelos.
 * FASE 2: añadirá Marcas, Concesionarios.
 * FASE 3: Coches nuevos / ocasión.
 *
 * @since 2.17.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_SuperExcel {

    /** Hojas internas (nombres exactos). */
    const HOJA_MODELOS         = 'Modelos';
    const HOJA_MARCAS_REF      = 'Marcas';
    const HOJA_COMBUSTIBLES    = 'Combustibles';
    const HOJA_CARROCERIAS     = 'Carrocerias';
    const HOJA_ETIQUETAS       = 'Etiquetas';
    const HOJA_INSTRUCCIONES   = 'Instrucciones';

    public static function init() {
        add_action( 'admin_post_welow_superexcel_descargar', array( __CLASS__, 'descargar' ) );
        add_action( 'admin_post_welow_superexcel_importar',  array( __CLASS__, 'importar' ) );
    }

    /* =====================================================================
     * DESCARGA
     * ===================================================================== */

    public static function descargar() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado' );
        check_admin_referer( 'welow_superexcel_descargar' );

        require_once WELOW_CONC_PATH . 'includes/lib/class-welow-xlsx-writer.php';

        $w = new Welow_Xlsx_Writer();

        // 1) Hojas de referencia (necesario calcularlas ANTES para conocer rangos)
        $marcas_data       = self::datos_marcas();
        $combustibles_data = self::datos_terms( 'welow_combustible' );
        $carrocerias_data  = self::datos_terms( 'welow_categoria_modelo' );
        $etiquetas_data    = self::datos_etiquetas();

        // 2) Hoja "Instrucciones" (la primera, para que sea lo primero que vea)
        self::escribir_instrucciones( $w );

        // 3) Hoja "Modelos" — la principal editable
        self::escribir_hoja_modelos( $w, count( $marcas_data ), count( $combustibles_data ), count( $carrocerias_data ), count( $etiquetas_data ) );

        // 4) Hojas de referencia
        self::escribir_hoja_referencia( $w, self::HOJA_MARCAS_REF,   'Marcas oficiales del concesionario', array( 'slug', 'nombre' ), $marcas_data );
        self::escribir_hoja_referencia( $w, self::HOJA_COMBUSTIBLES, 'Tipos de combustible',               array( 'slug', 'nombre' ), $combustibles_data );
        self::escribir_hoja_referencia( $w, self::HOJA_CARROCERIAS,  'Tipos de carrocería',                array( 'slug', 'nombre' ), $carrocerias_data );
        self::escribir_hoja_referencia( $w, self::HOJA_ETIQUETAS,    'Etiquetas visuales (eco, oferta, nuevo...)', array( 'slug', 'nombre' ), $etiquetas_data );

        $filename = 'welow-superexcel-' . gmdate( 'Y-m-d' ) . '.xlsx';
        $w->output_to_browser( $filename );
    }

    /* =====================================================================
     * Construcción de cada hoja
     * ===================================================================== */

    private static function escribir_instrucciones( Welow_Xlsx_Writer $w ) {
        $hoja = self::HOJA_INSTRUCCIONES;
        $w->add_sheet( $hoja );
        $w->set_col_widths( $hoja, array( 1 => 90 ) );

        $lineas = array(
            'WELOW CONCESIONARIOS — SUPEREXCEL DE IMPORTACIÓN',
            '',
            'Esta plantilla combina varias hojas en un único archivo:',
            '  • Modelos: rellena aquí los modelos a importar (una fila por modelo).',
            '  • Marcas / Combustibles / Carrocerias / Etiquetas: hojas de referencia (solo consulta).',
            '',
            'CÓMO USARLA',
            '  1. Rellena la hoja "Modelos". Cada fila es un modelo.',
            '  2. Las columnas con desplegable validan contra las hojas de referencia.',
            '  3. Guarda el archivo (.xlsx) y súbelo en "Importar superExcel".',
            '',
            'CAMPOS OBLIGATORIOS',
            '  • nombre: nombre visible del modelo (ej. "Corolla").',
            '  • marca_slug: slug de la marca. Debe existir previamente en la web.',
            '',
            'CAMPOS MÚLTIPLES',
            '  • combustible / categoria_modelo / etiquetas: separa varios valores con "|".',
            '    Ej: "hibrido|electrico" o "berlina|compacto".',
            '',
            'COMPORTAMIENTO AL IMPORTAR',
            '  • Si la marca no existe → ERROR. Crea la marca primero en Concesionarios → Marcas.',
            '  • Si un combustible/carrocería/etiqueta no existe → se crea automáticamente.',
            '  • Si un modelo con el mismo slug + marca ya existe → se actualiza.',
            '',
            'CARACTERÍSTICAS (campo "caracteristicas")',
            '  • Una característica por línea dentro de la celda (ALT+ENTER para saltar de línea).',
            '  • Ej:',
            '      Sistema de sonido Sony',
            '      Pantalla 15,6"',
            '      ADAS 19 asistentes',
            '',
            'COLOR DEL RÓTULO (campo "rotulo_color")',
            '  • Código HEX, ej: #2563eb (azul) o vacío para el color por defecto.',
        );
        foreach ( $lineas as $linea ) {
            $w->add_row( $hoja, array( $linea ) );
        }
    }

    private static function escribir_hoja_modelos( Welow_Xlsx_Writer $w, $n_marcas, $n_combustibles, $n_carrocerias, $n_etiquetas ) {
        $hoja = self::HOJA_MODELOS;
        $w->add_sheet( $hoja );

        $columnas = array(
            'nombre', 'slug', 'marca_slug', 'descripcion', 'excerpt',
            'enlace', 'texto_enlace', 'precio_desde', 'disclaimer',
            'combustible', 'categoria_modelo', 'plazas',
            'etiquetas', 'orden', 'activo',
            'rotulo', 'rotulo_color', 'caracteristicas',
            'imagen_url', 'imagen_2_url', 'imagen_3_url', 'imagen_4_url', 'imagen_5_url',
        );

        $w->add_row( $hoja, $columnas );

        // Anchos de columna razonables
        $w->set_col_widths( $hoja, array(
            1 => 22, 2 => 18, 3 => 18, 4 => 36, 5 => 28,
            6 => 22, 7 => 16, 8 => 12, 9 => 32,
            10 => 18, 11 => 22, 12 => 8,
            13 => 18, 14 => 8, 15 => 8,
            16 => 30, 17 => 14, 18 => 50,
            19 => 30, 20 => 30, 21 => 30, 22 => 30, 23 => 30,
        ) );

        // Una fila vacía de ejemplo para que el usuario vea dónde escribir
        $w->add_row( $hoja, array( '', '', '', '', '', '', 'Ver modelo', '', '', '', '', '5', '', '10', '1', '', '#2563eb', '', '', '', '', '', '' ) );

        // Validaciones de datos (dropdowns).
        // Las filas de referencia tienen cabecera en fila 1 → datos desde la 2.
        // Generamos rangos amplios (hasta 1000 filas en Modelos, hasta n+50 en referencias).
        $marca_max = max( 2, $n_marcas + 1 );
        $comb_max  = max( 2, $n_combustibles + 1 );
        $carr_max  = max( 2, $n_carrocerias + 1 );
        $etiq_max  = max( 2, $n_etiquetas + 1 );

        // C = marca_slug (col 3), valida con Marcas!$A$2:$A$<marca_max>
        $w->add_list_validation( $hoja, 'C2:C1001', self::HOJA_MARCAS_REF . '!$A$2:$A$' . $marca_max );

        // J = combustible (col 10) — DROPDOWN SIMPLE (un solo valor).
        // Para multi-valor con "|" el usuario tiene que escribir a mano; lo dejamos como guía.
        if ( $n_combustibles > 0 ) {
            $w->add_list_validation( $hoja, 'J2:J1001', self::HOJA_COMBUSTIBLES . '!$A$2:$A$' . $comb_max );
        }
        // K = categoria_modelo (col 11)
        if ( $n_carrocerias > 0 ) {
            $w->add_list_validation( $hoja, 'K2:K1001', self::HOJA_CARROCERIAS . '!$A$2:$A$' . $carr_max );
        }
        // M = etiquetas (col 13)
        if ( $n_etiquetas > 0 ) {
            $w->add_list_validation( $hoja, 'M2:M1001', self::HOJA_ETIQUETAS . '!$A$2:$A$' . $etiq_max );
        }
        // O = activo (col 15) — solo 0 o 1
        $w->add_list_validation( $hoja, 'O2:O1001', '"0,1"' );
    }

    private static function escribir_hoja_referencia( Welow_Xlsx_Writer $w, $nombre, $descripcion, $columnas, $filas ) {
        $w->add_sheet( $nombre );
        $w->set_col_widths( $nombre, array( 1 => 24, 2 => 36 ) );
        // Fila 1: cabecera
        $w->add_row( $nombre, $columnas );
        if ( empty( $filas ) ) {
            $w->add_row( $nombre, array( '(no hay)', $descripcion ) );
            return;
        }
        foreach ( $filas as $fila ) {
            $w->add_row( $nombre, $fila );
        }
    }

    /* =====================================================================
     * Datos para hojas de referencia
     * ===================================================================== */

    private static function datos_marcas() {
        $marcas = get_posts( array(
            'post_type'   => 'welow_marca',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'title',
            'order'       => 'ASC',
        ) );
        $out = array();
        foreach ( $marcas as $m ) {
            $out[] = array( $m->post_name, $m->post_title );
        }
        return $out;
    }

    private static function datos_terms( $taxonomy ) {
        $terms = get_terms( array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );
        if ( is_wp_error( $terms ) ) return array();
        $out = array();
        foreach ( $terms as $t ) {
            $out[] = array( $t->slug, $t->name );
        }
        return $out;
    }

    private static function datos_etiquetas() {
        $etiquetas = get_posts( array(
            'post_type'   => 'welow_etiqueta',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'title',
            'order'       => 'ASC',
        ) );
        $out = array();
        foreach ( $etiquetas as $e ) {
            $out[] = array( $e->post_name, $e->post_title );
        }
        return $out;
    }

    /* =====================================================================
     * IMPORTACIÓN
     * ===================================================================== */

    public static function importar() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado' );
        check_admin_referer( 'welow_superexcel_importar' );

        if ( empty( $_FILES['archivo_xlsx']['tmp_name'] ) ) {
            self::set_resultado( 'error', 'No se ha subido ningún archivo.' );
            self::redirect_back();
        }

        $tmp = $_FILES['archivo_xlsx']['tmp_name'];
        $actualizar         = ! empty( $_POST['actualizar'] );
        $descargar_imagenes = ! empty( $_POST['descargar_imagenes'] );

        require_once WELOW_CONC_PATH . 'includes/lib/class-welow-xlsx-reader.php';

        try {
            $reader = new Welow_Xlsx_Reader( $tmp );
            $filas = $reader->get_rows_assoc( self::HOJA_MODELOS );
        } catch ( Exception $e ) {
            self::set_resultado( 'error', 'Error leyendo el XLSX: ' . $e->getMessage() );
            self::redirect_back();
        }

        if ( empty( $filas ) ) {
            self::set_resultado( 'warning', 'La hoja "Modelos" está vacía.' );
            self::redirect_back();
        }

        // Reutilizamos el procesador del importador CSV existente.
        $importer_path = WELOW_CONC_PATH . 'includes/admin/class-welow-importer.php';
        if ( ! class_exists( 'Welow_Importer' ) && file_exists( $importer_path ) ) {
            require_once $importer_path;
        }

        $ok = 0; $errores = array();
        $reflexion = new ReflectionClass( 'Welow_Importer' );
        $metodo = $reflexion->getMethod( 'procesar_fila_modelo' );
        $metodo->setAccessible( true );

        $i = 2; // primera fila de datos en Excel
        foreach ( $filas as $fila ) {
            try {
                $metodo->invokeArgs( null, array( $fila, $actualizar, $descargar_imagenes ) );
                $ok++;
            } catch ( Exception $e ) {
                $errores[] = 'Fila ' . $i . ': ' . $e->getMessage();
            }
            $i++;
        }

        $mensaje = sprintf( 'SuperExcel: %d modelo(s) procesado(s).', $ok );
        if ( ! empty( $errores ) ) {
            $mensaje .= ' Con ' . count( $errores ) . ' error(es).';
        }
        self::set_resultado( empty( $errores ) ? 'success' : 'warning', $mensaje, $errores );
        self::redirect_back();
    }

    private static function set_resultado( $tipo, $mensaje, $detalle = array() ) {
        set_transient( 'welow_import_resultado', array(
            'tipo'    => $tipo,
            'mensaje' => $mensaje,
            'detalle' => $detalle,
        ), 60 );
    }

    private static function redirect_back() {
        wp_safe_redirect( admin_url( 'admin.php?page=welow_importer' ) );
        exit;
    }
}
