<?php
/**
 * Menú administrativo unificado "Concesionarios".
 *
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Admin_Menu {

    const SLUG = 'welow_concesionarios';

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'registrar_menu' ), 9 );
    }

    /**
     * Registra el menú padre "Concesionarios".
     * Los CPTs se añaden como submenús usando show_in_menu => self::SLUG.
     */
    public static function registrar_menu() {
        add_menu_page(
            'Concesionarios',
            'Concesionarios',
            'manage_options',
            self::SLUG,
            array( __CLASS__, 'render_dashboard' ),
            'dashicons-car',
            5
        );

        // El primer submenú siempre apunta al parent, renombramos
        add_submenu_page(
            self::SLUG,
            'Panel de Concesionarios',
            'Panel',
            'manage_options',
            self::SLUG,
            array( __CLASS__, 'render_dashboard' )
        );
    }

    /**
     * Dashboard con accesos rápidos.
     */
    public static function render_dashboard() {
        ?>
        <div class="wrap welow-dashboard">
            <h1>Concesionarios <span class="welow-version">v<?php echo esc_html( WELOW_CONC_VERSION ); ?></span></h1>
            <p>Panel de gestión del sistema de concesionarios multimarca.</p>

            <div class="welow-dashboard-grid">

                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=welow_coche_nuevo' ) ); ?>" class="welow-card welow-card-highlight">
                    <span class="dashicons dashicons-car"></span>
                    <h3>Coches NUEVOS</h3>
                    <p>Catálogo oficial. Vinculados a un modelo.</p>
                    <span class="welow-count">
                        <?php echo post_type_exists( 'welow_coche_nuevo' ) ? intval( wp_count_posts( 'welow_coche_nuevo' )->publish ) : 0; ?> publicados
                    </span>
                </a>

                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=welow_coche_ocasion' ) ); ?>" class="welow-card welow-card-highlight">
                    <span class="dashicons dashicons-car"></span>
                    <h3>Coches OCASIÓN / KM0</h3>
                    <p>Cualquier marca. Segunda mano y KM0.</p>
                    <span class="welow-count">
                        <?php echo post_type_exists( 'welow_coche_ocasion' ) ? intval( wp_count_posts( 'welow_coche_ocasion' )->publish ) : 0; ?> publicados
                    </span>
                </a>

                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=welow_concesionario' ) ); ?>" class="welow-card">
                    <span class="dashicons dashicons-store"></span>
                    <h3>Concesionarios</h3>
                    <p>Ubicaciones físicas y datos de contacto.</p>
                    <span class="welow-count">
                        <?php echo post_type_exists( 'welow_concesionario' ) ? intval( wp_count_posts( 'welow_concesionario' )->publish ) : 0; ?> publicados
                    </span>
                </a>

                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=welow_marca' ) ); ?>" class="welow-card">
                    <span class="dashicons dashicons-awards"></span>
                    <h3>Marcas oficiales</h3>
                    <p>Catálogo del concesionario (Toyota, Hyundai, JAECOO...).</p>
                    <span class="welow-count">
                        <?php echo intval( wp_count_posts( 'welow_marca' )->publish ); ?> publicadas
                    </span>
                </a>

                <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=welow_marca_externa&post_type=welow_coche_ocasion' ) ); ?>" class="welow-card">
                    <span class="dashicons dashicons-tag"></span>
                    <h3>Marcas externas</h3>
                    <p>BMW, Audi, Renault... para coches de ocasión.</p>
                </a>

                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=welow_modelo' ) ); ?>" class="welow-card">
                    <span class="dashicons dashicons-car"></span>
                    <h3>Modelos (catálogo)</h3>
                    <p>Modelos genéricos de las marcas oficiales (para coches nuevos).</p>
                    <span class="welow-count">
                        <?php echo intval( wp_count_posts( 'welow_modelo' )->publish ); ?> publicados
                    </span>
                </a>

                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=welow_etiqueta' ) ); ?>" class="welow-card">
                    <span class="dashicons dashicons-tag"></span>
                    <h3>Etiquetas</h3>
                    <p>Gestionar etiquetas visuales para modelos.</p>
                    <span class="welow-count">
                        <?php echo intval( wp_count_posts( 'welow_etiqueta' )->publish ); ?> publicadas
                    </span>
                </a>

                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=welow_slide' ) ); ?>" class="welow-card">
                    <span class="dashicons dashicons-slides"></span>
                    <h3>Slides</h3>
                    <p>Gestionar sliders reutilizables.</p>
                    <span class="welow-count">
                        <?php echo intval( wp_count_posts( 'welow_slide' )->publish ); ?> publicados
                    </span>
                </a>

                <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=welow_combustible&post_type=welow_modelo' ) ); ?>" class="welow-card">
                    <span class="dashicons dashicons-category"></span>
                    <h3>Combustibles</h3>
                    <p>Tipos de combustible / motorización.</p>
                </a>

                <a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=welow_categoria_modelo&post_type=welow_modelo' ) ); ?>" class="welow-card">
                    <span class="dashicons dashicons-car"></span>
                    <h3>Carrocerías</h3>
                    <p>Berlina, SUV, Monovolumen, Coupé, etc.</p>
                </a>

                <a href="<?php echo esc_url( admin_url( 'admin.php?page=welow_settings' ) ); ?>" class="welow-card">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <h3>Configuraciones</h3>
                    <p>Disclaimer global del precio y opciones generales.</p>
                </a>

                <a href="<?php echo esc_url( admin_url( 'admin.php?page=welow_importer' ) ); ?>" class="welow-card welow-card-highlight">
                    <span class="dashicons dashicons-upload"></span>
                    <h3>Importar / Exportar</h3>
                    <p>Importar marcas y modelos desde CSV, o descargar plantillas.</p>
                </a>

                <a href="<?php echo esc_url( admin_url( 'admin.php?page=welow_help' ) ); ?>" class="welow-card welow-card-highlight">
                    <span class="dashicons dashicons-book-alt"></span>
                    <h3>Ayuda y shortcodes</h3>
                    <p>Documentación de todos los shortcodes, estructura, CSV y datos para chatbots.</p>
                </a>

                <?php if ( post_type_exists( 'et_pb_layout' ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=et_pb_layout' ) ); ?>" class="welow-card welow-card-highlight">
                    <span class="dashicons dashicons-layout"></span>
                    <h3>Biblioteca Divi</h3>
                    <p>Layouts guardados de Divi insertables vía <code>[welow_divi]</code>.</p>
                </a>
                <?php endif; ?>

            </div>
        </div>

        <style>
            .welow-dashboard h1 { display: flex; align-items: center; gap: 12px; }
            .welow-version {
                font-size: 12px;
                background: #2563eb;
                color: white;
                padding: 3px 10px;
                border-radius: 20px;
                font-weight: 500;
            }
            .welow-dashboard-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 16px;
                margin-top: 24px;
            }
            .welow-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 20px;
                text-decoration: none;
                color: #1f2937;
                transition: all 0.2s ease;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .welow-card:hover {
                border-color: #2563eb;
                box-shadow: 0 4px 14px rgba(37,99,235,0.12);
                transform: translateY(-2px);
                color: #1f2937;
            }
            .welow-card .dashicons {
                font-size: 32px;
                width: 32px;
                height: 32px;
                color: #2563eb;
            }
            .welow-card h3 { margin: 4px 0; font-size: 18px; }
            .welow-card p { color: #6b7280; margin: 0; font-size: 13px; }
            .welow-count {
                margin-top: 8px;
                font-size: 12px;
                color: #059669;
                font-weight: 600;
            }
            .welow-card-highlight {
                background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
                border-color: #7dd3fc;
            }
        </style>
        <?php
    }
}
