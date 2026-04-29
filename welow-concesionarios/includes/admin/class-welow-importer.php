<?php
/**
 * Importador / Exportador CSV de marcas y modelos.
 *
 * @since 1.1.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Importer {

    const PAGE_SLUG = 'welow_importer';

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'registrar_pagina' ) );
        add_action( 'admin_post_welow_descargar_plantilla', array( __CLASS__, 'descargar_plantilla' ) );
        add_action( 'admin_post_welow_importar_csv', array( __CLASS__, 'procesar_importacion' ) );
        add_action( 'admin_post_welow_exportar_csv', array( __CLASS__, 'exportar_datos' ) );
    }

    public static function registrar_pagina() {
        add_submenu_page(
            'welow_concesionarios',
            'Importar / Exportar',
            'Importar / Exportar',
            'manage_options',
            self::PAGE_SLUG,
            array( __CLASS__, 'render_pagina' )
        );
    }

    /* =========================================================================
     * DEFINICIÓN DE COLUMNAS
     * ========================================================================= */

    /**
     * @since 1.0.0
     * @version 1.2.0 — Eliminadas columnas categorias/tipo_venta de marcas.
     */
    public static function columnas_marcas() {
        return array(
            'nombre', 'slug', 'desc_corta', 'slogan', 'web',
            'orden', 'activa',
            'logo_url', 'logo_negro_url', 'logo_blanco_url',
            'banner_portada_desktop_url', 'banner_portada_movil_url',
            'banner_media_desktop_url', 'banner_media_movil_url',
        );
    }

    /**
     * @since 1.0.0
     * @version 1.2.0 — Añadidas columnas categoria_modelo y plazas.
     */
    public static function columnas_modelos() {
        return array(
            'nombre', 'slug', 'marca_slug', 'descripcion', 'excerpt',
            'enlace', 'texto_enlace', 'precio_desde', 'disclaimer',
            'combustible', 'categoria_modelo', 'plazas',
            'etiquetas', 'orden', 'activo',
            'imagen_url', 'imagen_2_url', 'imagen_3_url', 'imagen_4_url', 'imagen_5_url',
        );
    }

    /**
     * @since 2.0.0
     */
    public static function columnas_concesionarios() {
        return array(
            'nombre', 'slug', 'direccion', 'cp', 'ciudad', 'provincia',
            'telefono', 'email', 'horario', 'lat', 'lng',
            'marcas', 'orden', 'activo', 'logo_url',
        );
    }

    /**
     * @since 2.1.0
     */
    public static function columnas_coches_nuevos() {
        return array(
            'titulo', 'slug', 'referencia', 'modelo_slug', 'version', 'estado',
            'precio_contado', 'precio_financiado', 'precio_anterior', 'cuota', 'disclaimer',
            'cambio', 'marchas', 'cv', 'cilindrada',
            'color', 'tipo_pintura', 'etiqueta_dgt', 'plazas', 'puertas',
            'combustible', 'carroceria', 'concesionario_slug', 'programa',
            'equipamiento', 'garantias',
            'matricula', 'vin',
            'imagen_url', 'galeria_urls',
        );
    }

    /**
     * @since 2.1.0
     */
    public static function columnas_coches_ocasion() {
        return array(
            'titulo', 'slug', 'referencia', 'marca_externa', 'modelo_texto', 'version',
            'tipo', 'estado',
            'mes_matricula', 'anio_matricula', 'km',
            'precio_contado', 'precio_financiado', 'precio_anterior', 'cuota', 'disclaimer',
            'cambio', 'marchas', 'cv', 'cilindrada',
            'color', 'tipo_pintura', 'etiqueta_dgt', 'plazas', 'puertas',
            'combustible', 'carroceria', 'concesionario_slug', 'programa',
            'equipamiento', 'garantias',
            'matricula', 'vin',
            'imagen_url', 'galeria_urls',
        );
    }

    /* =========================================================================
     * UI
     * ========================================================================= */

    public static function render_pagina() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        // Mostrar resultado tras importación
        $resultado = get_transient( 'welow_import_resultado' );
        if ( $resultado ) {
            delete_transient( 'welow_import_resultado' );
        }
        ?>
        <div class="wrap welow-importer">
            <h1>Importar / Exportar <span style="font-size:14px;color:#666;">v<?php echo esc_html( WELOW_CONC_VERSION ); ?></span></h1>
            <p>Importa marcas y modelos en lote desde un archivo CSV, o exporta los datos existentes.</p>

            <?php if ( $resultado ) : ?>
                <div class="notice notice-<?php echo esc_attr( $resultado['tipo'] ); ?>">
                    <p><strong><?php echo esc_html( $resultado['mensaje'] ); ?></strong></p>
                    <?php if ( ! empty( $resultado['detalle'] ) ) : ?>
                        <ul style="margin-left:20px; list-style:disc;">
                            <?php foreach ( $resultado['detalle'] as $linea ) : ?>
                                <li><?php echo esc_html( $linea ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="welow-importer-grid">

                <!-- MARCAS -->
                <div class="welow-importer-card">
                    <h2><span class="dashicons dashicons-awards"></span> Marcas</h2>

                    <h3>Descargar plantilla</h3>
                    <p>Plantilla CSV con las columnas necesarias para importar marcas.</p>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'welow_plantilla_marcas' ); ?>
                        <input type="hidden" name="action" value="welow_descargar_plantilla">
                        <input type="hidden" name="tipo" value="marcas">
                        <button type="submit" class="button">
                            <span class="dashicons dashicons-download" style="margin-top:4px;"></span>
                            Descargar plantilla CSV
                        </button>
                    </form>

                    <h3 style="margin-top:24px;">Importar CSV</h3>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                        <?php wp_nonce_field( 'welow_importar_marcas' ); ?>
                        <input type="hidden" name="action" value="welow_importar_csv">
                        <input type="hidden" name="tipo" value="marcas">
                        <p>
                            <input type="file" name="archivo_csv" accept=".csv" required>
                        </p>
                        <p>
                            <label>
                                <input type="checkbox" name="actualizar" value="1" checked>
                                Actualizar marcas existentes (por slug)
                            </label>
                        </p>
                        <p>
                            <label>
                                <input type="checkbox" name="descargar_imagenes" value="1" checked>
                                Descargar imágenes desde URLs (puede ser lento)
                            </label>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-upload" style="margin-top:4px;"></span>
                                Importar marcas
                            </button>
                        </p>
                    </form>

                    <h3 style="margin-top:24px;">Exportar</h3>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'welow_exportar_marcas' ); ?>
                        <input type="hidden" name="action" value="welow_exportar_csv">
                        <input type="hidden" name="tipo" value="marcas">
                        <button type="submit" class="button">
                            <span class="dashicons dashicons-migrate" style="margin-top:4px;"></span>
                            Exportar todas las marcas
                        </button>
                    </form>
                </div>

                <!-- MODELOS -->
                <div class="welow-importer-card">
                    <h2><span class="dashicons dashicons-car"></span> Modelos</h2>

                    <h3>Descargar plantilla</h3>
                    <p>Plantilla CSV con las columnas necesarias para importar modelos.</p>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'welow_plantilla_modelos' ); ?>
                        <input type="hidden" name="action" value="welow_descargar_plantilla">
                        <input type="hidden" name="tipo" value="modelos">
                        <button type="submit" class="button">
                            <span class="dashicons dashicons-download" style="margin-top:4px;"></span>
                            Descargar plantilla CSV
                        </button>
                    </form>

                    <h3 style="margin-top:24px;">Importar CSV</h3>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                        <?php wp_nonce_field( 'welow_importar_modelos' ); ?>
                        <input type="hidden" name="action" value="welow_importar_csv">
                        <input type="hidden" name="tipo" value="modelos">
                        <p>
                            <input type="file" name="archivo_csv" accept=".csv" required>
                        </p>
                        <p>
                            <label>
                                <input type="checkbox" name="actualizar" value="1" checked>
                                Actualizar modelos existentes (por slug + marca)
                            </label>
                        </p>
                        <p>
                            <label>
                                <input type="checkbox" name="descargar_imagenes" value="1" checked>
                                Descargar imágenes desde URLs (puede ser lento)
                            </label>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-upload" style="margin-top:4px;"></span>
                                Importar modelos
                            </button>
                        </p>
                    </form>

                    <h3 style="margin-top:24px;">Exportar</h3>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'welow_exportar_modelos' ); ?>
                        <input type="hidden" name="action" value="welow_exportar_csv">
                        <input type="hidden" name="tipo" value="modelos">
                        <button type="submit" class="button">
                            <span class="dashicons dashicons-migrate" style="margin-top:4px;"></span>
                            Exportar todos los modelos
                        </button>
                    </form>
                </div>

                <!-- COCHES NUEVOS (v2.1.0) -->
                <div class="welow-importer-card welow-importer-card--nuevos">
                    <h2><span class="dashicons dashicons-car"></span> Coches NUEVOS</h2>
                    <p>Coches del catálogo oficial (Toyota, Hyundai, JAECOO...). Requiere modelo_slug.</p>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:14px;">
                        <?php wp_nonce_field( 'welow_plantilla_coches_nuevos' ); ?>
                        <input type="hidden" name="action" value="welow_descargar_plantilla">
                        <input type="hidden" name="tipo" value="coches_nuevos">
                        <button type="submit" class="button">
                            <span class="dashicons dashicons-download" style="margin-top:4px;"></span>
                            Plantilla CSV
                        </button>
                    </form>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                        <?php wp_nonce_field( 'welow_importar_coches_nuevos' ); ?>
                        <input type="hidden" name="action" value="welow_importar_csv">
                        <input type="hidden" name="tipo" value="coches_nuevos">
                        <p><input type="file" name="archivo_csv" accept=".csv" required></p>
                        <p><label><input type="checkbox" name="actualizar" value="1" checked> Actualizar existentes</label></p>
                        <p><label><input type="checkbox" name="descargar_imagenes" value="1" checked> Descargar imágenes desde URLs</label></p>
                        <p>
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-upload" style="margin-top:4px;"></span>
                                Importar coches nuevos
                            </button>
                        </p>
                    </form>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:14px;">
                        <?php wp_nonce_field( 'welow_exportar_coches_nuevos' ); ?>
                        <input type="hidden" name="action" value="welow_exportar_csv">
                        <input type="hidden" name="tipo" value="coches_nuevos">
                        <button type="submit" class="button">
                            <span class="dashicons dashicons-migrate" style="margin-top:4px;"></span>
                            Exportar coches nuevos
                        </button>
                    </form>
                </div>

                <!-- COCHES DE OCASIÓN / KM0 (v2.1.0) -->
                <div class="welow-importer-card welow-importer-card--ocasion">
                    <h2><span class="dashicons dashicons-car"></span> Coches de OCASIÓN / KM0</h2>
                    <p>Cualquier marca (BMW, Audi, Renault...). Marca por taxonomía + modelo en texto libre.</p>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:14px;">
                        <?php wp_nonce_field( 'welow_plantilla_coches_ocasion' ); ?>
                        <input type="hidden" name="action" value="welow_descargar_plantilla">
                        <input type="hidden" name="tipo" value="coches_ocasion">
                        <button type="submit" class="button">
                            <span class="dashicons dashicons-download" style="margin-top:4px;"></span>
                            Plantilla CSV
                        </button>
                    </form>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                        <?php wp_nonce_field( 'welow_importar_coches_ocasion' ); ?>
                        <input type="hidden" name="action" value="welow_importar_csv">
                        <input type="hidden" name="tipo" value="coches_ocasion">
                        <p><input type="file" name="archivo_csv" accept=".csv" required></p>
                        <p><label><input type="checkbox" name="actualizar" value="1" checked> Actualizar existentes</label></p>
                        <p><label><input type="checkbox" name="descargar_imagenes" value="1" checked> Descargar imágenes desde URLs</label></p>
                        <p>
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-upload" style="margin-top:4px;"></span>
                                Importar coches de ocasión
                            </button>
                        </p>
                    </form>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:14px;">
                        <?php wp_nonce_field( 'welow_exportar_coches_ocasion' ); ?>
                        <input type="hidden" name="action" value="welow_exportar_csv">
                        <input type="hidden" name="tipo" value="coches_ocasion">
                        <button type="submit" class="button">
                            <span class="dashicons dashicons-migrate" style="margin-top:4px;"></span>
                            Exportar coches de ocasión
                        </button>
                    </form>
                </div>

                <!-- CONCESIONARIOS (v2.0.0) -->
                <div class="welow-importer-card">
                    <h2><span class="dashicons dashicons-store"></span> Concesionarios físicos</h2>
                    <p>Ubicaciones físicas con dirección y datos de contacto.</p>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-bottom:14px;">
                        <?php wp_nonce_field( 'welow_plantilla_concesionarios' ); ?>
                        <input type="hidden" name="action" value="welow_descargar_plantilla">
                        <input type="hidden" name="tipo" value="concesionarios">
                        <button type="submit" class="button">
                            <span class="dashicons dashicons-download" style="margin-top:4px;"></span>
                            Plantilla CSV
                        </button>
                    </form>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
                        <?php wp_nonce_field( 'welow_importar_concesionarios' ); ?>
                        <input type="hidden" name="action" value="welow_importar_csv">
                        <input type="hidden" name="tipo" value="concesionarios">
                        <p><input type="file" name="archivo_csv" accept=".csv" required></p>
                        <p>
                            <label>
                                <input type="checkbox" name="actualizar" value="1" checked>
                                Actualizar existentes (por slug)
                            </label>
                        </p>
                        <p>
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-upload" style="margin-top:4px;"></span>
                                Importar concesionarios
                            </button>
                        </p>
                    </form>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:14px;">
                        <?php wp_nonce_field( 'welow_exportar_concesionarios' ); ?>
                        <input type="hidden" name="action" value="welow_exportar_csv">
                        <input type="hidden" name="tipo" value="concesionarios">
                        <button type="submit" class="button">
                            <span class="dashicons dashicons-migrate" style="margin-top:4px;"></span>
                            Exportar concesionarios
                        </button>
                    </form>
                </div>

            </div>

            <hr style="margin: 30px 0;">

            <h2>Formato de los CSV</h2>
            <p>Los archivos CSV deben usar <strong>coma (,)</strong> como separador y <strong>UTF-8</strong> como codificación. Para campos con múltiples valores (etiquetas, combustible, categoria_modelo), usa el separador <strong>|</strong> (barra vertical).</p>

            <h3>Ejemplo Marcas:</h3>
            <pre style="background:#f5f5f5;padding:12px;border-radius:4px;overflow-x:auto;">nombre,slug,desc_corta,slogan,web,orden,activa,logo_url
Toyota,toyota,"Fiabilidad japonesa","Always a better way",https://toyota.es,10,1,https://ejemplo.com/toyota.png</pre>

            <h3>Ejemplo Modelos:</h3>
            <pre style="background:#f5f5f5;padding:12px;border-radius:4px;overflow-x:auto;">nombre,slug,marca_slug,descripcion,precio_desde,combustible,categoria_modelo,plazas,etiquetas,activo,imagen_url
Corolla,corolla,toyota,"Compacto híbrido",24990,hibrido,"berlina|compacto",5,"eco|nuevo",1,https://ejemplo.com/corolla.png</pre>
        </div>

        <style>
            .welow-importer-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-top: 20px;
            }
            .welow-importer-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 24px;
            }
            .welow-importer-card h2 {
                margin: 0 0 12px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .welow-importer-card h2 .dashicons {
                color: #2563eb;
                font-size: 24px;
                width: 24px;
                height: 24px;
            }
            .welow-importer-card h3 {
                font-size: 14px;
                text-transform: uppercase;
                color: #6b7280;
                margin-bottom: 8px;
            }
            @media (max-width: 900px) {
                .welow-importer-grid { grid-template-columns: 1fr; }
            }
        </style>
        <?php
    }

    /* =========================================================================
     * DESCARGA DE PLANTILLA
     * ========================================================================= */

    public static function descargar_plantilla() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado' );

        $tipo = isset( $_POST['tipo'] ) ? sanitize_key( $_POST['tipo'] ) : '';
        check_admin_referer( 'welow_plantilla_' . $tipo );

        if ( 'marcas' === $tipo ) {
            $columnas = self::columnas_marcas();
            $ejemplo = array(
                'Toyota', 'toyota', 'Fiabilidad japonesa', 'Always a better way', 'https://toyota.es',
                '10', '1',
                '', '', '', '', '', '', '',
            );
            $filename = 'plantilla-marcas.csv';
        } elseif ( 'modelos' === $tipo ) {
            $columnas = self::columnas_modelos();
            $ejemplo = array(
                'Corolla', 'corolla', 'toyota', 'Compacto híbrido de última generación', 'Desde 24.990€',
                '', 'Ver modelo', '24990', '',
                'hibrido', 'berlina|compacto', '5',
                'eco|nuevo', '10', '1',
                '', '', '', '', '',
            );
            $filename = 'plantilla-modelos.csv';
        } elseif ( 'coches_nuevos' === $tipo ) {
            $columnas = self::columnas_coches_nuevos();
            $ejemplo = array(
                'Toyota Corolla 2024', '', '8001', 'corolla', '1.8 Hybrid Active', 'disponible',
                '24990', '23990', '', '249', '',
                'automatico', '6', '122', '1798',
                'Blanco perla', 'perlada', 'eco', '5', '5',
                'hibrido', 'berlina', 'gamboa-madrid', '',
                '<ul><li>Climatizador bizona</li><li>Cámara trasera</li></ul>',
                '<ul><li>Garantía oficial 3 años</li></ul>',
                '', '',
                'https://ejemplo.com/corolla.jpg', '',
            );
            $filename = 'plantilla-coches-nuevos.csv';
        } elseif ( 'coches_ocasion' === $tipo ) {
            $columnas = self::columnas_coches_ocasion();
            $ejemplo = array(
                'BMW Serie 3 2020', '', '7539', 'bmw', 'Serie 3', '320d xDrive Sport',
                'ocasion', 'disponible',
                '7', '2020', '85000',
                '24990', '23990', '27000', '349', '',
                'automatico', '8', '190', '1995',
                'Negro metalizado', 'metalizada', 'c', '5', '4',
                'gasoil', 'berlina', 'gamboa-madrid', 'Premium Selection',
                '<ul><li>Cuero</li><li>Navegador profesional</li></ul>',
                '<ul><li>Garantía 24 meses</li></ul>',
                '', '',
                'https://ejemplo.com/bmw.jpg',
                'https://ejemplo.com/bmw2.jpg|https://ejemplo.com/bmw3.jpg',
            );
            $filename = 'plantilla-coches-ocasion.csv';
        } elseif ( 'concesionarios' === $tipo ) {
            $columnas = self::columnas_concesionarios();
            $ejemplo = array(
                'Gamboa Madrid Centro', 'gamboa-madrid', 'Calle Velázquez 123', '28006', 'Madrid', 'Madrid',
                '+34 91 234 56 78', 'info@gamboa.com', 'Lunes a Viernes 9:00-20:00 / Sábados 10:00-14:00',
                '40.4322', '-3.6789',
                'toyota|hyundai', '10', '1',
                'https://ejemplo.com/logo-gamboa.png',
            );
            $filename = 'plantilla-concesionarios.csv';
        } else {
            wp_die( 'Tipo desconocido' );
        }

        self::enviar_csv( $filename, array( $columnas, $ejemplo ) );
    }

    /* =========================================================================
     * EXPORTACIÓN
     * ========================================================================= */

    public static function exportar_datos() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado' );

        $tipo = isset( $_POST['tipo'] ) ? sanitize_key( $_POST['tipo'] ) : '';
        check_admin_referer( 'welow_exportar_' . $tipo );

        if ( 'marcas' === $tipo ) {
            $columnas = self::columnas_marcas();
            $rows = array( $columnas );
            $marcas = get_posts( array(
                'post_type' => 'welow_marca', 'post_status' => 'any', 'posts_per_page' => -1,
            ) );
            foreach ( $marcas as $m ) {
                $rows[] = self::fila_marca( $m );
            }
            self::enviar_csv( 'marcas-' . gmdate( 'Y-m-d' ) . '.csv', $rows );

        } elseif ( 'modelos' === $tipo ) {
            $columnas = self::columnas_modelos();
            $rows = array( $columnas );
            $modelos = get_posts( array(
                'post_type' => 'welow_modelo', 'post_status' => 'any', 'posts_per_page' => -1,
            ) );
            foreach ( $modelos as $m ) {
                $rows[] = self::fila_modelo( $m );
            }
            self::enviar_csv( 'modelos-' . gmdate( 'Y-m-d' ) . '.csv', $rows );

        } elseif ( 'coches_nuevos' === $tipo ) {
            $columnas = self::columnas_coches_nuevos();
            $rows = array( $columnas );
            $coches = get_posts( array(
                'post_type' => 'welow_coche_nuevo', 'post_status' => 'any', 'posts_per_page' => -1,
            ) );
            foreach ( $coches as $c ) {
                $rows[] = self::fila_coche_nuevo( $c );
            }
            self::enviar_csv( 'coches-nuevos-' . gmdate( 'Y-m-d' ) . '.csv', $rows );

        } elseif ( 'coches_ocasion' === $tipo ) {
            $columnas = self::columnas_coches_ocasion();
            $rows = array( $columnas );
            $coches = get_posts( array(
                'post_type' => 'welow_coche_ocasion', 'post_status' => 'any', 'posts_per_page' => -1,
            ) );
            foreach ( $coches as $c ) {
                $rows[] = self::fila_coche_ocasion( $c );
            }
            self::enviar_csv( 'coches-ocasion-' . gmdate( 'Y-m-d' ) . '.csv', $rows );

        } elseif ( 'concesionarios' === $tipo ) {
            $columnas = self::columnas_concesionarios();
            $rows = array( $columnas );
            $cs = get_posts( array(
                'post_type' => 'welow_concesionario', 'post_status' => 'any', 'posts_per_page' => -1,
            ) );
            foreach ( $cs as $c ) {
                $rows[] = self::fila_concesionario( $c );
            }
            self::enviar_csv( 'concesionarios-' . gmdate( 'Y-m-d' ) . '.csv', $rows );
        }
    }

    /**
     * @since 1.0.0
     * @version 1.2.0 — Eliminadas categorias y tipo_venta.
     */
    private static function fila_marca( $marca ) {
        $get_url = function( $meta_key ) use ( $marca ) {
            $id = get_post_meta( $marca->ID, $meta_key, true );
            return $id ? wp_get_attachment_url( $id ) : '';
        };
        $logo_id = get_post_thumbnail_id( $marca->ID );

        return array(
            $marca->post_title,
            $marca->post_name,
            get_post_meta( $marca->ID, '_welow_marca_desc_corta', true ),
            get_post_meta( $marca->ID, '_welow_marca_slogan', true ),
            get_post_meta( $marca->ID, '_welow_marca_web', true ),
            get_post_meta( $marca->ID, '_welow_marca_orden', true ),
            get_post_meta( $marca->ID, '_welow_marca_activa', true ) ?: '1',
            $logo_id ? wp_get_attachment_url( $logo_id ) : '',
            $get_url( '_welow_marca_logo_negro' ),
            $get_url( '_welow_marca_logo_blanco' ),
            $get_url( '_welow_marca_banner_portada_desktop' ),
            $get_url( '_welow_marca_banner_portada_movil' ),
            $get_url( '_welow_marca_banner_media_desktop' ),
            $get_url( '_welow_marca_banner_media_movil' ),
        );
    }

    /**
     * @since 1.0.0
     * @version 1.2.0 — Añadidas categoria_modelo y plazas.
     */
    private static function fila_modelo( $modelo ) {
        $marca_id = get_post_meta( $modelo->ID, '_welow_modelo_marca', true );
        $marca_slug = $marca_id ? get_post_field( 'post_name', $marca_id ) : '';

        $combustibles      = wp_get_post_terms( $modelo->ID, 'welow_combustible', array( 'fields' => 'slugs' ) );
        $categorias_modelo = wp_get_post_terms( $modelo->ID, 'welow_categoria_modelo', array( 'fields' => 'slugs' ) );
        $etiquetas_ids     = get_post_meta( $modelo->ID, '_welow_modelo_etiquetas', true ) ?: array();
        $etiquetas_slugs   = array();
        if ( is_array( $etiquetas_ids ) ) {
            foreach ( $etiquetas_ids as $eid ) {
                $et_slug = get_post_field( 'post_name', $eid );
                if ( $et_slug ) $etiquetas_slugs[] = $et_slug;
            }
        }

        $get_img_url = function( $meta_key ) use ( $modelo ) {
            $id = get_post_meta( $modelo->ID, $meta_key, true );
            return $id ? wp_get_attachment_url( $id ) : '';
        };

        $thumb_id = get_post_thumbnail_id( $modelo->ID );

        return array(
            $modelo->post_title,
            $modelo->post_name,
            $marca_slug,
            $modelo->post_content,
            $modelo->post_excerpt,
            get_post_meta( $modelo->ID, '_welow_modelo_enlace', true ),
            get_post_meta( $modelo->ID, '_welow_modelo_texto_enlace', true ),
            get_post_meta( $modelo->ID, '_welow_modelo_precio_desde', true ),
            get_post_meta( $modelo->ID, '_welow_modelo_disclaimer', true ),
            ! empty( $combustibles ) && ! is_wp_error( $combustibles ) ? implode( '|', $combustibles ) : '',
            ! empty( $categorias_modelo ) && ! is_wp_error( $categorias_modelo ) ? implode( '|', $categorias_modelo ) : '',
            get_post_meta( $modelo->ID, '_welow_modelo_plazas', true ),
            implode( '|', $etiquetas_slugs ),
            get_post_meta( $modelo->ID, '_welow_modelo_orden', true ),
            get_post_meta( $modelo->ID, '_welow_modelo_activo', true ) ?: '1',
            $thumb_id ? wp_get_attachment_url( $thumb_id ) : '',
            $get_img_url( '_welow_modelo_img_2' ),
            $get_img_url( '_welow_modelo_img_3' ),
            $get_img_url( '_welow_modelo_img_4' ),
            $get_img_url( '_welow_modelo_img_5' ),
        );
    }

    /**
     * Helper común para extraer datos compartidos de un coche.
     */
    private static function datos_coche_comunes( $coche ) {
        $g = function( $key ) use ( $coche ) {
            return get_post_meta( $coche->ID, '_welow_coche_' . $key, true );
        };

        $conc_id = $g( 'concesionario' );
        $conc_slug = $conc_id ? get_post_field( 'post_name', $conc_id ) : '';

        $combustibles = wp_get_post_terms( $coche->ID, 'welow_combustible', array( 'fields' => 'slugs' ) );
        $carrocerias  = wp_get_post_terms( $coche->ID, 'welow_categoria_modelo', array( 'fields' => 'slugs' ) );

        $thumb_id = get_post_thumbnail_id( $coche->ID );
        $galeria_ids = $g( 'galeria' ) ?: array();
        $galeria_urls = array();
        if ( is_array( $galeria_ids ) ) {
            foreach ( $galeria_ids as $gid ) {
                $u = wp_get_attachment_url( $gid );
                if ( $u ) $galeria_urls[] = $u;
            }
        }

        return array(
            'g'             => $g,
            'conc_slug'     => $conc_slug,
            'combustibles'  => ! empty( $combustibles ) && ! is_wp_error( $combustibles ) ? implode( '|', $combustibles ) : '',
            'carrocerias'   => ! empty( $carrocerias ) && ! is_wp_error( $carrocerias ) ? implode( '|', $carrocerias ) : '',
            'thumb_url'     => $thumb_id ? wp_get_attachment_url( $thumb_id ) : '',
            'galeria_urls'  => implode( '|', $galeria_urls ),
        );
    }

    /**
     * @since 2.1.0
     */
    private static function fila_coche_nuevo( $coche ) {
        $d = self::datos_coche_comunes( $coche );
        $g = $d['g'];

        $modelo_id = $g( 'modelo' );
        $modelo_slug = $modelo_id ? get_post_field( 'post_name', $modelo_id ) : '';

        return array(
            $coche->post_title,
            $coche->post_name,
            $g( 'referencia' ),
            $modelo_slug,
            $g( 'version' ),
            $g( 'estado' ),
            $g( 'precio_contado' ),
            $g( 'precio_financiado' ),
            $g( 'precio_anterior' ),
            $g( 'cuota' ),
            $g( 'disclaimer' ),
            $g( 'cambio' ),
            $g( 'marchas' ),
            $g( 'cv' ),
            $g( 'cilindrada' ),
            $g( 'color' ),
            $g( 'tipo_pintura' ),
            $g( 'etiqueta_dgt' ),
            $g( 'plazas' ),
            $g( 'puertas' ),
            $d['combustibles'],
            $d['carrocerias'],
            $d['conc_slug'],
            $g( 'programa' ),
            $g( 'equipamiento' ),
            $g( 'garantias' ),
            $g( 'matricula' ),
            $g( 'vin' ),
            $d['thumb_url'],
            $d['galeria_urls'],
        );
    }

    /**
     * @since 2.1.0
     */
    private static function fila_coche_ocasion( $coche ) {
        $d = self::datos_coche_comunes( $coche );
        $g = $d['g'];

        $marcas = wp_get_post_terms( $coche->ID, 'welow_marca_externa', array( 'fields' => 'slugs' ) );
        $marca_externa_slug = ! empty( $marcas ) && ! is_wp_error( $marcas ) ? $marcas[0] : '';

        return array(
            $coche->post_title,
            $coche->post_name,
            $g( 'referencia' ),
            $marca_externa_slug,
            $g( 'modelo_texto' ),
            $g( 'version' ),
            $g( 'tipo_venta' ),
            $g( 'estado' ),
            $g( 'mes_matricula' ),
            $g( 'anio_matricula' ),
            $g( 'km' ),
            $g( 'precio_contado' ),
            $g( 'precio_financiado' ),
            $g( 'precio_anterior' ),
            $g( 'cuota' ),
            $g( 'disclaimer' ),
            $g( 'cambio' ),
            $g( 'marchas' ),
            $g( 'cv' ),
            $g( 'cilindrada' ),
            $g( 'color' ),
            $g( 'tipo_pintura' ),
            $g( 'etiqueta_dgt' ),
            $g( 'plazas' ),
            $g( 'puertas' ),
            $d['combustibles'],
            $d['carrocerias'],
            $d['conc_slug'],
            $g( 'programa' ),
            $g( 'equipamiento' ),
            $g( 'garantias' ),
            $g( 'matricula' ),
            $g( 'vin' ),
            $d['thumb_url'],
            $d['galeria_urls'],
        );
    }

    /**
     * @since 2.0.0
     */
    private static function fila_concesionario( $conc ) {
        $g = function( $key ) use ( $conc ) {
            return get_post_meta( $conc->ID, '_welow_conc_' . $key, true );
        };
        $marcas_ids = get_post_meta( $conc->ID, '_welow_conc_marcas', true ) ?: array();
        $marcas_slugs = array();
        if ( is_array( $marcas_ids ) ) {
            foreach ( $marcas_ids as $mid ) {
                $s = get_post_field( 'post_name', $mid );
                if ( $s ) $marcas_slugs[] = $s;
            }
        }
        $logo_id = get_post_thumbnail_id( $conc->ID );

        return array(
            $conc->post_title,
            $conc->post_name,
            $g( 'direccion' ),
            $g( 'cp' ),
            $g( 'ciudad' ),
            $g( 'provincia' ),
            $g( 'telefono' ),
            $g( 'email' ),
            $g( 'horario' ),
            $g( 'lat' ),
            $g( 'lng' ),
            implode( '|', $marcas_slugs ),
            $g( 'orden' ),
            $g( 'activo' ) ?: '1',
            $logo_id ? wp_get_attachment_url( $logo_id ) : '',
        );
    }

    private static function enviar_csv( $filename, $rows ) {
        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

        // BOM para Excel
        echo "\xEF\xBB\xBF";

        $out = fopen( 'php://output', 'w' );
        foreach ( $rows as $row ) {
            fputcsv( $out, $row );
        }
        fclose( $out );
        exit;
    }

    /* =========================================================================
     * IMPORTACIÓN
     * ========================================================================= */

    public static function procesar_importacion() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado' );

        $tipo = isset( $_POST['tipo'] ) ? sanitize_key( $_POST['tipo'] ) : '';
        check_admin_referer( 'welow_importar_' . $tipo );

        $actualizar         = ! empty( $_POST['actualizar'] );
        $descargar_imagenes = ! empty( $_POST['descargar_imagenes'] );

        if ( empty( $_FILES['archivo_csv']['tmp_name'] ) ) {
            self::set_resultado( 'error', 'No se ha subido ningún archivo.' );
            self::redirect_back();
        }

        $filas = self::parsear_csv( $_FILES['archivo_csv']['tmp_name'] );
        if ( empty( $filas ) ) {
            self::set_resultado( 'error', 'El CSV está vacío o no se pudo leer.' );
            self::redirect_back();
        }

        $creados      = 0;
        $actualizados = 0;
        $errores      = array();

        if ( 'marcas' === $tipo ) {
            foreach ( $filas as $i => $fila ) {
                try {
                    $resultado = self::procesar_fila_marca( $fila, $actualizar, $descargar_imagenes );
                    if ( 'creado' === $resultado ) $creados++;
                    if ( 'actualizado' === $resultado ) $actualizados++;
                } catch ( Exception $e ) {
                    $errores[] = 'Fila ' . ( $i + 2 ) . ': ' . $e->getMessage();
                }
            }
        } elseif ( 'modelos' === $tipo ) {
            foreach ( $filas as $i => $fila ) {
                try {
                    $resultado = self::procesar_fila_modelo( $fila, $actualizar, $descargar_imagenes );
                    if ( 'creado' === $resultado ) $creados++;
                    if ( 'actualizado' === $resultado ) $actualizados++;
                } catch ( Exception $e ) {
                    $errores[] = 'Fila ' . ( $i + 2 ) . ': ' . $e->getMessage();
                }
            }
        } elseif ( 'coches_nuevos' === $tipo ) {
            foreach ( $filas as $i => $fila ) {
                try {
                    $resultado = self::procesar_fila_coche_nuevo( $fila, $actualizar, $descargar_imagenes );
                    if ( 'creado' === $resultado ) $creados++;
                    if ( 'actualizado' === $resultado ) $actualizados++;
                } catch ( Exception $e ) {
                    $errores[] = 'Fila ' . ( $i + 2 ) . ': ' . $e->getMessage();
                }
            }
        } elseif ( 'coches_ocasion' === $tipo ) {
            foreach ( $filas as $i => $fila ) {
                try {
                    $resultado = self::procesar_fila_coche_ocasion( $fila, $actualizar, $descargar_imagenes );
                    if ( 'creado' === $resultado ) $creados++;
                    if ( 'actualizado' === $resultado ) $actualizados++;
                } catch ( Exception $e ) {
                    $errores[] = 'Fila ' . ( $i + 2 ) . ': ' . $e->getMessage();
                }
            }
        } elseif ( 'concesionarios' === $tipo ) {
            foreach ( $filas as $i => $fila ) {
                try {
                    $resultado = self::procesar_fila_concesionario( $fila, $actualizar );
                    if ( 'creado' === $resultado ) $creados++;
                    if ( 'actualizado' === $resultado ) $actualizados++;
                } catch ( Exception $e ) {
                    $errores[] = 'Fila ' . ( $i + 2 ) . ': ' . $e->getMessage();
                }
            }
        }

        $mensaje = sprintf(
            'Importación completada: %d creados, %d actualizados, %d errores.',
            $creados, $actualizados, count( $errores )
        );
        $tipo_notice = empty( $errores ) ? 'success' : 'warning';
        self::set_resultado( $tipo_notice, $mensaje, $errores );
        self::redirect_back();
    }

    private static function parsear_csv( $ruta ) {
        $handle = fopen( $ruta, 'r' );
        if ( ! $handle ) return array();

        // Leer cabeceras
        $cabeceras = fgetcsv( $handle );
        if ( ! $cabeceras ) return array();

        // Quitar BOM si existe
        $cabeceras[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $cabeceras[0] );
        $cabeceras = array_map( 'trim', $cabeceras );

        $filas = array();
        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            if ( count( array_filter( $row ) ) === 0 ) continue; // fila vacía
            $filas[] = array_combine( $cabeceras, array_pad( $row, count( $cabeceras ), '' ) );
        }
        fclose( $handle );
        return $filas;
    }

    /**
     * Procesa una fila de marca.
     */
    private static function procesar_fila_marca( $fila, $actualizar, $descargar_imagenes ) {
        $nombre = trim( $fila['nombre'] ?? '' );
        if ( empty( $nombre ) ) {
            throw new Exception( 'Falta el campo "nombre".' );
        }

        $slug = ! empty( $fila['slug'] ) ? sanitize_title( $fila['slug'] ) : sanitize_title( $nombre );

        // Buscar existente por slug
        $existente = get_posts( array(
            'post_type'   => 'welow_marca',
            'name'        => $slug,
            'post_status' => 'any',
            'numberposts' => 1,
        ) );

        if ( $existente && ! $actualizar ) {
            throw new Exception( 'La marca "' . $slug . '" ya existe y no se actualizará.' );
        }

        $post_data = array(
            'post_type'    => 'welow_marca',
            'post_title'   => $nombre,
            'post_name'    => $slug,
            'post_status'  => 'publish',
        );

        if ( $existente ) {
            $post_data['ID'] = $existente[0]->ID;
            $post_id = wp_update_post( $post_data, true );
            $resultado_tipo = 'actualizado';
        } else {
            $post_id = wp_insert_post( $post_data, true );
            $resultado_tipo = 'creado';
        }

        if ( is_wp_error( $post_id ) ) {
            throw new Exception( $post_id->get_error_message() );
        }

        // Metas de texto
        $metas = array(
            '_welow_marca_desc_corta' => $fila['desc_corta'] ?? '',
            '_welow_marca_slogan'     => $fila['slogan'] ?? '',
            '_welow_marca_web'        => $fila['web'] ?? '',
            '_welow_marca_orden'      => $fila['orden'] ?? 0,
            '_welow_marca_activa'     => isset( $fila['activa'] ) && '' !== $fila['activa'] ? $fila['activa'] : '1',
        );
        foreach ( $metas as $key => $val ) {
            update_post_meta( $post_id, $key, $val );
        }

        // Imágenes desde URL
        if ( $descargar_imagenes ) {
            $mapeo_imagenes = array(
                'logo_url'                   => '_thumbnail_id',
                'logo_negro_url'             => '_welow_marca_logo_negro',
                'logo_blanco_url'            => '_welow_marca_logo_blanco',
                'banner_portada_desktop_url' => '_welow_marca_banner_portada_desktop',
                'banner_portada_movil_url'   => '_welow_marca_banner_portada_movil',
                'banner_media_desktop_url'   => '_welow_marca_banner_media_desktop',
                'banner_media_movil_url'     => '_welow_marca_banner_media_movil',
            );
            foreach ( $mapeo_imagenes as $columna => $meta_key ) {
                if ( ! empty( $fila[ $columna ] ) ) {
                    $img_id = self::sideload_imagen( $fila[ $columna ], $post_id );
                    if ( $img_id ) {
                        if ( '_thumbnail_id' === $meta_key ) {
                            set_post_thumbnail( $post_id, $img_id );
                        } else {
                            update_post_meta( $post_id, $meta_key, $img_id );
                        }
                    }
                }
            }
        }

        return $resultado_tipo;
    }

    /**
     * Procesa una fila de modelo.
     */
    private static function procesar_fila_modelo( $fila, $actualizar, $descargar_imagenes ) {
        $nombre = trim( $fila['nombre'] ?? '' );
        if ( empty( $nombre ) ) {
            throw new Exception( 'Falta el campo "nombre".' );
        }

        $marca_slug = trim( $fila['marca_slug'] ?? '' );
        if ( empty( $marca_slug ) ) {
            throw new Exception( 'Falta el campo "marca_slug".' );
        }

        $marca_id = Welow_Helpers::resolver_marca_id( $marca_slug );
        if ( ! $marca_id ) {
            throw new Exception( 'Marca "' . $marca_slug . '" no encontrada.' );
        }

        $slug = ! empty( $fila['slug'] ) ? sanitize_title( $fila['slug'] ) : sanitize_title( $nombre );

        // Buscar existente por slug + marca
        $existente = get_posts( array(
            'post_type'   => 'welow_modelo',
            'name'        => $slug,
            'post_status' => 'any',
            'numberposts' => 1,
            'meta_query'  => array(
                array( 'key' => '_welow_modelo_marca', 'value' => $marca_id ),
            ),
        ) );

        if ( $existente && ! $actualizar ) {
            throw new Exception( 'El modelo "' . $slug . '" ya existe en la marca "' . $marca_slug . '".' );
        }

        $post_data = array(
            'post_type'    => 'welow_modelo',
            'post_title'   => $nombre,
            'post_name'    => $slug,
            'post_status'  => 'publish',
            'post_content' => $fila['descripcion'] ?? '',
            'post_excerpt' => $fila['excerpt'] ?? '',
        );

        if ( $existente ) {
            $post_data['ID'] = $existente[0]->ID;
            $post_id = wp_update_post( $post_data, true );
            $tipo_resultado = 'actualizado';
        } else {
            $post_id = wp_insert_post( $post_data, true );
            $tipo_resultado = 'creado';
        }

        if ( is_wp_error( $post_id ) ) {
            throw new Exception( $post_id->get_error_message() );
        }

        update_post_meta( $post_id, '_welow_modelo_marca', $marca_id );

        // Metas
        $metas = array(
            '_welow_modelo_enlace'       => $fila['enlace'] ?? '',
            '_welow_modelo_texto_enlace' => $fila['texto_enlace'] ?? '',
            '_welow_modelo_precio_desde' => $fila['precio_desde'] ?? '',
            '_welow_modelo_disclaimer'   => $fila['disclaimer'] ?? '',
            '_welow_modelo_plazas'       => $fila['plazas'] ?? '',
            '_welow_modelo_orden'        => $fila['orden'] ?? 0,
            '_welow_modelo_activo'       => isset( $fila['activo'] ) && '' !== $fila['activo'] ? $fila['activo'] : '1',
        );
        foreach ( $metas as $key => $val ) {
            update_post_meta( $post_id, $key, $val );
        }

        // Combustible (taxonomía)
        if ( ! empty( $fila['combustible'] ) ) {
            $combustibles = array_map( 'trim', explode( '|', $fila['combustible'] ) );
            wp_set_object_terms( $post_id, $combustibles, 'welow_combustible', false );
        }

        // Categoría de modelo (taxonomía) — v1.2.0
        if ( ! empty( $fila['categoria_modelo'] ) ) {
            $cats_mod = array_map( 'trim', explode( '|', $fila['categoria_modelo'] ) );
            wp_set_object_terms( $post_id, $cats_mod, 'welow_categoria_modelo', false );
        }

        // Etiquetas (por slug)
        if ( ! empty( $fila['etiquetas'] ) ) {
            $slugs = array_map( 'trim', explode( '|', $fila['etiquetas'] ) );
            $ids = array();
            foreach ( $slugs as $etslug ) {
                $et = get_posts( array(
                    'post_type'   => 'welow_etiqueta',
                    'name'        => $etslug,
                    'post_status' => 'publish',
                    'numberposts' => 1,
                    'fields'      => 'ids',
                ) );
                if ( ! empty( $et ) ) $ids[] = $et[0];
            }
            update_post_meta( $post_id, '_welow_modelo_etiquetas', $ids );
        }

        // Imágenes
        if ( $descargar_imagenes ) {
            $mapeo = array(
                'imagen_url'   => '_thumbnail_id',
                'imagen_2_url' => '_welow_modelo_img_2',
                'imagen_3_url' => '_welow_modelo_img_3',
                'imagen_4_url' => '_welow_modelo_img_4',
                'imagen_5_url' => '_welow_modelo_img_5',
            );
            foreach ( $mapeo as $columna => $meta_key ) {
                if ( ! empty( $fila[ $columna ] ) ) {
                    $img_id = self::sideload_imagen( $fila[ $columna ], $post_id );
                    if ( $img_id ) {
                        if ( '_thumbnail_id' === $meta_key ) {
                            set_post_thumbnail( $post_id, $img_id );
                        } else {
                            update_post_meta( $post_id, $meta_key, $img_id );
                        }
                    }
                }
            }
        }

        return $tipo_resultado;
    }

    /**
     * Aplica metadatos comunes (precio, técnicos, comercial, etc.) a un coche.
     * Compartido entre nuevos y ocasión.
     */
    private static function aplicar_metas_comunes_coche( $coche_id, $fila, $descargar_imagenes ) {
        // Metas simples comunes
        $metas = array(
            'referencia', 'version', 'estado',
            'precio_contado', 'precio_financiado', 'precio_anterior', 'cuota', 'disclaimer',
            'cambio', 'marchas', 'cv', 'cilindrada',
            'color', 'tipo_pintura', 'etiqueta_dgt', 'plazas', 'puertas',
            'programa', 'matricula', 'vin',
        );
        foreach ( $metas as $key ) {
            if ( isset( $fila[ $key ] ) ) {
                update_post_meta( $coche_id, '_welow_coche_' . $key, $fila[ $key ] );
            }
        }

        // Calcular kW si solo hay CV
        $cv = floatval( $fila['cv'] ?? 0 );
        if ( $cv > 0 ) {
            update_post_meta( $coche_id, '_welow_coche_kw', round( $cv * 0.7355, 1 ) );
        }

        // WYSIWYG
        if ( isset( $fila['equipamiento'] ) ) {
            update_post_meta( $coche_id, '_welow_coche_equipamiento', wp_kses_post( $fila['equipamiento'] ) );
        }
        if ( isset( $fila['garantias'] ) ) {
            update_post_meta( $coche_id, '_welow_coche_garantias', wp_kses_post( $fila['garantias'] ) );
        }

        // Concesionario
        if ( ! empty( $fila['concesionario_slug'] ) ) {
            $conc_id = Welow_Helpers::resolver_post_id_by_slug( $fila['concesionario_slug'], 'welow_concesionario' );
            if ( $conc_id ) {
                update_post_meta( $coche_id, '_welow_coche_concesionario', $conc_id );
            }
        }

        // Taxonomías compartidas
        if ( ! empty( $fila['combustible'] ) ) {
            $combs = array_map( 'trim', explode( '|', $fila['combustible'] ) );
            wp_set_object_terms( $coche_id, $combs, 'welow_combustible', false );
        }
        if ( ! empty( $fila['carroceria'] ) ) {
            $cars = array_map( 'trim', explode( '|', $fila['carroceria'] ) );
            wp_set_object_terms( $coche_id, $cars, 'welow_categoria_modelo', false );
        }

        // Imágenes
        if ( $descargar_imagenes ) {
            if ( ! empty( $fila['imagen_url'] ) ) {
                $img_id = self::sideload_imagen( $fila['imagen_url'], $coche_id );
                if ( $img_id ) set_post_thumbnail( $coche_id, $img_id );
            }
            if ( ! empty( $fila['galeria_urls'] ) ) {
                $urls = array_map( 'trim', explode( '|', $fila['galeria_urls'] ) );
                $ids = array();
                foreach ( $urls as $u ) {
                    if ( ! $u ) continue;
                    $iid = self::sideload_imagen( $u, $coche_id );
                    if ( $iid ) $ids[] = $iid;
                    if ( count( $ids ) >= 30 ) break;
                }
                if ( ! empty( $ids ) ) {
                    update_post_meta( $coche_id, '_welow_coche_galeria', $ids );
                }
            }
        }
    }

    /**
     * Procesa una fila CSV de coche NUEVO (v2.1.0).
     */
    private static function procesar_fila_coche_nuevo( $fila, $actualizar, $descargar_imagenes ) {
        $titulo     = $fila['titulo'] ?? '';
        $slug       = sanitize_title( $fila['slug'] ?? '' );
        $referencia = $fila['referencia'] ?? '';

        if ( ! $titulo && ! $slug && ! $referencia ) {
            throw new Exception( 'Falta titulo, slug o referencia.' );
        }

        $modelo_slug = sanitize_title( $fila['modelo_slug'] ?? '' );
        if ( ! $modelo_slug ) throw new Exception( 'Falta modelo_slug.' );

        $modelo_id = Welow_Helpers::resolver_post_id_by_slug( $modelo_slug, 'welow_modelo' );
        if ( ! $modelo_id ) throw new Exception( 'Modelo no encontrado: ' . $modelo_slug );

        // Buscar existente
        $existente = self::buscar_coche_existente( 'welow_coche_nuevo', $referencia, $slug );

        if ( $existente && ! $actualizar ) {
            throw new Exception( 'Coche nuevo ya existe (modo actualizar desactivado).' );
        }

        if ( ! $titulo ) {
            $modelo = get_post( $modelo_id );
            $marca_id = get_post_meta( $modelo_id, '_welow_modelo_marca', true );
            $marca = $marca_id ? get_post( $marca_id ) : null;
            $titulo = trim( ( $marca ? $marca->post_title . ' ' : '' ) . ( $modelo ? $modelo->post_title : '' ) );
            if ( $referencia ) $titulo .= ' #' . $referencia;
        }

        $post_data = array(
            'post_type'   => 'welow_coche_nuevo',
            'post_status' => 'publish',
            'post_title'  => $titulo,
            'post_name'   => $slug ?: '',
        );
        if ( $existente ) {
            $post_data['ID'] = $existente->ID;
            $coche_id = wp_update_post( $post_data, true );
            $tipo_resultado = 'actualizado';
        } else {
            $coche_id = wp_insert_post( $post_data, true );
            $tipo_resultado = 'creado';
        }
        if ( is_wp_error( $coche_id ) ) throw new Exception( $coche_id->get_error_message() );

        // Campos específicos del nuevo
        update_post_meta( $coche_id, '_welow_coche_modelo', $modelo_id );
        update_post_meta( $coche_id, '_welow_coche_tipo_venta', 'nuevo' );

        // Metas comunes
        self::aplicar_metas_comunes_coche( $coche_id, $fila, $descargar_imagenes );

        return $tipo_resultado;
    }

    /**
     * Procesa una fila CSV de coche de OCASIÓN / KM0 (v2.1.0).
     */
    private static function procesar_fila_coche_ocasion( $fila, $actualizar, $descargar_imagenes ) {
        $titulo     = $fila['titulo'] ?? '';
        $slug       = sanitize_title( $fila['slug'] ?? '' );
        $referencia = $fila['referencia'] ?? '';

        if ( ! $titulo && ! $slug && ! $referencia ) {
            throw new Exception( 'Falta titulo, slug o referencia.' );
        }

        $marca_externa = sanitize_title( $fila['marca_externa'] ?? '' );
        $modelo_texto  = $fila['modelo_texto'] ?? '';
        if ( ! $marca_externa || ! $modelo_texto ) {
            throw new Exception( 'Faltan marca_externa o modelo_texto.' );
        }

        // Asegurar que el término marca_externa existe (crearlo si no)
        $term = get_term_by( 'slug', $marca_externa, 'welow_marca_externa' );
        if ( ! $term ) {
            // Crear con label igual al slug capitalizado
            $label = ucfirst( str_replace( '-', ' ', $marca_externa ) );
            $created = wp_insert_term( $label, 'welow_marca_externa', array( 'slug' => $marca_externa ) );
            if ( is_wp_error( $created ) ) throw new Exception( 'No se pudo crear marca: ' . $marca_externa );
        }

        $existente = self::buscar_coche_existente( 'welow_coche_ocasion', $referencia, $slug );

        if ( $existente && ! $actualizar ) {
            throw new Exception( 'Coche de ocasión ya existe (modo actualizar desactivado).' );
        }

        if ( ! $titulo ) {
            $titulo = trim( ucfirst( str_replace( '-', ' ', $marca_externa ) ) . ' ' . $modelo_texto );
            if ( $referencia ) $titulo .= ' #' . $referencia;
        }

        $post_data = array(
            'post_type'   => 'welow_coche_ocasion',
            'post_status' => 'publish',
            'post_title'  => $titulo,
            'post_name'   => $slug ?: '',
        );
        if ( $existente ) {
            $post_data['ID'] = $existente->ID;
            $coche_id = wp_update_post( $post_data, true );
            $tipo_resultado = 'actualizado';
        } else {
            $coche_id = wp_insert_post( $post_data, true );
            $tipo_resultado = 'creado';
        }
        if ( is_wp_error( $coche_id ) ) throw new Exception( $coche_id->get_error_message() );

        // Asignar marca externa (taxonomía)
        wp_set_object_terms( $coche_id, $marca_externa, 'welow_marca_externa', false );

        // Modelo en texto libre
        update_post_meta( $coche_id, '_welow_coche_modelo_texto', $modelo_texto );

        // Tipo (ocasion/km0)
        $tipo = $fila['tipo'] ?? 'ocasion';
        update_post_meta( $coche_id, '_welow_coche_tipo_venta', in_array( $tipo, array( 'ocasion', 'km0' ), true ) ? $tipo : 'ocasion' );

        // Específicos de ocasión: matriculación + km
        $nums = array( 'mes_matricula', 'anio_matricula', 'km' );
        foreach ( $nums as $f ) {
            if ( isset( $fila[ $f ] ) ) {
                update_post_meta( $coche_id, '_welow_coche_' . $f, $fila[ $f ] );
            }
        }

        // Metas comunes
        self::aplicar_metas_comunes_coche( $coche_id, $fila, $descargar_imagenes );

        return $tipo_resultado;
    }

    /**
     * Busca un coche existente por referencia o slug, dentro del CPT indicado.
     */
    private static function buscar_coche_existente( $cpt, $referencia, $slug ) {
        if ( $referencia ) {
            $found = get_posts( array(
                'post_type' => $cpt, 'posts_per_page' => 1, 'post_status' => 'any',
                'meta_query' => array( array( 'key' => '_welow_coche_referencia', 'value' => $referencia ) ),
            ) );
            if ( ! empty( $found ) ) return $found[0];
        }
        if ( $slug ) {
            $found = get_posts( array(
                'post_type' => $cpt, 'name' => $slug,
                'posts_per_page' => 1, 'post_status' => 'any',
            ) );
            if ( ! empty( $found ) ) return $found[0];
        }
        return null;
    }

    /**
     * Procesa una fila CSV de concesionario (v2.0.0).
     */
    private static function procesar_fila_concesionario( $fila, $actualizar ) {
        $nombre = $fila['nombre'] ?? '';
        $slug   = sanitize_title( $fila['slug'] ?? $nombre );
        if ( ! $nombre ) throw new Exception( 'Falta nombre.' );

        $existente = get_posts( array(
            'post_type' => 'welow_concesionario', 'name' => $slug,
            'posts_per_page' => 1, 'post_status' => 'any',
        ) );

        $post_data = array(
            'post_type'   => 'welow_concesionario',
            'post_status' => 'publish',
            'post_title'  => $nombre,
            'post_name'   => $slug,
        );

        if ( ! empty( $existente ) ) {
            if ( ! $actualizar ) throw new Exception( 'Ya existe (actualizar desactivado).' );
            $post_data['ID'] = $existente[0]->ID;
            $id = wp_update_post( $post_data, true );
            $tipo_resultado = 'actualizado';
        } else {
            $id = wp_insert_post( $post_data, true );
            $tipo_resultado = 'creado';
        }

        if ( is_wp_error( $id ) ) throw new Exception( $id->get_error_message() );

        $metas_text = array( 'direccion', 'cp', 'ciudad', 'provincia', 'telefono', 'email',
                             'horario', 'lat', 'lng', 'orden' );
        foreach ( $metas_text as $key ) {
            if ( isset( $fila[ $key ] ) ) {
                update_post_meta( $id, '_welow_conc_' . $key, $fila[ $key ] );
            }
        }
        if ( isset( $fila['activo'] ) ) {
            update_post_meta( $id, '_welow_conc_activo', $fila['activo'] ? '1' : '0' );
        }

        // Marcas
        if ( ! empty( $fila['marcas'] ) ) {
            $slugs = array_map( 'trim', explode( '|', $fila['marcas'] ) );
            $marca_ids = array();
            foreach ( $slugs as $s ) {
                $mid = Welow_Helpers::resolver_post_id_by_slug( $s, 'welow_marca' );
                if ( $mid ) $marca_ids[] = $mid;
            }
            update_post_meta( $id, '_welow_conc_marcas', $marca_ids );
        }

        // Logo
        if ( ! empty( $fila['logo_url'] ) ) {
            $img_id = self::sideload_imagen( $fila['logo_url'], $id );
            if ( $img_id ) set_post_thumbnail( $id, $img_id );
        }

        return $tipo_resultado;
    }

    /**
     * Descarga una imagen de una URL a la mediateca y devuelve el attachment ID.
     */
    private static function sideload_imagen( $url, $post_parent = 0 ) {
        if ( ! function_exists( 'media_sideload_image' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $url = esc_url_raw( $url );
        if ( empty( $url ) ) return false;

        // Verificar si ya existe en la mediateca (por filename)
        $filename = basename( wp_parse_url( $url, PHP_URL_PATH ) );
        $existing = get_posts( array(
            'post_type'   => 'attachment',
            'name'        => sanitize_title( pathinfo( $filename, PATHINFO_FILENAME ) ),
            'numberposts' => 1,
            'fields'      => 'ids',
        ) );
        if ( ! empty( $existing ) ) {
            return $existing[0];
        }

        $attachment_id = media_sideload_image( $url, $post_parent, null, 'id' );
        if ( is_wp_error( $attachment_id ) ) return false;

        return $attachment_id;
    }

    /* =========================================================================
     * HELPERS
     * ========================================================================= */

    private static function set_resultado( $tipo, $mensaje, $detalle = array() ) {
        set_transient( 'welow_import_resultado', array(
            'tipo'    => $tipo,
            'mensaje' => $mensaje,
            'detalle' => $detalle,
        ), 60 );
    }

    private static function redirect_back() {
        wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
        exit;
    }
}
