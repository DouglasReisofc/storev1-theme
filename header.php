<?php defined('ABSPATH') || exit; ?>
<!doctype html><html <?php language_attributes(); ?>><head>
<meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?></head><body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e('Pular para o conteúdo','storev1-theme'); ?></a>
<header class="site-header loja1-header">
<div class="loja1-shell loja1-header-main">
<button type="button" class="loja1-menu-toggle" data-sv1-open aria-controls="sv1-drawer" aria-expanded="false" aria-label="Abrir menu"><span></span><span></span><span></span><b>Menu</b></button>
<?php storev1_brand(); ?>
<div class="loja1-search"><?php storev1_search(); ?></div>
<div class="loja1-header-actions"><?php if (class_exists('WooCommerce') && storev1_account_enabled()) : ?>
<a class="loja1-account" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php storev1_icon('user'); ?><span><strong>Minha conta</strong><small>Acessar minha conta</small></span></a>
<?php endif; ?><?php if (class_exists('WooCommerce')) : ?>
<a class="loja1-cart-link" href="<?php echo esc_url(wc_get_cart_url()); ?>"><?php storev1_icon('cart'); ?><span class="loja1-cart-copy"><strong>Carrinho</strong><small>Ver meus produtos</small></span><span class="screen-reader-text">Abrir carrinho</span><?php storev1_cart_count(); ?></a>
<?php endif; ?></div>
</div>
</header>
<?php
ob_start();
if (is_front_page() || (function_exists('is_shop') && is_shop())) storev1_mobile_banner();
$storev1_promo = ob_get_clean();
?>
<div class="loja1-shell sv1-discovery<?php echo $storev1_promo ? ' sv1-discovery-with-promo' : ''; ?>">
<nav class="loja1-primary-nav" aria-label="Produtos e categorias"><div class="sv1-nav-row">
<div class="sv1-category-rail" data-category-rail>
<button type="button" class="sv1-rail-arrow" data-category-prev aria-label="Categorias anteriores" aria-controls="sv1-category-track" hidden>‹</button>
<?php storev1_category_menu_fallback(); ?>
<button type="button" class="sv1-rail-arrow" data-category-next aria-label="Próximas categorias" aria-controls="sv1-category-track" hidden>›</button>
</div>
</div></nav>
<?php echo $storev1_promo; // Trusted markup rendered by the theme above. ?>
</div>
<dialog id="sv1-drawer" class="sv1-drawer" aria-labelledby="sv1-drawer-title">
<div class="sv1-drawer-head"><strong id="sv1-drawer-title">Menu da loja</strong><button type="button" data-sv1-close aria-label="Fechar menu"><?php storev1_icon('close'); ?></button></div>
<div class="sv1-drawer-scroll"><?php storev1_search(); ?><?php if(class_exists('WooCommerce')) wc_get_template('loop/orderby.php',['sv1_drawer'=>true]); else storev1_categories(); ?>
<?php if (class_exists('WooCommerce') && storev1_account_enabled()) : ?><a class="sv1-drawer-account" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">Minha conta</a><?php endif; ?>
</div></dialog>
<main id="main-content" class="site-content loja1-shell">
