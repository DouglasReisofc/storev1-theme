<?php
defined('ABSPATH') || exit;

// Return a plain-text summary without merging adjacent HTML blocks.
function storev1_plain_summary($html) {
    $html = preg_replace('#</(?:p|h[1-6]|li|div)>|<br\s*/?>#i', ' ', (string) $html);
    return trim(preg_replace('/\s+/u', ' ', html_entity_decode(wp_strip_all_tags(strip_shortcodes($html)), ENT_QUOTES, 'UTF-8')));
}

function storev1_share_description($post) {
    $summary = $post->post_excerpt;
    if (!$summary) {
        // Prefer a real paragraph over repeating the heading in the snippet.
        if (preg_match('#<p\b[^>]*>(.*?)</p>#is', $post->post_content, $match)) $summary = $match[1];
        else $summary = $post->post_content;
    }
    return wp_html_excerpt(storev1_plain_summary($summary), 180, '…');
}

/** Cached, uncropped JPEG for social crawlers; the product image stays unchanged. */
function storev1_social_image($attachment_id) {
    $original = wp_get_attachment_image_src($attachment_id, 'full');
    if (!$original) return [];
    $fallback = ['url' => $original[0], 'width' => $original[1], 'height' => $original[2], 'type' => get_post_mime_type($attachment_id)];
    $file = get_attached_file($attachment_id);
    if (!$file || !is_readable($file) || !function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) return $fallback;
    $uploads = wp_upload_dir();
    if (!empty($uploads['error'])) return $fallback;
    $key = substr(hash('sha256', $file . '|' . filemtime($file) . '|' . filesize($file)), 0, 16);
    $name = absint($attachment_id) . '-' . $key . '-1200x630.jpg';
    $directory = $uploads['basedir'] . '/storev1-social';
    $destination = $directory . '/' . $name;
    if (!is_file($destination)) {
        // Bound decoding memory; never fetch remote URLs in a page request.
        $size = @getimagesize($file);
        if (!$size || $size[0] * $size[1] > 16000000 || filesize($file) > 12 * 1024 * 1024 || !wp_mkdir_p($directory)) return $fallback;
        $source = @imagecreatefromstring(file_get_contents($file));
        if (!$source) return $fallback;
        $canvas = imagecreatetruecolor(1200, 630);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        $scale = min(1200 / $size[0], 630 / $size[1]);
        $width = max(1, (int) round($size[0] * $scale));
        $height = max(1, (int) round($size[1] * $scale));
        imagecopyresampled($canvas, $source, (int) ((1200 - $width) / 2), (int) ((630 - $height) / 2), 0, 0, $width, $height, $size[0], $size[1]);
        $temporary = tempnam($directory, 'share-');
        $saved = $temporary && imagejpeg($canvas, $temporary, 88);
        imagedestroy($source);
        imagedestroy($canvas);
        if (!$saved || !@rename($temporary, $destination)) {
            if ($temporary && is_file($temporary)) wp_delete_file($temporary);
            return $fallback;
        }
        @chmod($destination, 0644);
    }
    return ['url' => $uploads['baseurl'] . '/storev1-social/' . $name, 'width' => 1200, 'height' => 630, 'type' => 'image/jpeg'];
}

add_action('wp_head', function() {
    if (storev1_has_seo_provider() || is_404() || is_search() || is_feed()) return;
    if (function_exists('is_account_page') && (is_account_page() || is_cart() || is_checkout())) return;
    $title = wp_get_document_title();
    $url = '';
    $description = '';
    $image_id = 0;
    $product = null;
    if (is_singular()) {
        $post = get_queried_object();
        if (post_password_required($post) || get_post_status($post) !== 'publish') return;
        $url = get_permalink($post);
        $description = storev1_share_description($post);
        $image_id = get_post_thumbnail_id($post);
        if (function_exists('is_product') && is_product()) $product = wc_get_product($post->ID);
    } elseif (is_front_page() || (function_exists('is_shop') && is_shop())) {
        $url = is_front_page() ? home_url('/') : get_permalink(wc_get_page_id('shop'));
        $description = storev1_site_description();
    } elseif (is_tax() || is_category() || is_tag()) {
        $term = get_queried_object();
        $url = get_term_link($term);
        $description = wp_html_excerpt(storev1_plain_summary(term_description()), 180, '…');
        if (!$description && function_exists('storev1_category_description')) $description = storev1_category_description($term);
        $image_id = absint(get_term_meta($term->term_id, 'thumbnail_id', true));
    }
    if (!$url || is_wp_error($url)) return;
    if (!is_singular() && get_query_var('paged') > 1) $url = get_pagenum_link(get_query_var('paged'), false);
    $image = $image_id ? storev1_social_image($image_id) : [];
    if (!$image && isset($term) && function_exists('storev1_category_image_url')) {
        $category_image = storev1_category_image_url($term);
        if ($category_image) $image = ['url' => $category_image, 'width' => 1438, 'height' => 905, 'type' => 'image/png'];
    }
    if (!$image && !$product) {
        $logo = absint(get_theme_mod('custom_logo'));
        if ($logo) $image = storev1_social_image($logo);
        else {
            $image = [
                'url' => get_template_directory_uri() . '/assets/brand/turbo-contas-logo.png',
                'width' => 1181,
                'height' => 480,
                'type' => 'image/png',
            ];
        }
    }
    $properties = ['og:type' => $product ? 'product' : 'website', 'og:title' => html_entity_decode($title, ENT_QUOTES, 'UTF-8'), 'og:description' => $description, 'og:url' => $url, 'og:site_name' => get_bloginfo('name'), 'og:locale' => get_locale()];
    if ($image) {
        $properties += ['og:image' => $image['url'], 'og:image:width' => $image['width'], 'og:image:height' => $image['height'], 'og:image:type' => $image['type'], 'og:image:alt' => $product ? $product->get_name() : $title];
    }
    if ($product && $product->get_price() !== '') {
        $properties['product:price:amount'] = wc_format_decimal(wc_get_price_to_display($product), wc_get_price_decimals());
        $properties['product:price:currency'] = get_woocommerce_currency();
    }
    foreach ($properties as $key => $value) {
        if ($value !== '') echo '<meta property="' . esc_attr($key) . '" content="' . esc_attr($value) . '">' . "\n";
    }
    $twitter = ['twitter:card' => $image ? 'summary_large_image' : 'summary', 'twitter:title' => $properties['og:title'], 'twitter:description' => $description];
    if ($image) $twitter += ['twitter:image' => $image['url'], 'twitter:image:alt' => $properties['og:image:alt']];
    foreach ($twitter as $key => $value) {
        if ($value !== '') echo '<meta name="' . esc_attr($key) . '" content="' . esc_attr($value) . '">' . "\n";
    }
}, 21);

// Optional discovery document. It does not change robots rules or promise ranking.
add_action('template_redirect', function() {
    $path = wp_parse_url(wp_unslash($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if ($path !== wp_parse_url(home_url('/llms.txt'), PHP_URL_PATH)) return;
    if (!(int) get_option('blog_public')) { status_header(404); exit; }
    status_header(200);
    header('Content-Type: text/plain; charset=UTF-8');
    header('X-Robots-Tag: noindex');
    $name = storev1_plain_summary(get_bloginfo('name'));
    echo '# ' . $name . "\n\n";
    echo '> ' . storev1_site_description() . "\n\n";
    echo "## Loja\n\n- [Página inicial](" . esc_url_raw(home_url('/')) . ")\n";
    if (function_exists('wc_get_page_id') && wc_get_page_id('shop') > 0) echo '- [Catálogo de produtos](' . esc_url_raw(get_permalink(wc_get_page_id('shop'))) . ")\n";
    $sitemap = function_exists('get_sitemap_url') ? get_sitemap_url('index') : '';
    if ($sitemap) echo '- [Sitemap](' . esc_url_raw($sitemap) . ")\n";
    echo "\nConsulte cada página de produto para preço, duração e disponibilidade atuais.\n";
    exit;
}, 0);
