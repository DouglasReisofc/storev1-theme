<?php
defined('ABSPATH') || exit;

function storev1_registration_enabled() {
    return 'yes' === get_option('woocommerce_enable_myaccount_registration');
}

function storev1_is_auth_page() {
    return is_page_template(['page-account-login.php', 'page-account-register.php']);
}

function storev1_account_view() {
    $view = isset($_GET['account-view']) && is_string($_GET['account-view']) ? sanitize_key(wp_unslash($_GET['account-view'])) : '';
    return storev1_registration_enabled() && (is_page_template('page-account-register.php') || $view === 'register') ? 'register' : 'login';
}

function storev1_account_url($view = 'login') {
    $template = $view === 'register' ? 'page-account-register.php' : 'page-account-login.php';
    $pages = get_posts(['post_type'=>'page', 'post_status'=>'publish', 'numberposts'=>1, 'meta_key'=>'_wp_page_template', 'meta_value'=>$template]);
    return $pages ? get_permalink($pages[0]) : add_query_arg('account-view', $view, wc_get_page_permalink('myaccount'));
}

// WC's form handler accepts nonce-valid registration POSTs even from a stale
// browser tab. Gate that handler, not checkout/admin/REST customer creation.
add_filter('woocommerce_process_registration_errors', function($errors) {
    if (!storev1_registration_enabled()) $errors->add('storev1_registration_disabled', 'A criação de contas está desativada no momento. Entre com uma conta existente.');
    return $errors;
}, 5);

add_action('template_redirect', function() {
    if (!storev1_is_auth_page() || !function_exists('WC')) return;
    nocache_headers();
    if (is_user_logged_in()) { wp_safe_redirect(wc_get_page_permalink('myaccount')); exit; }
});
add_filter('woocommerce_is_account_page', function($is_account) { return $is_account || storev1_is_auth_page(); });
add_filter('wp_robots', function($robots) {
    if (storev1_is_auth_page()) { unset($robots['index']); $robots['noindex'] = true; }
    return $robots;
});
add_filter('body_class', function($classes) {
    if (storev1_is_auth_page()) $classes[] = 'woocommerce-account';
    return $classes;
});
add_filter('the_title', function($title, $id) {
    if (!is_admin() && function_exists('wc_get_page_id') && $id && (int)$id === wc_get_page_id('myaccount')) return 'Minha conta';
    return $title;
}, 20, 2);
