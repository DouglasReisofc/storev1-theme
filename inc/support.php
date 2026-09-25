<?php
defined('ABSPATH') || exit;

/**
 * Receives the storefront support form. It deliberately sends only order
 * context (never credentials) so the administrator can identify a purchase
 * without exposing delivery data in an email request.
 */
function storev1_support_recipient() {
    if (class_exists('StoreZap_Settings')) {
        $email = StoreZap_Settings::get('support_email', StoreZap_Settings::get('smtp_admin_email', get_option('admin_email')));
    } else {
        $email = get_theme_mod('storev1_support_email', get_option('admin_email'));
    }
    return sanitize_email((string) $email) ?: sanitize_email(get_option('admin_email'));
}

function storev1_support_orders() {
    // Never render store-wide order data in a public support form. A guest can
    // still contact support, but only an authenticated customer can select one
    // of their own purchases as context.
    if (!function_exists('wc_get_orders') || !is_user_logged_in()) return [];
    return wc_get_orders([
        'limit'       => 20,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'return'      => 'objects',
        'customer_id' => get_current_user_id(),
    ]);
}

function storev1_support_order_label($order) {
    if (!$order instanceof WC_Order) return '';
    $names = [];
    foreach ($order->get_items() as $item) {
        if (!$item instanceof WC_Order_Item_Product) continue;
        $name = trim(wp_strip_all_tags($item->get_name()));
        if ($name !== '') $names[] = $name;
    }
    $account = $names ? $names[0] : 'Conta não informada';
    if (count($names) > 1) $account .= ' +' . (count($names) - 1);
    $created = $order->get_date_created();
    return sprintf(
        'Pedido #%s · %s · %s · %s',
        $order->get_order_number(),
        $account,
        $created ? wp_date('d/m/Y', $created->getTimestamp()) : '',
        wp_strip_all_tags($order->get_formatted_order_total())
    );
}

function storev1_support_order_context($order_id) {
    if (!$order_id || !function_exists('wc_get_order') || !is_user_logged_in()) return '';
    $order = wc_get_order(absint($order_id));
    if (!$order instanceof WC_Order) return '';
    if ((int) $order->get_customer_id() !== (int) get_current_user_id()) return '';
    $items = [];
    foreach ($order->get_items() as $item) {
        if ($item instanceof WC_Order_Item_Product) $items[] = $item->get_name() . ' x' . $item->get_quantity();
    }
    return sprintf(
        "Pedido #%s\nStatus: %s\nTotal: %s\nProdutos: %s",
        $order->get_order_number(),
        wc_get_order_status_name($order->get_status()),
        wp_strip_all_tags($order->get_formatted_order_total()),
        $items ? implode(', ', $items) : 'Não informado'
    );
}

function storev1_support_submit() {
    check_ajax_referer('storev1_support', 'nonce');
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $reason = sanitize_text_field(wp_unslash($_POST['reason'] ?? ''));
    $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
    if (!$name || !is_email($email) || !$reason || mb_strlen($message) < 10) {
        wp_send_json_error(['message' => 'Preencha nome, e-mail, motivo e uma mensagem com pelo menos 10 caracteres.'], 422);
    }
    $order_context = storev1_support_order_context($_POST['order_id'] ?? 0);
    $body = '<h2>Nova solicitação de suporte</h2>';
    $body .= '<p><strong>Nome:</strong> ' . esc_html($name) . '<br><strong>E-mail:</strong> ' . esc_html($email) . '<br><strong>Motivo:</strong> ' . esc_html($reason) . '</p>';
    if ($order_context) $body .= '<h3>Compra relacionada</h3><p>' . nl2br(esc_html($order_context)) . '</p>';
    $body .= '<h3>Mensagem</h3><p>' . nl2br(esc_html($message)) . '</p><p><small>Enviado em ' . esc_html(wp_date('d/m/Y H:i')) . ' por ' . esc_html(get_bloginfo('name')) . '.</small></p>';
    $attachments = [];
    if (!empty($_FILES['attachment']['tmp_name']) && is_uploaded_file($_FILES['attachment']['tmp_name'])) {
        if ((int) $_FILES['attachment']['size'] > 5 * MB_IN_BYTES) wp_send_json_error(['message' => 'A mídia deve ter no máximo 5 MB.'], 422);
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $upload = wp_handle_upload($_FILES['attachment'], ['test_form' => false, 'mimes' => ['jpg|jpeg|jpe'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','pdf'=>'application/pdf']]);
        if (!empty($upload['error'])) wp_send_json_error(['message' => 'Não foi possível anexar esse arquivo.'], 422);
        if (!empty($upload['file'])) $attachments[] = $upload['file'];
    }
    $subject = 'Suporte - ' . $reason . ' - ' . get_bloginfo('name');
    // The Store Connect plugin configures phpmailer_init, so wp_mail uses the
    // same StoreV2 SMTP connection while still allowing attachments here.
    $sent = wp_mail(storev1_support_recipient(), $subject, $body, ['Content-Type: text/html; charset=UTF-8', 'Reply-To: ' . $email], $attachments);
    foreach ($attachments as $file) if (is_string($file) && is_file($file)) @unlink($file);
    if (!$sent) wp_send_json_error(['message' => 'Não foi possível enviar agora. Tente pelo WhatsApp.'], 500);
    wp_send_json_success(['message' => 'Solicitação enviada. Nossa equipe responderá por e-mail.']);
}
add_action('wp_ajax_storev1_support', 'storev1_support_submit');
add_action('wp_ajax_nopriv_storev1_support', 'storev1_support_submit');
