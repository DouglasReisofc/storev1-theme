<?php
defined('ABSPATH') || exit;

/**
 * Describe WooCommerce variable products as a ProductGroup for Google.
 *
 * WooCommerce already emits Product/AggregateOffer JSON-LD. This companion
 * graph adds the relationship between the parent product and its offers
 * without replacing WooCommerce's native schema or creating fake URLs.
 */
add_action('wp_head', function() {
    if (!function_exists('is_product') || !is_product() || !function_exists('wc_get_product')) return;
    $product = wc_get_product(get_queried_object_id());
    if (!$product instanceof WC_Product || !$product->is_type('variable')) return;

    $parent_url = get_permalink($product->get_id());
    $parent_sku = trim((string) $product->get_sku());
    $group_id = $parent_sku !== '' ? $parent_sku : 'wc-product-' . $product->get_id();
    $variants = [];

    foreach ($product->get_children() as $variation_id) {
        $variation = wc_get_product($variation_id);
        if (!$variation instanceof WC_Product_Variation || $variation->get_status() !== 'publish') continue;

        // The selectable offer is authoritative. The legacy product title is
        // only a migration fallback and may be stale after an offer is edited.
        $legacy_id = absint($variation->get_meta('_storezap_legacy_product_id', true));
        $legacy = $legacy_id ? wc_get_product($legacy_id) : null;
        $name = trim((string) $variation->get_attribute('pa_oferta'));
        if ($name === '') {
            $attributes = $variation->get_variation_attributes(false);
            $labels = array_values(array_filter(array_map('wc_clean', $attributes)));
            $name = implode(' - ', $labels);
        }
        if ($name === '' && $legacy) $name = $legacy->get_name();
        if ($name === '') $name = $product->get_name();

        $variant = [
            '@type' => 'Product',
            'name' => wp_strip_all_tags($name),
            'inProductGroupWithID' => $group_id,
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => get_woocommerce_currency(),
                'availability' => $variation->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => $parent_url,
            ],
        ];
        $price = $variation->get_price();
        if ($price !== '') $variant['offers']['price'] = wc_format_decimal($price, wc_get_price_decimals());
        $sku = trim((string) ($variation->get_sku() ?: $variation->get_meta('_storezap_legacy_sku', true)));
        if ($sku !== '') $variant['sku'] = $sku;
        $image_id = absint($variation->get_image_id() ?: $product->get_image_id());
        if ($image_id) {
            $image = wp_get_attachment_image_url($image_id, 'full');
            if ($image) $variant['image'] = [$image];
        }
        $variants[] = $variant;
    }
    if (!$variants) return;

    $graph = [
        '@context' => 'https://schema.org',
        '@type' => 'ProductGroup',
        '@id' => trailingslashit($parent_url) . '#product-group',
        'name' => wp_strip_all_tags($product->get_name()),
        'url' => $parent_url,
        'productGroupID' => $group_id,
        'variesBy' => ['https://schema.org/name'],
        'hasVariant' => $variants,
    ];
    echo '<script type="application/ld+json">' . wp_json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
}, 16);

// WooCommerce can pass HTML through esc_html() before JSON-LD generation on
// some versions, producing literal &lt;p&gt; in search-engine descriptions.
add_filter('woocommerce_structured_data_product', function($markup, $product) {
    if (!$product instanceof WC_Product) return $markup;
    $description = storev1_plain_summary($product->get_short_description() ?: $product->get_description());
    if ($description !== '') $markup['description'] = $description;
    return $markup;
}, 20, 2);
