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
    wp_enqueue_style('storev1-style',get_stylesheet_uri(),[],wp_get_theme()->get('Version'));
    wp_enqueue_style('storev1-polish',get_template_directory_uri() . '/assets/storev1-polish.css',['storev1-style'],wp_get_theme()->get('Version'));
    if (is_singular() && comments_open() && get_option('thread_comments')) wp_enqueue_script('comment-reply');
}
add_action('wp_enqueue_scripts','storev1_assets');
