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
    wp_enqueue_script('storev1-support',get_template_directory_uri() . '/assets/storev1-support.js',[],wp_get_theme()->get('Version'),true);
    wp_add_inline_script('storev1-support', 'window.ajaxurl = ' . wp_json_encode(admin_url('admin-ajax.php')) . ';', 'before');
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
    $wp_customize->add_setting('storev1_support_email',['default'=>get_option('admin_email'),'sanitize_callback'=>'sanitize_email']);
    $wp_customize->add_control('storev1_support_email',['label'=>'E-mail de suporte (fallback)','description'=>'Se o Store Connect estiver ativo, o e-mail configurado nele tem prioridade.','section'=>'storev1_support','type'=>'email']);
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
// Add concise, semantic product facts to WooCommerce's native additional
// information tab. This gives crawlers useful context without keyword stuffing
// or replacing the product's original description.
add_action('woocommerce_product_additional_information', function($product) {
    if (!$product instanceof WC_Product) return;
    $categories = wp_strip_all_tags($product->get_categories(', '));
    $delivery = $product->is_virtual() ? 'Entrega digital após a confirmação do pagamento' : 'Entrega conforme as condições do produto';
    echo '<section class="sv1-product-technical" aria-labelledby="sv1-product-technical-title">';
    echo '<h3 id="sv1-product-technical-title">Informações técnicas do produto</h3><dl>';
    echo '<div><dt>Tipo de produto</dt><dd>' . esc_html($product->is_type('variable') ? 'Produto variável com ofertas selecionáveis' : 'Produto digital') . '</dd></div>';
    if ($categories) echo '<div><dt>Categoria</dt><dd>' . esc_html($categories) . '</dd></div>';
    echo '<div><dt>Disponibilidade</dt><dd>' . esc_html($product->is_in_stock() ? 'Disponível para compra' : 'Indisponível no momento') . '</dd></div>';
    echo '<div><dt>Entrega</dt><dd>' . esc_html($delivery) . '</dd></div>';
    echo '</dl></section>';
}, 20);
require_once get_template_directory() . '/inc/storefront.php';
require_once get_template_directory() . '/inc/storefront-modals.php';
require_once get_template_directory() . '/inc/catalog-search.php';
require_once get_template_directory() . '/inc/site-quality.php';
require_once get_template_directory() . '/inc/social-metadata.php';
require_once get_template_directory() . '/inc/product-schema.php';
require_once get_template_directory() . '/inc/launch-readiness.php';
require_once get_template_directory() . '/inc/account.php';
require_once get_template_directory() . '/inc/companion-installer.php';
require_once get_template_directory() . '/inc/support.php';
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
    // Checkout is rendered inside the payment iframe. Support belongs to the
    // storefront page behind it and must not compete with the payment UI.
    if (isset($_GET['storezap_modal_checkout']) && '1' === (string) $_GET['storezap_modal_checkout']) return;
    if (!get_theme_mod('storev1_whatsapp_enabled', true)) return;
    $phone = preg_replace('/[^0-9]/', '', (string)get_theme_mod('storev1_whatsapp_phone', '5511971294939'));
    $message = trim((string)get_theme_mod('storev1_whatsapp_message', 'Olá, Estou em seu site e gostaria de informações a respeito de seus produtos'));
    if ($phone === '') return;
    $url = 'https://api.whatsapp.com/send/?phone=' . rawurlencode($phone) . '&text=' . rawurlencode($message) . '&type=phone_number&app_absent=0';
    echo '<button type="button" class="sv1-floating-support" data-sv1-support-open aria-controls="sv1-support-drawer" aria-expanded="false" aria-label="Abrir suporte">';
    echo '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M20 11.5a8 8 0 0 1-8 8H8l-4 2 .8-3.6A8 8 0 1 1 20 11.5Z"/><path d="M8 11h.01M12 11h.01M16 11h.01"/></svg><span>Suporte</span></button>';
    echo '<div class="sv1-support-drawer" id="sv1-support-drawer" hidden role="dialog" aria-modal="true" aria-labelledby="sv1-support-title"><div class="sv1-support-drawer__panel"><header><div><small>ATENDIMENTO</small><h2 id="sv1-support-title">Como podemos ajudar?</h2></div><button type="button" data-sv1-support-close aria-label="Fechar suporte">×</button></header><div class="sv1-support-drawer__choices"><a class="sv1-support-choice sv1-support-choice--whatsapp" href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer"><span class="sv1-support-choice__icon">'; storev1_icon('whatsapp'); echo '</span><span class="sv1-support-choice__copy"><strong>WhatsApp</strong><span>Fale com a equipe agora</span></span></a><button type="button" class="sv1-support-choice" data-sv1-email-support><span class="sv1-support-choice__icon">'; storev1_icon('mail'); echo '</span><span class="sv1-support-choice__copy"><strong>E-mail</strong><span>Envie detalhes e anexos</span></span></button></div></div></div>';
    $orders = function_exists('storev1_support_orders') ? storev1_support_orders() : [];
    echo '<div class="sv1-support-modal" id="sv1-support-modal" hidden role="dialog" aria-modal="true" aria-labelledby="sv1-support-form-title"><div class="sv1-support-modal__panel"><header><div><small>SUPORTE POR E-MAIL</small><h2 id="sv1-support-form-title">Pedir suporte</h2><p>Descreva o problema e nossa equipe responderá pelo e-mail.</p></div><button type="button" data-sv1-support-form-close aria-label="Fechar formulário">×</button></header><form data-sv1-support-form enctype="multipart/form-data"><div class="sv1-support-form-grid"><label>Nome<input name="name" required value="' . esc_attr(is_user_logged_in() ? wp_get_current_user()->display_name : '') . '"></label><label>E-mail<input name="email" type="email" required value="' . esc_attr(is_user_logged_in() ? wp_get_current_user()->user_email : '') . '"></label></div><label>Motivo<select name="reason" required><option value="">Selecione</option><option>Problema com minha compra</option><option>Não recebi o acesso</option><option>Dúvida sobre o produto</option><option>Pagamento</option><option>Outro assunto</option></select></label>';
    if ($orders) { echo '<label>Compra relacionada (opcional)<select name="order_id"><option value="">Não selecionar</option>'; foreach ($orders as $order) echo '<option value="' . absint($order->get_id()) . '">' . esc_html(storev1_support_order_label($order)) . '</option>'; echo '</select></label>'; }
    echo '<label>Mensagem<textarea name="message" rows="5" minlength="10" required placeholder="Explique o que aconteceu..."></textarea></label><label class="sv1-support-file">Anexo (opcional)<input type="file" name="attachment" accept="image/jpeg,image/png,image/webp,application/pdf"><small>JPG, PNG, WebP ou PDF até 5 MB.</small></label><input type="hidden" name="action" value="storev1_support"><input type="hidden" name="nonce" value="' . esc_attr(wp_create_nonce('storev1_support')) . '"><div class="sv1-support-form-feedback" data-sv1-support-feedback role="status"></div><button class="sv1-support-submit" type="submit">Enviar solicitação</button></form></div></div>';
}, 4);
add_filter('woocommerce_product_tabs',function($tabs){if(!get_theme_mod('storev1_show_reviews',false)) unset($tabs['reviews']);return $tabs;},99);
add_filter('woocommerce_output_related_products_args',function($args){$args['posts_per_page']=8;return $args;});
