<?php
/**
 * Class AICG_Post_Saver
 *
 * Responsible for saving posts and their images into WordPress,
 * with debug output appended as a <pre> block.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class AICG_Post_Saver {
    /** @var AICG_Post_Saver|null */
    private static $instance = null;

    /**
     * Returns the single instance.
     *
     * @return AICG_Post_Saver
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Private constructor for singleton */
    private function __construct() {}

    /**
     * Saves the post and its images in WordPress, appending debug info.
     *
     * @param array $data {
     *     @type string   $title   Post title.
     *     @type string   $excerpt Post excerpt/meta description.
     *     @type string   $content Post content in HTML.
     *     @type string[] $tags    Array of tag strings.
     *     @type array[]  $images  Array of ['url' => ..., 'alt' => ...].
     * }
     * @return int|WP_Error The ID of the created post or WP_Error on failure.
     */
    public function save_post( array $data ) {
        $debug = [];

        // Sanitize inputs
        $title   = sanitize_text_field( $data['title'] ?? '' );
        $excerpt = sanitize_text_field( $data['excerpt'] ?? '' );
        $content = wp_kses_post( $data['content'] ?? '' );
        $tags    = ! empty( $data['tags'] ) && is_array( $data['tags'] )
                   ? array_map( 'sanitize_text_field', $data['tags'] )
                   : [];
        $images  = ! empty( $data['images'] ) && is_array( $data['images'] )
                   ? $data['images']
                   : [];

        $debug[] = "Input: title=" . ($title !== '' ? 'yes' : 'no')
                 . ", content=" . ($content !== '' ? 'yes' : 'no')
                 . ", tags=" . count( $tags )
                 . ", images=" . count( $images );

        // Basic validation
        if ( empty( $title ) || empty( $content ) ) {
            return new WP_Error( 'invalid_data', 'Title and content are required.' );
        }

        // Create the post
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
        $debug[] = "Post created: ID={$post_id}";

        // Assign tags
        if ( ! empty( $tags ) ) {
            wp_set_post_tags( $post_id, $tags, false );
            $debug[] = "Tags set: " . implode( ', ', $tags );
        }

        // Process images if any
        if ( ! empty( $images ) ) {
            // Load media functions if needed
            if ( ! function_exists( 'media_handle_sideload' ) ) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
                $debug[] = "Loaded media handling functions";
            }

            $url_map = [];
            $first   = true;

            foreach ( $images as $idx => $img ) {
                $remote_url = isset( $img['url'] ) ? esc_url_raw( $img['url'] ) : '';
                $alt_text   = isset( $img['alt'] ) ? sanitize_text_field( $img['alt'] ) : '';

                if ( empty( $remote_url ) ) {
                    $debug[] = "Image #{$idx}: no URL, skipped";
                    continue;
                }

                // Download to temp file
                $tmp_file = download_url( $remote_url );
                if ( is_wp_error( $tmp_file ) ) {
                    $debug[] = "Image #{$idx}: download_url error: " . $tmp_file->get_error_message();
                    continue;
                }
                $debug[] = "Image #{$idx}: downloaded to {$tmp_file}";

                // Extract filename without query string
                $parsed   = wp_parse_url( $remote_url );
                $filename = isset( $parsed['path'] )
                    ? wp_basename( $parsed['path'] )
                    : wp_basename( $remote_url );

                $file_array = [
                    'name'     => $filename,
                    'tmp_name' => $tmp_file,
                ];

                // Sideload and get attachment ID
                $attach_id = media_handle_sideload( $file_array, $post_id, $alt_text );
                if ( is_wp_error( $attach_id ) ) {
                    @unlink( $tmp_file );
                    $debug[] = "Image #{$idx}: sideload error: " . $attach_id->get_error_message();
                    continue;
                }
                $debug[] = "Image #{$idx}: sideloaded as attachment {$attach_id}";

                // Set first image as featured image
                if ( $first ) {
                    set_post_thumbnail( $post_id, $attach_id );
                    $debug[] = "Image #{$idx}: set as featured image";
                    $first = false;
                }

                // Map remote to local URL
                $local_url = wp_get_attachment_url( $attach_id );
                if ( $local_url ) {
                    $url_map[ $remote_url ] = $local_url;
                    $debug[] = "Image #{$idx}: mapped {$remote_url} -> {$local_url}";
                }
            }

            // Replace remote URLs in content
            if ( ! empty( $url_map ) ) {
                $updated_content = str_replace(
                    array_keys( $url_map ),
                    array_values( $url_map ),
                    $content
                );
                wp_update_post( [
                    'ID'           => $post_id,
                    'post_content' => $updated_content,
                ] );
                $debug[] = "Post content updated with local image URLs";
            }
        }

        // Append debug info as <pre> block
        // $debug_block  = "\n\n<pre class=\"aicg-debug\">\n";
        // $debug_block .= implode( "\n", $debug );
        // $debug_block .= "\n</pre>";

        wp_update_post( [
            'ID'           => $post_id,
            'post_content' => $content . $debug_block,
        ] );

        return $post_id;
    }
}