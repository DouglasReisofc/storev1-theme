<?php
defined('ABSPATH') || exit;
get_header();
while (have_posts()) : the_post(); ?>
<article <?php post_class('sv1-page'); ?>>
    <h1 class="entry-title"><?php the_title(); ?></h1>
    <div class="entry-content"><?php the_content(); wp_link_pages(); ?></div>
</article>
<?php endwhile; get_footer();
