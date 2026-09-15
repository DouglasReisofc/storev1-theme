<?php
if (! defined('ABSPATH')) exit;
require_once get_template_directory() . '/inc/class-storev1-updater.php';
function storev1_setup() {
    load_theme_textdomain('storev1-theme', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption','style','script']);
    add_theme_support('responsive-embeds');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-slider');
    register_nav_menus(['primary'=>__('Menu principal','storev1-theme')]);
}
add_action('after_setup_theme','storev1_setup');
function storev1_assets(){
    wp_enqueue_style('storev1-storefront',get_template_directory_uri() . '/assets/storev1-storefront.css',[],wp_get_theme()->get('Version'));
    wp_enqueue_style('storev1-components',get_template_directory_uri() . '/assets/storev1-components.css',['storev1-storefront'],wp_get_theme()->get('Version'));
    if (class_exists('WooCommerce')) wp_enqueue_script('wc-cart-fragments');
    wp_enqueue_script('storev1-ui',get_template_directory_uri() . '/assets/storev1-ui.js',[],wp_get_theme()->get('Version'),true);
    wp_enqueue_script('storev1-notices',get_template_directory_uri() . '/assets/storev1-notices.js',[],wp_get_theme()->get('Version'),true);
    wp_enqueue_script('storev1-shopping',get_template_directory_uri() . '/assets/storev1-shopping.js',[],wp_get_theme()->get('Version'),true);
    wp_enqueue_script('storev1-discovery',get_template_directory_uri() . '/assets/storev1-discovery.js',[],wp_get_theme()->get('Version'),true);
    wp_enqueue_script('storev1-search',get_template_directory_uri() . '/assets/storev1-search.js',[],wp_get_theme()->get('Version'),true);
    if (is_singular() && comments_open() && get_option('thread_comments')) wp_enqueue_script('comment-reply');
}
add_action('wp_enqueue_scripts','storev1_assets');
add_action('customize_register', function($wp_customize) {
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
