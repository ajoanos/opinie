<?php
/**
 * Nagłówek sekcji opinii.
 *
 * @var array $header_data
 */

use function Allemedia\Reviews\render_stars;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$average    = (float) ( $header_data['average'] ?? 0 );
$total      = (int) ( $header_data['total'] ?? 0 );
$heading_id = (string) ( $header_data['heading'] ?? 'amr-reviews-heading' );
$star_id    = uniqid( 'amr-average-star-', false );
?>
<header class="amr-reviews__header">
    <h2 id="<?php echo esc_attr( $heading_id ); ?>"><?php esc_html_e( 'Sprawdź co piszą o nas inni', 'allemedia-reviews' ); ?></h2>
    <div class="amr-reviews__summary" aria-live="polite">
        <div class="amr-reviews__rating">
            <span class="amr-reviews__rating-value" aria-hidden="true"><?php echo esc_html( number_format_i18n( $average, 1 ) ); ?></span>
            <div class="amr-reviews__stars" aria-label="<?php echo esc_attr( sprintf( __( 'Średnia ocena: %1$s na 5', 'allemedia-reviews' ), number_format_i18n( $average, 1 ) ) ); ?>">
                <?php echo render_stars( $average, $star_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </div>
        <span class="amr-reviews__count"><?php printf( esc_html__( 'na podstawie %s opinii', 'allemedia-reviews' ), esc_html( number_format_i18n( $total ) ) ); ?></span>
    </div>
</header>
