<?php
defined('ABSPATH') || exit;

// Add only missing presentation metadata; leave indexing and specialist SEO plugins in control.
function storev1_has_seo_provider() {
    return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION') || defined('SEOPRESS_VERSION') || defined('THE_SEO_FRAMEWORK_VERSION') || !apply_filters('storev1_fallback_metadata', true);
}
add_action('wp_head', function() {
    if (storev1_has_seo_provider() || is_404() || is_search() || is_feed()) return;
    if (function_exists('is_account_page') && (is_account_page() || is_cart() || is_checkout())) return;
    $description = '';
    if (is_front_page()) $description = get_bloginfo('description');
    elseif (is_tax() || is_category() || is_tag()) $description = term_description();
    elseif (is_singular() && !post_password_required()) {
        $post = get_queried_object();
        $description = $post->post_excerpt ?: $post->post_content;
    }
    if (!$description) $description = wp_get_document_title() . '. ' . get_bloginfo('description');
    $description = preg_replace('/\s+/u', ' ', wp_strip_all_tags(strip_shortcodes($description)));
    $description = wp_html_excerpt(trim($description), 160, '…');
    echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    // Core already emits singular canonicals. Never mirror arbitrary query parameters.
    if (!is_singular()) {
        $canonical = '';
        if (function_exists('is_shop') && is_shop()) $canonical = get_permalink(wc_get_page_id('shop'));
        elseif (is_tax() || is_category() || is_tag()) $canonical = get_term_link(get_queried_object());
        elseif (is_home()) $canonical = get_option('page_for_posts') ? get_permalink(get_option('page_for_posts')) : home_url('/');
        if ($canonical && !is_wp_error($canonical)) {
            if (get_query_var('paged') > 1) $canonical = get_pagenum_link(get_query_var('paged'), false);
            echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
        }
    }
}, 20);

add_filter('woocommerce_loop_add_to_cart_args', function($args, $product) {
    $args['attributes']['aria-label'] = wp_strip_all_tags($product->add_to_cart_text() . ': ' . $product->get_name());
    return $args;
}, 20, 2);

// WordPress generates srcset; sizes must reflect the actual catalogue grid, not 100vw.
add_filter('wp_get_attachment_image_attributes', function($attr, $attachment, $size) {
    if ($size === 'woocommerce_thumbnail') {
        $mobile = get_theme_mod('storev1_product_layout', 'grid') === 'grid' ? '46vw' : '92vw';
        $attr['sizes'] = '(max-width: 767px) ' . $mobile . ', (max-width: 1024px) 30vw, (min-width: 1800px) 18vw, 23vw';
    }
    return $attr;
}, 20, 3);
