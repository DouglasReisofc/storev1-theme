<?php
defined('ABSPATH') || exit;
get_header();
while (have_posts()) : the_post(); ?>
<article <?php post_class('sv1-card'); ?>>
    <h1 class="entry-title"><?php the_title(); ?></h1>
    <?php the_post_thumbnail('large'); ?>
    <div class="entry-content"><?php the_content(); wp_link_pages(); ?></div>
</article>
<?php the_post_navigation(); if (comments_open() || get_comments_number()) comments_template(); endwhile; get_footer();
