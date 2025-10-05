<?php
/**
 * Main Template File
 *
 * @package YSQ
 */

get_header();
?>

<div class="content-wrapper">
    <?php
    if (have_posts()) :
        while (have_posts()) :
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                </header>

                <div class="entry-content">
                    <?php
                    the_content();
                    ?>
                </div>
            </article>
            <?php
        endwhile;
    else :
        ?>
        <article>
            <header class="entry-header">
                <h1 class="entry-title">Selamat Datang</h1>
            </header>
            <div class="entry-content">
                <p>Selamat datang di <?php echo esc_html(get_bloginfo('name')); ?>.</p>
                <p>Untuk login, silakan kunjungi: <a href="<?php echo esc_url(get_theme_mod('ysq_login_button_url', home_url('/masuk'))); ?>">Halaman Login</a></p>
            </div>
        </article>
        <?php
    endif;
    ?>
</div>

<?php
get_footer();
