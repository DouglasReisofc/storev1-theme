<?php
defined('ABSPATH') || exit;

// Discover the default LCP banner in the head instead of waiting for body parsing.
add_action('wp_head', function() {
    if (!(is_front_page() || (function_exists('is_shop') && is_shop())) || !get_theme_mod('storev1_banner_enabled', true)) return;
    for ($i = 1; $i <= 5; $i++) {
        if (get_theme_mod('storev1_banner_'.$i, '') || get_theme_mod('storev1_banner_'.$i.'_video', 0)) return;
    }
    $base = get_template_directory_uri().'/assets/banners/storev1-gold-piggybank';
    $revision = '?ver='.rawurlencode(wp_get_theme()->get('Version'));
    echo '<link rel="preload" as="image" href="'.esc_url($base.'-960.webp'.$revision).'" imagesrcset="'.esc_attr($base.'-480.webp'.$revision.' 480w, '.$base.'-960.webp'.$revision.' 960w, '.$base.'-1440.webp'.$revision.' 1440w').'" imagesizes="(max-width: 767px) 96vw, 45vw" fetchpriority="high">' . "\n";
}, 2);

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
        $description = storev1_share_description($post);
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
        // Product banners are intentionally wide (16:9). Some WooCommerce
        // installs generate a square `woocommerce_thumbnail`, which crops
        // the logo and makes the catalogue look broken. Prefer the existing
        // uncropped medium/full variants when they are available, while
        // retaining responsive candidates for performance.
        $square = isset($attr['width'], $attr['height']) && (int) $attr['width'] === (int) $attr['height'];
        $full = wp_get_attachment_image_src($attachment->ID, 'full');
        if ($square && $full && (int) $full[1] > (int) $full[2]) {
            $full_ratio = (int) $full[1] / max(1, (int) $full[2]);
            $uncropped = [];
            $sources = [];
            $meta = wp_get_attachment_metadata($attachment->ID);
            if (is_array($meta) && !empty($meta['sizes'])) {
                $base = trailingslashit(dirname(wp_get_attachment_url($attachment->ID)));
                foreach ($meta['sizes'] as $key => $candidate) {
                    if (in_array($key, ['thumbnail', 'woocommerce_thumbnail', 'woocommerce_gallery_thumbnail'], true)) continue;
                    if (empty($candidate['file']) || empty($candidate['width']) || empty($candidate['height']) || (int) $candidate['width'] <= (int) $candidate['height']) continue;
                    $candidate_ratio = (int) $candidate['width'] / max(1, (int) $candidate['height']);
                    if (abs($candidate_ratio - $full_ratio) / $full_ratio > 0.05) continue;
                    $width = (int) $candidate['width'];
                    $url = esc_url($base . $candidate['file']);
                    $uncropped[$width] = $url . ' ' . $width . 'w';
                    $sources[$width] = [$url, $width, (int) $candidate['height']];
                }
            }
            $full_width = (int) $full[1];
            $uncropped[$full_width] = esc_url($full[0]) . ' ' . $full_width . 'w';
            $sources[$full_width] = [esc_url($full[0]), $full_width, (int) $full[2]];
            ksort($uncropped, SORT_NUMERIC);
            ksort($sources, SORT_NUMERIC);

            // Do not depend on the WordPress `medium` preset: many stores
            // configure it as a hard square crop too. Pick the first genuine
            // landscape derivative that is large enough for a catalogue card.
            $preferred = end($sources);
            foreach ($sources as $source) {
                if ($source[1] >= 300) {
                    $preferred = $source;
                    break;
                }
            }
            $attr['src'] = $preferred[0];
            $attr['width'] = $preferred[1];
            $attr['height'] = $preferred[2];
            $attr['srcset'] = implode(', ', array_values($uncropped));
        }
        $mobile = get_theme_mod('storev1_product_layout', 'grid') === 'grid' ? '46vw' : '92vw';
        $attr['sizes'] = '(max-width: 767px) ' . $mobile . ', (max-width: 1024px) 30vw, (min-width: 1800px) 18vw, 23vw';
    }
    return $attr;
}, 20, 3);
