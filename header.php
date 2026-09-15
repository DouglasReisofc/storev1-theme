<?php defined('ABSPATH') || exit; ?>
<!doctype html><html <?php language_attributes(); ?>><head>
<meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?></head><body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e('Pular para o conteúdo','storev1-theme'); ?></a>
<div class="loja1-topbar"><div class="loja1-shell loja1-topbar-inner"><span><?php echo esc_html(get_bloginfo('description')); ?></span><a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(get_bloginfo('name')); ?></a></div></div>
<header class="site-header loja1-header">
<div class="loja1-shell loja1-header-main">
<button type="button" class="loja1-menu-toggle" data-sv1-open aria-controls="sv1-drawer" aria-expanded="false" aria-label="Abrir menu"><span></span><span></span><span></span><b>Menu</b></button>
<?php storev1_brand(); ?>
<div class="loja1-search"><?php storev1_search(); ?></div>
<div class="loja1-header-actions"><?php if (class_exists('WooCommerce')) : ?>
<a class="loja1-account" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php storev1_icon('user'); ?><span><strong>Minha conta</strong><small>Acessar minha conta</small></span></a>
<a class="loja1-cart-link" href="<?php echo esc_url(wc_get_cart_url()); ?>" aria-label="Abrir carrinho"><?php storev1_icon('cart'); ?><span class="loja1-cart-copy"><strong>Carrinho</strong><small>Ver meus produtos</small></span><?php storev1_cart_count(); ?></a>
<?php endif; ?></div>
</div>
<div class="loja1-shell loja1-mobile-search"><?php storev1_search(); ?></div>
<nav class="loja1-primary-nav" aria-label="Produtos e categorias"><div class="loja1-shell sv1-nav-row">
<details class="sv1-categories"><summary><?php storev1_icon('menu'); ?> Todos os produtos <?php storev1_icon('chevron'); ?></summary><div class="sv1-category-list"><?php storev1_categories(); ?></div></details>
<?php wp_nav_menu(['theme_location'=>'primary','container'=>false,'menu_class'=>'loja1-menu','fallback_cb'=>false]); ?>
</div></nav>
</header>
<dialog id="sv1-drawer" class="sv1-drawer" aria-labelledby="sv1-drawer-title">
<div class="sv1-drawer-head"><strong id="sv1-drawer-title">Menu da loja</strong><button type="button" data-sv1-close aria-label="Fechar menu"><?php storev1_icon('close'); ?></button></div>
<div class="sv1-drawer-scroll"><?php storev1_search(); ?><details open class="sv1-mobile-categories"><summary>Produtos e categorias</summary><?php storev1_categories(); ?></details>
<?php if (class_exists('WooCommerce')) : ?><a class="sv1-drawer-account" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">Minha conta</a><?php endif; ?>
</div></dialog>
<main id="main-content" class="site-content loja1-shell">
