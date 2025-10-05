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
?>

<main class="site-main">
    <header class="page-header">
        <h1 class="page-title">
            <?php
            printf(
                esc_html__('Search Results for: %s', 'ysq'),
                '<span>' . get_search_query() . '</span>'
            );
            ?>
        </h1>
    </header>

    <?php
    if (have_posts()) :
        while (have_posts()) :
            the_post();
            get_template_part('template-parts/content');
        endwhile;

        the_posts_pagination();
    else :
        get_template_part('template-parts/content', 'none');
    endif;
    ?>
</main>

<?php
get_footer();
