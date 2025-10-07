<?php
/**
 * Template Name: Publikasi
 *
 * @package YSQ
 */

get_header();

$category_ids = get_posts(
    array(
        'post_type'      => 'publikasi',
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'posts_per_page' => -1,
        'no_found_rows'  => true,
    )
);

wp_reset_postdata();

$term_args = array(
    'taxonomy'   => 'category',
    'hide_empty' => false,
);

if (!empty($category_ids)) {
    $term_args['object_ids'] = $category_ids;
}

$terms = get_terms($term_args);

$category_map = array('all' => __('Semua', 'ysq'));

if (!is_wp_error($terms) && !empty($terms)) {
    foreach ($terms as $term) {
        if (isset($term->slug) && isset($term->name)) {
            $category_map[$term->slug] = $term->name;
        }
    }
}

$current_category = isset($_GET['category']) ? sanitize_title(wp_unslash($_GET['category'])) : 'all';
if ($current_category !== 'all' && !array_key_exists($current_category, $category_map)) {
    $current_category = 'all';
}

$paged = get_query_var('paged') ? absint(get_query_var('paged')) : 1;
if ($paged < 1) {
    $paged = 1;
}

$query_args = array(
    'post_type'      => 'publikasi',
    'post_status'    => 'publish',
    'posts_per_page' => 8,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'paged'          => $paged,
);

if ($current_category !== 'all') {
    $query_args['tax_query'] = array(
        array(
            'taxonomy' => 'category',
            'field'    => 'slug',
            'terms'    => $current_category,
        ),
    );
}

$publication_query = new WP_Query($query_args);
$pagination = '';

if ($publication_query->max_num_pages > 1) {
    $pagination = paginate_links(
        array(
            'base'      => str_replace(999999, '%#%', esc_url(get_pagenum_link(999999))),
            'format'    => '',
            'current'   => max(1, $paged),
            'total'     => max(1, $publication_query->max_num_pages),
            'type'      => 'list',
            'prev_text' => __('Sebelumnya', 'ysq'),
            'next_text' => __('Berikutnya', 'ysq'),
            'add_args'  => $current_category !== 'all' ? array('category' => $current_category) : array(),
        )
    );
}

$base_permalink = get_permalink();
$base_permalink = $base_permalink ? $base_permalink : home_url('/publikasi/');
?>

<div class="ysq-publication-page">
    <header class="ysq-publication-page__header">
        <h1 class="ysq-publication-heading"><?php the_title(); ?></h1>
    </header>

    <?php if (!empty($category_map)) : ?>
        <nav class="ysq-publication-filter" aria-label="<?php esc_attr_e('Filter kategori publikasi', 'ysq'); ?>">
            <ul class="ysq-publication-filter__list">
                <?php foreach ($category_map as $slug => $label) : ?>
                    <?php
                    $target_url = $base_permalink;
                    $link_args = array();

                    if ('all' !== $slug) {
                        $link_args['category'] = $slug;
                    }

                    if (!empty($link_args)) {
                        $target_url = add_query_arg($link_args, $base_permalink);
                    } else {
                        $target_url = remove_query_arg(array('category', 'paged'), $base_permalink);
                    }

                    $target_url = remove_query_arg('paged', $target_url);
                    $is_active  = ($current_category === $slug);
                    ?>
                    <li>
                        <a class="ysq-publication-filter__link<?php echo $is_active ? ' is-active' : ''; ?>" href="<?php echo esc_url($target_url); ?>">
                            <?php echo esc_html($label); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <?php if ($publication_query->have_posts()) : ?>
        <div class="ysq-publication-grid">
            <?php
            while ($publication_query->have_posts()) :
                $publication_query->the_post();
                $post_id       = get_the_ID();
                $thumb_url     = get_the_post_thumbnail_url($post_id, 'large');
                $date_iso      = get_post_time('c', false, $post_id);
                $date_display  = get_the_date('j M Y', $post_id);
                $terms         = wp_get_post_terms($post_id, 'category');
                $primary_term  = (!empty($terms) && !is_wp_error($terms)) ? $terms[0] : null;
                $category_name = '';

                if ($primary_term) {
                    $slug = sanitize_title($primary_term->slug);
                    if ($slug && isset($category_map[$slug])) {
                        $category_name = $category_map[$slug];
                    } else {
                        $category_name = $primary_term->name;
                    }
                }
                ?>
                <article class="ysq-publication-card<?php echo $thumb_url ? '' : ' is-placeholder'; ?>">
                    <div class="ysq-publication-card__media">
                        <?php if ($thumb_url) : ?>
                            <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                        <?php else : ?>
                            <span class="ysq-publication-card__placeholder"><?php esc_html_e('Tidak ada gambar', 'ysq'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="ysq-publication-card__body">
                        <?php if ($category_name !== '') : ?>
                            <span class="ysq-publication-card__category"><?php echo esc_html($category_name); ?></span>
                        <?php endif; ?>
                        <h2 class="ysq-publication-title"><?php the_title(); ?></h2>
                        <?php if ($date_display) : ?>
                            <time class="ysq-publication-date" datetime="<?php echo esc_attr($date_iso); ?>"><?php echo esc_html($date_display); ?></time>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    <?php else : ?>
        <p class="ysq-publication-empty"><?php esc_html_e('Belum ada publikasi pada kategori ini.', 'ysq'); ?></p>
    <?php endif; ?>

    <?php if ($pagination) : ?>
        <nav class="ysq-publication-pagination" aria-label="<?php esc_attr_e('Navigasi halaman publikasi', 'ysq'); ?>">
            <?php echo wp_kses_post($pagination); ?>
        </nav>
    <?php endif; ?>
</div>

<?php
wp_reset_postdata();
get_footer();
