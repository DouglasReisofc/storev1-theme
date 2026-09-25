<?php
defined('ABSPATH') || exit;

/**
 * Checkout login hand-off. The checkout stays open in the parent dialog while
 * these small same-origin requests check an e-mail and authenticate the
 * customer. No password is ever sent to the browser-side e-mail check.
 */
function storev1_checkout_login_nonce_valid() {
    return isset($_REQUEST['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['nonce'])), 'storev1_checkout_login');
}

function storev1_ajax_check_login_email() {
    if (!storev1_checkout_login_nonce_valid()) wp_send_json_error(['message' => 'Sessão expirada. Atualize a página e tente novamente.'], 403);
    $email = isset($_REQUEST['email']) ? sanitize_email(wp_unslash($_REQUEST['email'])) : '';
    if (!is_email($email)) wp_send_json_success(['exists' => false]);
    wp_send_json_success(['exists' => (bool) email_exists($email)]);
}
add_action('wp_ajax_storev1_check_login_email', 'storev1_ajax_check_login_email');
add_action('wp_ajax_nopriv_storev1_check_login_email', 'storev1_ajax_check_login_email');

function storev1_ajax_checkout_login() {
    if (!storev1_checkout_login_nonce_valid()) wp_send_json_error(['message' => 'Sessão expirada. Atualize a página e tente novamente.'], 403);
    if (is_user_logged_in()) wp_send_json_success(['message' => 'Você já está conectado.']);
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
    $remember = !empty($_POST['remember']);
    if (!is_email($email) || $password === '') wp_send_json_error(['message' => 'Informe seu e-mail e sua senha.'], 422);
    $user = get_user_by('email', $email);
    if (!$user) wp_send_json_error(['message' => 'E-mail ou senha inválidos.'], 401);
    $signed_in = wp_signon(['user_login' => $user->user_login, 'user_password' => $password, 'remember' => $remember], is_ssl());
    if (is_wp_error($signed_in)) wp_send_json_error(['message' => 'E-mail ou senha inválidos.'], 401);
    wp_set_current_user($signed_in->ID);
    wp_send_json_success(['message' => 'Login realizado. Retomando sua compra…', 'userId' => (int) $signed_in->ID]);
}
add_action('wp_ajax_storev1_checkout_login', 'storev1_ajax_checkout_login');
add_action('wp_ajax_nopriv_storev1_checkout_login', 'storev1_ajax_checkout_login');

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

/**
 * Keep an unpaid order payable from the purchase history.
 *
 * WooCommerce normally removes the "pay" action when a gateway reports that
 * an order does not need payment.  Store Connect orders in "Aguardando"
 * (on-hold) are still intentionally payable, so the customer must be able to
 * reopen the same payment flow after closing the checkout modal.
 */
function storev1_order_can_pay_again($order) {
    if (!$order instanceof WC_Order || !is_user_logged_in()) return false;
    if ((int) $order->get_user_id() !== (int) get_current_user_id()) return false;
    if ($order->is_paid()) return false;
    return in_array($order->get_status(), ['pending', 'on-hold', 'failed'], true);
}

// WooCommerce only considers pending/failed orders payable by default. Store
// Connect deliberately moves generated payments to on-hold ("Aguardando")
// while waiting for the provider webhook. Permit the owner to reopen that
// unpaid order without changing its status or creating a duplicate order.
add_filter('woocommerce_valid_order_statuses_for_payment', function($statuses, $order) {
    if (!$order instanceof WC_Order || $order->is_paid() || !is_user_logged_in()) return $statuses;
    if ((int) $order->get_user_id() !== (int) get_current_user_id()) return $statuses;
    if ($order->has_status('on-hold')) $statuses[] = 'on-hold';
    return array_values(array_unique($statuses));
}, 30, 2);

add_filter('woocommerce_my_account_my_orders_actions', function($actions, $order) {
    if (!storev1_order_can_pay_again($order)) return $actions;
    $actions['pay'] = [
        'url' => $order->get_checkout_payment_url(),
        'name' => 'Pagar agora',
        'aria-label' => 'Pagar novamente o pedido ' . $order->get_order_number(),
    ];
    return $actions;
}, 30, 2);

// The same recovery action is useful after opening an individual order.
add_action('woocommerce_order_details_after_order_table', function($order) {
    if (!storev1_order_can_pay_again($order)) return;
    // `woocommerce_order_details_table` may be rendered both by a thank-you
    // page and another extension in the same response. Never expose a second
    // payment action for the same order.
    static $rendered = [];
    $order_id = (int) $order->get_id();
    if (isset($rendered[$order_id])) return;
    $rendered[$order_id] = true;
    $url = $order->get_checkout_payment_url();
    echo '<section class="storev1-order-payment-pending" data-storev1-order-payment-pending data-storev1-order-pay-action>';
    echo '<strong>Estamos aguardando seu pagamento</strong><span>Selecione uma forma de pagamento para concluir este pedido.</span>';
    echo '<a class="woocommerce-button button pay" href="' . esc_url($url) . '">Pagar agora</a>';
    echo '</section>';
}, 20, 1);

// WooCommerce 11 can show an English "Confirm your email address" notice on
// the Orders endpoint when guest checkout is enabled.  Byteplant validates
// the address during checkout, so this extra flow is not used in our store.
add_filter('woocommerce_customer_email_verification_should_show_prompt', '__return_false', 20);

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
