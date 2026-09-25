<?php defined('ABSPATH') || exit; ?>
</main>
<footer class="loja1-footer"><div class="loja1-shell loja1-footer-grid">
<div><?php storev1_brand('loja1-footer-logo'); ?><p><?php echo esc_html(function_exists('storev1_site_description') ? storev1_site_description() : get_bloginfo('description')); ?></p></div>
<div><h2><?php echo class_exists('WooCommerce') && storev1_account_enabled() ? 'Minha conta' : 'Informações'; ?></h2><?php if (class_exists('WooCommerce') && storev1_account_enabled()) : ?><a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">Acessar minha conta</a><a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>">Histórico de compras</a><?php endif; ?><?php foreach (['politica-de-privacidade'=>'Privacidade','termos-de-uso'=>'Termos de uso','entrega-e-reembolso'=>'Entrega e reembolso','contato-e-suporte'=>'Contato e suporte'] as $slug=>$label) : $page=get_page_by_path($slug); if ($page && get_post_status($page)==='publish') : ?><a href="<?php echo esc_url(get_permalink($page)); ?>"><?php echo esc_html($label); ?></a><?php endif; endforeach; ?></div>
<div><h2>Explore a loja</h2><?php if(class_exists('WooCommerce')) : ?><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Todos os produtos</a><a href="<?php echo esc_url(wc_get_cart_url()); ?>">Meu carrinho</a><?php endif; ?><a href="<?php echo esc_url(home_url('/')); ?>">Início</a></div>
</div><div class="loja1-footer-bottom"><div class="loja1-shell">&copy; <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html(get_bloginfo('name')); ?> <span>Todos os direitos reservados.</span></div></div></footer>
<?php if(class_exists('WooCommerce')) : ?>
<div class="sv1-floating-cart-slot"><?php storev1_floating_cart_markup(); ?></div>
<nav class="loja1-mobile-toolbar<?php echo storev1_account_enabled() ? '' : ' loja1-mobile-toolbar--without-account'; ?>" aria-label="Atalhos da loja">
<a href="<?php echo esc_url(home_url('/')); ?>"><?php storev1_icon('home'); ?><small>Início</small></a>
<button type="button" data-sv1-open aria-controls="sv1-drawer" aria-expanded="false"><?php storev1_icon('menu'); ?><small>Produtos</small></button>
<?php if (storev1_account_enabled()) : ?><a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php storev1_icon('user'); ?><small>Minha conta</small></a><?php endif; ?>
<a href="<?php echo esc_url(wc_get_cart_url()); ?>"><?php storev1_icon('cart'); ?><small>Carrinho</small><?php storev1_cart_count(); ?></a>
</nav><?php endif; ?>
<?php wp_footer(); ?></body></html>
