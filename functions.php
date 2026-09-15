<?php
if (! defined('ABSPATH')) exit;
require_once get_template_directory() . '/inc/class-storev1-updater.php';
function storev1_setup() { add_theme_support('title-tag'); add_theme_support('post-thumbnails'); add_theme_support('custom-logo'); add_theme_support('woocommerce'); register_nav_menus(['primary'=>'Menu principal']); }
add_action('after_setup_theme','storev1_setup');
function storev1_assets(){ wp_enqueue_style('storev1-style',get_stylesheet_uri(),[],wp_get_theme()->get('Version')); }
add_action('wp_enqueue_scripts','storev1_assets');
