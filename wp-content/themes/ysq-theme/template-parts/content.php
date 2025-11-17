<?php
/**
 * Default template part for index/archive/search items.
 *
 * @package YSQ
 */

if (!defined('ABSPATH')) {
    exit;
}

?>

<article id="post-<?php the_ID(); ?>" <?php post_class('entry-summary-card'); ?> aria-labelledby="entry-title-<?php the_ID(); ?>">
    <header class="entry-header">
        <?php the_title('<h2 id="entry-title-' . get_the_ID() . '" class="entry-title"><a href="' . esc_url(get_permalink()) . '">', '</a></h2>'); ?>
        <div class="entry-meta">
            <?php
            if (get_post_type() === 'post') {
                ysq_posted_on();
            }
            ?>
        </div>
    </header>

    <div class="entry-summary">
        <?php the_excerpt(); ?>
    </div>

    <footer class="entry-footer">
        <a class="link-button" href="<?php echo esc_url(get_permalink()); ?>">
            <?php esc_html_e('Baca selengkapnya', 'ysq'); ?>
        </a>
    </footer>
</article>
