<?php
defined('ABSPATH') || exit;

/**
 * Storefront modal shell.
 *
 * The Store Connect plugin owns WooCommerce/payment behavior, while the
 * active theme owns the modal markup and visual layer. Keeping this shell in
 * the theme prevents header/footer stacking contexts from leaking over the
 * cart and checkout experience.
 */
function storev1_modal_setting($key, $default = '') {
    if (class_exists('StoreZap_Settings') && is_callable(['StoreZap_Settings', 'get'])) {
        return StoreZap_Settings::get($key, $default);
    }
    return $default;
}

/**
 * The cart is a modal-only journey. Keep WooCommerce's cart endpoint alive
 * for the asynchronous modal refreshes, but never render it as a destination
 * for shoppers who navigate there directly.
 */
function storev1_redirect_cart_route_to_modal() {
    if (is_admin() || wp_doing_ajax() || !function_exists('is_cart') || !is_cart()) return;
    if (!class_exists('StoreZap_Settings') || !StoreZap_Settings::enabled('cart_modal_enabled')) return;
    if (isset($_GET['storev1_cart_fragment']) && '1' === (string) $_GET['storev1_cart_fragment']) return;

    $destination = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
    if (!$destination) $destination = home_url('/');
    wp_safe_redirect(add_query_arg('storev1_cart', 'open', $destination), 302);
    exit;
}
add_action('template_redirect', 'storev1_redirect_cart_route_to_modal', 1);

function storev1_render_storefront_modals() {
    if (is_admin() || !function_exists('WC') || !function_exists('wc_get_cart_url')) return;
    $modal_enabled = class_exists('StoreZap_Settings') && StoreZap_Settings::enabled('cart_modal_enabled');
    if (!$modal_enabled) return;
    static $rendered = false;
    if ($rendered) return;
    $rendered = true;

    $open_cart_request = isset($_GET['storev1_cart']) && 'open' === (string) $_GET['storev1_cart'];
    $auto_open = StoreZap_Settings::enabled('cart_auto_open') || $open_cart_request ? 'yes' : 'no';
    $accent = sanitize_hex_color((string) storev1_modal_setting('modal_accent_color', '#087c4d')) ?: '#087c4d';
    $checkout_url = function_exists('wc_get_checkout_url')
        ? add_query_arg('storezap_modal_checkout', '1', wc_get_checkout_url())
        : home_url('/finalizar-compra/');

    $cart_fragment_url = add_query_arg('storev1_cart_fragment', '1', wc_get_cart_url());
    $lottie_url = get_template_directory_uri() . '/assets/lottie/easy-checkout.json';
    echo '<dialog class="storezap-cart-dialog sv1-storefront-modal" data-storezap-cart-dialog data-storev1-modal="cart" data-auto-open="' . esc_attr($auto_open) . '" data-cart-page="no" data-coupon-label="' . esc_attr(storev1_modal_setting('cart_coupon_label', 'Tenho um cupom')) . '" data-checkout-label="' . esc_attr(storev1_modal_setting('cart_checkout_label', 'Finalizar compra')) . '" data-empty-message="' . esc_attr(storev1_modal_setting('cart_empty_message', 'Seu carrinho está vazio.')) . '" data-accent="' . esc_attr($accent) . '" style="--storezap-accent:' . esc_attr($accent) . ';" data-cart-url="' . esc_url($cart_fragment_url) . '">';
    echo '<header class="storezap-cart-dialog__head"><div><span class="sv1-modal-eyebrow">Seu pedido</span><h2>' . esc_html(storev1_modal_setting('cart_modal_title', 'Seu carrinho')) . '</h2></div><button type="button" class="storezap-cart-dialog__close" data-storezap-cart-close aria-label="Fechar">&times;</button></header>';
    echo '<div class="storezap-cart-dialog__body">' . do_shortcode('[woocommerce_cart]') . '</div></dialog>';

    $login_trigger = is_user_logged_in() ? '' : '<button type="button" class="sv1-checkout-login-trigger" data-sv1-open-checkout-login>Já tenho cadastro</button>';
    echo '<dialog class="storezap-checkout-dialog sv1-storefront-modal" data-storezap-checkout-dialog data-storev1-modal="checkout" data-checkout-url="' . esc_url($checkout_url) . '">';
    echo '<header class="storezap-checkout-dialog__head"><strong>Finalizar compra <span class="sv1-checkout-lottie" data-sv1-checkout-lottie data-lottie-url="' . esc_url($lottie_url) . '" aria-hidden="true"></span></strong>' . $login_trigger . '<button type="button" class="storezap-checkout-dialog__close" data-storezap-checkout-close aria-label="Fechar">&times;</button></header>';
    echo '<iframe class="storezap-checkout-dialog__frame" data-storezap-checkout-frame title="Finalizar compra" loading="lazy"></iframe><div class="storezap-checkout-dialog__loading" data-storezap-checkout-loading>Carregando checkout…</div>';
    if (!is_user_logged_in()) {
    echo '<div class="sv1-checkout-login-modal" data-sv1-checkout-login hidden role="dialog" aria-modal="true" aria-labelledby="sv1-checkout-login-title">';
    echo '<div class="sv1-checkout-login-panel">';
    echo '<button type="button" class="sv1-checkout-login-close" data-sv1-login-close aria-label="Fechar login">&times;</button>';
    echo '<div class="sv1-checkout-login-copy"><span class="sv1-modal-eyebrow">Compra segura</span><h2 id="sv1-checkout-login-title" data-sv1-auth-title>Entre para continuar sua compra</h2><p data-sv1-auth-copy>Encontramos um cadastro com este e-mail. Entre agora e voltaremos automaticamente ao checkout, sem perder seu carrinho.</p>';
    echo '<div class="sv1-checkout-login-banners"><button type="button" class="sv1-checkout-login-banner is-active" data-sv1-auth-switch="login"><span class="sv1-checkout-login-banner__icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"/><path d="M3 12h12"/><path d="m11 8 4 4-4 4"/><path d="M3 12V6a2 2 0 0 1 2-2h2"/></svg></span><strong>Entrar</strong><span>Já tenho cadastro</span></button><button type="button" class="sv1-checkout-login-banner" data-sv1-auth-switch="register"><span class="sv1-checkout-login-banner__icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M19 8v6"/><path d="M16 11h6"/></svg></span><strong>Criar conta</strong><span>Ainda não tenho cadastro</span></button></div></div>';
    echo '<div class="sv1-checkout-auth-views">';
    echo '<form class="sv1-checkout-login-form" data-sv1-checkout-login-form data-sv1-auth-view="login" novalidate><label for="sv1-checkout-login-email">E-mail</label><input id="sv1-checkout-login-email" type="email" data-sv1-login-email autocomplete="email" required><label for="sv1-checkout-login-password">Senha</label><input id="sv1-checkout-login-password" type="password" data-sv1-login-password autocomplete="current-password" required><label class="sv1-checkout-login-remember"><input type="checkbox" data-sv1-login-remember> Lembrar acesso neste dispositivo</label><button type="submit" class="sv1-checkout-login-submit">Entrar</button><div class="sv1-checkout-login-actions"><button type="button" data-sv1-auth-switch="register">Criar conta</button><button type="button" data-sv1-auth-switch="recover">Recuperar senha</button></div></form>';
    $phone_mode = function_exists('storev1_registration_phone_mode') ? storev1_registration_phone_mode() : 'required';
    echo '<form class="sv1-checkout-login-form" data-sv1-checkout-register-form data-sv1-auth-view="register" hidden novalidate><label for="sv1-register-name">Nome e sobrenome</label><input id="sv1-register-name" name="name" autocomplete="name" required><label for="sv1-register-email">E-mail</label><input id="sv1-register-email" name="email" type="email" autocomplete="email" required>';
    if ($phone_mode !== 'hidden') echo '<label for="sv1-register-phone">WhatsApp' . ($phone_mode === 'required' ? '' : ' (opcional)') . '</label><input id="sv1-register-phone" name="phone" type="tel" inputmode="numeric" autocomplete="tel" placeholder="(11) 91234-5678" ' . ($phone_mode === 'required' ? 'required' : '') . '>';
    echo '<label for="sv1-register-password">Crie uma senha</label><input id="sv1-register-password" name="password" type="password" autocomplete="new-password" minlength="8" required><button type="submit" class="sv1-checkout-login-submit">Criar conta</button><button type="button" class="sv1-checkout-login-secondary" data-sv1-auth-switch="login">Já tenho conta: entrar</button></form>';
    echo '<form class="sv1-checkout-login-form" data-sv1-checkout-recover-form data-sv1-auth-view="recover" data-recovery-stage="request" hidden novalidate><label for="sv1-recover-email">E-mail cadastrado</label><input id="sv1-recover-email" name="email" type="email" autocomplete="email" required><p class="sv1-checkout-auth-help">Enviaremos um código de 6 dígitos e um link seguro para redefinir sua senha.</p><label for="sv1-recover-code" data-sv1-recover-code-label hidden>Código recebido</label><input id="sv1-recover-code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" data-sv1-recover-code hidden><label for="sv1-recover-new-password" data-sv1-recover-password-label hidden>Nova senha</label><input id="sv1-recover-new-password" name="password" type="password" autocomplete="new-password" minlength="8" data-sv1-recover-password hidden><button type="submit" class="sv1-checkout-login-submit" data-sv1-recover-submit>Enviar código e link</button><button type="button" class="sv1-checkout-login-secondary" data-sv1-auth-switch="login">Voltar para o login</button></form>';
    echo '<p class="sv1-checkout-login-feedback" data-sv1-login-feedback role="status" aria-live="polite"></p></div>';
    echo '</div></div>';
    }
    echo '</dialog>';
}
add_action('wp_footer', 'storev1_render_storefront_modals', 8);
