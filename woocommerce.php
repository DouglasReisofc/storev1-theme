<?php
defined('ABSPATH') || exit;
get_header();
echo '<div class="woocommerce">';
woocommerce_content();
echo '</div>';
get_footer();
