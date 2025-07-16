<?php
/**
 * Class AICG_REST
 *
 * Registra endpoints REST para geração e salvamento de artigos via IA.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Ensure the post saver class is available
require_once AICG_PLUGIN_DIR . 'includes/class-aicg-post-saver.php';

class AICG_REST {
    /** @var AICG_REST|null */
    private static $instance = null;

    /** Retorna instância única */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Construtor privado: registra as rotas */
    private function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /** Registra as rotas REST */
    public function register_routes() {
        register_rest_route( 'aicg/v1', '/generate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_generate' ],
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
        ] );
        register_rest_route( 'aicg/v1', '/save', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_save' ],
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
        ] );
        register_rest_route( 'aicg/v1', '/regenerate-image', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_regenerate_image' ],
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
        ] );
    }

    /**
     * Gera conteúdo via OpenAI.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_generate( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $prompt = isset( $params['prompt'] ) ? sanitize_textarea_field( $params['prompt'] ) : '';

        if ( empty( $prompt ) ) {
            return new WP_Error( 'no_prompt', __( 'Prompt não fornecido.', 'ai-content-generator' ), [ 'status' => 400 ] );
        }

        // Captura os modelos enviados pelo JS (ou usa string vazia)
        $text_model  = isset($params['text_model'])  ? sanitize_text_field($params['text_model'])  : '';
        $image_model = isset($params['image_model']) ? sanitize_text_field($params['image_model']) : '';

        // Captura quantas imagens foram solicitadas (mínimo 1)
        $num_images = isset( $params['num_images'] ) ? absint( $params['num_images'] ) : 1;
        if ( $num_images < 1 ) {
            $num_images = 1;
        }

        // Chama a geração com overrides
        $openai = AICG_OpenAI::get_instance();
        $result = $openai->generate_content( $prompt, $text_model, $image_model, $num_images );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( $result );
    }

    /**
     * Gera um post a partir dos dados recebidos.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_save( WP_REST_Request $request ) {
        $data = $request->get_json_params();

        // Delegate saving to AICG_Post_Saver
        $saver = AICG_Post_Saver::get_instance();
        $result = $saver->save_post( $data );

        if ( is_wp_error( $result ) ) {
            return $result;
        }
        return rest_ensure_response( [ 'post_id' => $result ] );
    }

    /**
     * Regenera uma imagem via OpenAI.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_regenerate_image( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $prompt = isset( $params['prompt'] ) ? sanitize_text_field( $params['prompt'] ) : '';

        if ( empty( $prompt ) ) {
            return new WP_Error(
                'no_prompt',
                __( 'Prompt para imagem não fornecido.', 'ai-content-generator' ),
                [ 'status' => 400 ]
            );
        }

        $openai = AICG_OpenAI::get_instance();
        $result = $openai->generate_image( $prompt );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [ 'url' => $result ] );
    }
}