<?php
/**
 * Integracja z WooCommerce poprzez dodatkowe pole Place ID.
 *
 * @package Allemedia\Reviews
 */

namespace Allemedia\Reviews;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Klasa obsługująca metabox produktu WooCommerce.
 */
class Metabox {

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_metabox' ] );
        add_action( 'save_post_product', [ $this, 'save_metabox' ], 10, 2 );
    }

    /**
     * Dodaje metabox do edycji produktu.
     */
    public function add_metabox(): void {
        add_meta_box(
            'allemedia_reviews_place_id',
            __( 'Allemedia: Place ID opinii Google', 'allemedia-reviews' ),
            [ $this, 'render_metabox' ],
            'product',
            'side'
        );
    }

    /**
     * Renderuje zawartość metaboxu.
     */
    public function render_metabox( $post ): void { // phpcs:ignore WordPressVIPMinimum.Hooks.RestrictedHooks.editor_field_in_metabox
        $value = get_post_meta( $post->ID, '_allemedia_place_id', true );
        wp_nonce_field( 'allemedia_reviews_metabox', 'allemedia_reviews_metabox_nonce' );

        echo '<p>' . esc_html__( 'Jeżeli pozostawisz puste pole, zostanie użyty domyślny Place ID z ustawień wtyczki.', 'allemedia-reviews' ) . '</p>';
        echo '<label for="allemedia_reviews_place_id" class="screen-reader-text">' . esc_html__( 'Place ID', 'allemedia-reviews' ) . '</label>';
        echo '<input type="text" name="allemedia_reviews_place_id" id="allemedia_reviews_place_id" value="' . esc_attr( $value ) . '" class="widefat" placeholder="ChIJ..." />';
    }

    /**
     * Zapisuje wartość metaboxu.
     */
    public function save_metabox( int $post_id, $post ): void {
        if ( ! isset( $_POST['allemedia_reviews_metabox_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['allemedia_reviews_metabox_nonce'] ) ), 'allemedia_reviews_metabox' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['allemedia_reviews_place_id'] ) ) {
            $value = sanitize_text_field( wp_unslash( $_POST['allemedia_reviews_place_id'] ) );
            if ( empty( $value ) ) {
                delete_post_meta( $post_id, '_allemedia_place_id' );
            } else {
                update_post_meta( $post_id, '_allemedia_place_id', $value );
            }
        }
    }
}
