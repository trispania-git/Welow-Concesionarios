<?php
/**
 * Plugin Name: Welow Concesionarios
 * Plugin URI:  https://welow.es
 * Description: Sistema de gestión para concesionarios multimarca. CPTs, shortcodes y herramientas para coches nuevos y de segunda mano.
 * Version:     2.5.1
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
 * 2.5.1 — Fix UI ficha: badge "OCASIÓN" + formulario integrado
 *
 *   FIX BADGE TIPO en header de la ficha:
 *   La regla `.welow-coche-tipo` tenía `position: absolute` (necesaria
 *   para las cards del grid donde flota sobre la imagen) pero también
 *   se aplicaba en el header de la ficha individual, causando que el
 *   badge "OCASIÓN/NUEVO" se solapara con la URL del navegador o la
 *   admin bar de WP. Override añadido: `position: static` cuando está
 *   dentro de `.welow-coche-ficha__header`.
 *
 *   FORMULARIO INTEGRADO EN ASIDE:
 *   El formulario de contacto ahora va dentro del shortcode
 *   `[welow_coche_ficha]` por defecto, justo debajo del precio en
 *   la columna derecha (sidebar), con estilo más compacto:
 *     - Padding reducido
 *     - Tipografía más pequeña (12-13px)
 *     - Campos tel/email apilados (no en 2 columnas)
 *     - Título cambiado a "¿Te interesa?" (más conciso)
 *     - Sin mostrar la referencia (ya está en el precio arriba)
 *
 *   Esto reduce el formulario a un tamaño apropiado para el sidebar.
 *   Si se quiere el formulario ancho aparte, se puede excluir del
 *   shortcode y añadir [welow_coche_formulario] por separado.
 *
 *   El nuevo bloque "formulario" se añade al default de `mostrar` del
 *   shortcode `[welow_coche_ficha]`. Documentación actualizada en la
 *   pestaña "Ficha del coche" de Ayuda.
 *
 * 2.5.0 — Ficha del coche: URLs personalizadas + 4 shortcodes nuevos
 *
 *   URLs PERSONALIZADAS:
 *   - Coches NUEVOS:    /coches/{marca}/{modelo}/{slug}/
 *   - Coches OCASIÓN:   /coches/segunda-mano/{marca}/{modelo}/{slug}/
 *   - Rewrite rules + filtro post_type_link
 *   - Auto-flush al activar v2.5.0 (option `welow_rewrite_rules_v2_5_0`)
 *   - Si no resuelve marca/modelo: fallback al permalink por defecto
 *
 *   NUEVOS SHORTCODES PARA LA FICHA:
 *   - [welow_coche_breadcrumb]    Inicio › Coches › Segunda mano › Marca › Modelo › Versión
 *   - [welow_coches_similares]    Coches relacionados (misma marca y CPT)
 *                                  Si no hay suficientes con la misma marca,
 *                                  completa con otros del mismo tipo
 *   - [welow_coche_compartir]     Botones WhatsApp · Facebook · X · Email · Copiar URL
 *                                  con SVGs vectoriales y "Copiado!" feedback
 *   - [welow_coche_formulario]    Formulario contacto pre-rellenado con datos del coche.
 *                                  Envía email al concesionario asociado (o admin).
 *                                  Hook `welow_coche_contacto_enviado` para CRMs externos.
 *
 *   PÁGINA DE AYUDA:
 *   - Nueva pestaña "🚗 Ficha del coche" con guía paso a paso para
 *     montar la plantilla en Divi Theme Builder
 *   - Documentación de los 4 shortcodes nuevos
 *
 *   ARCHIVOS NUEVOS:
 *   - includes/class-welow-coche-permalinks.php
 *   - includes/shortcodes/class-welow-shortcode-coche-extras.php
 *   - assets/css/coche-extras.css
 *
 * 2.4.0 — Página de ayuda + datos para chatbots (shortcode + REST API)
 *
 *   PÁGINA DE AYUDA EN ADMIN:
 *   - Nuevo submenú "Concesionarios → Ayuda y shortcodes"
 *   - 4 pestañas: Shortcodes (con ejemplos copiables), Estructura,
 *     Importación CSV y Chatbots/API
 *   - Documentación auto-generada de los 14 shortcodes del plugin con
 *     parámetros, valores por defecto y ejemplos
 *   - Tabla de CPTs y taxonomías con descripción de cada una
 *
 *   NUEVO SHORTCODE [welow_listado_completo]:
 *   - Vuelca TODOS los datos del concesionario en HTML estructurado
 *     (con <article>, <dl>/<dt>/<dd>, atributos data-*) para consumo
 *     de chatbots y crawlers
 *   - 5 tipos: nuevos | ocasion | todos | modelos | marcas
 *   - Modo texto plano opcional con sin_html="si"
 *   - Cada coche incluye TODOS sus datos + el concesionario asociado
 *     (dirección, teléfono, email, horario, mapa)
 *
 *   AUTO-NOINDEX:
 *   - Páginas que contengan [welow_listado_completo] se marcan
 *     automáticamente como noindex,nofollow,noarchive,nosnippet
 *   - Compatible con Yoast SEO y Rank Math (filtros específicos)
 *   - Se excluyen del sitemap WP nativo (transient cache 1h)
 *
 *   REST API ENDPOINTS (namespace welow/v1):
 *   - GET /wp-json/welow/v1/info           → resumen + estadísticas
 *   - GET /wp-json/welow/v1/coches/nuevos  → coches nuevos
 *   - GET /wp-json/welow/v1/coches/ocasion → ocasión + KM0
 *   - GET /wp-json/welow/v1/coches/todos   → ambos
 *   - GET /wp-json/welow/v1/coches/{id}    → coche individual
 *   - GET /wp-json/welow/v1/modelos        → catálogo
 *   - GET /wp-json/welow/v1/marcas         → marcas oficiales
 *   - Parámetros: ?max, ?estado, ?tipo (en ocasión)
 *   - Públicos sin autenticación
 *
 *   HELPERS NUEVOS:
 *   - Welow_Helpers::get_coche_completo_data()  — datos planos para API
 *   - Welow_Helpers::get_modelo_completo_data()
 *   - Welow_Helpers::get_marca_completo_data()
 *
 *   ARCHIVOS NUEVOS:
 *   - includes/admin/class-welow-help.php
 *   - includes/api/class-welow-rest-api.php
 *   - includes/shortcodes/class-welow-shortcode-listado-completo.php
 *   - templates/listado-completo.php
 *
 * 2.3.3 — Fix: imagen del coche en listados con fallback a galería
 *
 *   PROBLEMA:
 *   En el listado admin de coches (Concesionarios → Coches de ocasión)
 *   y en los grids del frontend, la columna/card "Imagen" solo usaba
 *   la imagen destacada (Featured Image). Si el editor solo había
 *   añadido fotos a la galería pero no había establecido una imagen
 *   destacada, los coches aparecían sin foto.
 *
 *   SOLUCIÓN:
 *   - Nuevos helpers `Welow_Helpers::get_coche_imagen_principal_id()`
 *     y `get_coche_imagen_principal_url()` con prioridad:
 *       1) Imagen destacada (post thumbnail)
 *       2) Primera imagen de la galería (fallback)
 *       3) Vacío (templates muestran placeholder)
 *   - Aplicado en:
 *       - Columna "Imagen" del listado admin de coches
 *       - Card de coches-grid-nuevos.php
 *       - Card de coches-grid-ocasion.php
 *
 *   La ficha individual (coche-galeria.php) ya combinaba featured +
 *   galería automáticamente, no requiere cambios.
 *
 * 2.3.2 — Fix: galería de coches no se guardaba al actualizar
 *
 *   PROBLEMA:
 *   Al añadir imágenes a la galería de un coche y pulsar "Actualizar",
 *   las thumbnails desaparecían tras recargar — la meta no se persistía.
 *   El metabox usaba un único <input hidden> con IDs separados por
 *   comas, gestionado por JS. Si el JS fallaba, no se actualizaba bien
 *   o el navegador interpretaba mal el value, el guardado quedaba vacío.
 *
 *   SOLUCIÓN — Refactor a inputs[] nativos:
 *   - Cada thumb tiene ahora su propio <input name="welow_coche_galeria[]">
 *     dentro de la card (en lugar de un único hidden con CSV)
 *   - WordPress recibe un array PHP nativo en $_POST, sin parseo de strings
 *   - El orden DOM = orden POST (jquery-ui-sortable mantiene el orden via
 *     reordenar los elementos, no via mantener un array sincronizado)
 *   - Marcador `welow_coche_galeria_present` para que el guardado solo
 *     procese la galería cuando el metabox estuvo en el form (evita
 *     borrar la galería accidentalmente desde otros contextos)
 *
 *   También:
 *   - JS más defensivo: comprueba que wp.media existe antes de usarlo
 *   - Validación tipográfica del ID al cargar las thumbs existentes
 *   - jquery-ui-sortable simplificado (sin callbacks de update)
 *
 *   Archivo: includes/cpt/class-welow-cpt-coche-base.php
 *
 * 2.3.1 — Fix: campos CV/kW aceptan decimales
 *   - Los campos `Potencia (CV)` y `Potencia (kW)` en el metabox de
 *     datos técnicos del coche tenían `step="1"` (solo enteros), lo
 *     que bloqueaba el guardado cuando el cálculo automático
 *     kW = CV × 0.7355 producía un decimal (ej: 140 CV → 102.97 kW).
 *   - Cambiado `step="1"` a `step="0.1"` en ambos inputs.
 *   - El cálculo automático y el guardado ya soportaban decimales
 *     (`floatval` + `round( ..., 1 )`), solo era el input HTML el que
 *     bloqueaba la validación del navegador.
 *
 * 2.3.0 — Catálogo completo de marcas externas (99 marcas)
 *   - Ampliada la lista de marcas pre-cargadas de 20 a 99 (catálogo
 *     completo del mercado: Abarth, Acura, Aeolus, Aion, Aiways,
 *     Alfa Romeo, Alpina, Alpine, Aston Martin, Audi, Aurus, BAIC,
 *     Bentley, BMW, Bugatti, Buick, BYD, Cadillac, Caterham, Changan,
 *     Chery, Chevrolet, Chrysler, Citroën, Cupra, Dacia, Daihatsu,
 *     DFSK, Dodge, DS, Ferrari, Fiat, Ford, GAC, Genesis, GMC,
 *     Great Wall, Haval, Honda, Hongqi, Hummer, Hyundai, Ineos,
 *     Infiniti, Isuzu, Iveco, JAC, Jaguar, Jeep, Jetta, Kia,
 *     Koenigsegg, Lada, Lamborghini, Lancia, Land Rover, Lexus,
 *     Lincoln, Lotus, Lucid, Lynk & Co, Mahindra, Maserati, Mazda,
 *     McLaren, Mercedes-Benz, MG, Mini, Mitsubishi, Morgan, Nio,
 *     Nissan, Omoda, Opel, Pagani, Peugeot, Polestar, Porsche, RAM,
 *     Renault, Rivian, Rolls-Royce, Saab, SEAT, Škoda, Smart,
 *     SsangYong (KGM), Subaru, Suzuki, Tata, Tesla, Toyota, Vauxhall,
 *     VinFast, Volkswagen, Volvo, Wuling, XPeng, Zeekr).
 *   - Nuevo método público `Welow_Tax_Marca_Externa::get_marcas_catalogo()`
 *     que devuelve el array completo de slug => nombre.
 *   - `crear_terminos_defecto()` ahora devuelve resumen `[creadas, existentes]`.
 *   - Nuevo botón en Configuraciones "Cargar las 99 marcas del catálogo":
 *     idempotente (solo añade las que faltan, no toca las existentes).
 *     Tras cargar muestra aviso con cuántas se crearon y cuántas ya estaban.
 *   - El botón también ejecuta sincronización de marcas oficiales por si
 *     alguna se desligó.
 *
 * 2.2.1 — Mejora UX: enlace "Gestionar marcas externas" en Configuraciones
 *   - Añadido botón de acceso rápido a la taxonomía welow_marca_externa
 *     en la página Configuraciones del plugin (junto a etiquetas,
 *     combustibles y carrocerías). Ahora hay 3 rutas de acceso a las
 *     marcas externas: submenú del CPT, dashboard, y Configuraciones.
 *
 * 2.2.0 — Sincronización Marcas oficiales ↔ Marcas externas
 *
 *   PROBLEMA RESUELTO:
 *   Cada marca oficial del concesionario (welow_marca: Toyota, Hyundai...)
 *   y cada marca externa (welow_marca_externa: BMW, Audi, Peugeot...) eran
 *   dos sistemas independientes. Si el concesionario vendía Peugeot nuevo
 *   Y entraba un Peugeot de ocasión, el editor podía:
 *     - Crear "Peugeot" en marcas externas duplicando la oficial
 *     - Cometer typos como "Peoget" o "PEUGEOT"
 *     - Tener tres entradas distintas para la misma marca
 *
 *   SOLUCIÓN: SINCRONIZACIÓN AUTOMÁTICA
 *   - Hook al guardar marca oficial → crea/actualiza término gemelo en
 *     welow_marca_externa con el mismo slug y nombre
 *   - El término sincronizado guarda meta `_welow_marca_oficial_id`
 *     apuntando al post de la marca oficial
 *   - Si renombras la marca oficial, se actualiza la externa en cascada
 *   - Al borrar marca oficial: NO se borra el término externo (puede tener
 *     coches de ocasión vinculados), solo se desliga
 *
 *   DETECCIÓN DE TYPOS:
 *   - Al crear un término nuevo en marcas externas, comparación fuzzy
 *     con todas las marcas (oficiales + externas) usando similar_text()
 *     y normalización (sin tildes, minúsculas, sin caracteres especiales)
 *   - Si la similitud > 75% con alguna existente, aviso visual al editor:
 *     "⚠️ 'Peoget' se parece a 'Peugeot' (oficial, 88% similar)..."
 *
 *   UI MEJORADA:
 *   - Columna "Tipo" en listado de marcas externas:
 *     · Badge verde "OFICIAL" + link a editar marca oficial
 *     · Badge gris "Externa" para las que no son del catálogo
 *   - Las marcas oficiales sincronizadas NO se pueden borrar desde
 *     marcas externas (deshabilitada la acción)
 *   - En el formulario "Añadir nueva marca externa", aviso con la lista
 *     de marcas oficiales del concesionario para evitar duplicados
 *
 *   SINCRONIZACIÓN INICIAL:
 *   - Al activar el plugin v2.2.0, todas las marcas oficiales existentes
 *     se sincronizan automáticamente con sus gemelas externas
 *
 * 2.1.0 — Separación clara: Coches NUEVOS vs Coches de OCASIÓN
 *
 *   CAMBIO ARQUITECTÓNICO:
 *   - Eliminado el CPT único `welow_coche` de v2.0.0 (estaba mezclando
 *     coches del catálogo oficial con ocasión, lo cual no era correcto).
 *   - Creados DOS CPTs separados:
 *     · welow_coche_nuevo  → catálogo oficial (relación con welow_modelo)
 *     · welow_coche_ocasion → cualquier marca, segunda mano y KM0
 *
 *   COCHES NUEVOS (welow_coche_nuevo):
 *   - Relación obligatoria con welow_modelo (catálogo del concesionario)
 *   - Hereda carrocería, plazas y datos genéricos del modelo
 *   - URL: /coche-nuevo/{slug}/
 *   - Sin campos km/año (no procede para coches sin matricular)
 *
 *   COCHES DE OCASIÓN (welow_coche_ocasion):
 *   - Marca por taxonomía welow_marca_externa (BMW, Audi, Renault...)
 *   - Modelo en texto libre
 *   - Tipo: ocasion / km0
 *   - Campos completos de matriculación (mes, año, kilómetros)
 *   - URL: /coche-ocasion/{slug}/
 *
 *   NUEVA TAXONOMÍA:
 *   - welow_marca_externa con 20 marcas pre-cargadas
 *   - Soporte de icono/logo por término (vía Welow_Tax_Icon_Trait)
 *
 *   REFACTOR:
 *   - Clase abstracta Welow_CPT_Coche_Base con todos los campos comunes
 *     (precio, técnicos, galería, equipamiento, garantías, comercial,
 *      privados). Cada CPT solo define su metabox de identificación.
 *
 *   SHORTCODES:
 *   - [welow_coches] (de v2.0.0) ELIMINADO
 *   - [welow_coches_nuevos] NUEVO
 *   - [welow_coches_ocasion] NUEVO (con filtros de tipo, km, año, etc.)
 *   - [welow_coche_ficha id="auto"] adaptado: detecta automáticamente
 *     si el coche es nuevo o de ocasión y muestra los datos correctos
 *   - [welow_buscador_coches tipo="nuevos|ocasion|todos"] con filtros
 *     adaptables al tipo
 *
 *   IMPORTADOR CSV:
 *   - Dos secciones separadas: "Coches NUEVOS" y "Coches OCASIÓN"
 *   - Plantillas CSV distintas con columnas específicas para cada tipo
 *
 *   COMPATIBILIDAD:
 *   - Como en v2.0.0 no había datos cargados aún (acabamos de subirla),
 *     este es un breaking change limpio. El CPT welow_coche desaparece.
 *
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
define( 'WELOW_CONC_VERSION', '2.5.1' );
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
require_once WELOW_CONC_PATH . 'includes/admin/class-welow-marca-sync.php';          // v2.2.0
require_once WELOW_CONC_PATH . 'includes/admin/class-welow-help.php';                // v2.4.0

// API
require_once WELOW_CONC_PATH . 'includes/api/class-welow-rest-api.php';              // v2.4.0

// CPTs
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-marca.php';
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-slide.php';
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-modelo.php';
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-etiqueta.php';
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-concesionario.php';        // v2.0.0
// CPTs de coches (v2.1.0): clase base + dos especializaciones
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-coche-base.php';           // v2.1.0
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-coche-nuevo.php';          // v2.1.0
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-coche-ocasion.php';        // v2.1.0

// Taxonomías
require_once WELOW_CONC_PATH . 'includes/taxonomies/trait-welow-tax-icon.php';                 // v2.0.0
require_once WELOW_CONC_PATH . 'includes/taxonomies/class-welow-tax-combustible.php';          // v1.1.0
require_once WELOW_CONC_PATH . 'includes/taxonomies/class-welow-tax-categoria-modelo.php';     // v1.2.0
require_once WELOW_CONC_PATH . 'includes/taxonomies/class-welow-tax-marca-externa.php';        // v2.1.0

// Shortcodes
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-marcas.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-slider.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-modelos.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-slider-cta.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-contenido.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-marca-banner.php';
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-divi.php';          // v1.4.0
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-coches-nuevos.php';      // v2.1.0
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-coches-ocasion.php';     // v2.1.0
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-coche-ficha.php';        // v2.0.0
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-buscador-coches.php';    // v2.0.0
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-listado-completo.php';   // v2.4.0
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-coche-extras.php';      // v2.5.0

// Permalinks de coches
require_once WELOW_CONC_PATH . 'includes/class-welow-coche-permalinks.php';                       // v2.5.0

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
    Welow_CPT_Concesionario::registrar_cpt();      // v2.0.0
    Welow_CPT_Coche_Nuevo::registrar_cpt();        // v2.1.0
    Welow_CPT_Coche_Ocasion::registrar_cpt();      // v2.1.0
    Welow_Tax_Combustible::registrar_taxonomia();
    Welow_Tax_Combustible::crear_terminos_defecto();
    Welow_Tax_Categoria_Modelo::registrar_taxonomia();
    Welow_Tax_Categoria_Modelo::crear_terminos_defecto();
    Welow_Tax_Marca_Externa::registrar_taxonomia();      // v2.1.0
    Welow_Tax_Marca_Externa::crear_terminos_defecto();   // v2.1.0

    // v2.2.0 — Sincronización inicial: cada marca oficial debe tener
    // su gemela en la taxonomía de marcas externas.
    if ( class_exists( 'Welow_Marca_Sync' ) ) {
        Welow_Marca_Sync::sincronizar_todas();
    }

    flush_rewrite_rules();
});

register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
});
