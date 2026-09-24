<?php
defined('ABSPATH') || exit;

function storev1_registration_enabled() {
    $value = get_option('woocommerce_enable_myaccount_registration', 'no');
    return in_array($value, ['yes', '1', 1, true], true);
}

// The account links are rendered in the cached storefront shell. Purge the
// common page/object caches as soon as WooCommerce changes this setting so a
// disabled registration option cannot leave stale "Minha conta" links public.
add_action('updated_option_woocommerce_enable_myaccount_registration', function($old_value, $value) {
    if ((string) $old_value === (string) $value) return;
    if (function_exists('do_action')) {
        do_action('litespeed_purge_all');
        do_action('rocket_clean_domain');
        do_action('w3tc_flush_all');
    }
    if (function_exists('wp_cache_flush')) wp_cache_flush();
}, 10, 2);

/**
 * The storefront account entry follows both WooCommerce settings: the page
 * must exist and account registration must be enabled.  This keeps the
 * account entry, drawer and footer consistent when the store owner disables
 * customer registration in WooCommerce.
 */
function storev1_account_enabled() {
    if (!class_exists('WooCommerce') || !function_exists('wc_get_page_id')) return false;
    $page_id = absint(wc_get_page_id('myaccount'));
    return storev1_registration_enabled() && $page_id > 0 && 'publish' === get_post_status($page_id);
}

function storev1_is_auth_page() {
    return is_page_template(['page-account-login.php', 'page-account-register.php']);
}

function storev1_account_view() {
    $view = isset($_GET['account-view']) && is_string($_GET['account-view']) ? sanitize_key(wp_unslash($_GET['account-view'])) : '';
    return storev1_registration_enabled() && (is_page_template('page-account-register.php') || $view === 'register') ? 'register' : 'login';
}

function storev1_account_url($view = 'login') {
    if (!storev1_account_enabled()) return home_url('/');
    $template = $view === 'register' ? 'page-account-register.php' : 'page-account-login.php';
    $pages = get_posts(['post_type'=>'page', 'post_status'=>'publish', 'numberposts'=>1, 'meta_key'=>'_wp_page_template', 'meta_value'=>$template]);
    return $pages ? get_permalink($pages[0]) : add_query_arg('account-view', $view, wc_get_page_permalink('myaccount'));
}

// Keep the WooCommerce dashboard useful and intentionally small: customers
// land on their purchase history instead of seeing unrelated endpoints.
add_action('template_redirect', function() {
    if (!storev1_account_enabled() || !is_user_logged_in() || !function_exists('is_account_page') || !is_account_page()) return;
    if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url()) return;
    if (function_exists('wc_get_account_endpoint_url')) {
        wp_safe_redirect(wc_get_account_endpoint_url('orders'));
        exit;
    }
}, 15);

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
