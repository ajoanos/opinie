<?php
/**
 * Obsługa komunikacji z API oraz cache'owania.
 *
 * @package Allemedia\Reviews
 */

namespace Allemedia\Reviews;


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Klasa odpowiedzialna za pobieranie opinii oraz zarządzanie cache.
 */
class API {

    /**
     * Nazwa opcji przechowującej rejestr kluczy cache.
     */
    private const REGISTRY_OPTION = 'amr_reviews_cache_keys';

    /**
     * Obiekt ustawień.
     *
     * @var Settings
     */
    private Settings $settings;

    /**
     * Konstruktor.
     *
     * @param Settings $settings Obiekt konfiguracji.
     */
    public function __construct( Settings $settings ) {
        $this->settings = $settings;

        add_action( 'amr_reviews_force_refresh', [ $this, 'clear_cache' ] );
    }

    /**
     * Pobiera opinie z cache lub zdalnego endpointu.
     *
     * @param string $place_id Identyfikator miejsca.
     * @param int    $limit    Limit opinii.
     *
     * @return array{average_rating:float,total_ratings:int,reviews:array<int,array<string,mixed>>}
     */
    public function get_reviews( string $place_id, int $limit = 12 ): array {
        $place_id = trim( $place_id );
        $limit    = max( 1, $limit );

        $cache_key = $this->build_cache_key( $place_id, $limit );
        $cached    = get_transient( $cache_key );

        if ( false !== $cached && is_array( $cached ) ) {
            return $cached;
        }

        $data = $this->request_reviews( $place_id, $limit );

        if ( empty( $data ) ) {
            $data = $this->get_fallback_data();
        }

        if ( isset( $data['reviews'] ) && is_array( $data['reviews'] ) ) {
            usort(
                $data['reviews'],
                static fn ( $a, $b ) => strtotime( $b['time'] ?? 'now' ) <=> strtotime( $a['time'] ?? 'now' )
            );
        }

        $ttl = $this->get_ttl();
        set_transient( $cache_key, $data, $ttl );
        $this->remember_cache_key( $cache_key, $place_id, $limit );

        return $data;
    }

    /**
     * Usuwa całość cache wtyczki.
     */
    public function clear_cache(): void {
        $registry = $this->get_cache_registry();
        foreach ( array_keys( $registry ) as $key ) {
            delete_transient( $key );
        }

        delete_option( self::REGISTRY_OPTION );
    }

    /**
     * Czyści cache dla konkretnego miejsca.
     *
     * @param string $place_id
     */
    public function clear_cache_for( string $place_id ): void {
        $registry = $this->get_cache_registry();
        $place_id = trim( $place_id );

        $updated = false;

        foreach ( $registry as $key => $meta ) {
            if ( isset( $meta['place_id'] ) && $place_id === $meta['place_id'] ) {
                delete_transient( $key );
                unset( $registry[ $key ] );
                $updated = true;
            }
        }

        if ( $updated ) {
            update_option( self::REGISTRY_OPTION, $registry, false );
        }
    }

    /**
     * Buduje klucz transientu.
     */
    private function build_cache_key( string $place_id, int $limit ): string {
        return 'amr_reviews_' . md5( $place_id . '|' . $limit );
    }

    /**
     * Zwraca TTL w sekundach.
     */
    private function get_ttl(): int {
        $minutes = (int) $this->settings->get_option( 'cache_ttl_minutes', 1440 );
        $minutes = max( 1, $minutes );

        return $minutes * MINUTE_IN_SECONDS;
    }

    /**
     * Pobiera dane z endpointu.
     */
    private function request_reviews( string $place_id, int $limit ): array {
        $base_url = (string) $this->settings->get_option( 'cloud_function_url', '' );
        if ( empty( $base_url ) ) {
            error_log( '[Allemedia Reviews] Brak skonfigurowanego adresu cloud_function_url.' );
            return [];
        }

        $endpoint = rtrim( $base_url, '/' ) . '/reviews';
        $url      = add_query_arg(
            [
                'place_id' => $place_id,
                'limit'    => $limit,
            ],
            $endpoint
        );

        $response = wp_remote_get(
            $url,
            [
                'timeout' => 8,
                'headers' => [
                    'Accept'         => 'application/json',
                    'X-Goog-Api-Key' => defined( 'ALLEMEDIA_GOOGLE_API_KEY' ) ? ALLEMEDIA_GOOGLE_API_KEY : '',
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            error_log( '[Allemedia Reviews] Błąd połączenia: ' . $response->get_error_message() );
            return [];
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            error_log( sprintf( '[Allemedia Reviews] Nieoczekiwany kod odpowiedzi %d', $code ) );
            return [];
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            error_log( '[Allemedia Reviews] Pusta odpowiedź API.' );
            return [];
        }

        $decoded = json_decode( $body, true );
        if ( ! is_array( $decoded ) ) {
            error_log( '[Allemedia Reviews] Nieprawidłowy JSON w odpowiedzi.' );
            return [];
        }

        $decoded['average_rating'] = isset( $decoded['average_rating'] ) ? (float) $decoded['average_rating'] : 0.0;
        $decoded['total_ratings']  = isset( $decoded['total_ratings'] ) ? (int) $decoded['total_ratings'] : 0;
        $decoded['reviews']        = isset( $decoded['reviews'] ) && is_array( $decoded['reviews'] ) ? array_values( $decoded['reviews'] ) : [];

        $decoded['reviews'] = array_map(
            static function ( $review ) {
                return [
                    'author_name'       => isset( $review['author_name'] ) ? (string) $review['author_name'] : '',
                    'rating'            => isset( $review['rating'] ) ? (float) $review['rating'] : 0.0,
                    'text'              => isset( $review['text'] ) ? (string) $review['text'] : '',
                    'time'              => isset( $review['time'] ) ? (string) $review['time'] : '',
                    'profile_photo_url' => $review['profile_photo_url'] ?? null,
                ];
            },
            $decoded['reviews']
        );

        if ( $decoded['total_ratings'] < count( $decoded['reviews'] ) ) {
            $decoded['total_ratings'] = count( $decoded['reviews'] );
        }

        if ( $decoded['average_rating'] <= 0 && ! empty( $decoded['reviews'] ) ) {
            $sum = array_sum( array_column( $decoded['reviews'], 'rating' ) );
            $decoded['average_rating'] = $sum ? round( $sum / count( $decoded['reviews'] ), 2 ) : 0.0;
        }

        return $decoded;
    }

    /**
     * Dane zapasowe.
     */
    private function get_fallback_data(): array {
        $path = ALLEMEDIA_REVIEWS_PATH . 'fallback.json';
        if ( ! file_exists( $path ) ) {
            return [
                'average_rating' => 5.0,
                'total_ratings'  => 0,
                'reviews'        => [],
            ];
        }

        $contents = file_get_contents( $path );
        if ( false === $contents ) {
            return [
                'average_rating' => 5.0,
                'total_ratings'  => 0,
                'reviews'        => [],
            ];
        }

        $decoded = json_decode( $contents, true );
        if ( ! is_array( $decoded ) ) {
            return [
                'average_rating' => 5.0,
                'total_ratings'  => 0,
                'reviews'        => [],
            ];
        }

        $decoded['average_rating'] = isset( $decoded['average_rating'] ) ? (float) $decoded['average_rating'] : 5.0;
        $decoded['total_ratings']  = isset( $decoded['total_ratings'] ) ? (int) $decoded['total_ratings'] : 0;
        $decoded['reviews']        = isset( $decoded['reviews'] ) && is_array( $decoded['reviews'] ) ? array_values( $decoded['reviews'] ) : [];

        $decoded['reviews'] = array_map(
            static function ( $review ) {
                return [
                    'author_name'       => isset( $review['author_name'] ) ? (string) $review['author_name'] : '',
                    'rating'            => isset( $review['rating'] ) ? (float) $review['rating'] : 0.0,
                    'text'              => isset( $review['text'] ) ? (string) $review['text'] : '',
                    'time'              => isset( $review['time'] ) ? (string) $review['time'] : '',
                    'profile_photo_url' => $review['profile_photo_url'] ?? null,
                ];
            },
            $decoded['reviews']
        );

        if ( $decoded['total_ratings'] < count( $decoded['reviews'] ) ) {
            $decoded['total_ratings'] = count( $decoded['reviews'] );
        }

        if ( $decoded['average_rating'] <= 0 && ! empty( $decoded['reviews'] ) ) {
            $sum = array_sum( array_column( $decoded['reviews'], 'rating' ) );
            $decoded['average_rating'] = $sum ? round( $sum / count( $decoded['reviews'] ), 2 ) : 0.0;
        }

        return $decoded;
    }

    /**
     * Zapamiętuje klucz cache w rejestrze.
     */
    private function remember_cache_key( string $cache_key, string $place_id, int $limit ): void {
        $registry = $this->get_cache_registry();
        $registry[ $cache_key ] = [
            'place_id' => trim( $place_id ),
            'limit'    => $limit,
        ];

        update_option( self::REGISTRY_OPTION, $registry, false );
    }

    /**
     * Zwraca rejestr cache.
     */
    private function get_cache_registry(): array {
        $registry = get_option( self::REGISTRY_OPTION, [] );
        if ( ! is_array( $registry ) ) {
            return [];
        }

        return $registry;
    }
}
