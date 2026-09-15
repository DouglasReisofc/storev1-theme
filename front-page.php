<?php
defined('ABSPATH') || exit;
if ('posts' === get_option('show_on_front')) { require get_template_directory() . '/index.php'; return; }
get_header();
while (have_posts()) : the_post();
    if (trim(get_the_content()) !== '') : ?>
        <article <?php post_class('sv1-page'); ?>><?php if (!preg_match('/<h1\b/i', get_the_content())) : ?><h1 class="screen-reader-text"><?php echo esc_html(get_bloginfo('name') . ' — ' . get_bloginfo('description')); ?></h1><?php endif; ?><div class="entry-content"><?php the_content(); wp_link_pages(); ?></div></article>
    <?php elseif (class_exists('WooCommerce')) : ?>
        <section class="sv1-hero">
            <p class="sv1-eyebrow"><?php echo esc_html(get_bloginfo('name')); ?></p>
            <h1><?php esc_html_e('Encontre o que combina com você.','storev1-theme'); ?></h1>
            <p><?php esc_html_e('Explore os produtos da nossa loja e escolha o seu próximo favorito.','storev1-theme'); ?></p>
            <a class="button" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('Explorar a loja','storev1-theme'); ?> &rarr;</a>
        </section>
        <section aria-labelledby="sv1-products-title">
            <div class="sv1-section-heading"><h2 id="sv1-products-title"><?php esc_html_e('Novidades da loja','storev1-theme'); ?></h2><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('Ver todos','storev1-theme'); ?> &rarr;</a></div>
            <?php echo do_shortcode('[products limit="8" columns="4" orderby="date" order="DESC" visibility="visible"]'); ?>
        </section>
    <?php else : ?>
        <h1><?php the_title(); ?></h1>
    <?php endif;
endwhile;
get_footer();
