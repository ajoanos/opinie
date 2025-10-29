<?php
/**
 * Nagłówek sekcji opinii.
 *
 * @var array $header_data
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$heading_id = (string) ( $header_data['heading'] ?? 'amr-reviews-heading' );
?>
<header class="amr-reviews__header">
    <p class="amr-reviews__subtitle"><?php esc_html_e( 'Dziękujemy za wszystkie opinie!', 'allemedia-reviews' ); ?></p>
    <h2 id="<?php echo esc_attr( $heading_id ); ?>" class="amr-reviews__heading">
        <?php esc_html_e( 'Sprawdź co piszą o nas inni', 'allemedia-reviews' ); ?>
    </h2>
    <span class="amr-reviews__divider" aria-hidden="true"></span>
</header>
