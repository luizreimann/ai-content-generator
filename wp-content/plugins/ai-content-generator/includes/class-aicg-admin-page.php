<?php
/**
 * Class AICG_Admin_Page
 *
 * Gerencia o menu e a renderização da página de admin.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AICG_Admin_Page {
    /** @var AICG_Admin_Page|null Instância única */
    private static $instance = null;

    /** Construtor privado */
    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
    }

    /** Retorna a instância única */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Registra nosso menu no admin */
    public function add_admin_page() {
        add_menu_page(
            __( 'Gerar Post com IA', 'ai-content-generator' ),
            __( 'Gerar Post com IA', 'ai-content-generator' ),
            'manage_options',
            'ai-content-generator',
            [ $this, 'render_page' ],
            'dashicons-admin-generic',
            6
        );
    }

    /** Inclui o template HTML */
    public function render_page() {
        include AICG_PLUGIN_DIR . 'templates/admin-page.php';
    }
}