<?php
defined('ABSPATH') || exit;

// A shop does not need public author archives; removing the provider prevents
// account names from being advertised in the XML sitemap.
add_filter('wp_sitemaps_add_provider', function($provider, $name) {
    return $name === 'users' ? false : $provider;
}, 10, 2);

/** Create the essential public information pages without overwriting edits. */
function storev1_ensure_information_pages() {
    $version = '1.12.65';
    if (get_option('storev1_information_pages_version') === $version) return;

    $pages = [
        'politica-de-privacidade' => [
            'title' => 'Política de privacidade',
            'content' => '<h2>Privacidade e proteção de dados</h2><p>A Turbo Contas utiliza os dados informados no cadastro e na compra para processar pagamentos, entregar produtos digitais, prestar suporte e cumprir obrigações legais.</p><h2>Dados utilizados</h2><p>Podem ser tratados nome, e-mail, telefone, informações do pedido e dados técnicos necessários à segurança da transação. Dados de pagamento são processados pelos provedores escolhidos no checkout.</p><h2>Seus direitos</h2><p>Você pode solicitar informações, correção ou exclusão de dados, observadas as obrigações legais de guarda. Use a página de contato e suporte para falar com a loja.</p>',
        ],
        'termos-de-uso' => [
            'title' => 'Termos de uso',
            'content' => '<h2>Uso da loja</h2><p>Ao realizar uma compra, o cliente deve conferir o nome da oferta, o período, o preço e as condições apresentadas na página do produto antes de concluir o pagamento.</p><h2>Produtos digitais</h2><p>Os acessos são destinados ao uso conforme as condições informadas em cada oferta. É responsabilidade do cliente fornecer dados de contato corretos e manter as credenciais recebidas em segurança.</p><h2>Atendimento</h2><p>Em caso de dúvida ou problema, entre em contato com o suporte informando o número do pedido para agilizar a análise.</p>',
        ],
        'entrega-e-reembolso' => [
            'title' => 'Entrega e reembolso',
            'content' => '<h2>Entrega digital</h2><p>Após a confirmação do pagamento, os dados do produto digital são enviados para o e-mail informado no pedido. O prazo pode variar quando a operadora de pagamento ainda estiver processando a transação.</p><h2>Problemas com a entrega</h2><p>Se o acesso não chegar ou apresentar problema, fale com o suporte e informe o número do pedido, o e-mail usado na compra e uma descrição do ocorrido.</p><h2>Cancelamento e reembolso</h2><p>As solicitações são analisadas conforme a legislação aplicável, a natureza do produto digital e as condições da oferta. Não compartilhe ou utilize o acesso após solicitar o cancelamento.</p>',
        ],
        'contato-e-suporte' => [
            'title' => 'Contato e suporte',
            'content' => '<h2>Precisa de ajuda?</h2><p>Use o botão flutuante de suporte para falar pelo WhatsApp ou enviar uma solicitação por e-mail. Se a dúvida estiver relacionada a uma compra, informe o número do pedido e o e-mail utilizado.</p><h2>Antes de enviar</h2><p>Confira também a caixa de spam e pesquise pelo assunto da entrega no seu e-mail. Nunca publique senhas ou credenciais em comentários públicos.</p>',
        ],
    ];

    foreach ($pages as $slug => $page) {
        $existing = get_page_by_path($slug, OBJECT, 'page');
        if ($existing) {
            $page_id = (int) $existing->ID;
        } else {
            $page_id = wp_insert_post([
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_name' => $slug,
                'post_title' => $page['title'],
                'post_content' => $page['content'],
                'comment_status' => 'closed',
            ], true);
            if (is_wp_error($page_id)) continue;
        }
        if ($slug === 'politica-de-privacidade' && !get_option('wp_page_for_privacy_policy')) {
            update_option('wp_page_for_privacy_policy', (int) $page_id);
        }
    }
    update_option('storev1_information_pages_version', $version, false);
}
add_action('init', 'storev1_ensure_information_pages', 30);
