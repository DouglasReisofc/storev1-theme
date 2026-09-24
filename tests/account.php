<?php
define('ABSPATH', '/unused/');
$callbacks = []; $registration = 'no'; $page_template = '';
function add_filter($name, $callback, ...$args) { $GLOBALS['callbacks'][$name] = $callback; }
function add_action($name, $callback, ...$args) {}
function get_option($name) { return $GLOBALS['registration']; }
function is_page_template($templates) { return in_array($GLOBALS['page_template'], (array)$templates, true); }
function sanitize_key($key) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($key)); }
function wp_unslash($text) { return stripslashes($text); }
class WooCommerce {}
function wc_get_page_id($page) { return 'myaccount' === $page ? 42 : 0; }
function get_post_status($id) { return 42 === (int)$id ? 'publish' : false; }
class WP_Error { public $errors = []; function add($key, $text) { $this->errors[$key] = $text; } }
require dirname(__DIR__).'/inc/account.php';
function check($condition, $message) { if (!$condition) throw new Exception($message); echo "PASS $message\n"; }
$page_template = 'page-account-register.php';
check(storev1_account_view() === 'login', 'disabled registration URL falls back to login');
$_GET['account-view'] = 'register';
check(storev1_account_view() === 'login', 'query cannot bypass disabled registration');
$guard = $callbacks['woocommerce_process_registration_errors'];
check(isset($guard(new WP_Error())->errors['storev1_registration_disabled']), 'server rejects stale registration form');
$registration = 'yes';
check(storev1_account_view() === 'register', 'enabled registration renders register view');
check(!$guard(new WP_Error())->errors, 'enabled registration reaches native validation');
$_GET = []; $page_template = 'page-account-login.php';
check(storev1_account_view() === 'login', 'login remains available with registration enabled');
check(storev1_account_enabled(), 'account entry is visible when registration is enabled');
$_GET['account-view'] = ['register'];
check(storev1_account_view() === 'login', 'malformed view cannot change the form');
$registration = 'no';
check(!storev1_account_enabled(), 'account entry is hidden when registration is disabled');
$robots = $callbacks['wp_robots'];
check(isset($robots(['index'=>true])['noindex']), 'auth pages remain noindex');
$page_template = '';
check($robots([]) === [], 'public page indexing unaffected');
