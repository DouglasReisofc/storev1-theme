<?php
defined('ABSPATH') || exit;

add_filter('body_class', function($classes) { $classes[] = 'loja1-contasvip-theme'; return $classes; });

function storev1_icon($name) {
    $paths = [
        'cart'=>'<circle cx="9" cy="20" r="1"/><circle cx="19" cy="20" r="1"/><path d="M2 3h3l3 13h12l2-9H6"/>',
        'user'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
        'home'=>'<path d="m3 10 9-7 9 7v11h-6v-7H9v7H3z"/>',
        'menu'=>'<path d="M3 6h18M3 12h18M3 18h18"/>',
        'close'=>'<path d="m6 6 12 12M18 6 6 18"/>',
        'chevron'=>'<path d="m6 9 6 6 6-6"/>',
        'trash'=>'<path d="M4 7h16M10 11v6M14 11v6M9 7V4h6v3m-9 0 1 13h8l1-13"/>',
    ];
    if (!isset($paths[$name])) return;
    echo '<svg class="sv1-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$name] . '</svg>';
}

function storev1_brand($extra = '') {
    $name = get_bloginfo('name');
    echo '<a class="loja1-logo ' . esc_attr($extra) . '" href="' . esc_url(home_url('/')) . '" aria-label="' . esc_attr($name) . '">';
    $logo = absint(get_theme_mod('custom_logo'));
    if ($logo) {
        echo wp_get_attachment_image($logo, 'full', false, ['class'=>'sv1-brand-image','alt'=>$name]);
    } else {
        preg_match('/^./us', $name, $initial);
        echo '<span class="storezap-mark" aria-hidden="true"><span>' . esc_html($initial[0] ?? 'S') . '</span><i>ϟ</i></span>';
        echo '<span class="storezap-wordmark">';
        if (strtolower($name) === 'storezap') echo '<strong>STORE</strong><b>ZAP</b>';
        else echo '<strong>' . esc_html($name) . '</strong>';
        echo '<small>' . esc_html__('Loja online','storev1-theme') . '</small></span>';
    }
    echo '</a>';
}

function storev1_search() {
    echo '<form role="search" method="get" action="' . esc_url(home_url('/')) . '" class="sv1-search-form">';
    echo '<label class="screen-reader-text" for="' . esc_attr($id = wp_unique_id('sv1-search-')) . '">Pesquisar produtos</label>';
    echo '<input id="' . esc_attr($id) . '" type="search" name="s" placeholder="Pesquisar produtos…" value="' . esc_attr(get_search_query()) . '">';
    if (class_exists('WooCommerce')) echo '<input type="hidden" name="post_type" value="product">';
    echo '<button type="submit" aria-label="Pesquisar">Pesquisar</button></form>';
}

function storev1_categories() {
    if (!class_exists('WooCommerce')) { wp_page_menu(['show_home'=>true]); return; }
    echo '<ul class="sv1-category-links"><li><a href="' . esc_url(wc_get_page_permalink('shop')) . '">Todos os produtos</a></li>';
    wp_list_categories(['taxonomy'=>'product_cat','title_li'=>'','hide_empty'=>true,'show_count'=>false,'hierarchical'=>true]);
    echo '</ul>';
}

function storev1_cart_count() {
    $count = function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
    echo '<span class="sv1-cart-count" aria-label="' . esc_attr(sprintf(__('%d itens no carrinho','storev1-theme'),$count)) . '">' . esc_html($count) . '</span>';
}
add_filter('woocommerce_add_to_cart_fragments', function($fragments) {
    ob_start(); storev1_cart_count(); $fragments['span.sv1-cart-count'] = ob_get_clean(); return $fragments;
});

function storev1_mobile_banner() {
    $images = [];
    for ($i=1; $i<=3; $i++) { $url = esc_url(get_theme_mod('storev1_banner_'.$i, '')); if ($url) $images[] = $url; }
    if (!$images) return;
    echo '<section class="sv1-mobile-banner" data-sv1-carousel aria-label="Destaques da loja"><div class="sv1-carousel-track">';
    foreach ($images as $i=>$url) echo '<a class="sv1-carousel-slide'.($i===0?' is-active':''). '" href="'.esc_url(wc_get_page_permalink('shop')).'"'.($i===0?'':' tabindex="-1"').' style="background-image:url('.esc_url($url).')"><span class="screen-reader-text">Destaque '.($i+1).'</span></a>';
    echo '</div>';
    if (count($images)>1) { echo '<button type="button" class="sv1-carousel-prev" data-carousel-prev aria-label="Imagem anterior">‹</button><button type="button" class="sv1-carousel-next" data-carousel-next aria-label="Próxima imagem">›</button><div class="sv1-carousel-dots">'; foreach ($images as $i=>$url) echo '<button type="button" data-carousel-dot="'.absint($i).'" aria-label="Ir para imagem '.($i+1).'"'.($i===0?' aria-current="true"':'').'></button>'; echo '</div>'; }
    echo '</section>';
}
