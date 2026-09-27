<?php
/**
 * Product price in catalogue cards. Variable products show their lowest
 * available offer; the selector on the product page still shows every offer.
 *
 * @see woocommerce/templates/loop/price.php
 */
defined('ABSPATH') || exit;

global $product;
if (!$product instanceof WC_Product) return;

$price_html = $product->get_price_html();
if ($product->is_type('variable')) {
    $variation_prices = $product->get_variation_prices(true);
    $prices = isset($variation_prices['display_price']) && is_array($variation_prices['display_price'])
        ? array_values(array_filter($variation_prices['display_price'], static function($value) {
            return $value !== '' && is_numeric($value);
        }))
        : [];
    if ($prices) $price_html = wc_price((float) min($prices));
}

if ($price_html !== '') echo '<span class="price">' . $price_html . '</span>';
