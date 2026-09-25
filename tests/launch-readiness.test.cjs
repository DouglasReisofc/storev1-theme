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
