<?php
/**
 * Página de ayuda y documentación: Concesionarios → Ayuda y shortcodes.
 *
 * Documentación completa de:
 *   - Todos los shortcodes con sus parámetros y ejemplos
 *   - Estructura del plugin (CPTs, taxonomías, relaciones)
 *   - Importación CSV
 *   - Endpoints REST API y shortcodes para chatbots
 *
 * @since 2.4.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Help {

    const PAGE_SLUG = 'welow_help';

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'registrar_pagina' ), 100 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
    }

    public static function registrar_pagina() {
        add_submenu_page(
            'welow_concesionarios',
            'Ayuda y shortcodes',
            'Ayuda y shortcodes',
            'edit_posts',
            self::PAGE_SLUG,
            array( __CLASS__, 'render_pagina' )
        );
    }

    public static function enqueue_scripts( $hook ) {
        if ( strpos( $hook, self::PAGE_SLUG ) === false ) return;
        wp_enqueue_style( 'dashicons' );
    }

    /* ========================================================================
       DEFINICIÓN DE SHORTCODES (la fuente de verdad)
       ======================================================================== */

    public static function get_shortcodes_doc() {
        return array(

            'welow_marcas' => array(
                'titulo' => 'Grid de logos de marcas oficiales',
                'desc'   => 'Muestra las marcas oficiales del concesionario en grid de logos clicables que enlazan a la página individual de cada marca.',
                'params' => array(
                    'columnas'        => array( 'def' => '4', 'desc' => 'Columnas en desktop' ),
                    'columnas_tablet' => array( 'def' => '3', 'desc' => 'Columnas en tablet' ),
                    'columnas_movil'  => array( 'def' => '2', 'desc' => 'Columnas en móvil' ),
                    'orden'           => array( 'def' => 'personalizado', 'desc' => 'personalizado | nombre' ),
                    'max'             => array( 'def' => '-1', 'desc' => 'Número máximo de marcas' ),
                    'variante_logo'   => array( 'def' => 'original', 'desc' => 'original | negro | blanco' ),
                ),
                'ejemplos' => array(
                    '[welow_marcas]',
                    '[welow_marcas columnas="6" variante_logo="negro"]',
                ),
            ),

            'welow_marcas_cards' => array(
                'titulo' => 'Tarjetas de marcas con info',
                'desc'   => 'Tarjetas más completas con logo, nombre, descripción corta y botón "Ver marca".',
                'params' => array(
                    'columnas'            => array( 'def' => '3', 'desc' => 'Columnas desktop' ),
                    'columnas_tablet'     => array( 'def' => '2', 'desc' => 'Columnas tablet' ),
                    'columnas_movil'      => array( 'def' => '1', 'desc' => 'Columnas móvil' ),
                    'mostrar_descripcion' => array( 'def' => 'si', 'desc' => 'si | no' ),
                    'texto_boton'         => array( 'def' => 'Ver marca', 'desc' => 'Texto del CTA' ),
                    'variante_logo'       => array( 'def' => 'original', 'desc' => 'original | negro | blanco' ),
                ),
                'ejemplos' => array(
                    '[welow_marcas_cards]',
                    '[welow_marcas_cards columnas="2" texto_boton="Descubrir"]',
                ),
            ),

            'welow_marca_banner' => array(
                'titulo' => 'Banner de marca (portada o zona media)',
                'desc'   => 'Muestra el banner desktop/móvil de una marca. Auto-detecta la marca actual si no se especifica.',
                'params' => array(
                    'marca'  => array( 'def' => 'auto', 'desc' => 'slug | ID | "auto" (detecta del contexto)' ),
                    'tipo'   => array( 'def' => 'portada', 'desc' => 'portada | media' ),
                    'enlace' => array( 'def' => '', 'desc' => 'URL al hacer clic (opcional)' ),
                    'altura' => array( 'def' => '', 'desc' => 'Altura CSS (ej: 500px)' ),
                ),
                'ejemplos' => array(
                    '[welow_marca_banner tipo="portada"]',
                    '[welow_marca_banner marca="toyota" tipo="media"]',
                ),
            ),

            'welow_modelos' => array(
                'titulo' => 'Grid de modelos de una marca',
                'desc'   => 'Muestra los modelos del catálogo de una marca con etiquetas, precio, disclaimer y combustible.',
                'params' => array(
                    'marca'           => array( 'def' => 'auto', 'desc' => 'slug | ID | "auto"' ),
                    'columnas'        => array( 'def' => '3', 'desc' => '' ),
                    'columnas_tablet' => array( 'def' => '2', 'desc' => '' ),
                    'columnas_movil'  => array( 'def' => '1', 'desc' => '' ),
                    'max'             => array( 'def' => '-1', 'desc' => 'Máximo de modelos' ),
                    'texto_boton'     => array( 'def' => 'Ver modelo', 'desc' => '' ),
                ),
                'ejemplos' => array(
                    '[welow_modelos columnas="4"]',
                    '[welow_modelos marca="hyundai" columnas="3"]',
                ),
            ),

            'welow_slider' => array(
                'titulo' => 'Slider de imágenes fullwidth',
                'desc'   => 'Slider de imágenes con responsive desktop/móvil. Soporta autoplay, flechas y dots. Acepta `grupo="auto"` para detectar el slug de la marca actual y buscar grupo "{slug}-home".',
                'params' => array(
                    'grupo'    => array( 'def' => '', 'desc' => 'Identificador del grupo de slides (ej: toyota-home). "auto" para detección.' ),
                    'sufijo'   => array( 'def' => 'home', 'desc' => 'Sufijo cuando grupo="auto" (ej: home, ofertas)' ),
                    'autoplay' => array( 'def' => 'si', 'desc' => 'si | no' ),
                    'velocidad'=> array( 'def' => '5000', 'desc' => 'ms entre slides' ),
                    'flechas'  => array( 'def' => 'si', 'desc' => 'si | no' ),
                    'puntos'   => array( 'def' => 'si', 'desc' => 'si | no' ),
                ),
                'ejemplos' => array(
                    '[welow_slider grupo="toyota-home"]',
                    '[welow_slider grupo="auto"]   <!-- en página de marca, busca {slug}-home -->',
                    '[welow_slider grupo="{marca}-ofertas"]',
                ),
            ),

            'welow_slider_cta' => array(
                'titulo' => 'Hero/banner con imagen de fondo + texto + botón',
                'desc'   => 'Sección hero con imagen fondo (responsive desktop/móvil), título, texto y CTA.',
                'params' => array(
                    'imagen'       => array( 'def' => '', 'desc' => 'ID imagen desktop' ),
                    'imagen_movil' => array( 'def' => '', 'desc' => 'ID imagen móvil (fallback al desktop)' ),
                    'titulo'       => array( 'def' => '', 'desc' => 'Título principal' ),
                    'texto'        => array( 'def' => '', 'desc' => 'Texto descriptivo' ),
                    'boton_texto'  => array( 'def' => '', 'desc' => 'Texto del botón' ),
                    'boton_enlace' => array( 'def' => '', 'desc' => 'URL del botón' ),
                    'overlay'      => array( 'def' => 'rgba(0,0,0,0.4)', 'desc' => 'Color overlay CSS' ),
                    'alineacion'   => array( 'def' => 'centro', 'desc' => 'centro | izquierda | derecha' ),
                    'altura'       => array( 'def' => '500px', 'desc' => 'Altura desktop' ),
                    'altura_movil' => array( 'def' => '350px', 'desc' => 'Altura móvil' ),
                ),
                'ejemplos' => array(
                    '[welow_slider_cta imagen="123" titulo="Ofertas" boton_texto="Ver" boton_enlace="/ofertas"]',
                ),
            ),

            'welow_contenido' => array(
                'titulo' => 'Sección de contenido flexible',
                'desc'   => 'Sección con título, texto, imagen y botón. 4 layouts. Soporta texto entre tags de apertura/cierre.',
                'params' => array(
                    'titulo'       => array( 'def' => '', 'desc' => 'Título' ),
                    'imagen'       => array( 'def' => '', 'desc' => 'ID imagen' ),
                    'imagen_movil' => array( 'def' => '', 'desc' => 'ID imagen móvil' ),
                    'layout'       => array( 'def' => 'imagen-derecha', 'desc' => 'imagen-derecha | imagen-izquierda | imagen-arriba | solo-texto' ),
                    'boton_texto'  => array( 'def' => '', 'desc' => '' ),
                    'boton_enlace' => array( 'def' => '', 'desc' => '' ),
                    'fondo'        => array( 'def' => 'transparente', 'desc' => 'Color fondo' ),
                ),
                'ejemplos' => array(
                    '[welow_contenido titulo="Sobre Toyota" imagen="456" layout="imagen-derecha"]Texto aquí[/welow_contenido]',
                ),
            ),

            'welow_divi' => array(
                'titulo' => 'Insertar layout de la Biblioteca Divi',
                'desc'   => 'Inserta cualquier sección/fila/módulo guardado en la Biblioteca de Divi.',
                'params' => array(
                    'id'       => array( 'def' => '', 'desc' => 'ID del layout' ),
                    'slug'     => array( 'def' => '', 'desc' => 'Slug del layout' ),
                    'nombre'   => array( 'def' => '', 'desc' => 'Título del layout' ),
                    'envolver' => array( 'def' => 'no', 'desc' => 'si | no — wrapper div con clase' ),
                    'clase'    => array( 'def' => '', 'desc' => 'Clase CSS adicional' ),
                ),
                'ejemplos' => array(
                    '[welow_divi id="123"]',
                    '[welow_divi slug="hero-marca" envolver="si"]',
                ),
            ),

            'welow_coches_nuevos' => array(
                'titulo' => 'Grid de coches NUEVOS',
                'desc'   => 'Listado de coches nuevos del catálogo oficial. Auto-detecta marca si está en página de marca.',
                'params' => array(
                    'marca'         => array( 'def' => 'auto', 'desc' => 'slug | ID | "auto"' ),
                    'modelo'        => array( 'def' => '', 'desc' => 'slug del modelo' ),
                    'combustible'   => array( 'def' => '', 'desc' => 'slug taxonomía' ),
                    'carroceria'    => array( 'def' => '', 'desc' => 'slug taxonomía' ),
                    'concesionario' => array( 'def' => '', 'desc' => 'slug del concesionario' ),
                    'precio_min'    => array( 'def' => '', 'desc' => 'Precio mínimo' ),
                    'precio_max'    => array( 'def' => '', 'desc' => 'Precio máximo' ),
                    'estado'        => array( 'def' => 'disponible', 'desc' => 'disponible | reservado | vendido | todos' ),
                    'orden'         => array( 'def' => 'recientes', 'desc' => 'precio_asc | precio_desc | recientes' ),
                    'columnas'      => array( 'def' => '3', 'desc' => '' ),
                    'max'           => array( 'def' => '12', 'desc' => '' ),
                ),
                'ejemplos' => array(
                    '[welow_coches_nuevos columnas="4"]',
                    '[welow_coches_nuevos marca="toyota" max="6"]',
                ),
            ),

            'welow_coches_ocasion' => array(
                'titulo' => 'Grid de coches OCASIÓN / KM0',
                'desc'   => 'Listado de coches de ocasión y KM0 con filtros completos.',
                'params' => array(
                    'marca_externa' => array( 'def' => '', 'desc' => 'slug taxonomía welow_marca_externa' ),
                    'tipo'          => array( 'def' => 'todos', 'desc' => 'ocasion | km0 | todos' ),
                    'combustible'   => array( 'def' => '', 'desc' => '' ),
                    'carroceria'    => array( 'def' => '', 'desc' => '' ),
                    'precio_min'    => array( 'def' => '', 'desc' => '' ),
                    'precio_max'    => array( 'def' => '', 'desc' => '' ),
                    'km_max'        => array( 'def' => '', 'desc' => 'Km máximos' ),
                    'anio_min'      => array( 'def' => '', 'desc' => 'Año mínimo' ),
                    'estado'        => array( 'def' => 'disponible', 'desc' => '' ),
                    'orden'         => array( 'def' => 'recientes', 'desc' => 'precio_asc | precio_desc | km_asc | anio_desc | recientes' ),
                    'columnas'      => array( 'def' => '3', 'desc' => '' ),
                    'max'           => array( 'def' => '12', 'desc' => '' ),
                ),
                'ejemplos' => array(
                    '[welow_coches_ocasion]',
                    '[welow_coches_ocasion marca_externa="bmw" precio_max="20000" km_max="80000"]',
                    '[welow_coches_ocasion tipo="km0" max="6"]',
                ),
            ),

            'welow_coche_ficha' => array(
                'titulo' => 'Ficha individual completa del coche',
                'desc'   => 'Renderiza la ficha completa del coche actual o uno específico. Detecta automáticamente si es nuevo o de ocasión.',
                'params' => array(
                    'id'      => array( 'def' => 'auto', 'desc' => 'ID del coche o "auto" (single actual)' ),
                    'mostrar' => array( 'def' => 'galeria,destacados,precio,equipamiento,garantias,concesionario', 'desc' => 'Bloques separados por coma' ),
                ),
                'ejemplos' => array(
                    '[welow_coche_ficha]',
                    '[welow_coche_ficha id="123"]',
                    '[welow_coche_ficha mostrar="galeria,precio,concesionario"]',
                ),
            ),

            'welow_buscador_coches' => array(
                'titulo' => 'Formulario buscador de coches',
                'desc'   => 'Formulario de búsqueda con filtros adaptables al tipo. Envía via GET a la URL del listado.',
                'params' => array(
                    'tipo'   => array( 'def' => 'todos', 'desc' => 'nuevos | ocasion | todos' ),
                    'accion' => array( 'def' => '', 'desc' => 'URL del listado destino' ),
                    'campos' => array( 'def' => '', 'desc' => 'Filtros: marca,marca_externa,combustible,carroceria,precio,km,anio,tipo' ),
                    'titulo' => array( 'def' => 'Buscar tu coche', 'desc' => '' ),
                ),
                'ejemplos' => array(
                    '[welow_buscador_coches tipo="ocasion"]',
                    '[welow_buscador_coches tipo="nuevos" accion="/listado-nuevos/"]',
                ),
            ),

            'welow_header' => array(
                'titulo' => '🧭 Cabecera del sitio (responsive)',
                'desc'   => 'Header completo con 3 zonas: logo + menú + CTAs. En móvil se convierte automáticamente en hamburger con overlay. Toma defaults de Configuraciones → Cabecera; los params del shortcode los sobrescriben.',
                'params' => array(
                    'logo'              => array( 'def' => '(default global)', 'desc' => 'ID o URL del logo principal' ),
                    'logo_movil'        => array( 'def' => '(default global)', 'desc' => 'ID/URL del logo móvil (opcional, fallback al desktop)' ),
                    'logo_altura'       => array( 'def' => '50', 'desc' => 'Altura del logo en px' ),
                    'logo_url'          => array( 'def' => 'home', 'desc' => 'URL al hacer clic en el logo' ),
                    'menu'              => array( 'def' => '(default global)', 'desc' => 'ID, slug o nombre del menú de WP' ),
                    'telefono'          => array( 'def' => '(default global)', 'desc' => 'Número de teléfono (click-to-call)' ),
                    'boton_texto'       => array( 'def' => '(default global)', 'desc' => 'Texto del botón principal' ),
                    'boton_enlace'      => array( 'def' => '(default global)', 'desc' => 'URL del botón principal' ),
                    'boton2_texto'      => array( 'def' => '(default global)', 'desc' => 'Texto del botón secundario (opcional)' ),
                    'boton2_enlace'     => array( 'def' => '(default global)', 'desc' => 'URL del botón secundario' ),
                    'color_fondo'       => array( 'def' => '#ffffff', 'desc' => 'Color de fondo del header' ),
                    'color_texto'       => array( 'def' => '#1f2937', 'desc' => 'Color del texto/menú' ),
                    'color_boton'       => array( 'def' => '#2563eb', 'desc' => 'Color del botón primario' ),
                    'color_boton_texto' => array( 'def' => '#ffffff', 'desc' => 'Color del texto del botón' ),
                    'sticky'            => array( 'def' => 'no', 'desc' => 'si | no — header pegado al hacer scroll' ),
                    'ancho_max'         => array( 'def' => '1280px', 'desc' => 'Anchura máxima del contenedor interior' ),
                ),
                'ejemplos' => array(
                    '[welow_header]   <!-- usa todos los defaults de Configuraciones -->',
                    '[welow_header sticky="si"]',
                    '[welow_header menu="cabecera-coches" boton_texto="Cita taller" boton_enlace="/contacto/cita/"]',
                ),
            ),

            'welow_coche_breadcrumb' => array(
                'titulo' => 'Breadcrumb de la ficha del coche',
                'desc'   => 'Genera un breadcrumb dinámico: Inicio › Coches › [Segunda mano] › Marca › Modelo › Versión. Auto-detecta el coche del contexto.',
                'params' => array(
                    'separador' => array( 'def' => '›', 'desc' => 'Carácter separador entre niveles' ),
                    'inicio'    => array( 'def' => 'Inicio', 'desc' => 'Texto del primer enlace' ),
                ),
                'ejemplos' => array(
                    '[welow_coche_breadcrumb]',
                    '[welow_coche_breadcrumb separador="/" inicio="Home"]',
                ),
            ),

            'welow_coches_similares' => array(
                'titulo' => 'Grid de coches similares al actual',
                'desc'   => 'Muestra coches relacionados al coche actual (misma marca y mismo CPT — nuevos o ocasión). Si no hay suficientes con la misma marca, completa con otros del mismo tipo.',
                'params' => array(
                    'max'      => array( 'def' => '4', 'desc' => 'Número máximo de coches a mostrar' ),
                    'columnas' => array( 'def' => '4', 'desc' => 'Columnas en desktop' ),
                    'titulo'   => array( 'def' => 'Otros coches que pueden interesarte', 'desc' => 'Título de la sección' ),
                ),
                'ejemplos' => array(
                    '[welow_coches_similares]',
                    '[welow_coches_similares max="6" columnas="3" titulo="También te puede interesar"]',
                ),
            ),

            'welow_coche_compartir' => array(
                'titulo' => 'Botones de compartir en redes',
                'desc'   => 'Botones para compartir la ficha del coche en WhatsApp, Facebook, X (Twitter), Email o copiar URL.',
                'params' => array(
                    'redes'  => array( 'def' => 'whatsapp,facebook,twitter,email,copiar', 'desc' => 'Lista de redes separadas por coma' ),
                    'titulo' => array( 'def' => 'Compartir', 'desc' => 'Título antes de los botones' ),
                ),
                'ejemplos' => array(
                    '[welow_coche_compartir]',
                    '[welow_coche_compartir redes="whatsapp,email" titulo=""]',
                ),
            ),

            'welow_coche_formulario' => array(
                'titulo' => 'Formulario de contacto pre-rellenado',
                'desc'   => 'Formulario con nombre/teléfono/email/mensaje + checkbox RGPD. Pre-rellena automáticamente la referencia del coche. Envía email al concesionario asociado (o al admin si no hay).',
                'params' => array(
                    'titulo'      => array( 'def' => '¿Te interesa este coche?', 'desc' => 'Título del bloque' ),
                    'mostrar_ref' => array( 'def' => 'si', 'desc' => 'si | no — mostrar la referencia del coche' ),
                ),
                'ejemplos' => array(
                    '[welow_coche_formulario]',
                    '[welow_coche_formulario titulo="Solicita más información" mostrar_ref="no"]',
                ),
            ),

            'welow_listado_completo' => array(
                'titulo' => '🤖 Listado completo para chatbots',
                'desc'   => 'Vuelca TODOS los datos en HTML estructurado para consumo de chatbots y crawlers. La página que lo contenga se marca automáticamente como noindex,nofollow.',
                'params' => array(
                    'tipo'     => array( 'def' => 'todos', 'desc' => 'nuevos | ocasion | todos | modelos | marcas' ),
                    'max'      => array( 'def' => '-1', 'desc' => 'Máximo de elementos (-1 = todos)' ),
                    'estado'   => array( 'def' => 'disponible', 'desc' => 'disponible | reservado | vendido | todos' ),
                    'sin_html' => array( 'def' => 'no', 'desc' => 'si | no — formato texto plano' ),
                ),
                'ejemplos' => array(
                    '[welow_listado_completo tipo="ocasion"]',
                    '[welow_listado_completo tipo="nuevos"]',
                    '[welow_listado_completo tipo="modelos"]',
                    '[welow_listado_completo tipo="marcas"]',
                ),
            ),

        );
    }

    /* ========================================================================
       UI
       ======================================================================== */

    public static function render_pagina() {
        $shortcodes = self::get_shortcodes_doc();
        $tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'shortcodes';
        ?>
        <div class="wrap welow-help">
            <h1>📖 Ayuda y shortcodes <span style="font-size:14px;color:#666;font-weight:400;">v<?php echo esc_html( WELOW_CONC_VERSION ); ?></span></h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo esc_attr( self::PAGE_SLUG ); ?>&tab=shortcodes" class="nav-tab <?php echo $tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">📋 Shortcodes</a>
                <a href="?page=<?php echo esc_attr( self::PAGE_SLUG ); ?>&tab=header" class="nav-tab <?php echo $tab === 'header' ? 'nav-tab-active' : ''; ?>">🧭 Cabecera</a>
                <a href="?page=<?php echo esc_attr( self::PAGE_SLUG ); ?>&tab=ficha" class="nav-tab <?php echo $tab === 'ficha' ? 'nav-tab-active' : ''; ?>">🚗 Ficha del coche</a>
                <a href="?page=<?php echo esc_attr( self::PAGE_SLUG ); ?>&tab=estructura" class="nav-tab <?php echo $tab === 'estructura' ? 'nav-tab-active' : ''; ?>">🏗️ Estructura</a>
                <a href="?page=<?php echo esc_attr( self::PAGE_SLUG ); ?>&tab=csv" class="nav-tab <?php echo $tab === 'csv' ? 'nav-tab-active' : ''; ?>">📥 Importación CSV</a>
                <a href="?page=<?php echo esc_attr( self::PAGE_SLUG ); ?>&tab=chatbot" class="nav-tab <?php echo $tab === 'chatbot' ? 'nav-tab-active' : ''; ?>">🤖 Chatbots / API</a>
            </h2>

            <div class="welow-help-content">
                <?php
                if ( $tab === 'shortcodes' ) self::render_tab_shortcodes( $shortcodes );
                elseif ( $tab === 'header' ) self::render_tab_header();
                elseif ( $tab === 'ficha' ) self::render_tab_ficha();
                elseif ( $tab === 'estructura' ) self::render_tab_estructura();
                elseif ( $tab === 'csv' ) self::render_tab_csv();
                elseif ( $tab === 'chatbot' ) self::render_tab_chatbot();
                ?>
            </div>
        </div>

        <style>
            .welow-help { max-width: 1200px; }
            .welow-help-content { background: #fff; padding: 24px; border: 1px solid #c3c4c7; margin-top: -1px; }
            .welow-help h2.nav-tab-wrapper { margin-bottom: 0; }
            .welow-shortcode-card {
                background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
                padding: 16px 20px; margin-bottom: 18px;
            }
            .welow-shortcode-card h3 {
                margin: 0 0 6px; font-size: 16px; display: flex; align-items: center; gap: 10px;
            }
            .welow-shortcode-card h3 code {
                background: #2563eb; color: #fff; padding: 2px 10px; border-radius: 4px;
                font-size: 13px; font-weight: 700;
            }
            .welow-shortcode-card .shortcode-desc { color: #475569; margin: 0 0 14px; font-size: 13px; }
            .welow-shortcode-card table {
                width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 13px;
                background: #fff; border-radius: 4px; overflow: hidden;
            }
            .welow-shortcode-card table th, .welow-shortcode-card table td {
                padding: 6px 10px; text-align: left; border-bottom: 1px solid #f1f5f9;
            }
            .welow-shortcode-card table th { background: #f1f5f9; font-weight: 600; }
            .welow-shortcode-card table code { background: #fef3c7; padding: 1px 6px; border-radius: 3px; font-size: 12px; }
            .welow-shortcode-card .ejemplos { margin-top: 12px; }
            .welow-shortcode-card .ejemplos pre {
                background: #1e293b; color: #e2e8f0; padding: 8px 12px; border-radius: 4px;
                font-size: 12px; margin: 4px 0; overflow-x: auto; cursor: pointer;
                position: relative;
            }
            .welow-shortcode-card .ejemplos pre:hover { background: #334155; }
            .welow-shortcode-card .ejemplos pre.copied::after {
                content: '✓ Copiado'; position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
                background: #10b981; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 10px;
            }
            .welow-info-box {
                background: #eff6ff; border-left: 4px solid #2563eb; padding: 12px 16px;
                border-radius: 4px; margin: 16px 0;
            }
            .welow-tabla-cpts { width: 100%; border-collapse: collapse; }
            .welow-tabla-cpts th, .welow-tabla-cpts td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
            .welow-tabla-cpts th { background: #f1f5f9; }
            .welow-tabla-cpts code { background: #fef3c7; padding: 1px 6px; border-radius: 3px; font-size: 12px; }
            .welow-endpoint {
                background: #f8fafc; border-left: 4px solid #10b981; padding: 12px 16px; margin: 10px 0;
                border-radius: 4px; font-family: ui-monospace, monospace; font-size: 13px;
            }
            .welow-endpoint a { word-break: break-all; }
        </style>

        <script>
        document.addEventListener('click', function(e) {
            var pre = e.target.closest('.welow-shortcode-card .ejemplos pre');
            if (!pre) return;
            navigator.clipboard.writeText(pre.textContent).then(function() {
                pre.classList.add('copied');
                setTimeout(function() { pre.classList.remove('copied'); }, 1500);
            });
        });
        </script>
        <?php
    }

    private static function render_tab_shortcodes( $shortcodes ) {
        ?>
        <p>Documentación de los <strong><?php echo count( $shortcodes ); ?> shortcodes</strong> disponibles. Click en los ejemplos para copiarlos.</p>

        <div class="welow-info-box">
            <strong>Auto-detección:</strong> los shortcodes <code>welow_marca_banner</code>, <code>welow_modelos</code>, <code>welow_slider</code>, <code>welow_coches_nuevos</code> y <code>welow_coche_ficha</code> detectan automáticamente la marca/coche del contexto cuando los usas en plantillas del Theme Builder.
        </div>

        <?php foreach ( $shortcodes as $tag => $info ) : ?>
            <div class="welow-shortcode-card">
                <h3><code>[<?php echo esc_html( $tag ); ?>]</code> <?php echo esc_html( $info['titulo'] ); ?></h3>
                <p class="shortcode-desc"><?php echo wp_kses_post( $info['desc'] ); ?></p>

                <?php if ( ! empty( $info['params'] ) ) : ?>
                    <table>
                        <thead><tr><th>Parámetro</th><th>Valor por defecto</th><th>Descripción</th></tr></thead>
                        <tbody>
                            <?php foreach ( $info['params'] as $name => $p ) : ?>
                                <tr>
                                    <td><code><?php echo esc_html( $name ); ?></code></td>
                                    <td><code><?php echo esc_html( $p['def'] ); ?></code></td>
                                    <td><?php echo esc_html( $p['desc'] ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <?php if ( ! empty( $info['ejemplos'] ) ) : ?>
                    <div class="ejemplos">
                        <strong>Ejemplos (click para copiar):</strong>
                        <?php foreach ( $info['ejemplos'] as $ej ) : ?>
                            <pre><?php echo esc_html( $ej ); ?></pre>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php
    }

    private static function render_tab_header() {
        ?>
        <h2>🧭 Cabecera del sitio (Header)</h2>
        <p>Construye la cabecera del sitio con un único shortcode <strong><code>[welow_header]</code></strong> que se ocupa de todo: logo, menú, teléfono, botones CTA y el comportamiento responsive (hamburger en móvil).</p>

        <div class="welow-info-box">
            <strong>Configura los defaults una sola vez</strong> en <a href="<?php echo esc_url( admin_url( 'admin.php?page=welow_settings' ) ); ?>">Concesionarios → Configuraciones → 🧭 Cabecera</a>. Después solo necesitas <code>[welow_header]</code> en cualquier sitio (se renderiza con esos defaults).
        </div>

        <h3>Paso 1: Configurar defaults globales</h3>
        <ol>
            <li>Ve a <a href="<?php echo esc_url( admin_url( 'admin.php?page=welow_settings' ) ); ?>">Configuraciones</a></li>
            <li>Sube tu <strong>logo</strong> principal y opcionalmente uno móvil (más compacto)</li>
            <li>Selecciona el <strong>menú de navegación</strong> (créalos en Apariencia → Menús)</li>
            <li>Rellena <strong>teléfono</strong> y <strong>botones CTA</strong> (ej: "Cita taller" / "Cita concesionario")</li>
            <li>Personaliza <strong>colores</strong> (opcional)</li>
            <li>Marca <strong>sticky</strong> si quieres header pegado al scroll</li>
            <li>Guardar</li>
        </ol>

        <h3>Paso 2: Crear plantilla en Divi Theme Builder</h3>
        <ol>
            <li><strong>Divi → Theme Builder → Add New Template</strong></li>
            <li>Marca <strong>"Build Custom Header"</strong></li>
            <li>Asignar a <strong>"All Pages"</strong> (o las que quieras)</li>
            <li>Dentro del header builder, añade <strong>UN módulo Texto</strong> a ancho completo</li>
            <li>Pega: <code>[welow_header]</code></li>
            <li>Guardar</li>
        </ol>

        <div class="welow-info-box">
            <strong>💡 Truco:</strong> elimina el padding/margin de la sección y fila que envuelve el módulo en Divi para que el header quede bien al ras del top. En la fila, pon <strong>"Make This Row Fullwidth"</strong>.
        </div>

        <h3>Estructura del header</h3>

        <p><strong>Desktop (>980px):</strong> 3 zonas en flexbox</p>
        <pre style="background:#f8fafc;padding:14px;border-radius:6px;font-size:12px;line-height:1.6;border-left:3px solid #2563eb;">
+-----------------------------------------------------------------+
| [LOGO]      Menú · de · navegación · centrado    📞 Tel  [BTN] |
+-----------------------------------------------------------------+</pre>

        <p><strong>Móvil (≤980px):</strong> logo + hamburger</p>
        <pre style="background:#f8fafc;padding:14px;border-radius:6px;font-size:12px;line-height:1.6;border-left:3px solid #f59e0b;">
+-----------------------------------------------+
| [LOGO]                                   [☰]  |
+-----------------------------------------------+

Tras pulsar [☰] (overlay fullscreen):
+-----------------------------------------------+
|  Inicio                                       |
|  Coches                                       |
|    └─ Nuevos                                  |
|    └─ Ocasión                                 |
|  Marcas                                       |
|  Posventa                                     |
|  Contacto                                     |
|  ───────────────────────────────────────      |
|  📞 919 496 619                               |
|  [Cita taller (botón fullwidth)]              |
|  [Cita concesionario (secundario)]            |
+-----------------------------------------------+</pre>

        <h3>Variantes y personalización</h3>

        <p><strong>Override de defaults:</strong> puedes pasar params al shortcode para sobrescribir los defaults globales en una página concreta:</p>
        <pre style="background:#1e293b;color:#e2e8f0;padding:12px;border-radius:4px;font-size:12px;">[welow_header menu="cabecera-coches-nuevos" boton_texto="Configurar mi coche" boton_enlace="/configurador/"]</pre>

        <p><strong>Colores específicos para una página de marca:</strong></p>
        <pre style="background:#1e293b;color:#e2e8f0;padding:12px;border-radius:4px;font-size:12px;">[welow_header color_fondo="#000000" color_texto="#ffffff" color_boton="#dc2626"]</pre>

        <p><strong>Sticky activado por shortcode:</strong></p>
        <pre style="background:#1e293b;color:#e2e8f0;padding:12px;border-radius:4px;font-size:12px;">[welow_header sticky="si"]</pre>

        <h3>Preguntas frecuentes</h3>

        <p><strong>¿Y los submenús (dropdowns)?</strong><br>
        Funcionan automáticamente. Cualquier menú de WP con elementos hijos genera dropdowns en desktop y submenús expandidos en el overlay móvil.</p>

        <p><strong>¿Es accesible?</strong><br>
        Sí: roles ARIA (<code>role="banner"</code>, <code>aria-expanded</code>, <code>aria-controls</code>, <code>aria-label</code>), cierre con tecla <kbd>Esc</kbd>, foco gestionado, body lock al abrir overlay.</p>

        <p><strong>¿Choca con la admin bar de WP cuando estoy logueado?</strong><br>
        No. El CSS detecta la admin bar y ajusta el offset del sticky automáticamente.</p>
        <?php
    }

    private static function render_tab_ficha() {
        ?>
        <h2>🚗 Construir la ficha del coche</h2>
        <p>La ficha individual del coche se monta con varios shortcodes complementarios. La forma recomendada es usar <strong>Divi Theme Builder</strong> con una sola plantilla que sirve a TODOS los coches automáticamente.</p>

        <div class="welow-info-box">
            <strong>URLs personalizadas (v2.5.0):</strong>
            <ul style="margin: 8px 0 0 20px; list-style: disc;">
                <li>Coches nuevos: <code>/coches/{marca}/{modelo}/{slug}/</code></li>
                <li>Coches ocasión: <code>/coches/segunda-mano/{marca}/{modelo}/{slug}/</code></li>
            </ul>
            Si no ves las URLs nuevas, ve a <strong>Ajustes → Enlaces permanentes</strong> y guarda (refresca rewrite rules).
        </div>

        <h3>Paso 1: Crear la plantilla en Divi Theme Builder</h3>
        <ol>
            <li>Ve a <strong>Divi → Theme Builder</strong></li>
            <li>Click en <strong>+ Add Custom Header / Body / Footer</strong> en la zona "Add New Template"</li>
            <li>En el modal, marca <strong>"Build Custom Body"</strong></li>
            <li>Marca también <strong>"All Coche Ocasion Posts"</strong> (y/o "All Coche Nuevo Posts")</li>
            <li>Click en "Create Template" → "Build Custom Body"</li>
        </ol>

        <h3>Paso 2: Estructura recomendada (estilo Grupo Gamboa)</h3>
        <p>Dentro del Theme Builder, añade módulos <strong>Texto</strong> con los shortcodes en este orden:</p>

        <pre style="background:#1e293b;color:#e2e8f0;padding:16px;border-radius:6px;overflow-x:auto;font-size:13px;line-height:1.7;">
<span style="color:#94a3b8;">// 1. Breadcrumb arriba del todo</span>
[welow_coche_breadcrumb]

<span style="color:#94a3b8;">// 2. Ficha completa (galería + destacados + precio + FORMULARIO + concesionario + equipamiento + garantías)
//    El formulario ya viene integrado en la columna derecha desde v2.5.1</span>
[welow_coche_ficha id="auto"]

<span style="color:#94a3b8;">// 3. Compartir en redes sociales</span>
[welow_coche_compartir]

<span style="color:#94a3b8;">// 4. Coches similares al final</span>
[welow_coches_similares max="4" columnas="4"]
</pre>

<div class="welow-info-box">
    <strong>💡 Nota:</strong> desde v2.5.1, el formulario de contacto va <strong>integrado en la columna derecha</strong> (debajo del precio) por defecto en el shortcode <code>[welow_coche_ficha]</code>. Si quisieras quitarlo del aside y ponerlo aparte ancho, usa:
    <pre style="background:#1e293b;color:#e2e8f0;padding:8px 12px;border-radius:4px;font-size:12px;margin:8px 0 0;">[welow_coche_ficha mostrar="galeria,destacados,precio,equipamiento,garantias,concesionario"]
[welow_coche_formulario]</pre>
</div>

        <h3>Paso 3: Personalizar (opcional)</h3>
        <p>Puedes envolver cada shortcode en su propia <strong>sección</strong>/<strong>fila</strong> de Divi para aplicar fondos, márgenes, animaciones, etc. También puedes intercalarlos con módulos de Divi (ej: un banner publicitario entre el formulario y los similares).</p>

        <h3>Variantes de la ficha</h3>

        <p><strong>Mostrar solo algunos bloques de la ficha</strong>:</p>
        <pre style="background:#1e293b;color:#e2e8f0;padding:12px;border-radius:4px;font-size:12px;">[welow_coche_ficha mostrar="galeria,destacados,precio"]</pre>
        <p>Bloques disponibles: <code>galeria</code>, <code>destacados</code>, <code>precio</code>, <code>equipamiento</code>, <code>garantias</code>, <code>concesionario</code></p>

        <p><strong>Compartir solo algunas redes</strong>:</p>
        <pre style="background:#1e293b;color:#e2e8f0;padding:12px;border-radius:4px;font-size:12px;">[welow_coche_compartir redes="whatsapp,email,copiar"]</pre>

        <p><strong>Coches similares con título personalizado</strong>:</p>
        <pre style="background:#1e293b;color:#e2e8f0;padding:12px;border-radius:4px;font-size:12px;">[welow_coches_similares max="6" columnas="3" titulo="También te puede interesar"]</pre>

        <h3>Formulario de contacto: ¿a dónde llegan los emails?</h3>
        <p>El formulario envía emails con esta lógica:</p>
        <ol>
            <li>Si el coche tiene un <strong>concesionario asignado</strong> con email → ese email</li>
            <li>Si no → el email del admin de WordPress (configurable en Ajustes → Generales)</li>
        </ol>
        <p>El email incluye: datos del cliente (nombre, teléfono, email), referencia del coche, URL de la ficha y mensaje.</p>

        <div class="welow-info-box">
            <strong>💡 Hook para integraciones:</strong> existe la acción <code>welow_coche_contacto_enviado</code> que recibe un array con todos los datos de la solicitud. Útil para enviarlo a un CRM externo, Google Sheets, etc.
        </div>
        <?php
    }

    private static function render_tab_estructura() {
        ?>
        <h2>Tipos de contenido (CPTs)</h2>
        <table class="welow-tabla-cpts">
            <thead><tr><th>CPT</th><th>Slug</th><th>Función</th></tr></thead>
            <tbody>
                <tr><td>Marcas oficiales</td><td><code>welow_marca</code></td><td>Catálogo de marcas que vende el concesionario (Toyota, Hyundai, JAECOO...)</td></tr>
                <tr><td>Modelos</td><td><code>welow_modelo</code></td><td>Modelos genéricos del catálogo, vinculados a una marca oficial</td></tr>
                <tr><td>Coches nuevos</td><td><code>welow_coche_nuevo</code></td><td>Unidades concretas del catálogo en venta (con relación a un modelo)</td></tr>
                <tr><td>Coches de ocasión</td><td><code>welow_coche_ocasion</code></td><td>Coches de segunda mano y KM0 (cualquier marca)</td></tr>
                <tr><td>Concesionarios</td><td><code>welow_concesionario</code></td><td>Ubicaciones físicas con dirección, contacto y horario</td></tr>
                <tr><td>Etiquetas</td><td><code>welow_etiqueta</code></td><td>Etiquetas visuales para superponer en cards de modelos</td></tr>
                <tr><td>Slides</td><td><code>welow_slide</code></td><td>Slides reutilizables agrupables para sliders</td></tr>
            </tbody>
        </table>

        <h2 style="margin-top:30px;">Taxonomías</h2>
        <table class="welow-tabla-cpts">
            <thead><tr><th>Taxonomía</th><th>Slug</th><th>Aplica a</th><th>Función</th></tr></thead>
            <tbody>
                <tr><td>Combustibles</td><td><code>welow_combustible</code></td><td>Modelos, coches</td><td>Tipo de motorización (gasolina, gasoil, híbrido, eléctrico...)</td></tr>
                <tr><td>Carrocerías</td><td><code>welow_categoria_modelo</code></td><td>Modelos, coches</td><td>Tipo de carrocería (berlina, SUV, monovolumen, coupé...)</td></tr>
                <tr><td>Marcas externas</td><td><code>welow_marca_externa</code></td><td>Coches de ocasión</td><td>99 marcas pre-cargadas para ocasión. Sincronizadas con marcas oficiales.</td></tr>
            </tbody>
        </table>

        <h2 style="margin-top:30px;">Relación coches ↔ marcas/modelos</h2>
        <div class="welow-info-box">
            <p><strong>Coches nuevos (welow_coche_nuevo)</strong>: requieren seleccionar un <code>welow_modelo</code> del catálogo. La marca se hereda del modelo.</p>
            <p><strong>Coches de ocasión (welow_coche_ocasion)</strong>: la marca se elige de la taxonomía <code>welow_marca_externa</code> (99 marcas pre-cargadas + las oficiales sincronizadas). El modelo es texto libre.</p>
            <p>Esto permite vender un Peugeot nuevo (vía catálogo) Y al mismo tiempo recibir un Peugeot de ocasión sin duplicar. Ambos comparten la misma marca externa "Peugeot" gracias a la sincronización automática.</p>
        </div>

        <h2 style="margin-top:30px;">Sistema de iconos</h2>
        <p>Los datos clave del coche (km, año, combustible, cambio, plazas, etc.) pueden tener un icono personalizado configurable desde <a href="<?php echo esc_url( admin_url( 'admin.php?page=welow_settings' ) ); ?>">Configuraciones</a>. También se pueden asignar iconos por valor de select (manual/automático, etiquetas DGT) y por término de taxonomía (cada combustible/carrocería con su icono).</p>
        <?php
    }

    private static function render_tab_csv() {
        ?>
        <h2>Importación / Exportación CSV</h2>
        <p>Desde <a href="<?php echo esc_url( admin_url( 'admin.php?page=welow_importer' ) ); ?>">Concesionarios → Importar / Exportar</a> puedes importar y exportar 4 tipos de datos en CSV:</p>

        <table class="welow-tabla-cpts">
            <thead><tr><th>Tipo</th><th>Columnas clave</th><th>Notas</th></tr></thead>
            <tbody>
                <tr>
                    <td><strong>Marcas oficiales</strong></td>
                    <td><code>nombre, slug, desc_corta, slogan, web, logo_url, banner_*_url</code></td>
                    <td>Las URLs de logos/banners se descargan automáticamente a la mediateca si activas la opción.</td>
                </tr>
                <tr>
                    <td><strong>Modelos del catálogo</strong></td>
                    <td><code>nombre, slug, marca_slug, descripcion, precio_desde, combustible, categoria_modelo, plazas, etiquetas, imagen_url</code></td>
                    <td><code>marca_slug</code> debe coincidir con el slug de una marca oficial existente.</td>
                </tr>
                <tr>
                    <td><strong>Coches NUEVOS</strong></td>
                    <td><code>titulo, slug, referencia, modelo_slug, version, precio_contado, ...</code></td>
                    <td>Requiere <code>modelo_slug</code> de un modelo del catálogo. Sin km/año (son nuevos).</td>
                </tr>
                <tr>
                    <td><strong>Coches OCASIÓN/KM0</strong></td>
                    <td><code>titulo, slug, referencia, marca_externa, modelo_texto, version, tipo, mes_matricula, anio_matricula, km, precio_contado, ...</code></td>
                    <td>Marca como slug de taxonomía <code>welow_marca_externa</code>. Modelo en texto libre.</td>
                </tr>
                <tr>
                    <td><strong>Concesionarios</strong></td>
                    <td><code>nombre, slug, direccion, cp, ciudad, telefono, email, marcas, lat, lng, logo_url</code></td>
                    <td><code>marcas</code> es lista de slugs separada por <code>|</code> de marcas oficiales.</td>
                </tr>
            </tbody>
        </table>

        <div class="welow-info-box" style="margin-top:20px;">
            <strong>💡 Tips:</strong>
            <ul style="margin: 8px 0 0 20px; list-style: disc;">
                <li>Codificación: <strong>UTF-8</strong> con BOM (compatible con Excel)</li>
                <li>Separador: <strong>coma (,)</strong></li>
                <li>Múltiples valores en un campo: <strong>barra vertical (|)</strong> — ej: <code>turismos|suv|hibridos</code></li>
                <li>Para evitar duplicados: marca <strong>"Actualizar existentes"</strong> al importar (busca por slug o referencia)</li>
                <li>Las imágenes desde URL se descargan a la mediateca automáticamente</li>
                <li>Descarga la plantilla CSV de cada tipo desde la página de importación</li>
            </ul>
        </div>
        <?php
    }

    private static function render_tab_chatbot() {
        ?>
        <h2>🤖 Datos para chatbots y crawlers externos</h2>
        <p>El plugin ofrece <strong>dos formas</strong> de exponer todos los datos del concesionario para que un chatbot, asistente IA o crawler externo pueda consultarlos:</p>

        <h3>1️⃣ Páginas WP "ocultas" con shortcode</h3>
        <p>Crea páginas WordPress y pega el shortcode dentro. La página queda accesible vía URL pero <strong>NO se indexa</strong> (auto-noindex,nofollow,noarchive,nosnippet) y se excluye del sitemap automáticamente. Compatible con Yoast SEO y Rank Math.</p>

        <p><strong>Ejemplo de configuración recomendada</strong> (Opción B: páginas separadas):</p>

        <table class="welow-tabla-cpts">
            <thead><tr><th>URL sugerida</th><th>Shortcode</th><th>Contenido</th></tr></thead>
            <tbody>
                <tr><td><code>/datos-bot/coches-nuevos/</code></td><td><code>[welow_listado_completo tipo="nuevos"]</code></td><td>Solo coches nuevos</td></tr>
                <tr><td><code>/datos-bot/coches-ocasion/</code></td><td><code>[welow_listado_completo tipo="ocasion"]</code></td><td>Ocasión y KM0</td></tr>
                <tr><td><code>/datos-bot/marcas/</code></td><td><code>[welow_listado_completo tipo="marcas"]</code></td><td>Marcas oficiales</td></tr>
                <tr><td><code>/datos-bot/modelos/</code></td><td><code>[welow_listado_completo tipo="modelos"]</code></td><td>Catálogo de modelos</td></tr>
            </tbody>
        </table>

        <div class="welow-info-box">
            <strong>Importante:</strong> NO enlaces estas páginas desde el menú principal ni desde otras páginas. Solo el chatbot necesita conocer las URLs.
        </div>

        <h3 style="margin-top:30px;">2️⃣ Endpoints REST API (JSON)</h3>
        <p>Si tu chatbot prefiere consumir JSON estructurado en vez de scrapear HTML, usa estos endpoints públicos:</p>

        <?php
        $base = rest_url( 'welow/v1' );
        $endpoints = array(
            array( '/info',                   'Resumen del sitio + estadísticas + lista de endpoints' ),
            array( '/coches/nuevos',          'Listado completo de coches nuevos' ),
            array( '/coches/ocasion',         'Listado completo de coches de ocasión + KM0' ),
            array( '/coches/todos',           'Todos los coches en un solo endpoint' ),
            array( '/coches/{id}',            'Datos de un coche concreto por ID' ),
            array( '/modelos',                'Catálogo de modelos del concesionario' ),
            array( '/marcas',                 'Marcas oficiales con sus modelos' ),
        );
        foreach ( $endpoints as $ep ) :
            $url = rtrim( $base, '/' ) . $ep[0];
            $is_param = strpos( $ep[0], '{' ) !== false;
        ?>
            <div class="welow-endpoint">
                <strong>GET</strong> <?php if ( $is_param ) : ?>
                    <?php echo esc_html( $url ); ?>
                <?php else : ?>
                    <a href="<?php echo esc_url( $url ); ?>" target="_blank"><?php echo esc_html( $url ); ?></a>
                <?php endif; ?>
                <br><small style="color:#64748b;"><?php echo esc_html( $ep[1] ); ?></small>
            </div>
        <?php endforeach; ?>

        <p><strong>Parámetros opcionales</strong> en endpoints de coches:</p>
        <ul style="margin-left: 20px; list-style: disc;">
            <li><code>?max=50</code> — limitar número de resultados (default: 100, -1 = todos)</li>
            <li><code>?estado=disponible</code> — filtrar por estado (disponible, reservado, vendido, todos)</li>
            <li><code>?tipo=km0</code> — solo en /coches/ocasion (ocasion, km0, todos)</li>
        </ul>

        <p><strong>Ejemplo combinado:</strong></p>
        <div class="welow-endpoint">
            <a href="<?php echo esc_url( $base . '/coches/ocasion?max=50&estado=disponible&tipo=ocasion' ); ?>" target="_blank">
                <?php echo esc_html( $base . '/coches/ocasion?max=50&estado=disponible&tipo=ocasion' ); ?>
            </a>
        </div>

        <div class="welow-info-box" style="margin-top:30px;">
            <strong>🔓 Sin autenticación:</strong> los endpoints son públicos por defecto. Si necesitas restringirlos en el futuro, podemos añadir un <code>?api_key=</code> configurable.
        </div>
        <?php
    }
}
