const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.join(__dirname, '..');

test('single-product variation payload omits duplicate description without changing the product tab', () => {
  const storefront = fs.readFileSync(path.join(root, 'inc/storefront.php'), 'utf8');
  const css = fs.readFileSync(path.join(root, 'assets/storev1-components.css'), 'utf8');
  assert.match(storefront, /add_filter\('woocommerce_available_variation',[\s\S]*?\$data\['variation_description'\]\s*=\s*'';/);
  assert.match(css, /\.summary \.woocommerce-variation-description\{display:none!important\}/);
  assert.doesNotMatch(storefront, /remove_action\('woocommerce_after_single_product_summary',\s*'woocommerce_output_product_data_tabs'/);
});

test('variation offers require an explicit shopper selection', () => {
  const variations = fs.readFileSync(path.join(root, 'assets/storev1-variations.js'), 'utf8');
  const components = fs.readFileSync(path.join(root, 'assets/storev1-components.css'), 'utf8');
  assert.doesNotMatch(variations, /firstAvailable/);
  assert.match(variations, /if \(select\.value\) return;/);
  assert.match(variations, /markSelectionRequired/);
  assert.match(components, /\.sv1-offer-trigger\.is-invalid/);
  assert.match(variations, /sv1-offer-buy-prompt/);
  assert.match(variations, /openDialog\(\)/);
});
