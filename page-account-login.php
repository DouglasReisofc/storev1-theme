<?php
/** Template Name: StoreV1 — Entrar */
defined('ABSPATH') || exit;
get_header();
echo '<article class="sv1-page sv1-auth-page"><h1 class="screen-reader-text">Entrar</h1>';
if (class_exists('WooCommerce')) echo do_shortcode('[woocommerce_my_account]');
echo '</article>';
get_footer();
