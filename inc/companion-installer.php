<?php
if (!defined('ABSPATH')) exit;

/**
 * Fresh WooCommerce installs receive Store Connect together with StoreV1.
 * The bundled copy is removed after the first successful activation.
 */
function storev1_install_companion_plugin() {
    if (defined('STOREZAP_SUITE_FILE') || get_option('storev1_companion_installed', false)) return;
    $source = trailingslashit(get_template_directory()) . 'bundled/storezap-commerce-suite';
    $entry  = $source . '/storezap-commerce-suite.php';
    if (!is_readable($entry)) return;
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    if (!WP_Filesystem()) return;
    global $wp_filesystem;
    $destination = trailingslashit(WP_PLUGIN_DIR) . 'storezap-commerce-suite';
    if (!is_dir($destination) && !copy_dir($source, $destination)) return;
    $plugin = 'storezap-commerce-suite/storezap-commerce-suite.php';
    if (!is_plugin_active($plugin)) {
        $activated = activate_plugin($plugin, '', false, true);
        if (is_wp_error($activated)) return;
    }
    update_option('storev1_companion_installed', 1, false);
    if (isset($wp_filesystem) && $wp_filesystem) $wp_filesystem->delete($source, true);
}
add_action('after_switch_theme', 'storev1_install_companion_plugin', 20);
add_action('admin_init', 'storev1_install_companion_plugin', 5);
