<?php
/**
 * Plugin Name: Welow Concesionarios
 * Plugin URI:  https://welow.es
 * Description: Sistema de gestión para concesionarios multimarca. CPTs, shortcodes y herramientas para coches nuevos y de segunda mano.
 * Version:     2.9.0
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
 * 2.9.0 — Header con logo de marca al lado (variante para páginas de marca)
 *
 *   NUEVOS PARÁMETROS DE [welow_header]:
 *   - logo_marca: '' (default) | 'auto' | slug de marca específica
 *   - logo_marca_variante: original | negro (default) | blanco
 *   - logo_marca_altura: px (por defecto igual al logo principal)
 *   - logo_marca_separador: si | no — línea vertical entre logos
 *
 *   AUTO-DETECCIÓN ('auto'):
 *   - Single welow_marca         → la marca actual
 *   - Single welow_modelo        → marca asociada al modelo
 *   - Single welow_coche_nuevo   → marca del modelo del coche
 *   - Single welow_coche_ocasion → marca externa (icono del término)
 *     o, si está sincronizada con una oficial, usa el logo oficial
 *
 *   USO TÍPICO en plantilla Theme Builder de "All Marca Posts":
 *   [welow_header logo_marca="auto" logo_marca_variante="negro"]
 *
 *   Resultado: header normal con logo del concesionario a la izquierda,
 *   un separador vertical fino, y el logo de la marca actual (negro)
 *   al lado.
 *
 *   HELPER NUEVO:
 *   Welow_Helpers::get_current_marca_logo_data($variante, $size)
 *   Devuelve {tipo, id, nombre, url_logo, url_link} de la marca del
 *   contexto actual, o null si no se puede determinar.
 *
 *   Archivos:
 *   - includes/helpers/class-welow-helpers.php
 *   - includes/shortcodes/class-welow-shortcode-header.php
 *   - templates/header.php
 *   - assets/css/header.css
 *   - includes/admin/class-welow-help.php (doc)
 *
 * 2.8.1 — Filtros con autosubmit (sin pulsar "Aplicar")
 *
 *   COMPORTAMIENTO MEJORADO:
 *   - Checkboxes, radios y selects → submit inmediato al cambiar
 *   - Inputs number (precio, km, año, CV) → submit con debounce
 *     de 700ms tras dejar de escribir, o al perder el foco (blur)
 *   - Al cambiar filtro se resetea siempre a página 1
 *   - Indicador visual de "cargando" (opacidad reducida en sidebar
 *     y main) durante el submit
 *
 *   BOTÓN "APLICAR FILTROS":
 *   - Oculto en desktop (no necesario con autosubmit)
 *   - Visible en móvil (más cómodo: marca varios filtros en el
 *     drawer y luego aplica con un solo tap)
 *   - Si JS está desactivado, sigue funcionando como antes (fallback)
 *
 *   Archivos: assets/js/coches-filtro.js, assets/css/coches-filtro.css,
 *   templates/coches-filtro.php
 *
 * 2.8.0 — Página de filtros + listado [welow_coches_filtro]
 *
 *   NUEVO SHORTCODE [welow_coches_filtro]:
 *   Página completa estilo "tienda" con sidebar de filtros (izq) +
 *   grid de resultados (der) + paginación + ordenación. Inspirado
 *   en grupogamboa.com/jaecoo/ofertas-coches.
 *
 *   FILTROS DISPONIBLES (configurables vía mostrar_filtros):
 *   - Marca (oficial: select / externa: checkboxes con scroll)
 *   - Tipo (ocasión/KM0) — solo en CPT ocasión
 *   - Carrocería (checkboxes)
 *   - Combustible (checkboxes)
 *   - Cambio (manual / automático / semi)
 *   - Precio (rango from-to en €)
 *   - Año (rango from-to)
 *   - Kilómetros (rango from-to)
 *   - Potencia CV (rango from-to)
 *
 *   CABECERA RESULTADOS:
 *   - Contador "X resultados" / "1 resultado"
 *   - Selector de ordenación (recientes, precio asc/desc, km asc, año desc)
 *     con autosubmit del form al cambiar
 *
 *   PAGINACIÓN:
 *   Configurable por_pagina (default 12). Navegación con páginas
 *   numeradas, "..." para saltos, prev/next. Mantiene los filtros
 *   activos en cada página.
 *
 *   COMPORTAMIENTO MÓVIL:
 *   En pantallas <= 980px, el sidebar se oculta y aparece un botón
 *   "Filtros" con badge del nº de filtros activos. Al pulsarlo
 *   se abre un drawer fullscreen con los filtros. Cierre con backdrop,
 *   botón × o tecla Escape.
 *
 *   PÁGINAS DE MARCA:
 *   Param `marca_fija="toyota"` permite crear páginas dedicadas tipo
 *   /toyota/ofertas/ donde la marca está fija y solo se filtran las
 *   demás opciones.
 *
 *   FILTROS VIA GET (URL params):
 *   Sin AJAX. Submit del form recarga la página manteniendo los
 *   parámetros en la URL (compartibles, indexables, accesibles).
 *
 *   HELPERS NUEVOS:
 *   - get_coches_paginado() devuelve {posts, total, paginas_total, pagina_actual, por_pagina}
 *   - get_coches() ampliado con: km_min, anio_max, cv_min, cv_max, cambio
 *     y soporte de arrays en combustible/carrocería/marca_externa
 *
 *   ARCHIVOS NUEVOS:
 *   - includes/shortcodes/class-welow-shortcode-coches-filtro.php
 *   - templates/coches-filtro.php
 *   - assets/css/coches-filtro.css
 *   - assets/js/coches-filtro.js
 *
 * 2.7.2 — Fix: header sticky desaparece debajo de sliders al hacer scroll
 *
 *   PROBLEMA:
 *   En páginas con sliders fullwidth de Divi (típicamente las páginas
 *   de marca con [welow_slider]), al hacer scroll el header sticky
 *   se quedaba debajo del slider y desaparecía. Esto pasa porque las
 *   secciones Divi pueden tener transform/filter/will-change que
 *   crean un contexto de stacking propio, atrapando al header
 *   `position: fixed` dentro y haciéndolo aparecer detrás de
 *   elementos con z-index propio (sliders).
 *
 *   SOLUCIÓN — Mover el header al body:
 *   El JS, tras detectar padres Divi y crear el spacer en la posición
 *   original, MUEVE el `<header>` al `<body>` directamente. Así el
 *   header escapa de cualquier contexto de stacking del Theme Builder
 *   y siempre se posiciona respecto al viewport.
 *
 *   El spacer queda en la posición original ocupando el hueco para
 *   que el contenido siguiente no se "salga" hacia arriba.
 *
 *   Adicionalmente:
 *   - z-index del sticky subido de 1000 a 99998 (overlay sigue en 99999)
 *   - `isolation: isolate` para crear contexto de stacking propio
 *
 *   Archivos: assets/js/header.js, assets/css/header.css
 *
 * 2.7.1 — Fix: header con márgenes laterales en Theme Builder
 *
 *   PROBLEMA:
 *   Cuando se insertaba [welow_header] en un módulo Texto del Theme
 *   Builder de Divi, aparecían márgenes laterales no deseados
 *   (logo no llegaba al borde izquierdo, botón no llegaba al derecho)
 *   por dos causas:
 *
 *   1) El default `ancho_max` era "1280px", que limitaba el contenido
 *      interior y dejaba espacios en blanco a los lados en pantallas
 *      anchas.
 *   2) Los contenedores Divi (.et_pb_text_inner, .et_pb_module,
 *      .et_pb_row, .et_pb_section) tienen padding propio que no se
 *      podía controlar desde el shortcode.
 *
 *   SOLUCIÓN:
 *   - `ancho_max` default cambiado de "1280px" a "100%"
 *   - Truco "fullwidth breakout" CSS para headers no-sticky:
 *     width: 100vw + margin: calc(50% - 50vw) → rompe contenedor padre
 *   - JS detecta los 4 niveles de contenedores Divi padres y les
 *     añade clases: welow-header-parent, welow-header-row-parent,
 *     welow-header-section-parent. CSS neutraliza padding/margin de
 *     todos ellos con !important.
 *
 *   Archivos: includes/shortcodes/class-welow-shortcode-header.php,
 *   assets/css/header.css, assets/js/header.js
 *
 * 2.7.0 — Tipografía configurable del header + Google Fonts auto
 *
 *   NUEVA SECCIÓN "Tipografía del header" en Configuraciones:
 *   - Familia tipográfica (texto libre con autocompletado de 19
 *     fuentes Google populares: Figtree, Inter, Roboto, Poppins,
 *     Open Sans, Montserrat, Lato, Raleway, Nunito, Outfit, DM Sans,
 *     Manrope, Plus Jakarta Sans, Work Sans, Source Sans 3, Ubuntu,
 *     Mulish, Barlow, Exo 2)
 *   - ☑ Cargar desde Google Fonts (carga automática en el head)
 *   - Peso del menú (300/400/500/600/700/800)
 *   - Peso del botón CTA (400/500/600/700/800)
 *   - Tamaño en px del menú, botón y teléfono (independientes)
 *   - Estilo del menú: text-transform (none/uppercase/capitalize)
 *   - Espaciado entre letras del menú (letter-spacing)
 *
 *   GOOGLE FONTS AUTO-LOAD:
 *   Si "Cargar desde Google Fonts" está marcado y hay una familia
 *   especificada, el plugin imprime los <link rel="preconnect"> +
 *   <link rel="stylesheet"> en wp_head con los pesos solicitados,
 *   sin necesidad de plugins externos. URL optimizada con &display=swap.
 *
 *   APLICACIÓN VÍA CSS VARIABLES:
 *   Todas las propiedades tipográficas se aplican vía CSS custom
 *   properties (--welow-h-font, --welow-h-fw-menu, --welow-h-fs-menu,
 *   etc.) en el atributo style del header. Esto permite override por
 *   shortcode sin tocar CSS.
 *
 *   OVERRIDE POR SHORTCODE:
 *   [welow_header font_family="Figtree" font_weight_menu="500"
 *                 font_size_menu="15" text_transform_menu="uppercase"]
 *
 *   Archivos:
 *   - includes/admin/class-welow-settings.php  (UI + sanitize)
 *   - includes/shortcodes/class-welow-shortcode-header.php  (params + Google Fonts)
 *   - templates/header.php  (variables CSS inline)
 *   - assets/css/header.css  (uso de las variables)
 *
 * 2.6.2 — Fix sticky header: position fixed + spacer dinámico
 *
 *   PROBLEMA:
 *   `position: sticky` no funcionaba en muchos contextos de Divi
 *   porque las secciones/filas pueden tener overflow, transform o
 *   filter que rompen el contexto de stacking de sticky. El header
 *   marcado como sticky no se quedaba arriba al hacer scroll.
 *
 *   SOLUCIÓN:
 *   Cambiado a `position: fixed` (robusto, no afectado por contexto).
 *   El JS crea automáticamente un elemento spacer justo después del
 *   header con la misma altura, para que el contenido siguiente no
 *   quede oculto debajo del header fixed.
 *
 *   - Recalcula la altura al resize del viewport
 *   - Recalcula tras cargar la imagen del logo
 *   - Recalcula al window 'load' (assets diferidos)
 *   - Funciona con admin bar de WP (top: 32px / 46px)
 *
 *   Archivos: assets/css/header.css, assets/js/header.js
 *
 * 2.6.1 — Fix UI header: logo pegado a la izquierda + sin bullets en menú
 *
 *   LOGO PEGADO A LA IZQUIERDA:
 *   El padding lateral del .welow-header__inner cambió de 24px a 0
 *   para que el logo quede al ras del borde izquierdo y el botón CTA
 *   al ras del borde derecho. En pantallas > 1280px se mantiene un
 *   pequeño padding (16px) por estética.
 *
 *   QUITAR BULLETS DEL MENÚ:
 *   Algunos temas (incluido Divi) aplicaban `list-style: disc` a los
 *   <ul>/<li> con !important, sobrescribiendo el reset del plugin.
 *   Reforzado con !important en todos los selectores del menú,
 *   submenús y overlay móvil. Adicionalmente se neutralizan los
 *   pseudo-elementos ::before y ::marker.
 *
 *   Archivo: assets/css/header.css
 *
 * 2.6.0 — Cabecera responsive del sitio [welow_header]
 *
 *   NUEVO SHORTCODE [welow_header]:
 *   Construye la cabecera del sitio con 3 zonas en desktop (logo +
 *   menú + CTAs) y se convierte automáticamente en hamburger con
 *   overlay fullscreen en móvil. Toda la lógica responsive incluida.
 *
 *   ZONAS:
 *   - Logo (desktop + opcional logo móvil compacto)
 *   - Menú de navegación (cualquier menú nativo de WP, con dropdowns)
 *   - Teléfono click-to-call + Botón principal + Botón secundario
 *
 *   COMPORTAMIENTO MÓVIL:
 *   - Hamburger animado (3 líneas → X)
 *   - Overlay fullscreen con menú expandido (incluye submenús)
 *   - Teléfono y botones CTA fullwidth en el overlay
 *   - Cierre con Escape, click en enlaces, o resize a desktop
 *   - Body lock para evitar scroll mientras está abierto
 *
 *   CONFIGURACIÓN:
 *   Nueva sección "🧭 Cabecera" en Configuraciones con defaults globales:
 *     - Logo (escritorio + móvil opcional)
 *     - Altura del logo
 *     - Menú de navegación
 *     - Teléfono
 *     - Botón principal y secundario (texto + URL)
 *     - Colores (fondo, texto, botón, texto botón)
 *     - Sticky on/off
 *
 *   Cualquier param se puede sobrescribir en el shortcode por página.
 *
 *   ACCESIBILIDAD:
 *   - role="banner"
 *   - aria-expanded en hamburger
 *   - aria-controls al overlay
 *   - aria-label en logo y botones
 *   - Compatible con admin bar de WP (ajusta sticky offset)
 *
 *   PÁGINA DE AYUDA:
 *   Nueva pestaña "🧭 Cabecera" con guía paso a paso para configurar
 *   defaults, crear plantilla en Divi Theme Builder y ejemplos de
 *   personalización.
 *
 *   ARCHIVOS NUEVOS:
 *   - includes/shortcodes/class-welow-shortcode-header.php
 *   - templates/header.php
 *   - assets/css/header.css
 *   - assets/js/header.js
 *
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
define( 'WELOW_CONC_VERSION', '2.9.0' );
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
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-header.php';            // v2.6.0
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-coches-filtro.php';     // v2.8.0

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
