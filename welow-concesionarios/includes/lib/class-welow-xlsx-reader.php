<?php
/**
 * Lector mínimo de archivos XLSX.
 *
 * Lee una hoja específica como array de filas, donde cada fila es un
 * array indexado por la cabecera de la primera fila.
 *
 * @since 2.17.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Xlsx_Reader {

    /** @var ZipArchive */
    private $zip;

    /** @var array<int, string> */
    private $shared_strings = array();

    /** @var array<string, string> nombre_hoja => ruta interna (xl/worksheets/sheetN.xml) */
    private $sheets = array();

    public function __construct( $ruta_xlsx ) {
        if ( ! class_exists( 'ZipArchive' ) ) {
            throw new Exception( 'Extensión PHP zip no disponible.' );
        }
        $this->zip = new ZipArchive();
        if ( true !== $this->zip->open( $ruta_xlsx ) ) {
            throw new Exception( 'No se pudo abrir el archivo XLSX.' );
        }
        $this->cargar_shared_strings();
        $this->cargar_lista_hojas();
    }

    public function __destruct() {
        if ( $this->zip ) {
            @$this->zip->close();
        }
    }

    /**
     * Lista todas las hojas del workbook (en orden).
     */
    public function get_sheet_names() {
        return array_keys( $this->sheets );
    }

    /**
     * Lee una hoja como array de filas asociativas, usando la primera fila
     * como cabecera.
     *
     * @param string $sheet_name
     * @return array
     */
    public function get_rows_assoc( $sheet_name ) {
        $rows = $this->get_rows( $sheet_name );
        if ( empty( $rows ) ) return array();

        $cabeceras = array_shift( $rows );
        $cabeceras = array_map( 'trim', $cabeceras );

        $out = array();
        foreach ( $rows as $row ) {
            // Saltar filas vacías
            if ( count( array_filter( $row, function( $v ) { return $v !== '' && $v !== null; } ) ) === 0 ) {
                continue;
            }
            $row_padded = array_pad( $row, count( $cabeceras ), '' );
            $row_padded = array_slice( $row_padded, 0, count( $cabeceras ) );
            $out[] = array_combine( $cabeceras, $row_padded );
        }
        return $out;
    }

    /**
     * Lee una hoja como array de filas indexadas (sin asociar a cabecera).
     */
    public function get_rows( $sheet_name ) {
        if ( ! isset( $this->sheets[ $sheet_name ] ) ) {
            // Intento case-insensitive
            foreach ( $this->sheets as $n => $path ) {
                if ( mb_strtolower( $n ) === mb_strtolower( $sheet_name ) ) {
                    $sheet_name = $n;
                    break;
                }
            }
            if ( ! isset( $this->sheets[ $sheet_name ] ) ) {
                throw new Exception( 'Hoja "' . $sheet_name . '" no encontrada en el XLSX.' );
            }
        }
        $path = $this->sheets[ $sheet_name ];
        $xml_str = $this->zip->getFromName( $path );
        if ( ! $xml_str ) return array();

        $xml = simplexml_load_string( $xml_str );
        if ( ! $xml || ! isset( $xml->sheetData ) ) return array();

        $rows = array();
        foreach ( $xml->sheetData->row as $row ) {
            $row_arr = array();
            $expected_col = 1;
            foreach ( $row->c as $c ) {
                $ref = (string) $c['r'];
                $col_idx = self::ref_to_col_index( $ref );
                // Rellenar huecos
                while ( $expected_col < $col_idx ) {
                    $row_arr[] = '';
                    $expected_col++;
                }
                $type = (string) $c['t'];
                $value = isset( $c->v ) ? (string) $c->v : '';
                if ( $type === 's' ) {
                    $value = $this->shared_strings[ intval( $value ) ] ?? '';
                } elseif ( $type === 'inlineStr' && isset( $c->is->t ) ) {
                    $value = (string) $c->is->t;
                }
                $row_arr[] = $value;
                $expected_col++;
            }
            $rows[] = $row_arr;
        }
        return $rows;
    }

    /* ====================================================================== */

    private function cargar_shared_strings() {
        $xml_str = $this->zip->getFromName( 'xl/sharedStrings.xml' );
        if ( ! $xml_str ) return;
        $xml = simplexml_load_string( $xml_str );
        if ( ! $xml ) return;
        foreach ( $xml->si as $si ) {
            if ( isset( $si->t ) ) {
                $this->shared_strings[] = (string) $si->t;
            } elseif ( isset( $si->r ) ) {
                // Texto enriquecido: concatenar los <r><t>
                $s = '';
                foreach ( $si->r as $r ) {
                    if ( isset( $r->t ) ) $s .= (string) $r->t;
                }
                $this->shared_strings[] = $s;
            } else {
                $this->shared_strings[] = '';
            }
        }
    }

    private function cargar_lista_hojas() {
        // Leer workbook.xml + workbook.xml.rels
        $wb_str   = $this->zip->getFromName( 'xl/workbook.xml' );
        $rels_str = $this->zip->getFromName( 'xl/_rels/workbook.xml.rels' );
        if ( ! $wb_str || ! $rels_str ) return;

        // Mapa rId => target
        $rels = simplexml_load_string( $rels_str );
        $rid_to_target = array();
        foreach ( $rels->Relationship as $rel ) {
            $rid_to_target[ (string) $rel['Id'] ] = (string) $rel['Target'];
        }

        // Hojas en workbook
        $wb = simplexml_load_string( $wb_str );
        $wb->registerXPathNamespace( 'r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships' );
        foreach ( $wb->sheets->sheet as $s ) {
            $name = (string) $s['name'];
            $rid_attr = $s->attributes( 'http://schemas.openxmlformats.org/officeDocument/2006/relationships' );
            $rid = (string) $rid_attr->id;
            if ( isset( $rid_to_target[ $rid ] ) ) {
                $target = $rid_to_target[ $rid ];
                // El target suele ser relativo a xl/ (ej. "worksheets/sheet1.xml")
                if ( strpos( $target, '/' ) !== 0 ) {
                    $target = 'xl/' . $target;
                } else {
                    $target = ltrim( $target, '/' );
                }
                $this->sheets[ $name ] = $target;
            }
        }
    }

    /** "B3" => 2, "AA1" => 27 */
    private static function ref_to_col_index( $ref ) {
        preg_match( '/^([A-Z]+)/', $ref, $m );
        if ( empty( $m[1] ) ) return 1;
        $letters = $m[1];
        $n = 0;
        for ( $i = 0; $i < strlen( $letters ); $i++ ) {
            $n = $n * 26 + ( ord( $letters[ $i ] ) - 64 );
        }
        return $n;
    }
}
