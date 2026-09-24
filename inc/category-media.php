<?php
defined('ABSPATH') || exit;

function storev1_category_image_url($term) {
    $term = is_object($term) ? $term : get_term($term, 'product_cat');
    if (!$term || is_wp_error($term)) return '';
    $thumbnail_id = absint(get_term_meta($term->term_id, 'thumbnail_id', true));
    if ($thumbnail_id) {
        $url = wp_get_attachment_image_url($thumbnail_id, 'woocommerce_thumbnail');
        if ($url) return $url;
    }
    $slug = sanitize_title($term->slug . '-' . $term->name);
    $asset = '';
    if (strpos($slug, '30-dias') !== false) $asset = 'contas-30-dias.png';
    elseif (strpos($slug, '90-dias') !== false) $asset = 'contas-90-dias.png';
    elseif (strpos($slug, '1-ano') !== false || strpos($slug, '365') !== false) $asset = 'contas-1-ano.png';
    elseif (strpos($slug, 'especial') !== false) $asset = 'contas-especiais.png';
    return $asset ? get_template_directory_uri() . '/assets/categories/' . $asset : '';
}

function storev1_category_description($term) {
    $description = trim(wp_strip_all_tags((string) $term->description));
    if ($description) return $description;
    $name = $term->name;
    if (stripos($name, '30') !== false) return 'Contas premium com acesso por 30 dias, entrega rápida e suporte para ativação.';
    if (stripos($name, '90') !== false) return 'Contas premium com acesso por 90 dias, envio imediato e suporte durante o período contratado.';
    if (stripos($name, 'ano') !== false || stripos($name, '365') !== false) return 'Contas premium com validade de 1 ano, acesso digital e entrega segura após a confirmação.';
    if (stripos($name, 'especial') !== false) return 'Seleção de contas premium especiais, com opções diferenciadas e entrega digital rápida.';
    return 'Encontre contas premium digitais com entrega rápida, acesso seguro e suporte especializado.';
}

// Once the bundled artwork is present in the media library, persist it as the
// real WooCommerce category thumbnail instead of relying only on the theme
// fallback. This is idempotent and retries until all four files are available.
add_action('admin_init', function() {
    if (get_option('storev1_category_assets_seeded') === '1' || !taxonomy_exists('product_cat')) return;
    $map = [
        'contas-30-dias' => 'contas-30-dias',
        'contas-90-dias' => 'contas-90-dias',
        'contas-1-ano' => 'contas-1-ano',
        'contas-especiais' => 'contas-especiais',
    ];
    $complete = true;
    foreach ($map as $term_slug => $attachment_slug) {
        $term = get_term_by('slug', $term_slug, 'product_cat');
        $attachment = get_page_by_path($attachment_slug, OBJECT, 'attachment');
        if (!$term || !$attachment) { $complete = false; continue; }
        update_term_meta($term->term_id, 'thumbnail_id', (int) $attachment->ID);
    }
    if ($complete) update_option('storev1_category_assets_seeded', '1', false);
});
