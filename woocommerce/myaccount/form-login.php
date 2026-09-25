<?php
/**
 * StoreV1 single-view account forms. Native WooCommerce processing and hooks.
 * @version 9.9.0
 */
defined('ABSPATH') || exit;
$register = storev1_account_view() === 'register';
$registration_disabled = is_page_template('page-account-register.php') && !storev1_registration_enabled();
$posted = function($key) { return isset($_POST[$key]) && is_string($_POST[$key]) ? esc_attr(wp_unslash($_POST[$key])) : ''; };
do_action('woocommerce_before_customer_login_form');
?>
<section class="sv1-auth" aria-label="<?php echo $register ? 'Criar conta' : 'Entrar'; ?>">
    <div class="sv1-auth-content">
        <?php if (!$registration_disabled) : ?><nav class="sv1-auth-tabs" aria-label="Acesso à loja">
            <a href="<?php echo esc_url(storev1_account_url()); ?>" <?php if (!$register) echo 'aria-current="page"'; ?>><span class="sv1-auth-tab-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5"/><path d="M3 12h12"/><path d="m11 8 4 4-4 4"/><path d="M3 12V6a2 2 0 0 1 2-2h2"/></svg></span><span>Entrar</span></a>
            <?php if (storev1_registration_enabled()) : ?><a href="<?php echo esc_url(storev1_account_url('register')); ?>" <?php if ($register) echo 'aria-current="page"'; ?>><span class="sv1-auth-tab-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M19 8v6"/><path d="M16 11h6"/></svg></span><span>Criar conta</span></a><?php endif; ?>
        </nav><?php endif; ?>
        <h2><?php echo $registration_disabled ? 'Cadastro indisponível' : ($register ? 'Crie sua conta' : 'Bem-vindo de volta'); ?></h2>
        <p class="sv1-auth-lead"><?php echo $registration_disabled ? 'A criação de contas está desativada no momento.' : ($register ? 'Seus pedidos e produtos digitais em um só lugar.' : 'Entre para acompanhar seus pedidos e acessar seus produtos.'); ?></p>
        <?php if ($registration_disabled) : ?>
            <p class="sv1-registration-disabled" role="status">No momento, novas contas não podem ser criadas.</p>
        <?php elseif (!$register) : ?>
        <form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>
            <?php do_action('woocommerce_login_form_start'); ?>
            <p class="form-row form-row-wide"><label for="username">E-mail ou nome de usuário <span aria-hidden="true">*</span></label><input type="text" class="input-text" name="username" id="username" autocomplete="username" value="<?php echo $posted('username'); ?>" required aria-required="true"></p>
            <p class="form-row form-row-wide"><label for="password">Senha <span aria-hidden="true">*</span></label><input type="password" class="input-text" name="password" id="password" autocomplete="current-password" required aria-required="true"></p>
            <?php do_action('woocommerce_login_form'); ?>
            <p class="sv1-auth-options"><label><input name="rememberme" type="checkbox" value="forever"> Lembrar de mim</label><a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Esqueci minha senha</a></p>
            <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
            <input type="hidden" name="redirect" value="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">
            <button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="Entrar">Entrar</button>
            <?php do_action('woocommerce_login_form_end'); ?>
        </form>
        <?php else : ?>
        <form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action('woocommerce_register_form_tag'); ?>>
            <?php do_action('woocommerce_register_form_start'); ?>
            <p class="form-row form-row-wide"><label for="storev1_name">Nome e sobrenome <span aria-hidden="true">*</span></label><input type="text" class="input-text" name="storev1_name" id="storev1_name" autocomplete="name" value="<?php echo $posted('storev1_name'); ?>" placeholder="Ex.: Maria da Silva" required aria-required="true"></p>
            <p class="form-row form-row-wide"><label for="reg_email">E-mail <span aria-hidden="true">*</span></label><input type="email" class="input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo $posted('email'); ?>" required aria-required="true"></p>
            <?php if (storev1_registration_phone_visible()) : ?><p class="form-row form-row-wide"><label for="storev1_phone">WhatsApp<?php if (storev1_registration_phone_required()) : ?> <span aria-hidden="true">*</span><?php endif; ?></label><input type="tel" class="input-text" name="storev1_phone" id="storev1_phone" autocomplete="tel-national" inputmode="numeric" maxlength="16" value="<?php echo $posted('storev1_phone'); ?>" placeholder="(11) 91234-5678" <?php if (storev1_registration_phone_required()) echo 'required aria-required="true"'; ?>></p><?php endif; ?>
            <p class="form-row form-row-wide"><label for="reg_password">Senha <span aria-hidden="true">*</span></label><input type="password" class="input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" minlength="6" placeholder="Crie uma senha para acessar seus pedidos"></p>
            <?php do_action('woocommerce_register_form'); wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
            <input type="hidden" name="redirect" value="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">
            <button type="submit" class="woocommerce-Button button woocommerce-form-register__submit" name="register" value="Criar conta">Criar conta</button>
            <?php do_action('woocommerce_register_form_end'); ?>
        </form>
        <?php endif; ?>
    </div>
</section>
<?php do_action('woocommerce_after_customer_login_form'); ?>
