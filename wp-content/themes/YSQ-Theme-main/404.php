<?php
/**
 * 404 Error Page Template
 *
 * @package YSQ
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main class="site-main">
    <div class="content-wrapper">
        <h1><?php esc_html_e('Halaman Tidak Ditemukan', 'ysq'); ?></h1>
        <p><?php esc_html_e('Maaf, halaman yang Anda cari tidak dapat ditemukan.', 'ysq'); ?></p>
        <p>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-primary">
                <?php esc_html_e('Kembali ke Beranda', 'ysq'); ?>
            </a>
        </p>
    </div>
</main>

<?php
get_footer();
