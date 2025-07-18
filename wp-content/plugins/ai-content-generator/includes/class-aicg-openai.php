<?php
/**
 * Class AICG_OpenAI
 *
 * Communicates with the OpenAI API to generate post title, meta, HTML content, and images.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class AICG_OpenAI {
    /** @var AICG_OpenAI|null */
    private static $instance = null;

    /** @var string Your OpenAI API key */
    private $api_key = '';

    /** @var string */
    private $api_url = 'https://api.openai.com/v1';

    /** @var string Text model to use */
    private $text_model = 'gpt-4o';

    /** @var string Image model to use */
    private $image_model = 'dall-e-3';

    /**
     * Singleton - returns the single instance.
     *
     * @return AICG_OpenAI
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor.
     * The API key is defined in the class property.
     */
    private function __construct() {
        // API key predefined in $this->api_key
    }

    /**
     * Generates structured content (JSON) and processes images.
     *
     * @param string $user_prompt User's article prompt.
     * @return array|WP_Error Structured result with title, excerpt, tags, content, and images.
     */
    public function generate_content( $user_prompt, $text_model = '', $image_model = '', $num_images = 1, $api_key_override = '', $min_words = 0 ) {
        // Use runtime override API key if provided
        $api_key = $api_key_override ?: $this->api_key;

        // Ensure minimum word count is an integer
        $min_words = absint( $min_words );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'Chave da OpenAI não configurada.', 'ai-content-generator' ) );
        }

        // Base prompt for structured generation
        $base = "Você é um gerador de artigos de blog em português. Receba o prompt do usuário e responda em JSON com estas chaves:\n"
              . "  - title: string (título do artigo)\n"
              . "  - excerpt: string (meta description, até 160 caracteres)\n"
              . "  - tags: array de strings (meta tags)\n"
              . "  - content: string (HTML completo do artigo, com elementos <p>, <h2>, etc.)\n"
              . "  - images: array de objetos com { prompt: string, alt: string }\n"
              . "Gere exatamente {$num_images} itens em images com descrições de imagens relevantes.\n"
              . "Gere pelo menos 5 minutos de leitura e use linguagem simples.\n"
              . "Foque o texto em SEO, com palavras-chave relevantes.\n"
              . "O artigo deve ter no mínimo {$min_words} palavras.\n"
              . "Prompt do usuário: {$user_prompt}"
              . "\nResponda APENAS com o JSON, sem qualquer texto adicional ou explicações.";

        // Build payload for chat/completions endpoint
        $payload = [
            'model'    => $text_model ?: $this->text_model,
            'messages' => [
                ['role' => 'system',  'content' => 'Você é um assistente útil.'],
                ['role' => 'user',    'content' => $base],
            ],
            'temperature' => 0.7,
        ];

        $response = wp_remote_post( "{$this->api_url}/chat/completions", [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body'    => wp_json_encode( $payload ),
            'timeout' => 60,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( empty( $data['choices'][0]['message']['content'] ) ) {
            return new WP_Error( 'openai_no_content', __( 'Nenhuma resposta da OpenAI. Revise sua chave API e tente novamente.', 'ai-content-generator' ) );
        }

        // Extract and decode the raw JSON from the AI response
        $content_message = trim( $data['choices'][0]['message']['content'] );
        $start = strpos( $content_message, '{' );
        $end   = strrpos( $content_message, '}' );
        if ( false !== $start && false !== $end ) {
            $json_string = substr( $content_message, $start, $end - $start + 1 );
        } else {
            $json_string = $content_message;
        }
        $json = json_decode( $json_string, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'json_error', __( 'Resposta JSON inválida da OpenAI. Revise seu prompt e tente novamente.', 'ai-content-generator' ) );
        }

        // Generate images via OpenAI image API
        $image_urls = [];
        if ( ! empty( $json['images'] ) && is_array( $json['images'] ) ) {
            foreach ( $json['images'] as $img ) {
                if ( ! empty( $img['prompt'] ) ) {
                    $result = $this->generate_image( $img['prompt'], $image_model, $api_key );
                    if ( ! is_wp_error( $result ) ) {
                        $image_urls[] = [
                            'url' => $result,
                            'alt' => sanitize_text_field( $img['alt'] ?? '' ),
                        ];
                    }
                }
            }
        }

        return [
            'title'   => sanitize_text_field( $json['title'] ),
            'excerpt' => sanitize_text_field( $json['excerpt'] ),
            'tags'    => array_map( 'sanitize_text_field', (array) $json['tags'] ),
            'content' => wp_kses_post( $json['content'] ),
            'images'  => $image_urls,
        ];
    }

    /**
     * Generates an image using DALL·E from a given prompt.
     *
     * @param string $prompt Description to generate the image.
     * @param string $model  Optional image model to use.
     * @return string|WP_Error URL of the generated image or WP_Error on failure.
     */
    public function generate_image( $prompt, $model = '', $api_key_override = '' ) {
        // Use runtime override API key if provided
        $api_key = $api_key_override ?: $this->api_key;

        $payload = [
            'model'           => $model ?: $this->image_model,
            'prompt'          => $prompt,
            'n'               => 1,
            'size'            => '1024x1024',
            'response_format' => 'url',
        ];

        $response = wp_remote_post( "{$this->api_url}/images/generations", [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body'    => wp_json_encode( $payload ),
            'timeout' => 60,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! empty( $data['data'][0]['url'] ) ) {
            return esc_url_raw( $data['data'][0]['url'] );
        }

        return new WP_Error( 'image_error', __( 'Falha ao gerar imagem.', 'ai-content-generator' ) );
    }
}