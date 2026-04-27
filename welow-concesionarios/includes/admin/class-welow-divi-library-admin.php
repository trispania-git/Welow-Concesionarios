<?php
/**
 * Mejoras admin para la Biblioteca Divi (et_pb_layout):
 * - Columna nueva con el shortcode [welow_divi id="X"] copiable
 * - Botón "Copiar shortcode" en cada fila del listado
 *
 * @since 1.4.0
 * @package Welow_Concesionarios
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Welow_Divi_Library_Admin {

    const DIVI_CPT = 'et_pb_layout';

    public static function init() {
        // Solo si Divi está activo
        add_action( 'admin_init', array( __CLASS__, 'maybe_register_hooks' ) );
    }

    public static function maybe_register_hooks() {
        if ( ! post_type_exists( self::DIVI_CPT ) ) {
            return;
        }

        // Columna nueva
        add_filter( 'manage_' . self::DIVI_CPT . '_posts_columns', array( __CLASS__, 'columna' ) );
        add_action( 'manage_' . self::DIVI_CPT . '_posts_custom_column', array( __CLASS__, 'contenido_columna' ), 10, 2 );

        // Pequeño JS+CSS para el botón copiar
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'inline_assets' ) );
    }

    public static function columna( $columns ) {
        $new = array();
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( 'title' === $key ) {
                $new['welow_shortcode'] = 'Shortcode Welow';
            }
        }
        return $new;
    }

    public static function contenido_columna( $column, $post_id ) {
        if ( 'welow_shortcode' !== $column ) return;

        $shortcode = '[welow_divi id="' . $post_id . '"]';
        ?>
        <div class="welow-shortcode-cell">
            <code class="welow-shortcode-code" data-clipboard="<?php echo esc_attr( $shortcode ); ?>"
                  title="Click para copiar"><?php echo esc_html( $shortcode ); ?></code>
            <button type="button" class="button button-small welow-copy-btn"
                    data-clipboard="<?php echo esc_attr( $shortcode ); ?>">
                <span class="dashicons dashicons-clipboard"></span>
            </button>
        </div>
        <?php
    }

    public static function inline_assets( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen || self::DIVI_CPT !== $screen->post_type ) return;

        ?>
        <style>
            .welow-shortcode-cell { display: flex; align-items: center; gap: 6px; }
            .welow-shortcode-code {
                background: #f0f4ff; color: #2563eb; padding: 4px 8px;
                border-radius: 4px; font-size: 11px; cursor: pointer;
                transition: background 0.15s;
            }
            .welow-shortcode-code:hover { background: #dbeafe; }
            .welow-copy-btn { padding: 0 6px !important; min-height: 26px; }
            .welow-copy-btn .dashicons { font-size: 14px; width: 14px; height: 14px; line-height: 26px; }
            .welow-copy-btn.welow-copied { background: #d1fae5 !important; color: #059669 !important; }
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.body.addEventListener('click', function(e) {
                var el = e.target.closest('.welow-copy-btn, .welow-shortcode-code');
                if (!el) return;
                e.preventDefault();
                var text = el.dataset.clipboard || el.textContent;
                navigator.clipboard.writeText(text).then(function() {
                    var btn = el.classList.contains('welow-copy-btn') ? el : el.nextElementSibling;
                    if (btn && btn.classList.contains('welow-copy-btn')) {
                        var orig = btn.innerHTML;
                        btn.classList.add('welow-copied');
                        btn.innerHTML = '<span class="dashicons dashicons-yes-alt"></span>';
                        setTimeout(function() {
                            btn.classList.remove('welow-copied');
                            btn.innerHTML = orig;
                        }, 1500);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
