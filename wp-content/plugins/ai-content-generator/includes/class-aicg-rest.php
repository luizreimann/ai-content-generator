<?php
/**
 * Class AICG_REST
 *
 * Registers REST endpoints for AI-generated article creation and saving.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Ensure the post saver class is available
require_once AICG_PLUGIN_DIR . 'includes/class-aicg-post-saver.php';

class AICG_REST {
    /** @var AICG_REST|null */
    private static $instance = null;

    /** Returns the single instance */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Private constructor: registers the REST routes */
    private function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /** Registers the REST routes */
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
        register_rest_route( 'aicg/v1', '/save-api-key', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_save_api_key' ],
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ] );
    }

    /**
     * Generates content via OpenAI.
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

        // Capture the models sent from JS (or use an empty string)
        $text_model  = isset($params['text_model'])  ? sanitize_text_field($params['text_model'])  : '';
        $image_model = isset($params['image_model']) ? sanitize_text_field($params['image_model']) : '';

        // Capture the number of requested images (minimum 1)
        $num_images = isset( $params['num_images'] ) ? absint( $params['num_images'] ) : 1;
        if ( $num_images < 1 ) {
            $num_images = 1;
        }

        // Capture the API key sent from JS (or use the default)
        $api_key = isset( $params['api_key'] ) ? sanitize_text_field( $params['api_key'] ) : '';

        // Capture the minimum word count if provided
        $min_words = isset( $params['min_words'] ) ? absint( $params['min_words'] ) : 1500; // Default to 1500 if not provided

        // Invoke generation with overrides
        $openai = AICG_OpenAI::get_instance();
        $result = $openai->generate_content( $prompt, $text_model, $image_model, $num_images, $api_key, $min_words );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( $result );
    }

    /**
     * Saves a post from the provided data.
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
     * Regenerates an image via OpenAI.
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

        // Capture the API key for regeneration
        $api_key = isset( $params['api_key'] ) ? sanitize_text_field( $params['api_key'] ) : '';

        $openai = AICG_OpenAI::get_instance();
        $result = $openai->generate_image( $prompt, '', $api_key );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response( [ 'url' => $result ] );
    }

    /**
     * Saves the OpenAI API key to the options table.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_save_api_key( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $api_key = isset( $params['api_key'] ) ? sanitize_text_field( $params['api_key'] ) : '';

        if ( empty( $api_key ) ) {
            return new WP_Error(
                'no_api_key',
                __( 'API key not provided.', 'ai-content-generator' ),
                [ 'status' => 400 ]
            );
        }

        // Save the option
        update_option( 'aicg_openai_api_key', $api_key );

        return rest_ensure_response( [ 'success' => true ] );
    }
}