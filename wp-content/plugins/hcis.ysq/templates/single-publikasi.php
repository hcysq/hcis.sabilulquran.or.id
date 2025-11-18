<?php
/**
 * The template for displaying a single 'publikasi' post.
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
        <?php
        // Start the loop.
        while (have_posts()) :
            the_post();

            // Include the single post content template.
            get_template_part('template-parts/content', 'single');

        endwhile; // End of the loop.
        ?>
    </main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>
