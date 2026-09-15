<?php defined('ABSPATH') || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e('Pular para o conteúdo','storev1-theme'); ?></a>
<header class="site-header"><div class="sv1-wrap sv1-header">
    <div class="site-branding"><?php if (has_custom_logo()) { the_custom_logo(); } else { ?><div class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a></div><?php } ?></div>
    <nav class="main-navigation" aria-label="<?php esc_attr_e('Menu principal','storev1-theme'); ?>"><?php wp_nav_menu(['theme_location'=>'primary','fallback_cb'=>'wp_page_menu']); ?></nav>
    <?php if (class_exists('WooCommerce')) : ?><div class="sv1-header-actions"><a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php esc_html_e('Minha conta','storev1-theme'); ?></a><a class="sv1-cart-link" href="<?php echo esc_url(wc_get_cart_url()); ?>"><?php esc_html_e('Carrinho','storev1-theme'); ?></a></div><?php endif; ?>
</div></header>
<main id="main-content" class="site-main sv1-wrap">
