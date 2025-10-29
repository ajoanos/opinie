<?php
/**
 * Funkcje pomocnicze dla wtyczki Allemedia Google Reviews.
 *
 * @package Allemedia\Reviews
 */

namespace Allemedia\Reviews;

use DateTimeImmutable;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function mask_surname( string $name ): string {
    $parts = preg_split( '/\s+/', trim( $name ) );
    if ( empty( $parts ) ) {
        return $name;
    }

    $first = array_shift( $parts );
    if ( empty( $parts ) ) {
        return $first;
    }

    $last      = array_pop( $parts );
    $last_char = mb_substr( $last, 0, 1 );

    $middle = '';
    if ( ! empty( $parts ) ) {
        $middle = ' ' . implode( ' ', $parts );
    }

    return trim( sprintf( '%s%s %s.', $first, $middle ? ' ' . $middle : '', $last_char ) );
}

function render_stars( float $rating, string $id_prefix = '' ): string {
    $rating    = max( 0.0, min( 5.0, $rating ) );
    $stars     = '';
    $id_prefix = $id_prefix ?: uniqid( 'amr-star-', false );

    for ( $i = 1; $i <= 5; $i++ ) {
        $fill_percentage = max( 0, min( 1, $rating - $i + 1 ) );
        $gradient_id     = $id_prefix . '-' . $i;
        $gradient_attr   = \esc_attr( $gradient_id );
        $offset          = $fill_percentage * 100;

        $stars .= '<span class="amr-star" aria-hidden="true">'
            . '<svg viewBox="0 0 24 24" focusable="false" role="presentation">'
            . '<defs><linearGradient id="' . $gradient_attr . '" x1="0" x2="1" y1="0" y2="0">'
            . '<stop offset="' . $offset . '%" stop-color="currentColor"></stop>'
            . '<stop offset="' . $offset . '%" stop-color="currentColor" stop-opacity="0.2"></stop>'
            . '</linearGradient></defs>'
            . '<path fill="url(#' . $gradient_attr . ')" d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>'
            . '</span>';
    }

    $stars .= '<span class="screen-reader-text">' . \esc_html( sprintf( __( 'Ocena: %1$s na 5', 'allemedia-reviews' ), number_format_i18n( $rating, 1 ) ) ) . '</span>';

    return $stars;
}

function format_date( string $date_string ): string {
    try {
        $date = new DateTimeImmutable( $date_string );
    } catch ( Exception $e ) {
        error_log( '[Allemedia Reviews] Błędna data opinii: ' . $e->getMessage() );
        return \esc_html( $date_string );
    }

    if ( class_exists( 'IntlDateFormatter' ) ) {
        $formatter = new \IntlDateFormatter( 'pl_PL', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE );
        $formatter->setPattern( 'd MMMM y' );
        $formatted = $formatter->format( $date );
        if ( false !== $formatted ) {
            return \esc_html( $formatted );
        }
    }

    return \esc_html( date_i18n( 'j F Y', $date->getTimestamp() ) );
}

function is_woocommerce_product(): bool {
    return function_exists( 'is_product' ) && is_product();
}
