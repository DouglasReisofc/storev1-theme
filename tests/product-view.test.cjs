const {test}=require('node:test');
const assert=require('node:assert/strict');
const {readFileSync}=require('node:fs');
const php=readFileSync(require.resolve('../functions.php'),'utf8');
test('catalog layout is controlled by the Customizer setting',()=>{
  assert.match(php,/storev1_product_layout/);
  assert.match(php,/get_theme_mod\('storev1_product_layout', 'grid'\)/);
  assert.match(php,/type'=>'radio'/);
  assert.match(php,/Grade/); assert.match(php,/Cartões/);
});
test('the product page no longer renders a public layout toggle',()=>{
  assert.doesNotMatch(php,/sv1-view-switcher/);
});
