<?php
/**
 * Plugin Name: Allemedia Google Reviews
 * Plugin URI: https://allemedia.pl/
 * Description: Wyświetla opinie Google pobierane przez dedykowany endpoint proxy i prezentuje je w formie atrakcyjnej sekcji na stronie.
 * Version: 1.0.0
 * Author: Allemedia
 * Author URI: https://allemedia.pl/
 * Text Domain: allemedia-reviews
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Bezpośredni dostęp zabroniony.
}

// Definicje stałych ułatwiających użycie ścieżek.
define( 'ALLEMEDIA_REVIEWS_VERSION', '1.0.0' );
define( 'ALLEMEDIA_REVIEWS_PATH', plugin_dir_path( __FILE__ ) );
define( 'ALLEMEDIA_REVIEWS_URL', plugin_dir_url( __FILE__ ) );

// Zgodnie z wymaganiem klienta przechowujemy klucz API Google jako stałą.
define( 'ALLEMEDIA_GOOGLE_API_KEY', 'AIzaSyDP0vhFiZV_yDB8urPJtQ4UdKQpAzmuOcU' );

// Prosty autoloader wtyczki.
spl_autoload_register( static function ( $class ) {
    if ( ! str_starts_with( $class, 'Allemedia\\Reviews\\' ) ) {
        return;
    }

    $relative = strtolower( str_replace( 'Allemedia\\Reviews\\', '', $class ) );
    $relative = str_replace( '\\', '-', $relative );
    $file     = ALLEMEDIA_REVIEWS_PATH . 'inc/class-' . $relative . '.php';

    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

// Wczytanie funkcji pomocniczych.
require_once ALLEMEDIA_REVIEWS_PATH . 'inc/helpers.php';

// Inicjalizacja wtyczki po załadowaniu wszystkich pluginów.
add_action( 'plugins_loaded', static function () {
    load_plugin_textdomain( 'allemedia-reviews', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    $settings = new Allemedia\Reviews\Settings();
    $api      = new Allemedia\Reviews\API( $settings );
    $render   = new Allemedia\Reviews\Render( $settings, $api );

    if ( class_exists( 'WooCommerce' ) ) {
        new Allemedia\Reviews\Metabox();
    }

    // Harmonogram automatycznego odświeżania cache (opcjonalne).
    add_action( 'amr_reviews_daily_refresh', static function () use ( $api, $settings ) {
        $default = $settings->get_option( 'default_place_id' );
        if ( ! empty( $default ) ) {
            $api->clear_cache_for( $default );
        }

        $place_ids = apply_filters( 'allemedia_reviews_place_ids', [] );
        if ( is_array( $place_ids ) ) {
            foreach ( $place_ids as $place_id ) {
                if ( is_string( $place_id ) && '' !== trim( $place_id ) ) {
                    $api->clear_cache_for( $place_id );
                }
            }
        }
    } );
} );

register_activation_hook( __FILE__, static function () {
    if ( ! wp_next_scheduled( 'amr_reviews_daily_refresh' ) ) {
        wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'amr_reviews_daily_refresh' );
    }
} );

register_deactivation_hook( __FILE__, static function () {
    wp_clear_scheduled_hook( 'amr_reviews_daily_refresh' );
} );
