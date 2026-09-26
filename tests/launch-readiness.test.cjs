const {test} = require('node:test');
const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {join} = require('node:path');

const read = file => readFileSync(join(__dirname, '..', file), 'utf8');

test('variation schema prefers the current offer title over legacy metadata', () => {
  const source = read('inc/product-schema.php');
  assert.ok(source.indexOf("get_attribute('pa_oferta')") < source.indexOf("$legacy->get_name()"));
  assert.match(source, /woocommerce_structured_data_product/);
});

test('launch readiness removes author sitemap and seeds essential pages', () => {
  const source = read('inc/launch-readiness.php');
  assert.match(source, /wp_sitemaps_add_provider/);
  assert.match(source, /\$name === 'users'/);
  for (const slug of ['politica-de-privacidade', 'termos-de-uso', 'entrega-e-reembolso', 'contato-e-suporte']) {
    assert.ok(source.includes(slug));
  }
});

test('homepage sharing metadata always has a useful description and image fallback', () => {
  assert.match(read('inc/site-quality.php'), /function storev1_site_description/);
  assert.match(read('inc/social-metadata.php'), /turbo-contas-og\.png/);
  assert.match(read('inc/social-metadata.php'), /og:image:secure_url/);
});

test('embedded checkout never renders storefront support controls', () => {
  assert.match(read('functions.php'), /storezap_modal_checkout/);
  assert.match(read('assets/storev1-modals.css'), /storev1-embedded-checkout \.sv1-floating-support/);
  assert.match(read('assets/storev1-modals.css'), /storev1-embedded-checkout \.sv1-support-modal/);
});

test('support order choices are private and include the purchased account name', () => {
  const support = read('inc/support.php');
  const theme = read('functions.php');
  assert.match(support, /!is_user_logged_in\(\)\) return \[\]/);
  assert.match(support, /'customer_id'\s*=>\s*get_current_user_id\(\)/);
  assert.match(support, /function storev1_support_order_label/);
  assert.match(theme, /storev1_support_order_label\(\$order\)/);
});

test('the floating cart is only rendered for a non-empty cart and refreshes as one fragment', () => {
  const storefront = read('inc/storefront.php');
  const footer = read('footer.php');
  assert.match(storefront, /function storev1_cart_has_items/);
  assert.match(storefront, /if \(!storev1_cart_has_items\(\)\) return/);
  assert.match(storefront, /div\.sv1-floating-cart-slot/);
  assert.match(footer, /sv1-floating-cart-slot/);
});

test('support button stays above the mobile toolbar and cart', () => {
  const css = read('assets/storev1-components.css');
  assert.match(css, /\.sv1-floating-support\{position:fixed;right:22px;bottom:84px/);
  assert.match(css, /body\.sv1-cart-is-empty \.sv1-floating-support\{bottom:calc\(78px \+ env\(safe-area-inset-bottom\)\)\}/);
});

test('adult products are isolated in their dedicated category', () => {
  const source = read('inc/launch-readiness.php');
  assert.match(source, /function storev1_isolate_adult_product_category/);
  assert.match(source, /get_term_by\('slug', 'contas-adultas', 'product_cat'\)/);
  assert.match(source, /wp_set_object_terms\(\(int\) \$product_id, \[\(int\) \$adult->term_id\], 'product_cat', false\)/);
});

test('the mobile drawer lists category names without thumbnails', () => {
  const source = read('woocommerce/loop/orderby.php');
  assert.match(source, /if \(!empty\(\$sv1_drawer\)\)/);
  assert.match(source, /<strong><\?php echo esc_html\(\$term->name\); \?><\/strong><\?php else/);
});

test('account order payments open the shared checkout modal', () => {
  const source = read('assets/storev1-shopping.js');
  assert.match(source, /data-storezap-checkout-dialog/);
  assert.match(source, /order-pay/);
  assert.match(source, /storezap_modal_checkout/);
  assert.match(source, /showModal\(\)/);
  assert.match(source, /contentDocument/);
  assert.match(source, /stopImmediatePropagation/);
  assert.match(source, /storezap-checkout-layout/);
  assert.match(source, /is-payment-only/);
});

test('registered checkout e-mails open an in-place login and resume checkout', () => {
  const account = read('inc/account.php');
  const modal = read('inc/storefront-modals.php');
  const script = read('assets/storev1-shopping.js');
  assert.match(account, /storev1_check_login_email/);
  assert.match(account, /storev1_checkout_login/);
  assert.match(account, /wp_signon/);
  assert.match(account, /storev1_checkout_register/);
  assert.match(account, /storev1_checkout_recover/);
  assert.match(account, /storev1_checkout_reset_password/);
  assert.match(account, /storev1_send_password_recovery_email/);
  assert.match(account, /storev1_password_recovery_code/);
  assert.match(modal, /data-sv1-checkout-login/);
  assert.match(modal, /data-sv1-checkout-register-form/);
  assert.match(modal, /data-sv1-checkout-recover-form/);
  assert.match(modal, /sv1-checkout-login-banner__icon/);
  assert.match(modal, /data-sv1-auth-switch="login"/);
  assert.match(modal, /data-sv1-auth-switch="register"/);
  assert.match(modal, />Entrar<\/button>/);
  assert.match(modal, />Criar conta<\/button>/);
  assert.doesNotMatch(modal, /Continuar como visitante/);
  assert.match(modal, /data-sv1-recover-code/);
  assert.match(modal, /Enviar código de redefinição de senha/);
  assert.match(modal, /E-mail já cadastrado\. Entre para continuar\./);
  assert.match(script, /input\[name="billing_email"\]/);
  assert.match(script, /captureCheckoutValues/);
  assert.match(script, /contentWindow\?\.location\.reload/);
  assert.match(script, /storev1-checkout-existing-account/);
  assert.match(script, /existingAccountNotice/);
});

test('Pix temporarily hides the checkout shell without unloading its iframe', () => {
  const css = read('assets/storev1-modals.css');
  const script = read('assets/storev1-shopping.js');
  assert.match(css, /storezap-checkout-dialog\.storezap-pix-active/);
  assert.match(css, /storezap-pix-active \.storezap-checkout-dialog__head/);
  assert.match(css, /storezap-pix-active \.storezap-checkout-dialog__frame\{height:100%/);
  assert.match(script, /frameHasPixDialog/);
  assert.match(script, /woocommerce-order-received.*&& !frameHasPixDialog/);
});

test('payment-only checkout keeps a compact modal on desktop and mobile', () => {
  const source = read('assets/storev1-modals.css');
  assert.match(source, /is-payment-only\{width:min\(94vw,980px\);height:min\(500px/);
  assert.match(source, /is-payment-only\{width:calc\(100vw - 12px\);height:min\(440px/);
  assert.match(source, /is-pix-payment\{width:min\(94vw,680px\);height:min\(820px/);
  assert.match(source, /is-pix-payment\{width:calc\(100vw - 16px\);height:calc\(100dvh - 16px\)/);
  assert.match(read('assets/storev1-shopping.js'), /data-storezap-pix-dialog/);
});

test('the theme updater bypasses its release cache on a forced check', () => {
  const source = read('inc/class-storev1-updater.php');
  assert.match(source, /private static function update_data\(\$force = false\)/);
  assert.match(source, /self::update_data\(\$force\)/);
  assert.match(source, /self::release\(\$force\)/);
});

test('unpaid account orders keep a Portuguese pay-again action and hide Woo email verification', () => {
  const source = read('inc/account.php');
  assert.match(source, /storev1_order_can_pay_again/);
  assert.match(source, /woocommerce_my_account_my_orders_actions/);
  assert.match(source, /woocommerce_valid_order_statuses_for_payment/);
  assert.match(source, /\$statuses\[\] = 'on-hold'/);
  assert.match(source, /Pagar agora/);
  assert.match(source, /woocommerce_order_details_after_order_table/);
  assert.match(source, /woocommerce_customer_email_verification_should_show_prompt/);
});
