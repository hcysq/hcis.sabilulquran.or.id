<?php
/**
 * Archive Template
 *
 * @package YSQ
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$archive_title_id = 'archive-title-' . uniqid();
?>

<div class="content-wrapper" role="region" aria-labelledby="<?php echo esc_attr($archive_title_id); ?>">
    <header class="page-header">
        <?php
        the_archive_title('<h1 id="' . esc_attr($archive_title_id) . '" class="page-title">', '</h1>');
        the_archive_description('<div class="archive-description">', '</div>');
        ?>
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
