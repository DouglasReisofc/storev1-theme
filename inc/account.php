<?php
defined('ABSPATH') || exit;

function storev1_registration_enabled() {
    $value = get_option('woocommerce_enable_myaccount_registration', 'no');
    return in_array($value, ['yes', '1', 1, true], true);
}

/** Keep the standalone registration form in lockstep with checkout fields. */
function storev1_registration_phone_mode() {
    if (class_exists('StoreZap_Settings') && method_exists('StoreZap_Settings', 'enabled')) {
        return StoreZap_Settings::enabled('checkout_require_phone') ? 'required' : 'hidden';
    }
    $mode = (string) get_option('woocommerce_checkout_phone_field', 'required');
    return in_array($mode, ['required', 'optional'], true) ? $mode : 'hidden';
}

function storev1_registration_phone_visible() {
    return storev1_registration_phone_mode() !== 'hidden';
}

function storev1_registration_phone_required() {
    return storev1_registration_phone_mode() === 'required';
}

function storev1_registration_form_context() {
    $account_page = function_exists('is_account_page') && is_account_page();
    return !is_admin() && storev1_registration_enabled() && storev1_account_view() === 'register' && ($account_page || storev1_is_auth_page());
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

// The account page uses the same direct-password model as the checkout. This
// prevents WooCommerce from replacing the supplied password with a generated
// one and from displaying a "set your password by email" message.
add_filter('pre_option_woocommerce_registration_generate_password', function($value) {
    if (storev1_registration_form_context()) return 'no';
    return $value;
}, 10);

// The customer-facing form identifies the customer by email and name; keep
// WooCommerce's internal username generation enabled even if an older store
// had the optional username field turned on.
add_filter('pre_option_woocommerce_registration_generate_username', function($value) {
    if (storev1_registration_form_context()) return 'yes';
    return $value;
}, 10);

add_filter('woocommerce_registration_errors', function($errors, $username, $email) {
    if (!storev1_registration_enabled() || storev1_account_view() !== 'register') return $errors;
    $name = isset($_POST['storev1_name']) && is_string($_POST['storev1_name']) ? trim(sanitize_text_field(wp_unslash($_POST['storev1_name']))) : '';
    $parts = array_values(array_filter(preg_split('/\s+/', $name) ?: []));
    if (count($parts) < 2) $errors->add('storev1_registration_name', 'Informe nome e sobrenome.');
    if (storev1_registration_phone_required() || (storev1_registration_phone_visible() && !empty($_POST['storev1_phone']))) {
        $phone = isset($_POST['storev1_phone']) && is_string($_POST['storev1_phone']) ? (string) wp_unslash($_POST['storev1_phone']) : '';
        $valid = class_exists('StoreZap_Cart') && method_exists('StoreZap_Cart', 'is_brazil_mobile')
            ? StoreZap_Cart::is_brazil_mobile($phone)
            : (bool) preg_match('/^[1-9][0-9]9[0-9]{8}$/', preg_replace('/\D+/', '', $phone));
        if (!$valid) $errors->add('storev1_registration_phone', 'Informe um WhatsApp brasileiro válido com DDD e nono dígito.');
    }
    return $errors;
}, 10, 3);

add_action('woocommerce_created_customer', function($customer_id) {
    $name = isset($_POST['storev1_name']) && is_string($_POST['storev1_name']) ? trim(sanitize_text_field(wp_unslash($_POST['storev1_name']))) : '';
    if ($name === '') return;
    $parts = array_values(array_filter(preg_split('/\s+/', $name) ?: []));
    $first = (string) array_shift($parts);
    $last = implode(' ', $parts);
    $phone = isset($_POST['storev1_phone']) && is_string($_POST['storev1_phone']) ? sanitize_text_field(wp_unslash($_POST['storev1_phone'])) : '';
    wp_update_user(['ID' => absint($customer_id), 'first_name' => $first, 'last_name' => $last, 'display_name' => trim($first . ' ' . $last)]);
    update_user_meta($customer_id, 'billing_first_name', $first);
    update_user_meta($customer_id, 'billing_last_name', $last);
    if ($phone !== '') update_user_meta($customer_id, 'billing_phone', $phone);
}, 10, 1);

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
