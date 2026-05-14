<?php
/**
 * Escritor mínimo de archivos XLSX (Office Open XML).
 *
 * Soporta:
 *  - Múltiples hojas
 *  - Strings, números, fórmulas básicas
 *  - Validación de datos tipo lista (dropdowns) referenciando otras hojas
 *  - Ancho de columna
 *  - Fila de cabecera en negrita
 *
 * NO soporta: estilos avanzados, fórmulas complejas, imágenes, charts.
 * Pensado para generar plantillas de importación.
 *
 * @since 2.17.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Xlsx_Writer {

    /** @var array<string, array{rows: array, validations: array, col_widths: array}> */
    private $sheets = array();

    /** @var array<string, int> Tabla de strings compartidos */
    private $shared_strings = array();

    /** @var int */
    private $shared_strings_count = 0;

    /**
     * Crea/recupera una hoja.
     */
    public function add_sheet( $name ) {
        $name = self::sanitizar_nombre_hoja( $name );
        if ( ! isset( $this->sheets[ $name ] ) ) {
            $this->sheets[ $name ] = array(
                'rows'        => array(),
                'validations' => array(),
                'col_widths'  => array(),
            );
        }
        return $name;
    }

    /**
     * Añade una fila a la hoja.
     */
    public function add_row( $sheet_name, array $cells ) {
        $sheet_name = self::sanitizar_nombre_hoja( $sheet_name );
        if ( ! isset( $this->sheets[ $sheet_name ] ) ) {
            $this->add_sheet( $sheet_name );
        }
        $this->sheets[ $sheet_name ]['rows'][] = $cells;
    }

    /**
     * Añade validación de datos tipo lista (dropdown).
     *
     * @param string $sheet_name        Hoja sobre la que actúa la validación.
     * @param string $cell_range        Ej. "C2:C1000"
     * @param string $list_source       Ej. "Marcas!$A$2:$A$100" o "Si,No,Quizá"
     */
    public function add_list_validation( $sheet_name, $cell_range, $list_source ) {
        $sheet_name = self::sanitizar_nombre_hoja( $sheet_name );
        if ( ! isset( $this->sheets[ $sheet_name ] ) ) {
            $this->add_sheet( $sheet_name );
        }
        $this->sheets[ $sheet_name ]['validations'][] = array(
            'range'   => $cell_range,
            'formula' => $list_source,
        );
    }

    /**
     * Anchos de columna por hoja.
     *
     * @param string $sheet_name
     * @param array  $widths Mapa columna_idx (1-based) => width (caracteres).
     */
    public function set_col_widths( $sheet_name, array $widths ) {
        $sheet_name = self::sanitizar_nombre_hoja( $sheet_name );
        if ( ! isset( $this->sheets[ $sheet_name ] ) ) {
            $this->add_sheet( $sheet_name );
        }
        $this->sheets[ $sheet_name ]['col_widths'] = $widths;
    }

    /**
     * Construye el archivo .xlsx en disco.
     *
     * @return string Ruta al archivo temporal generado.
     */
    public function save_to_temp_file() {
        $tmp = wp_tempnam( 'welow-xlsx-' );
        $zip = new ZipArchive();
        if ( true !== $zip->open( $tmp, ZipArchive::OVERWRITE ) ) {
            throw new Exception( 'No se pudo crear el archivo XLSX temporal.' );
        }

        // [Content_Types].xml
        $zip->addFromString( '[Content_Types].xml', $this->build_content_types() );
        // _rels/.rels
        $zip->addFromString( '_rels/.rels', $this->build_root_rels() );
        // xl/_rels/workbook.xml.rels
        $zip->addFromString( 'xl/_rels/workbook.xml.rels', $this->build_workbook_rels() );
        // xl/workbook.xml
        $zip->addFromString( 'xl/workbook.xml', $this->build_workbook() );
        // xl/styles.xml
        $zip->addFromString( 'xl/styles.xml', $this->build_styles() );

        // Hojas
        $i = 1;
        foreach ( $this->sheets as $name => $data ) {
            $zip->addFromString( 'xl/worksheets/sheet' . $i . '.xml', $this->build_sheet( $data ) );
            $i++;
        }

        // sharedStrings.xml (al final, ya hemos rellenado la tabla)
        $zip->addFromString( 'xl/sharedStrings.xml', $this->build_shared_strings() );

        $zip->close();
        return $tmp;
    }

    /**
     * Envía el archivo como descarga directamente.
     */
    public function output_to_browser( $filename ) {
        $tmp = $this->save_to_temp_file();
        nocache_headers();
        header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . filesize( $tmp ) );
        readfile( $tmp );
        @unlink( $tmp );
        exit;
    }

    /* =========================================================================
     * BUILDERS de los distintos XML del paquete OOXML
     * ========================================================================= */

    private function build_content_types() {
        $sheet_overrides = '';
        $i = 1;
        foreach ( $this->sheets as $name => $data ) {
            $sheet_overrides .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $i++;
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . $sheet_overrides
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '</Types>';
    }

    private function build_root_rels() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function build_workbook_rels() {
        $out = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
             . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $rid = 1;
        $i = 1;
        foreach ( $this->sheets as $name => $data ) {
            $out .= '<Relationship Id="rId' . $rid . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
            $rid++;
            $i++;
        }
        $out .= '<Relationship Id="rId' . $rid . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $rid++;
        $out .= '<Relationship Id="rId' . $rid . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>';
        $out .= '</Relationships>';
        return $out;
    }

    private function build_workbook() {
        $sheets_xml = '';
        $i = 1;
        foreach ( $this->sheets as $name => $data ) {
            $sheets_xml .= '<sheet name="' . self::esc( $name ) . '" sheetId="' . $i . '" r:id="rId' . $i . '"/>';
            $i++;
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheets_xml . '</sheets>'
            . '</workbook>';
    }

    private function build_styles() {
        // Estilo 0: por defecto. Estilo 1: cabecera en negrita.
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="2"><xf/><xf fontId="1" applyFont="1"/></cellXfs>'
            . '</styleSheet>';
    }

    private function build_sheet( $data ) {
        $rows_xml = '';
        $row_idx  = 1;
        $is_first = true;
        foreach ( $data['rows'] as $row ) {
            $rows_xml .= '<row r="' . $row_idx . '">';
            $col_idx = 1;
            foreach ( $row as $cell ) {
                $ref = self::col_letter( $col_idx ) . $row_idx;
                $rows_xml .= $this->build_cell( $cell, $ref, $is_first );
                $col_idx++;
            }
            $rows_xml .= '</row>';
            $row_idx++;
            $is_first = false;
        }

        // Anchos de columna
        $cols_xml = '';
        if ( ! empty( $data['col_widths'] ) ) {
            $cols_xml .= '<cols>';
            foreach ( $data['col_widths'] as $idx => $w ) {
                $cols_xml .= '<col min="' . intval( $idx ) . '" max="' . intval( $idx ) . '" width="' . floatval( $w ) . '" customWidth="1"/>';
            }
            $cols_xml .= '</cols>';
        }

        // Validaciones de datos
        $validations_xml = '';
        if ( ! empty( $data['validations'] ) ) {
            $validations_xml .= '<dataValidations count="' . count( $data['validations'] ) . '">';
            foreach ( $data['validations'] as $v ) {
                $validations_xml .= '<dataValidation type="list" allowBlank="1" showInputMessage="1" showErrorMessage="0" sqref="' . self::esc( $v['range'] ) . '">'
                    . '<formula1>' . self::esc( $v['formula'] ) . '</formula1>'
                    . '</dataValidation>';
            }
            $validations_xml .= '</dataValidations>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . $cols_xml
            . '<sheetData>' . $rows_xml . '</sheetData>'
            . $validations_xml
            . '</worksheet>';
    }

    private function build_cell( $value, $ref, $bold ) {
        $style = $bold ? ' s="1"' : '';
        if ( $value === null || $value === '' ) {
            return '<c r="' . $ref . '"' . $style . '/>';
        }
        // Números nativos (sin notación científica): solo si es numeric Y razonable
        if ( is_int( $value ) || ( is_string( $value ) && preg_match( '/^-?\d+(\.\d+)?$/', $value ) && strlen( $value ) < 15 ) ) {
            return '<c r="' . $ref . '"' . $style . '><v>' . $value . '</v></c>';
        }
        // String (shared strings)
        $idx = $this->add_shared_string( (string) $value );
        return '<c r="' . $ref . '"' . $style . ' t="s"><v>' . $idx . '</v></c>';
    }

    private function add_shared_string( $s ) {
        if ( isset( $this->shared_strings[ $s ] ) ) {
            $this->shared_strings_count++;
            return $this->shared_strings[ $s ];
        }
        $idx = count( $this->shared_strings );
        $this->shared_strings[ $s ] = $idx;
        $this->shared_strings_count++;
        return $idx;
    }

    private function build_shared_strings() {
        $items = '';
        foreach ( $this->shared_strings as $s => $idx ) {
            $items .= '<si><t xml:space="preserve">' . self::esc( $s ) . '</t></si>';
        }
        $unique = count( $this->shared_strings );
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $this->shared_strings_count . '" uniqueCount="' . $unique . '">'
            . $items
            . '</sst>';
    }

    /* =========================================================================
     * HELPERS estáticos
     * ========================================================================= */

    /** Convierte índice 1-based a letra de columna (1=>A, 27=>AA). */
    public static function col_letter( $n ) {
        $s = '';
        while ( $n > 0 ) {
            $r = ( $n - 1 ) % 26;
            $s = chr( 65 + $r ) . $s;
            $n = intval( ( $n - 1 ) / 26 );
        }
        return $s;
    }

    public static function esc( $s ) {
        return htmlspecialchars( (string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
    }

    public static function sanitizar_nombre_hoja( $name ) {
        // Excel: máximo 31 caracteres, sin :\/?*[]
        $name = preg_replace( '/[:\\\\\\/\\?\\*\\[\\]]/', '', $name );
        return mb_substr( $name, 0, 31 );
    }
}
