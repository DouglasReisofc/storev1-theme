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
});
