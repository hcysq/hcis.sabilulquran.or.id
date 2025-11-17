<?php
/**
 * Search Results Template
 *
 * @package YSQ
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$search_title_id = 'search-title-' . uniqid();
?>

<div class="content-wrapper" role="region" aria-labelledby="<?php echo esc_attr($search_title_id); ?>">
    <header class="page-header">
        <h1 id="<?php echo esc_attr($search_title_id); ?>" class="page-title">
            <?php
            printf(
                esc_html__('Hasil pencarian untuk: %s', 'ysq'),
                '<span>' . esc_html(get_search_query()) . '</span>'
            );
            ?>
        </h1>
    </header>

    <?php
    if (have_posts()) :
        while (have_posts()) :
            the_post();
            get_template_part('template-parts/content', get_post_type());
        endwhile;

        the_posts_pagination();
    else :
        get_template_part('template-parts/content', 'none');
    endif;
    ?>
</div>

<?php
get_footer();
