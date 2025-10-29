<?php
/**
 * Renderowanie shortcode'u, bloku oraz zasobów frontendu.
 *
 * @package Allemedia\Reviews
 */

namespace Allemedia\Reviews;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Klasa odpowiedzialna za generowanie HTML i powiązanych zasobów.
 */
class Render {

    private Settings $settings;

    private API $api;

    /**
     * Aktualne dane do wygenerowania JSON-LD.
     *
     * @var array|null
     */
    private ?array $schema_payload = null;

    /**
     * Konstruktor.
     */
    public function __construct( Settings $settings, API $api ) {
        $this->settings = $settings;
        $this->api      = $api;

        add_action( 'init', [ $this, 'register_assets' ] );
        add_shortcode( 'allemedia_reviews', [ $this, 'render_shortcode' ] );
        add_action( 'init', [ $this, 'register_block' ] );
        add_action( 'wp_head', [ $this, 'print_schema' ], 20 );
        add_action( 'wp_footer', [ $this, 'print_schema' ], 20 );
    }

    /**
     * Rejestruje style oraz skrypty.
     */
    public function register_assets(): void {
        wp_register_style(
            'allemedia-reviews',
            ALLEMEDIA_REVIEWS_URL . 'assets/css/reviews.css',
            [],
            ALLEMEDIA_REVIEWS_VERSION
        );

        wp_register_script(
            'allemedia-reviews',
            ALLEMEDIA_REVIEWS_URL . 'assets/js/reviews.js',
            [],
            ALLEMEDIA_REVIEWS_VERSION,
            true
        );

        wp_localize_script(
            'allemedia-reviews',
            'AllemediaReviewsConfig',
            [
                'more' => __( 'Pokaż więcej', 'allemedia-reviews' ),
                'less' => __( 'Pokaż mniej', 'allemedia-reviews' ),
            ]
        );

        wp_register_script(
            'allemedia-reviews-block',
            ALLEMEDIA_REVIEWS_URL . 'assets/js/block.js',
            [ 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-i18n' ],
            ALLEMEDIA_REVIEWS_VERSION,
            true
        );

        wp_set_script_translations(
            'allemedia-reviews-block',
            'allemedia-reviews',
            ALLEMEDIA_REVIEWS_PATH . 'languages'
        );
    }

    /**
     * Rejestruje blok edytora Gutenberg.
     */
    public function register_block(): void {
        register_block_type(
            'allemedia/reviews',
            [
                'api_version'     => 2,
                'attributes'      => [
                    'place_id'        => [
                        'type'    => 'string',
                        'default' => '',
                    ],
                    'limit'           => [
                        'type'    => 'number',
                        'default' => 12,
                    ],
                    'show_more_chars' => [
                        'type'    => 'number',
                        'default' => 220,
                    ],
                ],
                'render_callback' => [ $this, 'render_block' ],
                'editor_script'   => 'allemedia-reviews-block',
                'style'           => 'allemedia-reviews',
            ]
        );
    }

    /**
     * Renderuje shortcode.
     */
    public function render_shortcode( array $atts = [], string $content = '' ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
        $atts = shortcode_atts(
            [
                'place_id'        => '',
                'limit'           => 12,
                'show_more_chars' => 220,
            ],
            $atts,
            'allemedia_reviews'
        );

        return $this->render_reviews( $atts );
    }

    /**
     * Renderuje blok.
     */
    public function render_block( array $attributes ): string {
        $atts = wp_parse_args(
            $attributes,
            [
                'place_id'        => '',
                'limit'           => 12,
                'show_more_chars' => 220,
            ]
        );

        return $this->render_reviews( $atts );
    }

    /**
     * Główna logika renderowania.
     */
    private function render_reviews( array $atts ): string {
        $place_id = (string) ( $atts['place_id'] ?? '' );
        $limit    = max( 1, (int) ( $atts['limit'] ?? 12 ) );
        $show     = (int) ( $atts['show_more_chars'] ?? 220 );

        if ( empty( $place_id ) && is_woocommerce_product() ) {
            $product_id = get_the_ID();
            if ( $product_id ) {
                $meta_place = get_post_meta( $product_id, '_allemedia_place_id', true );
                if ( ! empty( $meta_place ) ) {
                    $place_id = (string) $meta_place;
                }
            }
        }

        if ( empty( $place_id ) ) {
            $place_id = (string) $this->settings->get_option( 'default_place_id', '' );
        }

        if ( empty( $place_id ) ) {
            return '<div class="amr-reviews amr-reviews--notice">' . esc_html__( 'Skonfiguruj identyfikator Place ID w ustawieniach wtyczki lub w atrybucie shortcode.', 'allemedia-reviews' ) . '</div>';
        }

        $data = $this->api->get_reviews( $place_id, $limit );

        wp_enqueue_style( 'allemedia-reviews' );
        wp_enqueue_script( 'allemedia-reviews' );

        $context = [
            'average_rating'   => $data['average_rating'] ?? 0,
            'total_ratings'    => $data['total_ratings'] ?? 0,
            'reviews'          => $data['reviews'] ?? [],
            'show_more_chars'  => max( 50, $show ),
            'place_id'         => $place_id,
            'limit'            => $limit,
        ];

        $this->prepare_schema_payload( $context );

        $markup = $this->load_view( 'wrapper', $context );
        $markup = apply_filters( 'allemedia_reviews_markup', $markup, $context );

        return $markup;
    }

    /**
     * Przygotowuje dane do JSON-LD.
     */
    private function prepare_schema_payload( array $context ): void {
        $type = is_woocommerce_product() ? 'Product' : apply_filters( 'allemedia_reviews_schema_type', 'Organization' );

        $review_count = (int) $context['total_ratings'];
        if ( 0 === $review_count && ! empty( $context['reviews'] ) ) {
            $review_count = count( $context['reviews'] );
        }

        $payload = [
            '@context'        => 'https://schema.org',
            '@type'           => $type,
            'aggregateRating' => [
                '@type'       => 'AggregateRating',
                'ratingValue' => round( (float) $context['average_rating'], 2 ),
                'reviewCount' => $review_count,
            ],
        ];

        if ( 'Product' === $type && function_exists( 'wc_get_product' ) && is_woocommerce_product() ) {
            $product = wc_get_product( get_the_ID() );
            if ( $product ) {
                $payload['name'] = $product->get_name();
            }
        } else {
            $payload['name'] = get_bloginfo( 'name' );
        }

        if ( empty( $payload['name'] ) ) {
            $payload['name'] = get_bloginfo( 'name' );
        }

        $this->schema_payload = $payload;
    }

    /**
     * Wypisuje JSON-LD w nagłówku.
     */
    public function print_schema(): void {
        if ( empty( $this->schema_payload ) ) {
            return;
        }

        static $printed = false;
        if ( $printed ) {
            return;
        }

        $printed = true;

        echo '<script type="application/ld+json">' . wp_json_encode( $this->schema_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    }

    /**
     * Ładuje plik widoku.
     */
    private function load_view( string $view, array $context ): string {
        $path = ALLEMEDIA_REVIEWS_PATH . 'views/' . $view . '.php';
        if ( ! file_exists( $path ) ) {
            return '';
        }

        ob_start();
        $data = $context;
        include $path;

        return ob_get_clean();
    }
}
