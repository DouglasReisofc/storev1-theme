# StoreV1 Theme

Tema WordPress leve e responsivo para WooCommerce. As atualizações são verificadas automaticamente no GitHub Releases, sem chave no cliente.

Instale o arquivo `storev1-theme-VERSAO.zip` anexado à release em Aparência > Temas > Adicionar tema > Enviar tema. A pasta instalada deve ser `storev1-theme`.

Em Aparência > Temas > Informações do tema, habilite as atualizações automáticas se desejar instalar novas versões em segundo plano. A execução depende do agendamento do WordPress. A verificação usa o endpoint público do GitHub e está sujeita aos limites da API. O atualizador é carregado quando o tema ou um tema filho dele está ativo.

As páginas existentes renderizam seu conteúdo completo, incluindo os blocos e shortcodes de carrinho, checkout e conta. Uma página inicial sem conteúdo recebe uma vitrine com os últimos produtos publicados. Nenhum produto, pedido ou configuração de pagamento é criado pelo tema.

Para publicar uma versão: altere Version em style.css, faça commit, crie a tag vX.Y.Z e anexe à release o ZIP com a pasta `storev1-theme/`. O workflow valida a sintaxe PHP e gera o pacote. Releases sem o ZIP esperado são ignoradas pelo atualizador.

## 1.3.0
- Slot mobile de banner configurável em Aparência > Personalizar > Banner mobile, com até três imagens e carrossel acessível.
- Feedback visual de Adicionando/Adicionado nos botões de compra.
- Avisos WooCommerce movidos para overlay global no topo; remoção do carrinho usa ícone de lixeira.

## 1.2.0
- Cards com nome, preço e botão Comprar centralizados; botões e campos arredondados.
- A ordenação da vitrine foi substituída por categorias com busca instantânea dentro do dropdown, sem diferenciar acentos ou maiúsculas.
- Selecionar uma categoria abre seu catálogo; a busca interna filtra os nomes das categorias sem recarregar a página.

## 1.1.0
- Identidade visual restaurada a partir do StoreZap Woo Lab (BotAdmin), com estilos adaptados para funcionar sem o Storefront.
- Cabeçalho mobile com marca centralizada, menu off-canvas acessível e rolagem interna isolada.
- Dropdown de categorias, botões Comprar para produtos disponíveis e contador de carrinho sincronizado com WooCommerce.
- Rodapé em colunas, carrinho flutuante no desktop e atalhos fixos no celular.
- Mantém os produtos, páginas, checkout e configurações de pagamento existentes.

## 1.0.2
- Templates de páginas, posts, produto e vitrine inicial.
- Navegação para conta e carrinho; ajustes de layout e acessibilidade.
- Integração com o mecanismo nativo de atualizações de temas do WordPress 6.1+.
