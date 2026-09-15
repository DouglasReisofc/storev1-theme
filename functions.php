<?php
if (! defined('ABSPATH')) exit;
require_once get_template_directory() . '/inc/class-storev1-updater.php';
function storev1_setup() {
    load_theme_textdomain('storev1-theme', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption','style','script']);
    add_theme_support('responsive-embeds');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    register_nav_menus(['primary'=>__('Menu principal','storev1-theme')]);
}
add_action('after_setup_theme','storev1_setup');
function storev1_assets(){
    wp_enqueue_style('storev1-storefront',get_template_directory_uri() . '/assets/storev1-storefront.css',[],wp_get_theme()->get('Version'));
    wp_enqueue_style('storev1-components',get_template_directory_uri() . '/assets/storev1-components.css',['storev1-storefront'],wp_get_theme()->get('Version'));
    if (class_exists('WooCommerce')) wp_enqueue_script('wc-cart-fragments');
    wp_enqueue_script('storev1-ui',get_template_directory_uri() . '/assets/storev1-ui.js',[],wp_get_theme()->get('Version'),true);
    if (is_singular() && comments_open() && get_option('thread_comments')) wp_enqueue_script('comment-reply');
}
add_action('wp_enqueue_scripts','storev1_assets');
add_filter('woocommerce_product_add_to_cart_text',function($text,$product){return $product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock() ? __('Comprar','storev1-theme') : $text;},10,2);
add_filter('woocommerce_product_single_add_to_cart_text',function(){return __('Comprar agora','storev1-theme');});
require_once get_template_directory() . '/inc/storefront.php';
