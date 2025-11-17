<?php
/**
 * Template part for displaying a message when no posts are found
 *
 * @package YSQ
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<section class="no-results not-found content-wrapper" role="status" aria-live="polite">
    <header class="page-header">
        <h1 class="page-title"><?php esc_html_e('Tidak Ada Konten', 'ysq'); ?></h1>
    </header>

    <div class="page-content">
        <?php
        if (is_home() && current_user_can('publish_posts')) :
            printf(
                '<p>' . wp_kses(
                    __('Siap untuk menerbitkan post pertama Anda? <a href="%1$s">Mulai di sini</a>.', 'ysq'),
                    array(
                        'a' => array(
                            'href' => array(),
                        ),
                    )
                ) . '</p>',
                esc_url(admin_url('post-new.php'))
            );
        elseif (is_search()) :
            ?>
            <p><?php esc_html_e('Maaf, tidak ada hasil yang sesuai dengan pencarian Anda. Silakan coba kata kunci lain.', 'ysq'); ?></p>
            <?php
            get_search_form();
        else :
            ?>
            <p><?php esc_html_e('Sepertinya kami tidak dapat menemukan apa yang Anda cari.', 'ysq'); ?></p>
            <?php
        endif;
        ?>
    </div>
</section>
