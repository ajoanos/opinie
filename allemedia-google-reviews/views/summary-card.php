<?php
/**
 * Karta podsumowania opinii Google.
 *
 * @var array $summary_data
 */

use function Allemedia\Reviews\render_stars;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$average     = (float) ( $summary_data['average'] ?? 0 );
$total       = (int) ( $summary_data['total'] ?? 0 );
$review_link = isset( $summary_data['review'] ) ? (string) $summary_data['review'] : '';
$star_id     = uniqid( 'amr-summary-star-', false );
?>
<article class="amr-summary-card" role="listitem">
    <header class="amr-summary-card__header">
        <span class="amr-summary-card__brand">
            <span class="amr-summary-card__brand-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="presentation">
                    <path fill="#4285F4" d="M21.6 12.23c0-.74-.07-1.45-.21-2.14H12v4.05h5.39c-.23 1.2-.93 2.22-1.98 2.9v2.41h3.2c1.87-1.72 2.99-4.25 2.99-7.22z" />
                    <path fill="#34A853" d="M12 22c2.7 0 4.96-.9 6.62-2.45l-3.2-2.41c-.9.6-2.05.96-3.42.96-2.63 0-4.86-1.77-5.66-4.15H3.04v2.52C4.68 19.99 8.09 22 12 22z" />
                    <path fill="#FBBC05" d="M6.34 13.95c-.2-.6-.31-1.24-.31-1.95s.11-1.34.31-1.95V7.53H3.04A9.996 9.996 0 0 0 2 12c0 1.6.38 3.11 1.04 4.47l3.3-2.52z" />
                    <path fill="#EA4335" d="M12 6.36c1.47 0 2.78.5 3.81 1.49l2.86-2.86C16.95 3.62 14.7 2.64 12 2.64 8.09 2.64 4.68 4.65 3.04 7.53l3.3 2.52c.8-2.38 3.03-4.15 5.66-4.15z" />
                </svg>
            </span>
            <span class="amr-summary-card__brand-label"><?php esc_html_e( 'Opinie Google', 'allemedia-reviews' ); ?></span>
        </span>
        <span class="amr-summary-card__verified"><?php esc_html_e( 'Zweryfikowane opinie', 'allemedia-reviews' ); ?></span>
    </header>
    <div class="amr-summary-card__rating" role="img" aria-label="<?php echo esc_attr( sprintf( __( 'Średnia ocena: %1$s na 5', 'allemedia-reviews' ), number_format_i18n( $average, 1 ) ) ); ?>">
        <span class="amr-summary-card__score" aria-hidden="true"><?php echo esc_html( number_format_i18n( $average, 1 ) ); ?></span>
        <div class="amr-summary-card__stars">
            <?php echo render_stars( $average, $star_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </div>
    <p class="amr-summary-card__count">
        <?php printf( esc_html__( 'na podstawie %s opinii', 'allemedia-reviews' ), esc_html( number_format_i18n( $total ) ) ); ?>
    </p>
    <?php if ( ! empty( $review_link ) ) : ?>
        <a class="amr-summary-card__cta" href="<?php echo esc_url( $review_link ); ?>" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e( 'Dodaj opinię w Google', 'allemedia-reviews' ); ?>
        </a>
    <?php endif; ?>
</article>
