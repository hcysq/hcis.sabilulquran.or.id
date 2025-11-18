<?php
/**
 * The template for displaying archive pages for the 'publikasi' post type.
 *
 * @package HCISYSQ
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <?php if (have_posts()) : ?>

            <header class="page-header">
                <?php
                    the_archive_title('<h1 class="page-title">', '</h1>');
                    the_archive_description('<div class="archive-description">', '</div>');
                ?>
            </header><!-- .page-header -->

            <?php
            // Start the Loop.
            while (have_posts()) :
                the_post();

                /**
                 * Include the Post-Format-specific template for the content.
                 * If you want to override this in a child theme, then include a file
                 * called content-___.php (where ___ is the Post Format) and that will be used instead.
                 */
                get_template_part('template-parts/content', get_post_type());

            endwhile;

            // Previous/next page navigation.
            the_posts_pagination(
                array(
                    'prev_text'          => __('Previous page', 'hcisysq'),
                    'next_text'          => __('Next page', 'hcisysq'),
                    'before_page_number' => '<span class="meta-nav screen-reader-text">' . __('Page', 'hcisysq') . ' </span>',
                )
            );

        else :
            // If no content, include the "No posts found" template.
            get_template_part('template-parts/content', 'none');

        endif;
        ?>
    </main><!-- .site-main -->
</div><!-- .content-area -->

<?php get_footer(); ?>
