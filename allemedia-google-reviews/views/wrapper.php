<?php
/**
 * Główny kontener sekcji opinii.
 *
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$average = (float) ( $data['average_rating'] ?? 0 );
$total   = (int) ( $data['total_ratings'] ?? 0 );
$reviews           = is_array( $data['reviews'] ?? null ) ? $data['reviews'] : [];
$show              = (int) ( $data['show_more_chars'] ?? 220 );
$autoplay_interval = (int) ( $data['autoplay_interval'] ?? 6000 );
$autoplay_enabled  = (bool) ( $data['autoplay'] ?? false );
$place_id          = isset( $data['place_id'] ) ? (string) $data['place_id'] : '';
$review_url        = isset( $data['review_url'] ) ? (string) $data['review_url'] : '';

$autoplay_available = $autoplay_enabled && count( $reviews ) > 1;
$autoplay_value     = $autoplay_available ? '1' : '0';
$autoplay_interval  = $autoplay_interval < 1000 ? 6000 : $autoplay_interval;

$heading_id = 'amr-reviews-heading-' . uniqid();

$summary_data = [
    'average' => $average,
    'total'   => $total,
    'review'  => $review_url,
];
?>
<section
    class="amr-reviews amr-is-loading"
    aria-labelledby="<?php echo esc_attr( $heading_id ); ?>"
    data-show-more="<?php echo esc_attr( $show ); ?>"
    data-autoplay="<?php echo esc_attr( $autoplay_value ); ?>"
    data-autoplay-interval="<?php echo esc_attr( $autoplay_interval ); ?>"
    <?php if ( ! empty( $place_id ) ) : ?>data-place-id="<?php echo esc_attr( $place_id ); ?>"<?php endif; ?>
>
    <div class="amr-reviews__inner">
        <?php
        $header_data = [
            'heading' => $heading_id,
        ];
        include __DIR__ . '/header.php';
        ?>
        <div class="amr-reviews__carousel">
            <div class="amr-reviews__list" aria-live="polite"<?php echo empty( $reviews ) ? '' : ' role="list"'; ?>>
                <?php
                $summary_data = apply_filters( 'allemedia_reviews_summary_card_data', $summary_data, $data );
                include __DIR__ . '/summary-card.php';
                ?>
                <?php if ( empty( $reviews ) ) : ?>
                    <p class="amr-reviews__empty"><?php esc_html_e( 'Brak opinii.', 'allemedia-reviews' ); ?></p>
                <?php else : ?>
                    <?php foreach ( $reviews as $index => $review ) :
                        $item_data = [
                            'review' => $review,
                            'index'  => $index,
                            'show'   => $show,
                        ];
                        include __DIR__ . '/item.php';
                    endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if ( $autoplay_available ) : ?>
                <div class="amr-reviews__controls">
                    <button type="button" class="amr-reviews__control amr-reviews__control--prev" data-direction="prev">
                        <span aria-hidden="true">&#8249;</span>
                        <span class="screen-reader-text"><?php esc_html_e( 'Poprzednia opinia', 'allemedia-reviews' ); ?></span>
                    </button>
                    <button type="button" class="amr-reviews__control amr-reviews__control--next" data-direction="next">
                        <span aria-hidden="true">&#8250;</span>
                        <span class="screen-reader-text"><?php esc_html_e( 'Następna opinia', 'allemedia-reviews' ); ?></span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
