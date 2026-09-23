<?php
define('ABSPATH', '/unused/');
$actions = []; $seo = false; $private = false;
function add_action($name, $cb, ...$args) { $GLOBALS['actions'][$name] = $cb; }
function strip_shortcodes($s) { return $s; }
function wp_strip_all_tags($s) { return strip_tags($s); }
function wp_html_excerpt($s, $length, $suffix) { return strlen($s) > $length ? substr($s, 0, $length) . $suffix : $s; }
function storev1_has_seo_provider() { return $GLOBALS['seo']; }
function is_404() { return false; } function is_search() { return false; } function is_feed() { return false; }
function is_singular() { return true; } function is_product() { return true; }
function wp_get_document_title() { return 'Mega & Turbo Contas'; }
function get_queried_object() { return (object) ['ID'=>3,'post_excerpt'=>'','post_content'=>'<h2>Title</h2><p>A &amp; B</p><p>Next</p>']; }
function post_password_required($post) { return false; }
function get_post_status($post) { return $GLOBALS['private'] ? 'private' : 'publish'; }
function get_permalink($post) { return 'https://example.com/conta/mega/'; }
function get_post_thumbnail_id($post) { return 7; }
function wp_get_attachment_image_src($id, $size) { return ['https://example.com/mega.webp',1672,941]; }
function get_post_mime_type($id) { return 'image/webp'; }
function get_attached_file($id) { return false; }
function get_bloginfo($key) { return 'Turbo Contas'; }
function get_locale() { return 'pt_BR'; }
function is_wp_error($value) { return false; }
function esc_attr($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
function wc_get_product($id) { return new class { function get_name(){return 'Mega';} function get_price(){return '15';} }; }
function wc_get_price_to_display($p) { return 15; } function wc_get_price_decimals(){return 2;}
function wc_format_decimal($p,$d) { return number_format($p,$d,'.',''); }
function get_woocommerce_currency(){return 'BRL';}
require dirname(__DIR__) . '/inc/social-metadata.php';
function check($ok,$label){if(!$ok)throw new Exception($label);echo "PASS $label\n";}
check(storev1_plain_summary('<p>One</p><p>Two &amp; three</p>') === 'One Two & three','block spacing and entities');
check(storev1_share_description(get_queried_object()) === 'A & B','paragraph summary skips heading');
ob_start(); $actions['wp_head'](); $html=ob_get_clean();
check(substr_count($html,'property="og:image"') === 1,'single product image');
check(strpos($html,'content="Mega &amp; Turbo Contas"') !== false,'escaped social title');
check(strpos($html,'content="15.00"') !== false,'current offer price');
check(strpos($html,'summary_large_image') !== false,'large social card');
$seo=true;ob_start();$actions['wp_head']();check(ob_get_clean()==='','SEO provider avoids duplicate tags');
$seo=false;$private=true;ob_start();$actions['wp_head']();check(ob_get_clean()==='','private product metadata suppressed');
