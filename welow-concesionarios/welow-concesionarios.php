<?php
/**
 * Plugin Name: Welow Concesionarios
 * Plugin URI:  https://welow.es
 * Description: Sistema de gestión para concesionarios multimarca. CPTs, shortcodes y herramientas para coches nuevos y de segunda mano.
 * Version:     2.47.0
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
 * 2.47.0 — Footer: icono 📍 en ubicaciones + toggles dirección/teléfono
 *   - Cada nombre de ubicación lleva ahora un pin 📍 a su izquierda.
 *   - 2 checkboxes nuevos en Configuraciones → Footer → "Información a mostrar":
 *       • Mostrar dirección  (default ON)
 *       • Mostrar teléfono   (default ON)
 *     Si los desactivas, esos datos no aparecen aunque estén rellenados en la
 *     ficha del concesionario. El nombre del concesionario se muestra siempre.
 *
 * 2.46.0 — Footer rediseñado en 3 filas (logos / ubicaciones+menús / legal)
 *
 *   ESTRUCTURA VISUAL:
 *     FILA 1: Logo empresa (grande) + logos de TODAS las marcas publicadas
 *             (en la variante elegida: original, negro o blanco)
 *     ────── línea separadora ──────
 *     FILA 2: Ubicaciones (automático desde welow_concesionario publicados)
 *             | separador vertical | 3 columnas de menús WP
 *     ────── línea separadora ──────
 *     FILA 3: Copyright | redes sociales | enlaces legales
 *
 *   NUEVOS CAMPOS en Configuraciones → Footer:
 *     - "Variante de logos de marca" (original / negro / blanco)
 *     - "Título del bloque de ubicaciones" (default: "Nuestras ubicaciones")
 *
 *   Las ubicaciones se cargan automáticamente desde los CPT welow_concesionario
 *   publicados. Cada una muestra: nombre + dirección/CP/ciudad + teléfono.
 *
 *   Los campos "Contacto rápido" (tel, email, dirección, horario) del v2.45.0
 *   quedan ocultos en la UI (siguen guardándose por compat) — ahora la info
 *   por ubicación viene de los propios concesionarios.
 *
 * 2.45.0 — Configuraciones organizadas en pestañas + Footer
 *
 *   PESTAÑAS EN CONFIGURACIONES:
 *     - General      (disclaimer + moneda)
 *     - Formularios  (selectores, RGPD, página Me Interesa, Cita Taller)
 *     - Estilos      (colores + tipografía)
 *     - Cabecera     (config header)
 *     - Footer       (NUEVO — config completa del footer)
 *     - Iconos       (sistema de iconos)
 *
 *   Las pestañas usan ?tab=X. Todas las secciones se renderizan en el DOM
 *   (escondidas via CSS) para que al guardar no se pierda ningún campo.
 *
 *   NUEVO TAB "FOOTER" con todos los campos:
 *     - Logo + descripción
 *     - Contacto rápido (tel, email, dirección, horario)
 *     - 3 columnas de menús WP (selector menu + título)
 *     - Redes sociales (Facebook, Instagram, LinkedIn, YouTube, TikTok, X)
 *     - Copyright (con placeholder {year}), URLs legales (privacidad, aviso, cookies)
 *     - Estilos: color fondo/texto/títulos/link
 *
 *   NUEVO SHORTCODE [welow_footer]:
 *     Renderiza el footer global del sitio. Pensado para meter en el template
 *     de footer del Theme Builder de Divi.
 *
 *   ARCHIVOS NUEVOS:
 *   - includes/shortcodes/class-welow-shortcode-footer.php
 *   - assets/css/footer.css
 *
 *   ARCHIVOS MODIFICADOS:
 *   - includes/admin/class-welow-settings.php (tabs + sanitize + render_section_footer)
 *   - welow-concesionarios.php (require)
 *   - includes/class-welow-main.php (init + asset)
 *   - includes/admin/class-welow-help.php (entrada en Ayuda)
 *
 * 2.44.0 — API /modelos: concesionarios incluidos (via cascada marca→conc)
 *
 *   Cada modelo del endpoint /wp-json/welow/v1/modelos ahora incluye:
 *     - concesionario        Objeto del concesionario primario (primero que
 *                            vende esa marca, ordenado por menu_order/title)
 *     - concesionarios       Array completo de TODOS los concesionarios que
 *                            venden la marca de este modelo
 *     - concesionarios_count Número de concesionarios
 *
 *   Cada objeto concesionario contiene id, slug, nombre, url, logo,
 *   direccion, telefono, email, horario, lat, lng (igual que en /coches).
 *
 *   Nuevo helper público: Welow_Helpers::get_concesionarios_de_marca($marca_id)
 *   que devuelve la lista de IDs de concesionarios que venden esa marca.
 *
 * 2.43.1 — API /coches/nuevos retirado por completo
 *   La ruta REST ya no se registra → devuelve 404 limpio en vez de respuesta
 *   deprecated. Método endpoint_coches_nuevos() eliminado del controlador.
 *   Usar /wp-json/welow/v1/modelos para consultar el catálogo oficial.
 *
 * 2.43.0 — Soft remove del CPT welow_coche_nuevo
 *
 *   El CPT "Coches NUEVOS" no se usaba (el catálogo se gestiona a nivel
 *   de welow_modelo). Cambios:
 *
 *   - CPT welow_coche_nuevo: sigue REGISTRADO internamente (para no perder
 *     datos ni romper integraciones), pero se OCULTA del admin:
 *       show_ui=false, show_in_menu=false, show_in_rest=false, public=false.
 *   - Dashboard "Concesionarios → Panel": retirada la card "Coches NUEVOS".
 *   - Importer: retirada la card "Coches NUEVOS" (handlers legacy se mantienen
 *     por si alguien tiene CSV antiguo).
 *   - Endpoint REST /coches/nuevos: marcado como deprecated, devuelve
 *     {total:0, deprecated:true, aviso:"..."}.
 *   - /info: retirado del listado de endpoints y de estadisticas.
 *   - Settings: dropdown "Formulario para coches NUEVOS" renombrado a
 *     "Formulario para 'Me Interesa' (modelos)" — el ajuste sigue usándose
 *     por [welow_me_interesa] aunque ya no haya fichas de coche nuevo.
 *
 *   Lo que NO se toca:
 *   - welow_modelo (catálogo del concesionario, sigue siendo el principal)
 *   - welow_coche_ocasion (stock real de segunda mano, sin cambios)
 *   - [welow_modelos], [welow_coche_ficha] para ocasión
 *   - Endpoints /coches/ocasion, /coches/todos (que ahora solo devuelve ocasión),
 *     /modelos, /marcas, /concesionarios
 *
 * 2.42.0 — API /coches/*: objetos completos de marca y modelo
 *
 *   Antes /coches/nuevos y /coches/ocasion devolvían "marca" y "modelo"
 *   solo como strings. El concesionario ya era un objeto completo desde
 *   v2.25.0 (vía cascada coche→modelo→marca→concesionario).
 *
 *   Ahora cada coche también incluye:
 *     "marca_data": {
 *        "tipo": "oficial" | "externa",
 *        "id": ...,
 *        "slug": ...,
 *        "nombre": ...,
 *        "url": ...,         // permalink (vacío si externa)
 *        "logo": ...,         // logo (vacío si externa)
 *        "web": ...,          // solo oficial
 *        "slogan": ...        // solo oficial
 *     }
 *     "modelo_data": {
 *        "tipo": "catalogo" | "texto",
 *        "id": ...,
 *        "slug": ...,
 *        "nombre": ...,
 *        "url": ...,          // permalink del modelo (vacío si texto libre)
 *        "imagen": ...        // imagen destacada del modelo
 *     }
 *
 *   Campos planos "marca", "modelo", "marca_id" se mantienen para compat.
 *
 *   El concesionario sigue resolviéndose automáticamente vía la marca
 *   oficial (cuando es coche nuevo). Para coches de ocasión, solo se
 *   muestra concesionario si está explícitamente asignado en su meta.
 *
 * 2.41.0 — API /modelos: marca enriquecida + timestamps
 *
 *   El endpoint /wp-json/welow/v1/modelos devolvía solo "marca" (string) y
 *   "marca_id" (int). Ahora cada modelo incluye:
 *     - marca       string (compat, igual que antes)
 *     - marca_id    int (compat)
 *     - marca_slug  string nuevo
 *     - marca_url   string nuevo (permalink de la marca)
 *     - marca_logo  string nuevo (URL del logo negro o thumbnail)
 *     - marca_data  objeto completo: {id, slug, nombre, url, logo, web, slogan}
 *     - slug        string del modelo (faltaba)
 *     - fecha_alta + fecha_modificacion (ISO 8601 UTC) para sync incremental
 *
 *   Endpoint también disponible: /wp-json/welow/v1/modelos
 *
 * 2.40.1 — Fix: tooltip del disclaimer del precio se cortaba por el ancho del card
 *   - Quitado overflow:hidden del .welow-modelo-card que recortaba el tooltip.
 *   - Las esquinas superiores redondeadas se mantienen aplicándolas en
 *     .welow-modelo-card__imagen (donde sí hay overflow:hidden).
 *   - Tooltip ahora se ancla a la IZQUIERDA del icono (extendiéndose hacia
 *     la derecha) en vez de a la derecha (extendiéndose hacia la izquierda),
 *     ya que el disclaimer está en la zona izquierda del card.
 *   - Ancho responsive con clamp(240px, 80vw, 320px) para que se adapte mejor.
 *
 * 2.40.0 — Texto RGPD global (fallback de todos los formularios)
 *
 *   NUEVO en Configuraciones → Formularios → "Texto RGPD global":
 *      - Textarea "Texto del consentimiento" (default precargado con texto
 *        recomendado de TALLERES CHINARES SA, configurable).
 *      - "URL Política de Privacidad" (global).
 *      - Botón "Usar texto recomendado" para volver al default.
 *
 *   COMPORTAMIENTO en cada formulario:
 *      - Si el campo "Texto del consentimiento" del formulario está vacío
 *        → se usa el texto global de Configuraciones.
 *      - Si el global también está vacío → se usa la constante por defecto
 *        (texto de Chinares).
 *      - Lo mismo aplica a la URL de política de privacidad.
 *
 *   Así no tienes que repetir el mismo texto RGPD en cada formulario nuevo.
 *
 * 2.39.0 — Cita previa de taller (consistencia UX con resto de forms)
 *
 *   NUEVO en Configuraciones → Formularios → "Cita previa de taller":
 *      - Dropdown "Formulario de Cita Taller" (selector de welow_formulario)
 *      - Dropdown "Página de Cita Taller" (page picker)
 *
 *   NUEVO SHORTCODE [welow_cita_taller]:
 *      - Renderiza el formulario configurado, sin tener que pegar ID a mano
 *      - Título y texto introductorios configurables (defaults razonables)
 *      - Reusa el CSS de [welow_me_interesa] (mismo look visual)
 *
 *   FLUJO:
 *   1. Crear formulario "Cita de taller" en Concesionarios → Formularios
 *   2. Configuraciones → Formularios → "Cita previa de taller":
 *      - Selecciona el formulario en el dropdown
 *      - Selecciona la página donde lo mostrarás (o créala antes)
 *   3. En esa página, pega [welow_cita_taller]
 *   4. Botón del header → enlace a esa página
 *
 *   Mismo patrón consistente que [welow_me_interesa]: settings + shortcode
 *   que usa lo configurado, sin tener que recordar IDs.
 *
 * 2.38.1 — [welow_formulario]: diagnóstico visible a admin si falla la carga
 *   Antes los errores (ID inválido, formulario despublicado, sin campos)
 *   se devolvían como comentario HTML invisible. Ahora salen como aviso
 *   amarillo visible a admins logueados, explicando exactamente qué falla.
 *   Visitantes anónimos siguen sin ver nada (comentario HTML).
 *
 * 2.38.0 — Formularios: tokens dinámicos en opciones de select/radio/checkbox
 *
 *   Ahora puedes usar estos tokens en el campo "opciones":
 *     {marcas-oficiales}  → todas las welow_marca publicadas (alfabético)
 *     {marcas-externas}   → todos los términos welow_marca_externa (alfabético)
 *     {marcas-todas}      → unión de ambas sin duplicados (alfabético)
 *     {concesionarios}    → welow_concesionario publicados (alfabético)
 *
 *   Se sustituyen automáticamente al renderizar el formulario. Si añades
 *   una marca nueva en el sitio, el select del formulario se actualiza solo.
 *
 *   Combinables con texto libre:
 *     "Sin preferencia|{marcas-oficiales}"
 *     "{marcas-oficiales}|Otra"
 *
 * 2.37.0 — Formularios: tipos de campo "fecha", "hora" y "fecha_hora"
 *   - Nuevos tipos en el builder de welow_formulario (botones + en el admin).
 *   - Render frontend con inputs HTML5 nativos:
 *       fecha       → <input type="date">      (selector calendario)
 *       hora        → <input type="time">      (selector hora)
 *       fecha_hora  → <input type="datetime-local">
 *   - fecha y fecha_hora bloquean por defecto fechas pasadas (min=hoy).
 *   - Sanitización vía sanitize_text_field (formato ISO YYYY-MM-DD es seguro).
 *
 *   Pensado especialmente para formularios de cita previa (taller, prueba
 *   de conducción, visita al concesionario...).
 *
 * 2.36.0 — Concesionario: formulario al lado de los datos en "Contacto y horario"
 *
 *   - Nuevo selector "Formulario para ficha de CONCESIONARIO" en
 *     Configuraciones → Formularios.
 *   - Si lo configuras, el bloque "info" de [welow_concesionario_ficha]
 *     pasa a 2 columnas: izquierda los 4 datos (dirección/tel/email/horario),
 *     derecha el formulario seleccionado.
 *   - Si no lo configuras, el bloque sigue siendo una sola columna como antes.
 *   - En móvil (≤820px) las dos columnas se apilan.
 *
 *   El formulario hereda el contexto del concesionario actual (concesionario_id)
 *   en el lead generado.
 *
 * 2.35.1 — Card de marca: quitar título + placeholder {marca} en el CTA
 *   - Eliminado el <h3> con el nombre de la marca (el logo ya identifica).
 *   - Default texto_boton: "Ver modelos" → "Ver modelos {marca}".
 *     El placeholder {marca} se reemplaza por el nombre real de la marca.
 *     Ej. una card de Dongfeng muestra "Ver modelos Dongfeng →".
 *   - Si no quieres incluir la marca, basta con escribir texto_boton="Ver modelos".
 *
 * 2.35.0 — Card de marca [welow_marcas_cards] rediseñada
 *   - Header en 2 columnas: logo (izq) + foto de modelo al azar (der)
 *     La foto se toma de un modelo aleatorio publicado de esa marca
 *     (preferimos los que tengan imagen destacada; fallback a cualquiera).
 *   - Título y slogan movidos al cuerpo (debajo del header).
 *   - CTA cambiada: botón grande "Ver marca" → enlace simple "Ver modelos →"
 *     con flecha animada en hover.
 *   - En móvil (≤600px) el header pasa a una columna para no apretar.
 *
 * 2.34.0 — Me Interesa: versión genérica si no hay modelo en la URL
 *
 *   Antes: se redirigía a /contacto/ (para visitantes) o se mostraba
 *   un aviso amarillo (para admins).
 *   Ahora: la misma página renderiza una versión genérica del formulario,
 *   sin el hero del modelo, con un título y texto introductorio.
 *
 *   Nuevos atributos del shortcode:
 *     titulo_generico  Default: "¿En qué podemos ayudarte?"
 *     texto_generico   Default: "Déjanos tus datos y te contactaremos en breve."
 *
 *   El formulario sigue siendo el configurado en Configuraciones →
 *   "Formulario para coches NUEVOS".
 *
 *   El hook template_redirect anterior se ha retirado: ya no hay redirección.
 *
 * 2.33.1 — Fix acentos en datos de leads (López → Lu00f3pez)
 *   - Las llamadas a wp_json_encode al guardar datos/contexto del lead ahora
 *     usan JSON_UNESCAPED_UNICODE para evitar el bug de \uXXXX sin barra.
 *   - Helper Welow_CPT_Lead::leer_meta_json centraliza la lectura con
 *     reparación recursiva de strings (mismo patrón que en formularios).
 *   - Aplicado en render_metabox_datos, render_metabox_contexto y
 *     contenido_columnas del listado.
 *
 *   Los leads ya guardados con el bug se reparan automáticamente al verlos.
 *
 * 2.33.0 — Me Interesa: campo URL del modelo eliminado + redirect a /contacto/
 *
 *   1) Quitado el campo "URL del botón ¡Me interesa!" del editor de modelo.
 *      Ahora todos los modelos usan SIEMPRE la página global configurada en
 *      Configuraciones → Formularios → "Página del botón Me Interesa".
 *      Más simple y menos margen de error. (El meta antiguo se mantiene en BD,
 *      pero ya no se lee ni se guarda. No se borra por seguridad.)
 *
 *   2) Si un visitante (NO admin) llega a la página "Me Interesa" SIN el
 *      query param ?modelo=slug, ahora se redirige automáticamente a
 *      /contacto/ con 302. Los admins siguen viendo el aviso amarillo para
 *      poder testear con ?modelo= manualmente.
 *
 *   Filtro disponible para cambiar la URL fallback:
 *      add_filter( 'welow_me_interesa_fallback_url', function() {
 *          return home_url( '/otra-pagina/' );
 *      } );
 *
 * 2.32.0 — Página "¡Me interesa!" para cards de modelo
 *
 *   NUEVO SHORTCODE [welow_me_interesa]:
 *      Renderiza una página tipo landing con:
 *        - Hero con foto destacada del modelo + marca + nombre
 *        - Formulario configurado en Configuraciones para "coches nuevos"
 *      Detecta el modelo desde ?modelo=slug en la URL.
 *
 *   FLUJO:
 *   1. Crear página WP "Me interesa" con shortcode [welow_me_interesa] dentro
 *   2. Configuraciones → Formularios → seleccionar esa página en "Página del
 *      botón Me Interesa"
 *   3. Card de modelo: si no tiene URL propia en "URL del botón ¡Me interesa!",
 *      el botón auto-genera URL → /pagina-me-interesa/?modelo=slug-del-modelo
 *
 *   El formulario hereda automáticamente el contexto del modelo (marca_id,
 *   modelo_id, UTM, referrer) en el lead generado.
 *
 *   ARCHIVOS NUEVOS:
 *   - includes/shortcodes/class-welow-shortcode-me-interesa.php
 *   - assets/css/me-interesa.css
 *
 *   ARCHIVOS MODIFICADOS:
 *   - welow-concesionarios.php (require + bootstrap)
 *   - includes/class-welow-main.php (init + asset)
 *   - includes/admin/class-welow-settings.php (page picker)
 *   - includes/admin/class-welow-help.php (shortcode help)
 *   - templates/modelos-grid.php (auto-URL si no hay interesa_url propia)
 *
 * 2.31.0 — Unificación de formularios en fichas de coche
 *
 *   El sistema antiguo (Welow_Shortcode_Coche_Extras::render_formulario)
 *   ahora delega automáticamente al sistema nuevo (welow_formulario CPT +
 *   Welow_Shortcode_Formulario) si hay un formulario configurado.
 *
 *   NUEVO en Configuraciones → "Formularios por defecto en fichas de coche":
 *      - Selector de formulario por defecto para coches NUEVOS
 *      - Selector de formulario por defecto para coches OCASIÓN / KM0
 *
 *   COMPORTAMIENTO:
 *   - Si seleccionas un formulario para un tipo → tanto el bloque
 *     `formulario` de [welow_coche_ficha] como el shortcode legacy
 *     [welow_coche_formulario] muestran ese formulario nuevo.
 *   - Si dejas "Usar formulario clásico (legacy)" → comportamiento como antes.
 *   - Atributo `forzar_legacy="si"` permite override por instancia.
 *
 *   BENEFICIOS:
 *   - Un solo sistema para gestionar todos los formularios (CPT welow_formulario)
 *   - Todos los envíos quedan como leads con contexto auto-detectado del coche
 *     (marca, modelo, concesionario, UTMs, IP, referrer)
 *   - RGPD configurable por formulario, no hardcoded
 *   - Sin tocar las páginas Divi existentes — totalmente transparente
 *
 * 2.30.3 — Fix de escapes Unicode HUÉRFANOS (barra invertida perdida)
 *   El HTML del usuario revela el patrón "Telu00e9fono" SIN backslash.
 *   En algún paso del flujo (sanitización/WP magic quotes/Divi) se está
 *   stripeando la barra `\` antes del `é`, dejando texto literal.
 *   reparar_campos ahora detecta también ese patrón orphan (`uXXXX`)
 *   y lo decodifica al carácter correspondiente, acotado al rango
 *   U+0080–U+024F (latín extendido) para no afectar texto inocente
 *   como "url" o "user".
 *
 * 2.30.2 — Fix completo de caracteres Unicode en formularios (auto-migración)
 *   - El fix de v2.30.1 reparaba el frontend pero no el admin: al abrir+
 *     guardar un formulario en el editor, los datos corruptos se persistían.
 *   - Ahora render_metabox_campos también limpia los \uXXXX literales ANTES
 *     de pasarlos al builder JS, así al volver a guardar la BD queda limpia.
 *   - guardar_meta aplica la misma reparación a los datos POSTed.
 *   - Helper reparar_campos centralizado con hasta 3 pasadas para casos
 *     de doble/triple codificación.
 *
 *   Para limpiar formularios afectados: abre el formulario en admin, guarda.
 *
 * 2.30.1 — Fix: caracteres unicode (é, á, ñ...) en etiquetas de campo
 *   - wp_json_encode usa JSON_UNESCAPED_UNICODE para no convertir
 *     "Teléfono" en "Teléfono" al guardar.
 *   - get_campos repara defensivamente cadenas con \uXXXX literal en
 *     formularios guardados antes del fix (auto-recovery sin perder datos).
 *
 * 2.30.0 — FASE 1: Formularios + Leads (gestión de captación)
 *
 *   ⚙ CPT welow_formulario:
 *      - Builder visual de campos (texto, email, teléfono, textarea,
 *        select, radio, checkbox, oculto). Drag&drop con sortable.
 *      - Configuración (botón, mensaje éxito, redirect, título).
 *      - Notificaciones: lista de emails destinatarios (responsables,
 *        comerciales) + asunto con comodines {sitio}/{formulario}/{nombre}.
 *      - RGPD: texto de consentimiento + URL política de privacidad.
 *      - Cada formulario tiene su shortcode listo para copiar.
 *
 *   📥 CPT welow_lead (no creable manualmente):
 *      - Guarda datos del envío + contexto (coche/modelo/concesionario/marca
 *        autodetectados) + UTMs + referrer + IP + user agent.
 *      - Estados: nuevo / contactado / en_negociacion / ganado / perdido / descartado.
 *      - Listado con columnas: nombre, formulario, email, tel, contexto, estado, fecha.
 *      - Filtro por estado + columna ordenable. Badge contador en menú.
 *
 *   🎯 Shortcode [welow_formulario id="X" / slug="Y"]:
 *      - Render responsive con validación nativa + AJAX submit.
 *      - Honeypot + timing (≥2 seg) anti-spam.
 *      - Consentimiento RGPD obligatorio si está configurado.
 *      - Captura contexto del Theme Builder y de URL (UTMs).
 *      - Envía email con tabla de datos + reply-to al email del cliente.
 *
 *   🗂 Menú Concesionarios → Formularios / Leads con contador de nuevos.
 *
 *   PRÓXIMAS FASES:
 *   - v2.31.0 Lead management UI: filtros avanzados, export CSV, bulk-status
 *   - v2.32.0 Auto-respuesta al cliente, plantillas, asignación por concesionario
 *   - v2.33.0 reCAPTCHA v3 opcional, dashboard de estadísticas
 *   - v2.34.0 Modal "¡Me interesa!", webhooks a CRM externo
 *
 *   ARCHIVOS NUEVOS:
 *   - includes/cpt/class-welow-cpt-formulario.php
 *   - includes/cpt/class-welow-cpt-lead.php
 *   - includes/shortcodes/class-welow-shortcode-formulario.php
 *   - assets/css/formulario.css
 *   - assets/js/formulario.js
 *
 *   ARCHIVOS MODIFICADOS:
 *   - welow-concesionarios.php (requires)
 *   - includes/class-welow-main.php (init + assets)
 *   - includes/admin/class-welow-admin-menu.php (submenús + contador leads)
 *   - includes/admin/class-welow-help.php (entrada shortcode)
 *
 * 2.29.1 — HOTFIX estilos globales: OPTION_KEY incorrecta + !important
 *   - El inyector buscaba "welow_concesionarios_settings" pero la opción
 *     real es "welow_conc_settings". Por eso no se cargaban los valores.
 *   - Añadido !important a los overrides para ganar al shorthand background:
 *     de los CSS originales y al CSS de Divi/tema.
 *
 * 2.29.0 — Configuraciones: estilos generales del frontend (colores + tipografía)
 *
 *   NUEVA SECCIÓN en Concesionarios → Configuraciones:
 *   "Estilos generales del frontend"
 *
 *   Campos:
 *   - Color principal (botones, CTA, hover de enlaces)
 *   - Color principal — hover (default #1d4ed8)
 *   - Color de títulos (h2/h3 de cards)
 *   - Color del texto sobre los botones (default blanco)
 *   - Color de los rótulos destacados (si vacío usa color principal)
 *   - Tipografía + opción "Cargar desde Google Fonts" con weights 400-800
 *
 *   FUNCIONAMIENTO:
 *   Si rellenas cualquier campo, se inyectan reglas CSS en <head> que
 *   sobrescriben los colores/tipos hardcoded del plugin. Si lo dejas
 *   vacío, no se inyecta nada (compatibilidad 100% con lo anterior).
 *
 *   Aplica a: cards de modelo, cards de concesionario, fichas, banners,
 *   buttons "¡Me interesa!", "Ver concesionario", overlays, etc.
 *
 *   Color picker nativo de WP ahora también en la pantalla de
 *   Configuraciones (antes solo en la ficha de modelo).
 *
 * 2.28.1 — Ajustes visuales card de concesionario
 *   - Logos de marca: 60×30 → 180×90 (x3)
 *   - Dirección: 13px → 16px, color más fuerte, weight 500
 *
 * 2.28.0 — Nuevo shortcode [welow_concesionarios] (grid de fichas)
 *
 *   Listado tipo grid de los concesionarios publicados, una card por cada uno:
 *   - Imagen principal (banner desktop → primera foto galería → thumbnail)
 *   - Localidad (h3, grande) + dirección + CP (pequeño, debajo)
 *   - Logos de las marcas que vende (filtrados de welow_marca._welow_marca_logo_negro)
 *   - Botón "Ver concesionario" → permalink del CPT
 *
 *   Atributos: columnas (3/2/1) + max + texto_boton + orden.
 *
 *   Reutiliza el CSS de welow-concesionario-ficha para no duplicar estilos.
 *   Añadido a la página de Ayuda y shortcodes.
 *
 *   ARCHIVOS NUEVOS:
 *   - includes/shortcodes/class-welow-shortcode-concesionarios.php
 *
 *   ARCHIVOS MODIFICADOS:
 *   - welow-concesionarios.php (require_once)
 *   - includes/class-welow-main.php (init shortcode)
 *   - includes/admin/class-welow-help.php (Ayuda)
 *   - assets/css/concesionario-ficha.css (estilos card+grid)
 *
 * 2.27.6 — Concesionario ficha: 3 fixes
 *   1) HORARIO: quitado white-space:pre-line porque doblaba los saltos
 *      junto a nl2br. Ahora se ven con la separación natural.
 *   2) MAPA FULL-WIDTH: sacado del wrapper central (que tiene max-width
 *      1280px) hacia el contenedor exterior. El título y el enlace de
 *      "Abrir en Maps" siguen centrados con max-width, pero el iframe
 *      ocupa el ancho completo disponible (450px de alto).
 *   3) SECCIÓN DIVI 5 RENDERIZA: el shortcode [welow_divi] ahora detecta
 *      si el layout está en formato BLOQUES (Divi 5 Visual Builder) y
 *      ejecuta do_blocks() antes de do_shortcode(). Sin esto, layouts
 *      block-based devolvían cadena vacía porque do_shortcode ignora
 *      los marcadores "<!-- wp:divi/section -->".
 *
 * 2.27.5 — Quitado el título y ubicación del shortcode (redundante)
 *   El bloque <h1>Nombre concesionario</h1> + 📍 ciudad provincia ya se
 *   pinta desde el módulo Title/Heading del Theme Builder de Divi.
 *   El shortcode ya no los duplica.
 *
 * 2.27.4 — HOTFIX layout caótico de v2.27.3
 *
 *   PROBLEMA detectado: apply_filters('the_content') disparaba la cadena
 *   completa de filtros, lo que en algunos entornos inyectaba footer/sidebar
 *   en medio del shortcode. Y el truco "width:100vw + margin:calc(50%-50vw)"
 *   provocaba que la galería se viese cortada por el menú y el orden
 *   visual quedaba roto.
 *
 *   SOLUCIÓN:
 *   - render_divi vuelve a usar el shortcode [welow_divi id="X"] ya existente,
 *     que sí funciona sin disparar filtros recursivos.
 *   - El wrapper .welow-conc-ficha ya no tiene max-width: deja que el banner
 *     y la sección Divi ocupen el ancho natural del contenedor Divi padre.
 *   - El max-width:1280px se aplica solo al título + cuerpo central de bloques
 *     informativos (info, marcas, galeria, mapa), manteniendo legibilidad
 *     sin romper el layout.
 *
 *   Si quieres que las secciones Divi vayan a ancho completo, configura los
 *   módulos Divi como Fullwidth Section dentro de su propio Library item.
 *
 * 2.27.3 — Concesionario ficha: banner + Divi a full-width, render Divi mejorado
 *   - Banner ahora rompe el max-width del wrapper y se muestra a 100vw
 *     (mismo truco CSS para la sección Divi, que suele necesitar el ancho
 *     completo para sus secciones con fondo de color/imagen).
 *   - render_divi cambia de do_shortcode → apply_filters('the_content'),
 *     que es compatible con layouts del Visual Builder de Divi 5 y
 *     ejecuta los filtros propios de Divi necesarios para renderizar
 *     correctamente módulos como et_pb_section.
 *
 * 2.27.2 — [welow_concesionario_ficha]: 3 ajustes
 *   1) "mapa" añadido al default de mostrar, así si tienes lat/lng se ve
 *      automáticamente sin tener que pasarlo en el atributo mostrar.
 *   2) Título de la sección de marcas: "Marcas que representamos" → "Nuestras Marcas".
 *   3) render_divi: ahora renderiza el contenido del layout directamente
 *      (no via subshortcode) y muestra avisos visibles a admin si no hay
 *      layout seleccionado, el ID es inválido o el layout está vacío.
 *
 * 2.27.1 — [welow_concesionario_ficha]: detector mejorado + diagnóstico admin
 *   - resolver_id ahora prueba is_singular + get_queried_object + global $post
 *     (3 rutas) para detectar el concesionario en más contextos del Theme Builder.
 *   - Si la detección falla, se muestra un aviso AMARILLO visible SOLO a usuarios
 *     con manage_options explicando qué pasa y cómo arreglarlo. Los visitantes
 *     siguen viendo nada (comentario HTML invisible).
 *
 * 2.27.0 — Shortcode [welow_concesionario_ficha] para páginas de concesionario
 *
 *   NUEVO SHORTCODE: [welow_concesionario_ficha id="auto" mostrar="..."]
 *
 *   Bloques disponibles (todos opcionales vía atributo mostrar):
 *     - banner   Banner de portada con overlay opcional (texto + CTA)
 *     - info     Dirección, teléfono, email, horario (con icons + tel:/mailto:)
 *     - marcas   Logos de las marcas que representa el concesionario
 *     - galeria  Grid de fotos con lightbox vanilla JS (flechas + ESC + swipe)
 *     - mapa     Embed de Google Maps a partir de lat/lng + "Cómo llegar"
 *     - divi     Render del layout Divi Library seleccionado en la ficha
 *
 *   Default: "banner,info,marcas,galeria,divi" (mapa fuera por defecto).
 *
 *   Atributo id: "auto" (detecta single de welow_concesionario en Theme Builder),
 *                slug, o ID numérico.
 *
 *   AYUDA: el shortcode aparece en la página "Ayuda y shortcodes" con sus
 *   parámetros y ejemplos.
 *
 *   ARCHIVOS NUEVOS:
 *   - includes/shortcodes/class-welow-shortcode-concesionario-ficha.php
 *   - assets/css/concesionario-ficha.css
 *   - assets/js/concesionario-ficha.js (lightbox, ~80 líneas vanilla)
 *
 *   ARCHIVOS MODIFICADOS:
 *   - welow-concesionarios.php (require_once)
 *   - includes/class-welow-main.php (init shortcode + register assets)
 *   - includes/admin/class-welow-help.php (entrada en Ayuda)
 *
 * 2.26.0 — Concesionarios: banner portada + galería (6 fotos) + sección Divi
 *
 *   FICHA DE CONCESIONARIO (admin), 3 metaboxes nuevos:
 *
 *   1) BANNER DE PORTADA con texto superpuesto opcional
 *      - Imagen desktop + imagen móvil
 *      - Overlay opcional: título, subtítulo, botón (texto + URL), posición
 *        (9 anclas, igual que en marcas).
 *      - Meta keys: _welow_conc_banner_desktop, _banner_movil,
 *        _banner_overlay_titulo / _subtitulo / _btn_texto / _btn_url / _posicion
 *
 *   2) GALERÍA DE FOTOS (hasta 6)
 *      - Selector WP Media + drag-reorder (sortable)
 *      - Meta key: _welow_conc_galeria (array de IDs)
 *
 *   3) SECCIÓN DE BIBLIOTECA DIVI
 *      - Selector dropdown con los layouts de et_pb_layout
 *      - Meta key: _welow_conc_divi_layout_id (post ID o 0)
 *
 *   PRÓXIMO PASO: crear el shortcode [welow_concesionario_ficha] que
 *   compondrá banner + galería + datos de contacto + sección Divi para
 *   las páginas frontend de cada concesionario.
 *
 *   Assets admin: añadido welow_concesionario a la lista de CPTs que
 *   cargan jquery-ui-sortable (necesario para reordenar la galería).
 *
 * 2.25.1 — Ayuda: añadido /concesionarios al listado de endpoints REST API
 *
 * 2.25.0 — REST API: concesionario en cascada + endpoint /concesionarios + timestamps
 *
 *   1) CONCESIONARIO EN CADA COCHE (resolución en cascada):
 *      Si el coche tiene asignado un concesionario en su meta, se usa ese.
 *      Si NO (campo vacío), para coches NUEVOS se resuelve automáticamente:
 *        coche → modelo (catálogo) → marca oficial → concesionario que vende
 *        esa marca (busca en welow_concesionario._welow_conc_marcas).
 *      Para coches de OCASIÓN no hay cascada posible (marca externa libre).
 *      El JSON sale con: { id, slug, nombre, url, direccion, telefono, email, ... }
 *
 *   2) NUEVO ENDPOINT /wp-json/welow/v1/concesionarios
 *      Lista completa de concesionarios físicos publicados con datos
 *      (direccion, telefono, email, horario, lat/lng, url, logo) +
 *      array de marcas que vende cada uno + timestamps.
 *
 *   3) NUEVOS CAMPOS EN COCHE: fecha_alta y fecha_modificacion (ISO 8601 UTC).
 *      Permite sync incremental: el chatbot puede filtrar por fecha_modificacion
 *      desde la última pasada para no procesar todo cada vez.
 *
 *   NOTA sobre /info.estadisticas.concesionarios:
 *      El número refleja wp_count_posts('welow_concesionario')->publish. Si el
 *      cliente confirma 3 pero el endpoint dice 2, hay 1 concesionario sin
 *      publicar (borrador, pendiente o papelera).
 *
 *   ARCHIVOS MODIFICADOS:
 *   - includes/helpers/class-welow-helpers.php (cascada + timestamps + id/slug/url en concesionario)
 *   - includes/api/class-welow-rest-api.php (endpoint /concesionarios)
 *
 * 2.24.0 — Card de modelo: mini-slider de galería (hasta 5 imágenes)
 *
 *   - Si el modelo tiene 2+ imágenes (destacada + img_2..img_5), la card
 *     muestra un mini-slider en el área de imagen.
 *   - Scroll-snap nativo (CSS) para swipe móvil natural y rendimiento.
 *   - Flechas prev/next visibles al hover (ocultas en táctil).
 *   - Puntitos sincronizados con el scroll, clic para saltar a imagen.
 *   - Si solo hay 1 imagen, se muestra como antes (sin slider).
 *   - Lazy load de todas excepto la primera.
 *
 *   ARCHIVOS NUEVOS:
 *   - assets/js/modelos-slider.js (~70 líneas, vanilla JS)
 *
 *   ARCHIVOS MODIFICADOS:
 *   - templates/modelos-grid.php
 *   - assets/css/secciones.css
 *   - includes/class-welow-main.php (register script)
 *   - includes/shortcodes/class-welow-shortcode-modelos.php (enqueue script)
 *
 * 2.23.0 — Card de modelo: disclaimer junto al precio + botón "¡Me interesa!" centrado
 *
 *   1) ICONO DISCLAIMER: estaba con margin-left:auto, lo que lo empujaba al
 *      final del flex del precio (apareciendo pegado a "Ver modelo"). Ahora
 *      queda inmediatamente al lado del precio con margin-left de 4px.
 *
 *   2) BOTÓN "¡ME INTERESA!": antes alineado a la izquierda y compacto.
 *      Ahora centrado en la card, con más padding (12×28), letter-spacing
 *      de 0.6px, ancho mínimo de 180px y sombra más marcada. Más legible
 *      y más destacado visualmente.
 *
 * 2.22.0 — Card de modelo: enlaces opcionales + botón "¡Me interesa!"
 *
 *   CARD DE MODELO [welow_modelos]:
 *   1) La IMAGEN ya no se enlaza (antes apuntaba a get_permalink/ enlace).
 *   2) El TÍTULO solo se enlaza si el campo "Enlace" tiene URL propia.
 *      (Antes caía a get_permalink() y abría una ficha vacía del CPT).
 *   3) Botón "Ver modelo" SOLO aparece si el campo "Enlace" tiene URL.
 *      Cuando aparece, abre en pestaña nueva (target="_blank" rel="noopener").
 *   4) Nuevo campo en la ficha del modelo: "URL del botón ¡Me interesa!".
 *      Si se rellena, aparece un botón azul redondeado en la parte
 *      inferior izquierda de la card.
 *
 *   META KEY nueva: _welow_modelo_interesa_url
 *
 *   ARCHIVOS MODIFICADOS:
 *   - includes/cpt/class-welow-cpt-modelo.php (campo admin + save)
 *   - templates/modelos-grid.php (render)
 *   - assets/css/secciones.css (estilos botón ¡Me interesa!)
 *
 * 2.21.0 — Header: separación de items y estilos de hover del menú
 *
 *   NUEVOS ATRIBUTOS de [welow_header]:
 *   - menu_gap: px entre items del menú (default 4). Ej: 28.
 *   - menu_hover: estilo del hover sobre los enlaces del menú:
 *       • "fondo" (default): pill azul claro de fondo
 *       • "color":           solo cambia el color del texto
 *       • "underline":       subrayado estático que aparece al hover
 *       • "subrayado-animado": barra que crece desde el centro (estilo Divi)
 *
 *   El estilo activo se aplica también al item correspondiente a la página
 *   actual (current-menu-item / current-menu-parent / current-menu-ancestor).
 *
 *   USO TÍPICO (look como ejemplo cliente):
 *     [welow_header opacidad_fondo="80" blur="3" overlay="si"
 *                   font_size_menu="15" text_transform_menu="uppercase"
 *                   letter_spacing_menu="1.5px"
 *                   menu_gap="28" menu_hover="subrayado-animado"]
 *
 * 2.20.1 — Hotfix: error crítico en header.php (tags <?php anidados)
 *   - templates/header.php abría <?php dentro de un bloque PHP existente,
 *     rompiendo el parser y devolviendo "Error crítico" en cualquier página
 *     que renderizara el header.
 *
 * 2.20.0 — Header translúcido + modo overlay (slider hasta arriba)
 *
 *   NUEVOS ATRIBUTOS de [welow_header]:
 *   - opacidad_fondo: 0-100 (default 100 = opaco). Convierte el color de fondo
 *     a rgba con el alpha indicado. Ej: opacidad_fondo="80" deja el header
 *     al 80% (ejemplo de referencia del cliente).
 *   - overlay: si | no. Si "si", el header se superpone al contenido y NO
 *     se crea el spacer → el slider/contenido empieza desde y=0, con el
 *     header flotando encima. Implica sticky=si automáticamente.
 *   - blur: px (default 0). Aplica backdrop-filter blur al fondo del header.
 *     Recomendado: blur="3" cuando opacidad_fondo está bajo 90.
 *
 *   USO TÍPICO para conseguir el look del ejemplo:
 *     [welow_header opacidad_fondo="80" blur="3" overlay="si"]
 *
 *   IMPLEMENTACIÓN:
 *   - Welow_Shortcode_Header::color_a_rgba() — helper que convierte HEX a rgba.
 *   - templates/header.php aplica la conversión y la clase .welow-header--overlay.
 *   - assets/js/header.js omite la creación del spacer en modo overlay.
 *   - assets/css/header.css aplica backdrop-filter con la variable --welow-h-blur.
 *
 *   ARCHIVOS MODIFICADOS:
 *   - includes/shortcodes/class-welow-shortcode-header.php
 *   - templates/header.php
 *   - assets/js/header.js
 *   - assets/css/header.css
 *
 * 2.19.0 — Banners de marca con texto superpuesto opcional
 *
 *   FICHA DE MARCA:
 *   - Cada uno de los 4 slots de banner (portada/zona-media × desktop/móvil)
 *     gana 5 campos opcionales: título, subtítulo, texto y URL del botón,
 *     y posición del texto (9 anclas: top-left, top-center, ..., bottom-right).
 *   - Si los dejas vacíos, el banner se muestra como imagen pura (igual que antes).
 *
 *   FRONTEND:
 *   - Banner ahora soporta overlay con fondo translúcido oscuro (rgba(0,0,0,.45))
 *     y texto blanco. Backdrop-filter blur para resaltar sobre cualquier imagen.
 *   - Overlay desktop/móvil independientes: se ven según viewport vía media query.
 *   - Si hay botón propio en el overlay, el enlace global del shortcode se desactiva
 *     (evita anidar enlaces — accesibilidad).
 *
 *   META KEYS nuevas (4 slots × 5 campos = 20 nuevas):
 *     _welow_marca_banner_<slot>_overlay_titulo
 *     _welow_marca_banner_<slot>_overlay_subtitulo
 *     _welow_marca_banner_<slot>_overlay_btn_texto
 *     _welow_marca_banner_<slot>_overlay_btn_url
 *     _welow_marca_banner_<slot>_overlay_posicion
 *
 *   ARCHIVOS MODIFICADOS:
 *   - includes/cpt/class-welow-cpt-marca.php (admin + save)
 *   - includes/shortcodes/class-welow-shortcode-marca-banner.php
 *   - templates/marca-banner.php
 *   - assets/css/secciones.css
 *
 * 2.18.0 — Modelos: filtro por marca + toggle activo en la lista
 *
 *   LISTA ADMIN DE MODELOS:
 *   - Nuevo desplegable "Todas las marcas" arriba de la lista para filtrar
 *     por marca (junto al filtro estándar de fechas).
 *   - La columna "Activo" pasa de icono estático a botón clicable que
 *     activa/desactiva el modelo sin entrar al post (vía AJAX, con nonce).
 *   - Verde con tick = activo, rojo con X = inactivo.
 *
 *   FICHA DE MODELO:
 *   - Checkbox "Modelo activo" reemplazado por un toggle tipo switch
 *     visible y con feedback visual (color + texto cambian al instante).
 *
 *   ARCHIVOS MODIFICADOS:
 *   - includes/cpt/class-welow-cpt-modelo.php
 *
 * 2.17.2 — Modelos: comportamiento predecible con campos vacíos
 *
 *   IMPORTADOR DE MODELOS (CSV + SuperExcel):
 *   - CREAR modelo nuevo:
 *       slug vacío         → se genera desde el nombre
 *       texto_enlace vacío → "Ver modelo" (default)
 *       orden vacío        → 0
 *       activo vacío       → 1 (activo por defecto)
 *       resto vacío        → meta vacía
 *   - ACTUALIZAR modelo existente:
 *       cualquier campo vacío → SE PRESERVA el valor anterior.
 *       Permite exportar todo, editar pocos campos, y reimportar sin
 *       perder el resto.
 *   - Taxonomías (combustible, categoria_modelo, etiquetas) e imágenes
 *     ya se comportaban así desde antes (no se tocaban si vacías).
 *
 *   INSTRUCCIONES del SuperExcel actualizadas con tabla de comportamiento.
 *
 * 2.17.1 — SuperExcel: columnas alineadas con ficha admin del modelo
 *
 *   - Eliminadas "descripcion" y "excerpt" del SuperExcel hoja Modelos
 *     (la ficha del CPT no los usa).
 *   - procesar_fila_modelo del importador CSV ahora solo actualiza
 *     post_content/post_excerpt si esas columnas vienen presentes en la
 *     fila. Antes los ponía a vacío y podía borrar contenido existente.
 *   - Validaciones de datos (dropdowns) reubicadas a las nuevas posiciones
 *     de columna en el XLSX.
 *   - Instrucciones del SuperExcel actualizadas.
 *
 * 2.17.0 — SuperExcel (Fase 1: Modelos)
 *
 *   NUEVA FUNCIONALIDAD: superExcel
 *   - Un único archivo .xlsx que combina la hoja editable de Modelos
 *     con hojas de referencia (Marcas, Combustibles, Carrocerías, Etiquetas).
 *   - Las hojas de referencia se generan al vuelo desde la BD: siempre
 *     reflejan los datos actuales del sitio.
 *   - Columnas con desplegable (data validation) en marca_slug, combustible,
 *     categoria_modelo, etiquetas y activo (0/1).
 *   - Importación leyendo el .xlsx directamente, sin paso intermedio por CSV.
 *
 *   IMPLEMENTACIÓN:
 *   - Escritor y lector XLSX propios en PHP puro (OOXML mínimo), sin
 *     dependencias externas. ~50KB de código añadido al plugin.
 *   - Reutiliza el procesador de fila de modelo existente para no duplicar
 *     lógica (creación/actualización/imágenes).
 *
 *   ARCHIVOS NUEVOS:
 *   - includes/lib/class-welow-xlsx-writer.php
 *   - includes/lib/class-welow-xlsx-reader.php
 *   - includes/admin/class-welow-superexcel.php
 *
 *   ARCHIVOS MODIFICADOS:
 *   - welow-concesionarios.php (requires)
 *   - includes/class-welow-main.php (init)
 *   - includes/admin/class-welow-importer.php (UI superExcel)
 *
 *   PRÓXIMAS FASES:
 *   - v2.18.0 — Añadir Marcas y Concesionarios al superExcel
 *   - v2.19.0 — Añadir Coches Nuevos
 *   - v2.20.0 — Añadir Coches de Ocasión
 *
 * 2.16.0 — Plantilla de modelos autoadaptable + errores más útiles
 *
 *   IMPORTADOR:
 *   1) La plantilla CSV de modelos detecta tus marcas existentes y usa
 *      la primera como marca_slug en el ejemplo, en vez del genérico "toyota"
 *      que fallaba si no tenías esa marca creada.
 *   2) El error "Marca no encontrada" ahora lista las marcas disponibles
 *      para ayudar a corregir el slug, o avisa si no hay ninguna marca creada.
 *   3) El nombre de ejemplo cambia a "Modelo ejemplo (BORRAR)" para que
 *      quede claro que es solo un placeholder.
 *
 * 2.15.0 — Importador: fix delimitador + campos faltantes en Modelos
 *
 *   IMPORTADOR / EXPORTADOR:
 *   1) AUTO-DETECCIÓN DE DELIMITADOR (',' vs ';'):
 *      - Excel en locales con coma decimal (ES) re-guarda con ';' al editar.
 *      - Antes: fgetcsv() usaba ',' por defecto → "Falta el campo nombre".
 *      - Ahora: detecta automáticamente el separador de la primera línea.
 *
 *   2) CAMPOS NUEVOS EN EXPORT/IMPORT DE MODELOS:
 *      - rotulo (v2.10.0) — texto destacado encima del nombre
 *      - rotulo_color (v2.10.0) — color HEX del rótulo
 *      - caracteristicas (v2.13.0) — bullets (una por línea)
 *
 *   3) UI MÁS CLARA:
 *      - Card "Modelos" → "Modelos del catálogo" + aviso explicativo
 *        de la diferencia respecto a "Coches Nuevos".
 *
 *   4) PLANTILLA DE EJEMPLO CORREGIDA:
 *      - excerpt ya no contiene "Desde 24.990€" (era confuso).
 *      - Incluye ejemplo de rótulo + características en multilínea.
 *
 *   ARCHIVOS MODIFICADOS:
 *   - includes/admin/class-welow-importer.php
 *
 * 2.14.0 — Card de modelo: etiquetas DGT + combustible en misma fila
 *
 *   AJUSTES VISUALES en card de modelo [welow_modelos]:
 *   - Etiquetas visuales (DGT/eco) reducidas al 70%:
 *       desktop: 56px → 39px, móvil: 48px → 34px
 *   - Etiquetas DGT y badge de combustible ahora en MISMA fila,
 *     con la etiqueta primero y combustible después.
 *   - Nueva clase CSS: .welow-modelo-card__etiquetas-row
 *
 *   ARCHIVOS MODIFICADOS:
 *   - templates/modelos-grid.php
 *   - assets/css/secciones.css
 *
 * 2.13.0 — Características principales en CPT welow_modelo
 *
 *   FICHA DE MODELO (admin):
 *   - Nuevo campo "Características principales" (textarea, una por línea)
 *     en el metabox "Datos del modelo", justo debajo del rótulo y color.
 *   - Meta key: _welow_modelo_caracteristicas
 *
 *   CARD DE MODELO en páginas de marca [welow_modelos]:
 *   - Nueva lista de características renderizada entre la descripción
 *     y el footer (precio + CTA).
 *   - Estilo: borde lateral gris, una característica por línea.
 *   - Si no hay características, la card se mantiene como en v2.10.0.
 *
 *   ARCHIVOS MODIFICADOS:
 *   - includes/cpt/class-welow-cpt-modelo.php (campo + save)
 *   - templates/modelos-grid.php (render bullets)
 *   - assets/css/secciones.css (estilos lista)
 *
 * 2.12.0 — Ficha individual: bloque "resaltado" con rótulo + características
 *
 *   FICHA DE COCHE [welow_coche_ficha]:
 *
 *   1) NUEVO BLOQUE "resaltado" en el orquestador
 *      - Renderiza rótulo grande + lista de características con checks azules
 *      - Solo se muestra si el coche tiene rótulo o características rellenas
 *      - Posición: justo después de la galería, antes de los datos técnicos
 *
 *   2) DEFAULT 'mostrar' ACTUALIZADO
 *      - Antes: "galeria,destacados,precio,formulario,equipamiento,..."
 *      - Ahora: "galeria,resaltado,destacados,precio,formulario,equipamiento,..."
 *      - Para excluirlo: [welow_coche_ficha mostrar="galeria,destacados,..."]
 *
 *   3) DISEÑO DEL BLOQUE
 *      - Fondo gradiente sutil (#f8fafc → #eef2ff)
 *      - Borde lateral azul de 4px (#2563eb)
 *      - Rótulo h2 24px (20px en móvil)
 *      - Lista en 2 columnas en desktop, 1 en móvil
 *      - Checks redondos azules con tick blanco
 *
 *   ARCHIVOS NUEVOS:
 *      - templates/coche-resaltado.php
 *
 *   ARCHIVOS MODIFICADOS:
 *      - includes/shortcodes/class-welow-shortcode-coche-ficha.php
 *      - templates/coche-ficha.php
 *      - assets/css/coche-ficha.css
 *
 * 2.11.0 — Card de coches NUEVOS: rótulo + características principales
 *
 *   CPT welow_coche_nuevo — Nuevo metabox "I. Destacados (card)":
 *
 *   1) RÓTULO DESTACADO
 *      - Campo de texto (máx. 80 caracteres)
 *      - Frase corta para llamar la atención sobre el modelo
 *      - Ej: "La innovación tiene nuevo nombre"
 *
 *   2) CARACTERÍSTICAS PRINCIPALES
 *      - Textarea (una característica por línea)
 *      - Se renderizan como lista vertical en la card
 *      - Recomendado: 3-5 líneas
 *      - Ej: "Sistema de Sonido Sony de 12 Altavoces"
 *            "Pantalla Central Deslizante de 15,6"
 *            "19 Asistentes de Conducción (ADAS)"
 *
 *   3) FALLBACK INTELIGENTE
 *      - Si no se rellenan los destacados, la card mantiene el layout
 *        clásico (combustible / cambio / CV)
 *      - Si hay rótulo o características, sustituyen al layout clásico
 *
 *   META KEYS NUEVAS:
 *      - _welow_coche_rotulo
 *      - _welow_coche_caracteristicas
 *
 *   ARCHIVOS MODIFICADOS:
 *      - includes/cpt/class-welow-cpt-coche-nuevo.php (metabox + save)
 *      - templates/coches-grid-nuevos.php (render)
 *      - assets/css/coches.css (estilos .welow-coche-card__destacados)
 *
 * 2.10.0 — Rediseño card modelo + nuevo campo "rótulo" destacado
 *
 *   CARDS DE MODELO en páginas de marca [welow_modelos]:
 *
 *   1) TÍTULO MÁS GRANDE Y DESTACADO
 *      - Cambiado de h3 18px → h2 26px (22px tablet, 20px móvil)
 *      - Font-weight 800, letter-spacing -0.01em
 *      - Color #0f172a (más contrastado)
 *
 *   2) BADGES SIMPLIFICADAS
 *      - ELIMINADAS: carrocería (Berlina, SUV, Compacto...) y plazas
 *      - Solo se muestra COMBUSTIBLE (badge verde)
 *
 *   3) ETIQUETAS VISUALES (welow_etiqueta) MOVIDAS
 *      - Antes: flotaban absolute sobre la imagen (top-left)
 *      - Ahora: en el flujo del card, DEBAJO del título del modelo
 *      - Más legibles, no tapan la imagen, mejor accesibilidad
 *
 *   4) BOTÓN "VER MODELO" REPOSICIONADO
 *      - Ya no es un botón ancho azul abajo
 *      - Ahora es un enlace pequeño en la esquina INFERIOR DERECHA
 *      - Estilo: texto azul + flecha → con animación de hover
 *      - Footer del card: precio (izq) + CTA (der), separados por borde
 *
 *   5) NUEVO CAMPO "RÓTULO DESTACADO" (opcional)
 *      Editor del modelo (Concesionarios → Modelos → [Modelo]):
 *      - Campo de texto (max 60 chars) para texto destacado
 *        Ej: "NUEVO", "OFERTA EXCLUSIVA", "100% ELÉCTRICO", "NOVEDAD 2026"
 *      - Color personalizable con color picker nativo de WP
 *      - Aparece como pill/etiqueta encima del título en la card
 *      - Si está vacío, no se muestra (no afecta al diseño)
 *
 *   FUENTES:
 *   La card hereda la fuente del tema activo (Divi por defecto).
 *   Si quieres usar la fuente del header (Figtree, etc.), podemos
 *   añadir un CSS variable global compartida en próxima versión.
 *
 *   Archivos:
 *   - includes/cpt/class-welow-cpt-modelo.php (campos rótulo + admin color picker)
 *   - includes/class-welow-main.php (enqueue wp-color-picker en pantalla modelo)
 *   - templates/modelos-grid.php (rediseño completo)
 *   - assets/css/secciones.css (CSS card modelo)
 *
 * 2.9.1 — Fix: galería del modelo se desbordaba sobre el sidebar admin
 *
 *   PROBLEMA:
 *   En el editor del CPT welow_modelo, el metabox "Galería de imágenes"
 *   usaba un grid fijo de 5 columnas (`repeat(5, 1fr)`). Cuando el
 *   ancho del metabox se reducía por el panel lateral derecho de
 *   metaboxes (Etiquetas visuales, Configuración), la quinta card de
 *   imagen se desbordaba lateralmente y se solapaba con las etiquetas,
 *   bloqueando los clicks.
 *
 *   SOLUCIÓN:
 *   Cambiados todos los grids fijos del admin por `auto-fill` con
 *   `minmax()`, que adapta el número de columnas al ancho real
 *   disponible sin desbordar:
 *
 *   - Galería modelo:    minmax(150px, 1fr)
 *   - Logos marca:       minmax(220px, 1fr)
 *   - Banners marca:     minmax(280px, 1fr)
 *
 *   También añadido `min-width: 0` a las cards y `overflow: hidden` +
 *   `max-width: 100%` en las previews para evitar overflows residuales.
 *
 *   Archivos:
 *   - includes/cpt/class-welow-cpt-modelo.php
 *   - includes/cpt/class-welow-cpt-marca.php
 *
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
define( 'WELOW_CONC_VERSION', '2.47.0' );
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
require_once WELOW_CONC_PATH . 'includes/admin/class-welow-superexcel.php';          // v2.17.0

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
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-concesionario-ficha.php'; // v2.27.0
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-concesionarios.php';      // v2.28.0
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-formulario.php';                       // v2.30.0
require_once WELOW_CONC_PATH . 'includes/cpt/class-welow-cpt-lead.php';                             // v2.30.0
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-formulario.php';          // v2.30.0
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-me-interesa.php';         // v2.32.0
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-cita-taller.php';         // v2.39.0
require_once WELOW_CONC_PATH . 'includes/shortcodes/class-welow-shortcode-footer.php';              // v2.45.0

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
