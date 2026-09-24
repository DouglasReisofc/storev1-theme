<?php
if (! defined('ABSPATH')) exit;
require_once get_template_directory() . '/inc/class-storev1-updater.php';
require_once get_template_directory() . '/inc/category-media.php';
function storev1_setup() {
    load_theme_textdomain('storev1-theme', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', [
        'height'      => 180,
        'width'       => 600,
        'flex-width'  => true,
        'flex-height' => true,
    ]);
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption','style','script']);
    add_theme_support('responsive-embeds');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-slider');
    add_theme_support('storev1-storefront-modals');
    register_nav_menus(['primary'=>__('Menu principal','storev1-theme')]);
}
add_action('after_setup_theme','storev1_setup');

// A fresh WooCommerce installation has no primary menu yet. Keep the
// catalogue navigation visible by falling back to the non-empty product
// categories until the administrator creates a custom menu.
function storev1_category_menu_fallback() {
    if (!taxonomy_exists('product_cat')) return;
    $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0]);
    if (is_wp_error($terms) || !$terms) return;
    echo '<ul id="sv1-category-track" class="loja1-menu">';
    foreach ($terms as $term) {
        if (stripos($term->name, '60 dias') !== false) continue;
        printf('<li><a href="%s">%s</a></li>', esc_url(get_term_link($term)), esc_html($term->name));
    }
    echo '</ul>';
}

/**
 * Ship the Turbo Contas identity with the theme so a fresh installation has
 * a complete favicon/app-icon set before the Customizer is configured.
 */
function storev1_brand_head() {
    $uri = trailingslashit(get_template_directory_uri()) . 'assets/brand/';
    echo '<link rel="icon" href="' . esc_url($uri . 'favicon.svg') . '" type="image/svg+xml">' . "\n";
    echo '<link rel="icon" href="' . esc_url($uri . 'favicon.ico') . '" sizes="any">' . "\n";
    echo '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url($uri . 'favicon-32.png') . '">' . "\n";
    echo '<link rel="icon" type="image/png" sizes="16x16" href="' . esc_url($uri . 'favicon-16.png') . '">' . "\n";
    echo '<link rel="icon" type="image/png" sizes="96x96" href="' . esc_url($uri . 'favicon-96.png') . '">' . "\n";
    echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url($uri . 'apple-touch-icon.png') . '">' . "\n";
    echo '<link rel="manifest" href="' . esc_url($uri . 'site.webmanifest') . '">' . "\n";
    echo '<meta name="application-name" content="Turbo Contas">' . "\n";
    echo '<meta name="apple-mobile-web-app-title" content="Turbo Contas">' . "\n";
    echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="theme-color" content="#102449">' . "\n";
}
add_action('wp_head', 'storev1_brand_head', 1);
// Hide only the generic shop heading; retain category and search context.
add_filter('woocommerce_show_page_title', function($show) {
    return is_shop() && !is_search() ? false : $show;
});
add_filter('body_class', function($classes) {
    if (get_theme_mod('storev1_product_layout', 'grid') === 'grid') $classes[] = 'sv1-compact-grid';
    return $classes;
});
function storev1_assets(){
    wp_enqueue_style('storev1-storefront',get_template_directory_uri() . '/assets/storev1-storefront.css',[],wp_get_theme()->get('Version'));
    wp_enqueue_style('storev1-components',get_template_directory_uri() . '/assets/storev1-components.css',['storev1-storefront'],wp_get_theme()->get('Version'));
    wp_enqueue_style('storev1-gold',get_template_directory_uri() . '/assets/storev1-gold.css',['storev1-components'],wp_get_theme()->get('Version'));
    wp_enqueue_style('storev1-modals',get_template_directory_uri() . '/assets/storev1-modals.css',['storev1-gold'],wp_get_theme()->get('Version'));
    if (function_exists('is_account_page') && is_account_page()) wp_enqueue_style('storev1-account',get_template_directory_uri().'/assets/storev1-account.css',['storev1-gold'],wp_get_theme()->get('Version'));
    // The public home/catalog pages render the cart count server-side. Avoid
    // loading WooCommerce's polling fragment bundle there; keep it on pages
    // where cart/account state can change without a full navigation.
    if (class_exists('WooCommerce') && !is_front_page() && !is_shop() && !is_product_category() && !is_product_tag()) {
        wp_enqueue_script('wc-cart-fragments');
    }
    wp_enqueue_script('storev1-ui',get_template_directory_uri() . '/assets/storev1-ui.js',[],wp_get_theme()->get('Version'),true);
    wp_enqueue_script('storev1-shopping',get_template_directory_uri() . '/assets/storev1-shopping.js',[],wp_get_theme()->get('Version'),true);
    if (function_exists('is_product') && is_product()) wp_enqueue_script('storev1-variations',get_template_directory_uri() . '/assets/storev1-variations.js',['jquery','wc-add-to-cart-variation'],wp_get_theme()->get('Version'),true);
    if (class_exists('WooCommerce')) {
        wp_enqueue_script('storev1-lottie','https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js',[],'5.12.2',true);
        wp_enqueue_script('storev1-checkout-lottie',get_template_directory_uri() . '/assets/storev1-checkout-lottie.js',['storev1-lottie'],wp_get_theme()->get('Version'),true);
    }
    wp_enqueue_script('storev1-discovery',get_template_directory_uri() . '/assets/storev1-discovery.js',[],wp_get_theme()->get('Version'),true);
    wp_enqueue_script('storev1-search',get_template_directory_uri() . '/assets/storev1-search.js',[],wp_get_theme()->get('Version'),true);
    if (is_singular() && comments_open() && get_option('thread_comments')) wp_enqueue_script('comment-reply');
}
add_action('wp_enqueue_scripts','storev1_assets');

// Allow a host-level page cache to reuse anonymous storefront responses while
// never caching logged-in or session-bound WooCommerce pages.
add_action('send_headers', function() {
    if (is_user_logged_in() || is_admin() || headers_sent() || !class_exists('WooCommerce')) return;
    $public_storefront = is_front_page() || is_shop() || is_product_category() || is_product_tag();
    if (!$public_storefront) return;
    header('Cache-Control: public, max-age=0, s-maxage=300, stale-while-revalidate=30');
    header('Vary: Accept-Encoding');
}, 20);
add_action('customize_register', function($wp_customize) {
    $wp_customize->add_section('storev1_catalog', ['title'=>__('Catálogo de produtos','storev1-theme'),'priority'=>30]);
    $wp_customize->add_setting('storev1_product_layout',['default'=>'grid','sanitize_callback'=>function($value){return in_array($value,['grid','cards'],true)?$value:'grid';}]);
    $wp_customize->add_control('storev1_product_layout',['label'=>'Layout dos produtos no celular','description'=>'Escolha Grade (duas colunas) ou Cartões (uma coluna).','section'=>'storev1_catalog','type'=>'radio','choices'=>['grid'=>'Grade','cards'=>'Cartões']]);
    $wp_customize->add_section('storev1_banner', ['title'=>__('Banners da loja','storev1-theme'),'description'=>'Carrossel no início e na loja, em desktop e celular. Envie imagens horizontais; use a mesma proporção em todas. Sem imagens próprias, mostramos duas capas demonstrativas.','priority'=>35]);
    $wp_customize->add_setting('storev1_banner_enabled',['default'=>true,'sanitize_callback'=>'rest_sanitize_boolean']);
    $wp_customize->add_control('storev1_banner_enabled',['label'=>'Exibir carrossel','section'=>'storev1_banner','type'=>'checkbox']);
    $wp_customize->add_setting('storev1_show_reviews',['default'=>false,'sanitize_callback'=>'rest_sanitize_boolean']);
    $wp_customize->add_control('storev1_show_reviews',['label'=>'Mostrar avaliações nos produtos','section'=>'storev1_banner','type'=>'checkbox']);
    $wp_customize->add_section('storev1_support', ['title'=>__('Suporte via WhatsApp','storev1-theme'),'description'=>'Ative, altere ou remova o botão flutuante exibido na loja.','priority'=>40]);
    $wp_customize->add_setting('storev1_whatsapp_enabled',['default'=>true,'sanitize_callback'=>'rest_sanitize_boolean']);
    $wp_customize->add_control('storev1_whatsapp_enabled',['label'=>'Exibir botão flutuante do WhatsApp','section'=>'storev1_support','type'=>'checkbox']);
    $wp_customize->add_setting('storev1_whatsapp_phone',['default'=>'5511971294939','sanitize_callback'=>function($value){return preg_replace('/[^0-9]/','',(string)$value);}]);
    $wp_customize->add_control('storev1_whatsapp_phone',['label'=>'Número do WhatsApp (com DDI)','description'=>'Somente números. Exemplo: 5511999999999.','section'=>'storev1_support','type'=>'text']);
    $wp_customize->add_setting('storev1_whatsapp_message',['default'=>'Olá, Estou em seu site e gostaria de informações a respeito de seus produtos','sanitize_callback'=>'sanitize_textarea_field']);
    $wp_customize->add_control('storev1_whatsapp_message',['label'=>'Mensagem inicial','section'=>'storev1_support','type'=>'textarea']);
    for ($i=1; $i<=5; $i++) {
        $setting = 'storev1_banner_' . $i;
        $wp_customize->add_setting($setting, ['default'=>'','sanitize_callback'=>'esc_url_raw']);
        $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, $setting, ['label'=>sprintf(__('Imagem %d','storev1-theme'),$i),'section'=>'storev1_banner']));
        $wp_customize->add_setting($setting.'_link',['default'=>'','sanitize_callback'=>'esc_url_raw']);
        $wp_customize->add_control($setting.'_link',['label'=>'Link do banner '.$i,'section'=>'storev1_banner','type'=>'url']);
        $wp_customize->add_setting($setting.'_alt',['default'=>'','sanitize_callback'=>'sanitize_text_field']);
        $wp_customize->add_control($setting.'_alt',['label'=>'Descrição da imagem '.$i,'section'=>'storev1_banner','type'=>'text']);
        $wp_customize->add_setting($setting.'_cta',['default'=>'Ver produtos','sanitize_callback'=>'sanitize_text_field']);
        $wp_customize->add_control($setting.'_cta',['label'=>'Texto do botão '.$i,'section'=>'storev1_banner','type'=>'text']);
        $wp_customize->add_setting($setting.'_video',['default'=>0,'sanitize_callback'=>'absint']);
        $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize,$setting.'_video',['label'=>'Vídeo do banner '.$i.' (opcional)','description'=>'MP4 ou WebM compatível com o navegador. Substitui a imagem; reproduz sem som. A imagem vira capa de carregamento.','mime_type'=>'video','section'=>'storev1_banner']));
    }
});
add_filter('woocommerce_product_add_to_cart_text',function($text,$product){return $product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock() ? __('Comprar','storev1-theme') : $text;},10,2);
add_filter('woocommerce_product_single_add_to_cart_text',function(){return __('Comprar agora','storev1-theme');});
require_once get_template_directory() . '/inc/storefront.php';
require_once get_template_directory() . '/inc/storefront-modals.php';
require_once get_template_directory() . '/inc/catalog-search.php';
require_once get_template_directory() . '/inc/site-quality.php';
require_once get_template_directory() . '/inc/social-metadata.php';
require_once get_template_directory() . '/inc/account.php';
require_once get_template_directory() . '/inc/companion-installer.php';
// Preserve WooCommerce validation and POST/redirect flow; flag only a successful addition.
add_action('woocommerce_add_to_cart', function() {
    if (isset($_POST['add-to-cart']) && !wp_doing_ajax() && WC()->session) {
        WC()->session->set('storev1_open_cart', time());
    }
});
add_action('wp_footer', function() {
    if (!function_exists('WC') || !WC()->session) return;
    $added = WC()->session->get('storev1_open_cart');
    if (!$added) return;
    WC()->session->__unset('storev1_open_cart');
    if (time() - (int)$added < 120) echo '<span hidden data-sv1-cart-added></span>';
}, 1);
add_action('wp_footer', function() {
    if (!get_theme_mod('storev1_whatsapp_enabled', true)) return;
    $phone = preg_replace('/[^0-9]/', '', (string)get_theme_mod('storev1_whatsapp_phone', '5511971294939'));
    $message = trim((string)get_theme_mod('storev1_whatsapp_message', 'Olá, Estou em seu site e gostaria de informações a respeito de seus produtos'));
    if ($phone === '') return;
    $url = 'https://api.whatsapp.com/send/?phone=' . rawurlencode($phone) . '&text=' . rawurlencode($message) . '&type=phone_number&app_absent=0';
    echo '<a class="sv1-floating-whatsapp" href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer" aria-label="Falar com o suporte pelo WhatsApp">';
    echo '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0C5.6 0 .3 5.3.3 11.8c0 2.1.6 4.1 1.6 5.9L.2 24l6.5-1.7a11.8 11.8 0 0 0 5.4 1.3h.1c6.5 0 11.8-5.3 11.8-11.8 0-3.2-1.3-6.1-3.5-8.3Zm-8.4 18.1h-.1a9.8 9.8 0 0 1-5-1.4l-.4-.2-3.8 1 1-3.7-.2-.4a9.8 9.8 0 0 1-1.5-5.2c0-5.4 4.4-9.8 9.9-9.8 2.6 0 5.1 1 6.9 2.9a9.8 9.8 0 0 1 2.9 7c0 5.4-4.4 9.8-9.8 9.8Zm5.4-7.3c-.3-.1-1.8-.9-2.1-1-.3-.1-.5-.1-.7.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-1.8-.9-3-1.6-4.2-3.6-.3-.5.3-.5.8-1.6.1-.2.1-.4 0-.6-.1-.2-.7-1.7-.9-2.3-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.1 1.1-1.1 2.7s1.1 3.1 1.3 3.3c.2.2 2.1 3.3 5.2 4.6 1.9.8 2.7.9 3.7.8.6-.1 1.8-.7 2-1.4.3-.7.3-1.3.2-1.4-.1-.2-.3-.3-.5-.4Z"/></svg><span>WhatsApp</span></a>';
}, 4);
add_filter('woocommerce_product_tabs',function($tabs){if(!get_theme_mod('storev1_show_reviews',false)) unset($tabs['reviews']);return $tabs;},99);
add_filter('woocommerce_output_related_products_args',function($args){$args['posts_per_page']=8;return $args;});
