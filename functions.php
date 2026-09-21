<?php
if (! defined('ABSPATH')) exit;
require_once get_template_directory() . '/inc/class-storev1-updater.php';
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
    register_nav_menus(['primary'=>__('Menu principal','storev1-theme')]);
}
add_action('after_setup_theme','storev1_setup');

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
    if (function_exists('is_account_page') && is_account_page()) wp_enqueue_style('storev1-account',get_template_directory_uri().'/assets/storev1-account.css',['storev1-gold'],wp_get_theme()->get('Version'));
    // The public home/catalog pages render the cart count server-side. Avoid
    // loading WooCommerce's polling fragment bundle there; keep it on pages
    // where cart/account state can change without a full navigation.
    if (class_exists('WooCommerce') && !is_front_page() && !is_shop() && !is_product_category() && !is_product_tag()) {
        wp_enqueue_script('wc-cart-fragments');
    }
    wp_enqueue_script('storev1-ui',get_template_directory_uri() . '/assets/storev1-ui.js',[],wp_get_theme()->get('Version'),true);
    wp_enqueue_script('storev1-shopping',get_template_directory_uri() . '/assets/storev1-shopping.js',[],wp_get_theme()->get('Version'),true);
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
require_once get_template_directory() . '/inc/catalog-search.php';
require_once get_template_directory() . '/inc/site-quality.php';
require_once get_template_directory() . '/inc/account.php';
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
add_filter('woocommerce_product_tabs',function($tabs){if(!get_theme_mod('storev1_show_reviews',false)) unset($tabs['reviews']);return $tabs;},99);
add_filter('woocommerce_output_related_products_args',function($args){$args['posts_per_page']=8;return $args;});
