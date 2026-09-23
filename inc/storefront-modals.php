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

function storev1_render_storefront_modals() {
    if (is_admin() || !function_exists('WC') || !function_exists('wc_get_cart_url')) return;
    $modal_enabled = class_exists('StoreZap_Settings') && StoreZap_Settings::enabled('cart_modal_enabled');
    if (!$modal_enabled) return;
    static $rendered = false;
    if ($rendered) return;
    $rendered = true;

    $cart_page = function_exists('is_cart') && is_cart();
    $auto_open = StoreZap_Settings::enabled('cart_auto_open') || $cart_page ? 'yes' : 'no';
    $accent = sanitize_hex_color((string) storev1_modal_setting('modal_accent_color', '#087c4d')) ?: '#087c4d';
    $checkout_url = function_exists('wc_get_checkout_url')
        ? add_query_arg('storezap_modal_checkout', '1', wc_get_checkout_url())
        : home_url('/finalizar-compra/');

    echo '<dialog class="storezap-cart-dialog sv1-storefront-modal" data-storezap-cart-dialog data-storev1-modal="cart" data-auto-open="' . esc_attr($auto_open) . '" data-cart-page="' . ($cart_page ? 'yes' : 'no') . '" data-coupon-label="' . esc_attr(storev1_modal_setting('cart_coupon_label', 'Tenho um cupom')) . '" data-checkout-label="' . esc_attr(storev1_modal_setting('cart_checkout_label', 'Finalizar compra')) . '" data-empty-message="' . esc_attr(storev1_modal_setting('cart_empty_message', 'Seu carrinho está vazio.')) . '" data-accent="' . esc_attr($accent) . '" style="--storezap-accent:' . esc_attr($accent) . ';" data-cart-url="' . esc_url(wc_get_cart_url()) . '">';
    echo '<header class="storezap-cart-dialog__head"><div><span class="sv1-modal-eyebrow">Seu pedido</span><h2>' . esc_html(storev1_modal_setting('cart_modal_title', 'Seu carrinho')) . '</h2></div><button type="button" class="storezap-cart-dialog__close" data-storezap-cart-close aria-label="Fechar">&times;</button></header>';
    echo '<div class="storezap-cart-dialog__body">' . do_shortcode('[woocommerce_cart]') . '</div></dialog>';

    echo '<dialog class="storezap-checkout-dialog sv1-storefront-modal" data-storezap-checkout-dialog data-storev1-modal="checkout" data-checkout-url="' . esc_url($checkout_url) . '">';
    echo '<header class="storezap-checkout-dialog__head"><div><span class="sv1-modal-eyebrow">Pagamento seguro</span><strong>Finalizar compra</strong><span>Preencha seus dados para concluir o pedido.</span></div><button type="button" class="storezap-checkout-dialog__close" data-storezap-checkout-close aria-label="Fechar">&times;</button></header>';
    echo '<iframe class="storezap-checkout-dialog__frame" data-storezap-checkout-frame title="Finalizar compra" loading="lazy"></iframe><div class="storezap-checkout-dialog__loading" data-storezap-checkout-loading>Carregando checkout…</div></dialog>';
}
add_action('wp_footer', 'storev1_render_storefront_modals', 8);
