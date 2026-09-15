<?php
/** StoreV1 category picker in place of catalog ordering. @version 9.7.0 */
defined('ABSPATH') || exit;
$terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);
$current = is_product_category() ? get_queried_object_id() : 0;
$label = $current ? single_term_title('', false) : __('Todas as categorias', 'storev1-theme');
$search_id = wp_unique_id('sv1-category-search-');
?>
<details class="sv1-catalog-categories" data-category-picker>
    <summary><?php storev1_icon('menu'); ?><span><?php echo esc_html($label); ?></span><?php storev1_icon('chevron'); ?></summary>
    <div class="sv1-category-panel">
        <label for="<?php echo esc_attr($search_id); ?>"><?php esc_html_e('Buscar categoria', 'storev1-theme'); ?></label>
        <input id="<?php echo esc_attr($search_id); ?>" type="search" placeholder="Digite para filtrar…" autocomplete="off" data-category-search>
        <ul class="sv1-category-options">
            <li data-category-option><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" <?php if (!$current) echo 'aria-current="page"'; ?>><?php esc_html_e('Todas as categorias', 'storev1-theme'); ?></a></li>
            <?php if (!is_wp_error($terms)) : foreach ($terms as $term) :
                $url = get_term_link($term);
                if (is_wp_error($url)) continue;
            ?>
            <li data-category-option><a href="<?php echo esc_url($url); ?>" <?php if ($current === $term->term_id) echo 'aria-current="page"'; ?>><?php echo esc_html($term->name); ?><small><?php echo absint($term->count); ?></small></a></li>
            <?php endforeach; endif; ?>
        </ul>
        <p class="sv1-category-status" role="status" aria-live="polite" data-category-status></p>
    </div>
</details>
