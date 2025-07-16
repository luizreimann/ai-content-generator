<?php
/**
 * Class AICG_Builder
 *
 * Responsável pelo registro e enfileiramento de scripts e estilos para a página de admin do plugin.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AICG_Builder {
    /** @var AICG_Builder|null Instância única */
    private static $instance = null;

    /** Construtor privado para singleton */
    private function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /** Retorna a instância única */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Enfileira scripts e estilos apenas na nossa admin-page.
     *
     * @param string $hook O hook da página atual
     */
    public function enqueue_assets( $hook ) {
        // Ajuste para o slug declarado em add_menu_page(): toplevel_page_ai-content-generator
        if ( 'toplevel_page_ai-content-generator' !== $hook ) {
            return;
        }

        // Bootstrap CSS via CDN
        wp_enqueue_style(
            'aicg-bootstrap-css',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            [],
            AICG_VERSION
        );

        // CSS personalizado
        wp_enqueue_style(
            'aicg-admin-css',
            AICG_PLUGIN_URL . 'assets/css/aicg-admin.css',
            [],
            AICG_VERSION
        );

        // Bootstrap JS Bundle via CDN
        wp_enqueue_script(
            'aicg-bootstrap-js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            [],
            AICG_VERSION,
            true
        );

        // Nosso JS
        wp_enqueue_script(
            'aicg-admin-js',
            AICG_PLUGIN_URL . 'assets/js/aicg.js',
            ['jquery'],
            AICG_VERSION,
            true
        );

        // Passa URLs e nonce ao JS
        wp_localize_script(
            'aicg-admin-js',
            'aicgData',
            [
                'generateUrl' => rest_url( 'aicg/v1/generate' ),
                'saveUrl'     => rest_url( 'aicg/v1/save' ),
                'nonce'       => wp_create_nonce( 'wp_rest' ),
            ]
        );
    }
}