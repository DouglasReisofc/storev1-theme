<?php
defined('ABSPATH') || exit;

// Public, read-only catalog search. Never includes drafts, private or search-hidden products.
function storev1_catalog_search() {
    if (!class_exists('WooCommerce')) wp_send_json_error([], 503);
    $query = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
    $query = mb_substr($query, 0, 100);
    $visibility = wc_get_product_visibility_term_ids();
    $exclude = [$visibility['exclude-from-search']];
    if ('yes' === get_option('woocommerce_hide_out_of_stock_items')) $exclude[] = $visibility['outofstock'];
    $products = new WP_Query([
        'post_type'=>'product', 'post_status'=>'publish', 'has_password'=>false,
        's'=>$query, 'posts_per_page'=>24, 'orderby'=>'title', 'order'=>'ASC',
        'tax_query'=>[['taxonomy'=>'product_visibility','field'=>'term_taxonomy_id','terms'=>$exclude,'operator'=>'NOT IN']],
    ]);
    ob_start();
    wc_set_loop_prop('columns', 4);
    woocommerce_product_loop_start();
    while ($products->have_posts()) { $products->the_post(); wc_get_template_part('content','product'); }
    woocommerce_product_loop_end();
    $html = ob_get_clean();
    wp_reset_postdata();
    wp_send_json_success(['html'=>$html, 'total'=>(int)$products->found_posts, 'shown'=>(int)$products->post_count]);
}
add_action('wp_ajax_storev1_catalog_search','storev1_catalog_search');
add_action('wp_ajax_nopriv_storev1_catalog_search','storev1_catalog_search');
