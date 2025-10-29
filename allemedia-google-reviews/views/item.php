<?php
/**
 * Pojedyncza karta opinii.
 *
 * @var array $item_data
 */

use function Allemedia\Reviews\render_stars;
use function Allemedia\Reviews\mask_surname;
use function Allemedia\Reviews\format_date;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$review = is_array( $item_data['review'] ?? null ) ? $item_data['review'] : [];
$index  = (int) ( $item_data['index'] ?? 0 );
$show   = (int) ( $item_data['show'] ?? 220 );

$rating = isset( $review['rating'] ) ? (float) $review['rating'] : 0.0;
$name   = isset( $review['author_name'] ) ? (string) $review['author_name'] : __( 'Anonimowy użytkownik', 'allemedia-reviews' );
$text   = isset( $review['text'] ) ? wp_strip_all_tags( (string) $review['text'] ) : '';
$time   = isset( $review['time'] ) ? (string) $review['time'] : '';
$photo  = isset( $review['profile_photo_url'] ) ? (string) $review['profile_photo_url'] : '';

$full_text  = trim( preg_replace( '/\s+/', ' ', $text ) );
$short_text = $full_text;
$needs_more = false;

if ( mb_strlen( $full_text ) > $show ) {
    $needs_more = true;
    $short_text = trim( mb_substr( $full_text, 0, $show ) );
    if ( mb_strlen( $short_text ) < mb_strlen( $full_text ) ) {
        $short_text = rtrim( $short_text, ',.;:!?"\'' );
        $short_text .= '…';
    }
}

$text_id = 'amr-review-text-' . $index . '-' . uniqid();
$card_id = 'amr-review-card-' . $index . '-' . uniqid();
$star_id = uniqid( 'amr-review-star-', false );
$initial = mb_strtoupper( mb_substr( trim( $name ), 0, 1 ) );

if ( empty( $initial ) ) {
    $initial = 'G';
}

$avatar_classes = [
    'amr-review-card__avatar--blue',
    'amr-review-card__avatar--green',
    'amr-review-card__avatar--purple',
    'amr-review-card__avatar--orange',
    'amr-review-card__avatar--pink',
];

$avatar_class = $avatar_classes[ $index % count( $avatar_classes ) ];
$has_photo    = ! empty( $photo ) && filter_var( $photo, FILTER_VALIDATE_URL );
?>
<article class="amr-review-card" id="<?php echo esc_attr( $card_id ); ?>" role="listitem">
    <div class="amr-review-card__top">
        <div class="amr-review-card__profile">
            <span class="amr-review-card__avatar <?php echo esc_attr( $avatar_class ); ?>" aria-hidden="true">
                <?php if ( $has_photo ) : ?>
                    <img src="<?php echo esc_url( $photo ); ?>" alt="" loading="lazy" />
                <?php else : ?>
                    <span class="amr-review-card__avatar-letter" aria-hidden="true"><?php echo esc_html( $initial ); ?></span>
                <?php endif; ?>
            </span>
            <div class="amr-review-card__identity">
                <span class="amr-review-card__author"><?php echo esc_html( mask_surname( $name ) ); ?></span>
                <span class="amr-review-card__source">
                    <span class="amr-review-card__source-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" role="presentation">
                            <path fill="#4285F4" d="M21.6 12.23c0-.74-.07-1.45-.21-2.14H12v4.05h5.39c-.23 1.2-.93 2.22-1.98 2.9v2.41h3.2c1.87-1.72 2.99-4.25 2.99-7.22z" />
                            <path fill="#34A853" d="M12 22c2.7 0 4.96-.9 6.62-2.45l-3.2-2.41c-.9.6-2.05.96-3.42.96-2.63 0-4.86-1.77-5.66-4.15H3.04v2.52C4.68 19.99 8.09 22 12 22z" />
                            <path fill="#FBBC05" d="M6.34 13.95c-.2-.6-.31-1.24-.31-1.95s.11-1.34.31-1.95V7.53H3.04A9.996 9.996 0 0 0 2 12c0 1.6.38 3.11 1.04 4.47l3.3-2.52z" />
                            <path fill="#EA4335" d="M12 6.36c1.47 0 2.78.5 3.81 1.49l2.86-2.86C16.95 3.62 14.7 2.64 12 2.64 8.09 2.64 4.68 4.65 3.04 7.53l3.3 2.52c.8-2.38 3.03-4.15 5.66-4.15z" />
                        </svg>
                    </span>
                    <span class="amr-review-card__source-label"><?php esc_html_e( 'Opinia Google', 'allemedia-reviews' ); ?></span>
                </span>
            </div>
        </div>
        <div class="amr-review-card__rating" aria-label="<?php echo esc_attr( sprintf( __( 'Ocena: %1$s na 5', 'allemedia-reviews' ), number_format_i18n( $rating, 1 ) ) ); ?>">
            <div class="amr-review-card__stars">
                <?php echo render_stars( $rating, $star_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
            <span class="amr-review-card__rating-value" aria-hidden="true"><?php echo esc_html( number_format_i18n( $rating, 1 ) ); ?></span>
        </div>
    </div>
    <div class="amr-review-card__content">
        <p class="amr-review-card__text" id="<?php echo esc_attr( $text_id ); ?>" aria-live="polite" data-full="<?php echo esc_attr( $full_text ); ?>" data-short="<?php echo esc_attr( $short_text ); ?>"><?php echo esc_html( $needs_more ? $short_text : $full_text ); ?></p>
        <?php if ( $needs_more ) : ?>
            <button type="button" class="amr-review-card__toggle" data-target="<?php echo esc_attr( $text_id ); ?>" aria-expanded="false">
                <span class="amr-review-card__toggle-label"><?php esc_html_e( 'Pokaż więcej', 'allemedia-reviews' ); ?></span>
            </button>
        <?php endif; ?>
    </div>
    <footer class="amr-review-card__meta">
        <?php if ( ! empty( $time ) ) : ?>
            <time datetime="<?php echo esc_attr( $time ); ?>" class="amr-review-card__date"><?php echo format_date( $time ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></time>
        <?php endif; ?>
    </footer>
</article>
