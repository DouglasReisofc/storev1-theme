<?php defined('ABSPATH') || exit; ?>
</main>
<footer class="loja1-footer"><div class="loja1-shell loja1-footer-grid">
<div><?php storev1_brand('loja1-footer-logo'); ?><p><?php echo esc_html(get_bloginfo('description')); ?></p></div>
<div><h2>Minha conta</h2><?php if(class_exists('WooCommerce')) : ?><a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">Acessar minha conta</a><a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>">Meus pedidos</a><?php endif; ?><?php if(get_privacy_policy_url()) : ?><a href="<?php echo esc_url(get_privacy_policy_url()); ?>">Privacidade</a><?php endif; ?></div>
<div><h2>Explore a loja</h2><?php if(class_exists('WooCommerce')) : ?><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Todos os produtos</a><a href="<?php echo esc_url(wc_get_cart_url()); ?>">Meu carrinho</a><?php endif; ?><a href="<?php echo esc_url(home_url('/')); ?>">Início</a></div>
</div><div class="loja1-footer-bottom"><div class="loja1-shell">&copy; <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html(get_bloginfo('name')); ?> <span>Todos os direitos reservados.</span></div></div></footer>
<?php if(class_exists('WooCommerce')) : ?>
<a class="sv1-floating-cart" href="<?php echo esc_url(wc_get_cart_url()); ?>" aria-label="Abrir carrinho"><?php storev1_icon('cart'); ?><span>Carrinho</span><?php storev1_cart_count(); ?></a>
<nav class="loja1-mobile-toolbar" aria-label="Atalhos da loja">
<a href="<?php echo esc_url(home_url('/')); ?>"><?php storev1_icon('home'); ?><small>Início</small></a>
<button type="button" data-sv1-open aria-controls="sv1-drawer" aria-expanded="false"><?php storev1_icon('menu'); ?><small>Produtos</small></button>
<a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php storev1_icon('user'); ?><small>Minha conta</small></a>
<a href="<?php echo esc_url(wc_get_cart_url()); ?>" aria-label="Abrir carrinho"><?php storev1_icon('cart'); ?><small>Carrinho</small><?php storev1_cart_count(); ?></a>
</nav><?php endif; ?>
<?php wp_footer(); ?></body></html>
