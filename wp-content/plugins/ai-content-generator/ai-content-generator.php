<?php
/*
Plugin Name: AI Content Generator
Description: Gera posts via OpenAI a partir de um prompt personalizado.
Version:     1.0.0
Author:      Luiz Reimann
Author URI:  https://luizreimann.dev
Text Domain: ai-content-generator
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Plugin constants
define( 'AICG_VERSION', '1.0.0' );
define( 'AICG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AICG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include core classes
require_once AICG_PLUGIN_DIR . 'includes/class-aicg-builder.php';
require_once AICG_PLUGIN_DIR . 'includes/class-aicg-admin-page.php';
require_once AICG_PLUGIN_DIR . 'includes/class-aicg-rest.php';
require_once AICG_PLUGIN_DIR . 'includes/class-aicg-openai.php';
require_once AICG_PLUGIN_DIR . 'includes/class-aicg-post-saver.php';


/**
 * Inicializa o plugin após o carregamento de todas as dependências.
 */
function aicg_init_plugin() {
    AICG_Builder::get_instance();
    AICG_Admin_Page::get_instance();
    AICG_REST::get_instance();
}
add_action( 'plugins_loaded', 'aicg_init_plugin' );

// Register activation hook to create the option with a default API key
register_activation_hook( __FILE__, function() {
    add_option( 'aicg_openai_api_key', '' );
});