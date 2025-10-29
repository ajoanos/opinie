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
$reviews = is_array( $data['reviews'] ?? null ) ? $data['reviews'] : [];
$show    = (int) ( $data['show_more_chars'] ?? 220 );

$heading_id = 'amr-reviews-heading-' . uniqid();
?>
<section class="amr-reviews amr-is-loading" aria-labelledby="<?php echo esc_attr( $heading_id ); ?>" data-show-more="<?php echo esc_attr( $show ); ?>">
    <div class="amr-reviews__inner">
        <?php
        $header_data = [
            'average' => $average,
            'total'   => $total,
            'heading' => $heading_id,
        ];
        include __DIR__ . '/header.php';
        ?>
        <div class="amr-reviews__list" aria-live="polite">
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
    </div>
</section>
