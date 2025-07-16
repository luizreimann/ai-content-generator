

<?php
/**
 * Class AICG_Post_Saver
 *
 * Responsável por salvar posts e suas imagens dentro do WordPress.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class AICG_Post_Saver {
    /** @var AICG_Post_Saver|null */
    private static $instance = null;

    /** Retorna a instância única da classe */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Construtor privado para singleton */
    private function __construct() {}

    /**
     * Salva o post e suas imagens no WordPress.
     *
     * @param array $data {
     *     @type string $title   Título do post.
     *     @type string $excerpt Excerpt/meta description.
     *     @type string $content Conteúdo em HTML.
     *     @type string[] $tags  Array de tags.
     *     @type array[] $images Array de arrays com 'url' e 'alt'.
     * }
     * @return int|WP_Error ID do post criado ou WP_Error em caso de falha.
     */
    public function save_post( array $data ) {
        $title   = isset( $data['title'] )   ? sanitize_text_field( $data['title'] )   : '';
        $excerpt = isset( $data['excerpt'] ) ? sanitize_text_field( $data['excerpt'] ) : '';
        $content = isset( $data['content'] ) ? wp_kses_post( $data['content'] )         : '';
        $tags    = isset( $data['tags'] )    && is_array( $data['tags'] )
                   ? array_map( 'sanitize_text_field', $data['tags'] )
                   : [];
        $images  = isset( $data['images'] ) && is_array( $data['images'] )
                   ? $data['images']
                   : [];

        // Validação básica
        if ( empty( $title ) || empty( $content ) ) {
            return new WP_Error(
                'invalid_data',
                __( 'Título e conteúdo são obrigatórios.', 'ai-content-generator' )
            );
        }

        // Cria o post
        $post_arr = [
            'post_title'   => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_status'  => 'publish',
            'post_author'  => get_current_user_id(),
        ];
        $post_id = wp_insert_post( $post_arr, true );
        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // Tags
        if ( ! empty( $tags ) ) {
            wp_set_post_tags( $post_id, $tags, false );
        }

        // Imagens
        if ( ! empty( $images ) ) {
            // Carrega funções de mídia se necessário
            if ( ! function_exists( 'media_handle_sideload' ) ) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
            }

            $first = true;
            foreach ( $images as $img ) {
                $url = isset( $img['url'] ) ? esc_url_raw( $img['url'] ) : '';
                $alt = isset( $img['alt'] ) ? sanitize_text_field( $img['alt'] ) : '';
                if ( ! $url ) {
                    continue;
                }

                // Faz download temporário da imagem
                $tmp = download_url( $url );
                if ( is_wp_error( $tmp ) ) {
                    continue;
                }

                $file_array = [
                    'name'     => wp_basename( $url ),
                    'tmp_name' => $tmp,
                ];

                // Faz o sideload e obtém o attachment ID
                $attach_id = media_handle_sideload( $file_array, $post_id, $alt );

                // Se houve erro, remove arquivo temporário e continua
                if ( is_wp_error( $attach_id ) ) {
                    @unlink( $tmp );
                    continue;
                }

                // Define a primeira imagem como featured image
                if ( $first ) {
                    set_post_thumbnail( $post_id, $attach_id );
                    $first = false;
                }
            }
        }

        return $post_id;
    }
}