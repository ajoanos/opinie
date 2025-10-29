<?php
/**
 * Obsługa panelu ustawień wtyczki.
 *
 * @package Allemedia\Reviews
 */

namespace Allemedia\Reviews;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Klasa zarządzająca ekranem opcji i przechowywaniem ustawień.
 */
class Settings {

    /**
     * Nazwa opcji w bazie danych.
     */
    private const OPTION_NAME = 'amr_reviews_settings';

    /**
     * Aktualne ustawienia.
     *
     * @var array
     */
    private array $options = [];

    /**
     * Konstruktor.
     */
    public function __construct() {
        $this->options = $this->get_all_options();

        add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_post_amr_force_refresh', [ $this, 'handle_force_refresh' ] );
    }

    /**
     * Zwraca konkretną opcję.
     *
     * @param string $key     Klucz.
     * @param mixed  $default Domyślna wartość.
     *
     * @return mixed
     */
    public function get_option( string $key, $default = '' ) {
        return $this->options[ $key ] ?? $default;
    }

    /**
     * Rejestruje stronę ustawień w menu.
     */
    public function register_settings_page(): void {
        add_options_page(
            __( 'Allemedia Opinie', 'allemedia-reviews' ),
            __( 'Allemedia Opinie', 'allemedia-reviews' ),
            'manage_options',
            'allemedia-reviews-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Rejestruje ustawienia, sekcje oraz pola formularza.
     */
    public function register_settings(): void {
        register_setting(
            'allemedia_reviews_settings',
            self::OPTION_NAME,
            [
                'type'              => 'array',
                'sanitize_callback' => [ $this, 'sanitize_settings' ],
                'default'           => $this->get_defaults(),
            ]
        );

        add_settings_section(
            'allemedia_reviews_general',
            __( 'Konfiguracja połączenia', 'allemedia-reviews' ),
            '__return_false',
            'allemedia_reviews_settings'
        );

        add_settings_field(
            'cloud_function_url',
            __( 'Adres Cloud Function', 'allemedia-reviews' ),
            [ $this, 'render_cloud_url_field' ],
            'allemedia_reviews_settings',
            'allemedia_reviews_general'
        );

        add_settings_field(
            'default_place_id',
            __( 'Domyślny Place ID', 'allemedia-reviews' ),
            [ $this, 'render_place_id_field' ],
            'allemedia_reviews_settings',
            'allemedia_reviews_general'
        );

        add_settings_field(
            'cache_ttl_minutes',
            __( 'Czas cache (minuty)', 'allemedia-reviews' ),
            [ $this, 'render_ttl_field' ],
            'allemedia_reviews_settings',
            'allemedia_reviews_general'
        );
    }

    /**
     * Renderuje stronę ustawień.
     */
    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $this->options = $this->get_all_options();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Allemedia Opinie', 'allemedia-reviews' ) . '</h1>';

        settings_errors( 'allemedia_reviews_settings' );

        echo '<form action="' . esc_url( admin_url( 'options.php' ) ) . '" method="post">';
        settings_fields( 'allemedia_reviews_settings' );
        do_settings_sections( 'allemedia_reviews_settings' );
        submit_button( __( 'Zapisz ustawienia', 'allemedia-reviews' ) );
        echo '</form>';

        echo '<hr />';
        echo '<h2>' . esc_html__( 'Wymuś odświeżenie danych', 'allemedia-reviews' ) . '</h2>';
        echo '<p>' . esc_html__( 'Jeżeli podejrzewasz, że dane są nieaktualne, możesz wyczyścić cache i pobrać opinie ponownie przy następnym ładowaniu strony.', 'allemedia-reviews' ) . '</p>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'amr_force_refresh_action' );
        echo '<input type="hidden" name="action" value="amr_force_refresh" />';
        submit_button( __( 'Wymuś odświeżenie', 'allemedia-reviews' ), 'secondary' );
        echo '</form>';

        echo '<hr />';
        echo '<h2>' . esc_html__( 'Jak używać', 'allemedia-reviews' ) . '</h2>';
        echo '<p>' . wp_kses_post( __( 'Użyj shortcode <code>[allemedia_reviews]</code> lub bloku <strong>Allemedia: Opinie Google</strong> w edytorze blokowym. Dostępne atrybuty: <code>place_id</code>, <code>limit</code>, <code>show_more_chars</code>.', 'allemedia-reviews' ) ) . '</p>';
        echo '</div>';
    }

    /**
     * Render pola adresu Cloud Function.
     */
    public function render_cloud_url_field(): void {
        $value = (string) $this->get_option( 'cloud_function_url', '' );
        echo '<input type="url" name="' . esc_attr( self::OPTION_NAME ) . '[cloud_function_url]" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="https://region-project.cloudfunctions.net" />';
        echo '<p class="description">' . esc_html__( 'Adres bez końcowego ukośnika.', 'allemedia-reviews' ) . '</p>';
    }

    /**
     * Render pola domyślnego Place ID.
     */
    public function render_place_id_field(): void {
        $value = (string) $this->get_option( 'default_place_id', '' );
        echo '<input type="text" name="' . esc_attr( self::OPTION_NAME ) . '[default_place_id]" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="ChIJ..." />';
    }

    /**
     * Render pola TTL.
     */
    public function render_ttl_field(): void {
        $value = (int) $this->get_option( 'cache_ttl_minutes', 1440 );
        echo '<input type="number" min="1" name="' . esc_attr( self::OPTION_NAME ) . '[cache_ttl_minutes]" value="' . esc_attr( $value ) . '" class="small-text" /> ';
        echo '<span>' . esc_html__( 'minut', 'allemedia-reviews' ) . '</span>';
    }

    /**
     * Obsługa kliknięcia w przycisk „Wymuś odświeżenie”.
     */
    public function handle_force_refresh(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Brak uprawnień.', 'allemedia-reviews' ) );
        }

        check_admin_referer( 'amr_force_refresh_action' );

        do_action( 'amr_reviews_force_refresh' );

        add_settings_error( 'allemedia_reviews_settings', 'amr_force_refresh', __( 'Cache został wyczyszczony.', 'allemedia-reviews' ), 'updated' );

        wp_safe_redirect( wp_get_referer() ?: admin_url( 'options-general.php?page=allemedia-reviews-settings' ) );
        exit;
    }

    /**
     * Sanityzuje dane przed zapisaniem.
     *
     * @param array $input Dane wejściowe.
     *
     * @return array
     */
    public function sanitize_settings( array $input ): array {
        $sanitized = $this->get_defaults();

        if ( isset( $input['cloud_function_url'] ) ) {
            $sanitized['cloud_function_url'] = esc_url_raw( trim( $input['cloud_function_url'] ) );
        }

        if ( isset( $input['default_place_id'] ) ) {
            $sanitized['default_place_id'] = sanitize_text_field( $input['default_place_id'] );
        }

        if ( isset( $input['cache_ttl_minutes'] ) ) {
            $ttl                             = (int) $input['cache_ttl_minutes'];
            $sanitized['cache_ttl_minutes'] = max( 1, $ttl );
        }

        $this->options = $sanitized;

        return $sanitized;
    }

    /**
     * Zwraca wszystkie opcje wraz z domyślnymi wartościami.
     */
    private function get_all_options(): array {
        $options = get_option( self::OPTION_NAME, [] );
        if ( ! is_array( $options ) ) {
            $options = [];
        }

        return array_merge( $this->get_defaults(), $options );
    }

    /**
     * Zwraca wartości domyślne.
     */
    private function get_defaults(): array {
        return [
            'cloud_function_url' => '',
            'default_place_id'   => '',
            'cache_ttl_minutes'  => 1440,
        ];
    }
}
