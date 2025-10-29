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
?>
<article class="amr-review-card" id="<?php echo esc_attr( $card_id ); ?>">
    <div class="amr-review-card__header">
        <div class="amr-review-card__stars" aria-label="<?php echo esc_attr( sprintf( __( 'Ocena: %1$s na 5', 'allemedia-reviews' ), number_format_i18n( $rating, 1 ) ) ); ?>">
            <?php echo render_stars( $rating, $star_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
        <span class="amr-review-card__author"><?php echo esc_html( mask_surname( $name ) ); ?></span>
        <?php if ( ! empty( $time ) ) : ?>
            <time datetime="<?php echo esc_attr( $time ); ?>" class="amr-review-card__date"><?php echo format_date( $time ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></time>
        <?php endif; ?>
    </footer>
</article>
