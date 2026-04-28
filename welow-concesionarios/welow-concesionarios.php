<?php
/**
 * Plugin Name: Welow Concesionarios
 * Plugin URI:  https://welow.es
 * Description: Sistema de gestión para concesionarios multimarca. CPTs, shortcodes y herramientas para coches nuevos y de segunda mano.
 * Version:     2.0.0
 * Author:      Welow
 * Author URI:  https://welow.es
 * License:     GPL-2.0+
 * Text Domain: welow-concesionarios
 * Domain Path: /languages
 *
 * @package Welow_Concesionarios
 *
 * CHANGELOG
 * ---------
 * 2.0.0 — Coches en venta (ocasión, KM0, nuevos) y concesionarios físicos
 *
 *   ARQUITECTURA:
 *   - Nuevo CPT welow_coche: unidades concretas en venta con relación
 *     obligatoria a welow_modelo (hereda datos genéricos)
 *   - Nuevo CPT welow_concesionario: ubicaciones físicas con dirección,
 *     contacto, horario, mapa, marcas representadas
 *
 *   FICHA DEL COCHE (8 bloques):
 *   - A. Identificación: modelo, versión, tipo_venta, estado, referencia,
 *     mes/año matriculación, kilómetros
 *   - B. Precio: contado, financiado, anterior (tachado), cuota, disclaimer
 *   - C. Datos técnicos: cambio, marchas, CV/kW (auto-calc), cilindrada,
 *     plazas, puertas, color, tipo pintura, etiqueta DGT
 *   - D. Galería: imagen principal + hasta 30 imágenes (drag-reorder)
 *   - E. Equipamiento: editor WYSIWYG libre
 *   - F. Garantías: editor WYSIWYG libre
 *   - G. Comercial: concesionario, programa especial
 *   - H. Datos privados: matrícula, VIN (NO se muestran en frontend)
 *
 *   SISTEMA DE ICONOS:
 *   - Nueva sección "Iconos" en Configuraciones para asignar iconos
 *     personalizados a campos clave (km, año, combustible, etc.)
 *   - Iconos por valor de select (manual/automático, etiquetas DGT...)
 *   - Iconos por término de taxonomía (combustible, carrocería)
 *   - Fallback automático a Dashicons si no hay icono asignado
 *
 *   SHORTCODES NUEVOS:
 *   - [welow_coches] — grid filtrado de coches con paginación
 *   - [welow_coche_ficha] — ficha individual completa con galería,
 *     destacados, precio, equipamiento, garantías y concesionario.
 *     Soporta id="auto" para detectar coche del contexto (Theme Builder)
 *   - [welow_buscador_coches] — formulario de búsqueda con filtros
 *
 *   IMPORTADOR CSV:
 *   - Soporte de coches y concesionarios
 *   - Plantillas con ejemplos
 *   - Descarga automática de imágenes (galería completa) desde URLs
 *   - Modo actualizar por referencia/slug
 *
 *   COMPATIBILIDAD:
 *   - Esta versión NO rompe nada de v1.x — solo añade.
 *   - Todos los datos existentes (marcas, modelos, etiquetas, slides,
 *     carrocerías, combustibles) siguen funcionando idénticamente.
 *
 * 1.4.0 — Integración con la Biblioteca de Divi
 *   - Nuevo shortcode [welow_divi id="X"] que inserta cualquier layout
 *     guardado en la Biblioteca Divi (et_pb_layout): secciones, filas,
 *     módulos o layouts completos
 *   - Soporta búsqueda por id, slug o nombre del layout
 *   - Parámetro `envolver="si"` añade un wrapper con clase para targeting CSS
 *   - Mejora admin: en la lista de Biblioteca Divi se añade una columna
 *     con el shortcode copiable al portapapeles (botón clipboard)
 *   - Card de "Biblioteca Divi" añadida al dashboard de Concesionarios
 *   - Permite reutilizar diseños complejos creados en Divi en cualquier
 *     contexto: páginas, plantillas Theme Builder, otros shortcodes, etc.
 *
 * 1.3.0 — Auto-detección de marca (Theme Builder ready)
 *   - Nuevo helper Welow_Helpers::get_current_marca_id() que detecta
 *     la marca del contexto actual (single de marca o de modelo)
 *   - [welow_marca_banner] sin marca → usa la marca actual automáticamente
 *   - [welow_modelos] sin marca → muestra los modelos de la marca actual
 *   - [welow_slider grupo="auto"] → busca el grupo "{slug-marca}-home"
 *   - [welow_slider grupo="{marca}-ofertas"] → reemplaza {marca} por el
 *     slug de la marca actual (placeholder dinámico)
 *   - Filtro `welow_current_marca_id` para forzar la marca desde código
 *   - Permite construir UNA plantilla en Divi Theme Builder que sirve
 *     a las 13+ marcas
 *
 * 1.2.1 — Renombrar Categoría de modelo → Carrocería
 *   - Taxonomía welow_categoria_modelo: labels visibles cambiadas a "Carrocería"
 *     (slug interno se mantiene para no romper datos existentes)
 *   - Slug de URL: /carroceria/
 *   - Añadido enlace "Gestionar carrocerías" en página Configuraciones
 *   - Añadida card "Carrocerías" en el dashboard del menú Concesionarios
 *
 * 1.2.0 — Reorganización de campos
 *   - Marcas: ELIMINADOS metabox "Clasificación y venta" (categorías + tipo_venta)
 *   - Modelos: NUEVO campo "plazas" (número de plazas)
 *   - Modelos: NUEVA taxonomía welow_categoria_modelo (Berlina, SUV,
 *     Monovolumen, Coupé, Familiar, Pick-up, etc.)
 *   - Template modelos-grid: muestra categoría, combustible y plazas
 *   - Importador CSV actualizado con nuevas columnas
 *   - Shortcodes [welow_marcas] / [welow_marcas_cards]: ELIMINADOS
 *     parámetros `tipo` y `mostrar_categorias`
 *
 * 1.1.0 (Fase 3) — Reestructuración administrativa + nuevos campos
 *   - Menú unificado "Concesionarios" con dashboard
 *   - Marcas: 3 logos (original, negro, blanco) + 2 banners (portada + zona media) con desktop/móvil
 *   - Modelos: galería de 5 imágenes, precio desde, disclaimer con override, etiquetas visuales
 *   - Nuevo CPT: welow_etiqueta (etiquetas visuales)
 *   - Nueva taxonomía: welow_combustible (Gasoil, Gasolina, Híbrido, etc.)
 *   - Nueva página Configuraciones (disclaimer global + icono)
 *   - Nuevo shortcode: [welow_marca_banner tipo="portada|media"]
 *   - Shortcodes marcas: parámetro variante_logo
 *   - Importador/Exportador CSV de marcas y modelos
 *
 * 1.0.0 — Versión inicial
 *   - CPTs: marca, slide, modelo
 *   - Shortcodes: [welow_marcas], [welow_marcas_cards], [welow_slider],
 *     [welow_modelos], [welow_slider_cta], [welow_contenido]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constantes del plugin
define( 'WELOW_CONC_VERSION', '2.0.0' );
define( 'WELOW_CONC_PATH', plugin_dir_path( __FILE__ ) );
define( 'WELOW_CONC_URL', plugin_dir_url( __FILE__ ) );
define( 'WELOW_CONC_BASENAME', plugin_basename( __FILE__ ) );

// Cargar clases
require_once WELOW_CONC_PATH . 'includes/helpers/class-welow-helpers.php';

// Admin
require_once WELOW_CONC_PATH . 'includes/admin/class-welow-admin-menu.php';         // v1.1.0
require_once WELOW_CONC_PATH . 'includes/admin/class-welow-settings.php';            // v1.1.0
require_once WELOW_CONC_PATH . 'includes/admin/class-welow-importer.php';            // v1.1.0
require_once WELOW_CONC_PATH . 'includes/admin/class-welow-divi-library-admin.php';  // v1.4.0
require_once WELOW_CONC_PATH . 'includes/admin/class-welow-icons.php';               // v2.0.0

// CPTs
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-marca.php';
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-slide.php';
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-modelo.php';
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-etiqueta.php';
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-concesionario.php';     // v2.0.0
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-coche.php';             // v2.0.0

// Taxonomías
require_once WELOW_CONC_PATH . 'includes/taxonomies/trait-welow-tax-icon.php';                 // v2.0.0
require_once WELOW_CONC_PATH . 'includes/taxonomies/class-welow-tax-combustible.php';        // v1.1.0
require_once WELOW_CONC_PATH . 'includes/taxonomies/class-welow-tax-categoria-modelo.php';   // v1.2.0

// Shortcodes
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-marcas.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-slider.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-modelos.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-slider-cta.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-contenido.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-marca-banner.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-divi.php';          // v1.4.0
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-coches.php';        // v2.0.0
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-coche-ficha.php';   // v2.0.0
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-buscador-coches.php';// v2.0.0

// Principal
require_once WELOW_CONC_PATH . 'includes/class-welow-main.php';

// Inicializar plugin
function welow_concesionarios_init() {
    Welow_Main::get_instance();
}
add_action( 'plugins_loaded', 'welow_concesionarios_init' );

// Flush rewrite rules al activar/desactivar
register_activation_hook( __FILE__, function() {
    Welow_CPT_Marca::registrar_cpt();
    Welow_CPT_Slide::registrar_cpt();
    Welow_CPT_Modelo::registrar_cpt();
    Welow_CPT_Etiqueta::registrar_cpt();
    Welow_CPT_Concesionario::registrar_cpt();   // v2.0.0
    Welow_CPT_Coche::registrar_cpt();           // v2.0.0
    Welow_Tax_Combustible::registrar_taxonomia();
    Welow_Tax_Combustible::crear_terminos_defecto();
    Welow_Tax_Categoria_Modelo::registrar_taxonomia();
    Welow_Tax_Categoria_Modelo::crear_terminos_defecto();
    flush_rewrite_rules();
});

register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
});
