<?php
/**
 * Main Template File
 *
 * @package YSQ
 */

get_header();

$ysq_notices = function_exists('ysq_get_admin_notices') ? ysq_get_admin_notices() : array();
$announcement_args = array(
    'post_type'      => 'ysq_announcement',
    'posts_per_page' => 6,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
);
$ysq_announcements = get_posts($announcement_args);

$ysq_announcement_links = array();
if (class_exists('HCISYSQ\\Announcements')) {
    $hcisysq_items = array();

    if (is_callable(array('HCISYSQ\\Announcements', 'published_for_user'))) {
        $hcisysq_items = \HCISYSQ\Announcements::published_for_user(array());
    } elseif (is_callable(array('HCISYSQ\\Announcements', 'all'))) {
        $hcisysq_items = \HCISYSQ\Announcements::all();
    }

    if (is_array($hcisysq_items)) {
        foreach ($hcisysq_items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $item_id = isset($item['id']) ? absint($item['id']) : 0;
            if (!$item_id) {
                continue;
            }

            $link_label = isset($item['link_label']) && is_string($item['link_label']) ? $item['link_label'] : '';
            $link_url   = isset($item['link_url']) && is_string($item['link_url']) ? $item['link_url'] : '';

            $ysq_announcement_links[$item_id] = array(
                'link_label' => $link_label,
                'link_url'   => $link_url,
            );
        }
    }
}

$editing_post = null;
if (function_exists('ysq_admin_is_logged_in') && ysq_admin_is_logged_in() && isset($_GET['ysq_edit'])) {
    $edit_id = absint($_GET['ysq_edit']);
    if ($edit_id) {
        $candidate = get_post($edit_id);
        if ($candidate && 'ysq_announcement' === $candidate->post_type) {
            $editing_post = $candidate;
        }
    }
}
?>

<div class="content-wrapper">
    <?php if (!empty($ysq_notices)) : ?>
        <div class="admin-notice-stack">
            <?php foreach ($ysq_notices as $notice) : ?>
                <div class="admin-notice admin-notice-<?php echo esc_attr($notice['type']); ?>">
                    <?php echo esc_html($notice['message']); ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (function_exists('ysq_admin_is_logged_in') && ysq_admin_is_logged_in()) : ?>
        <div class="dashboard-layout">
            <aside class="dashboard-sidebar">
                <div class="sidebar-header">
                    <h2><?php esc_html_e('Panel Administrator', 'ysq'); ?></h2>
                    <?php if (function_exists('ysq_admin_get_username')) : ?>
                        <p class="sidebar-username"><?php echo esc_html(ysq_admin_get_username()); ?></p>
                    <?php endif; ?>
                </div>
                <nav class="sidebar-menu">
                    <a class="active" href="#"><?php esc_html_e('Pengumuman', 'ysq'); ?></a>
                    <a href="#"><?php esc_html_e('Data Pegawai', 'ysq'); ?></a>
                    <a href="#"><?php esc_html_e('Absensi', 'ysq'); ?></a>
                    <a href="#"><?php esc_html_e('Pengaturan Sistem', 'ysq'); ?></a>
                </nav>
                <a class="sidebar-logout" href="<?php echo esc_url(add_query_arg('ysq_admin_logout', '1')); ?>"><?php esc_html_e('Keluar', 'ysq'); ?></a>
            </aside>

            <div class="dashboard-content">
                <section class="dashboard-card">
                    <header class="card-header">
                        <h2>
                            <?php echo $editing_post ? esc_html__('Perbarui Pengumuman', 'ysq') : esc_html__('Tambah Pengumuman', 'ysq'); ?>
                        </h2>
                    </header>
                    <form method="post" class="form-stack">
                        <?php wp_nonce_field('ysq_save_announcement', 'ysq_save_announcement_nonce'); ?>
                        <input type="hidden" name="ysq_save_announcement" value="1">
                        <?php if ($editing_post) : ?>
                            <input type="hidden" name="ysq_announcement_id" value="<?php echo esc_attr($editing_post->ID); ?>">
                        <?php endif; ?>
                        <div class="form-field">
                            <label for="ysq_announcement_title"><?php esc_html_e('Judul Pengumuman', 'ysq'); ?></label>
                            <input type="text" id="ysq_announcement_title" name="ysq_announcement_title" value="<?php echo esc_attr($editing_post ? $editing_post->post_title : ''); ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="ysq_announcement_content"><?php esc_html_e('Isi Pengumuman', 'ysq'); ?></label>
                            <textarea id="ysq_announcement_content" name="ysq_announcement_content" rows="6"><?php echo esc_textarea($editing_post ? $editing_post->post_content : ''); ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">
                                <?php echo $editing_post ? esc_html__('Simpan Perubahan', 'ysq') : esc_html__('Publikasikan', 'ysq'); ?>
                            </button>
                            <?php if ($editing_post) : ?>
                                <a class="btn-secondary" href="<?php echo esc_url(remove_query_arg('ysq_edit')); ?>"><?php esc_html_e('Batalkan', 'ysq'); ?></a>
                            <?php endif; ?>
                        </div>
                    </form>
                </section>

                <section class="dashboard-card">
                    <header class="card-header">
                        <h2><?php esc_html_e('Histori Pengumuman', 'ysq'); ?></h2>
                    </header>
                    <?php if (!empty($ysq_announcements)) : ?>
                        <ul class="announcement-history">
                            <?php foreach ($ysq_announcements as $announcement) : ?>
                                <li>
                                    <div class="history-main">
                                        <h3><?php echo esc_html(get_the_title($announcement)); ?></h3>
                                        <time datetime="<?php echo esc_attr(get_post_time('c', false, $announcement)); ?>"><?php echo esc_html(get_the_date('', $announcement)); ?></time>
                                    </div>
                                    <div class="history-actions">
                                        <a class="link-button" href="<?php echo esc_url(add_query_arg('ysq_edit', $announcement->ID)); ?>"><?php esc_html_e('Edit', 'ysq'); ?></a>
                                        <form method="post">
                                            <?php wp_nonce_field('ysq_delete_announcement_' . $announcement->ID, 'ysq_delete_announcement_nonce'); ?>
                                            <input type="hidden" name="ysq_delete_announcement" value="<?php echo esc_attr($announcement->ID); ?>">
                                            <button type="submit" class="link-button link-danger" onclick="return confirm('<?php echo esc_js(__('Hapus pengumuman ini?', 'ysq')); ?>');"><?php esc_html_e('Hapus', 'ysq'); ?></button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p><?php esc_html_e('Belum ada pengumuman yang dipublikasikan.', 'ysq'); ?></p>
                    <?php endif; ?>
                </section>

                <section class="dashboard-card">
                    <header class="card-header">
                        <h2><?php esc_html_e('Pengaturan Akun Administrator', 'ysq'); ?></h2>
                    </header>
                    <form method="post" class="form-stack">
                        <?php wp_nonce_field('ysq_update_credentials', 'ysq_update_credentials_nonce'); ?>
                        <input type="hidden" name="ysq_update_credentials" value="1">
                        <div class="form-field">
                            <label for="ysq_new_username"><?php esc_html_e('Username Administrator', 'ysq'); ?></label>
                            <input type="text" id="ysq_new_username" name="ysq_new_username" value="<?php echo esc_attr(function_exists('ysq_admin_get_username') ? ysq_admin_get_username() : 'administrator'); ?>" required>
                        </div>
                        <div class="form-field form-field-inline">
                            <div>
                                <label for="ysq_new_password"><?php esc_html_e('Password Baru', 'ysq'); ?></label>
                                <input type="password" id="ysq_new_password" name="ysq_new_password" autocomplete="new-password">
                            </div>
                            <div>
                                <label for="ysq_confirm_password"><?php esc_html_e('Konfirmasi Password', 'ysq'); ?></label>
                                <input type="password" id="ysq_confirm_password" name="ysq_confirm_password" autocomplete="new-password">
                            </div>
                        </div>
                        <p class="form-helper"><?php esc_html_e('Kosongkan password jika tidak ingin mengubahnya.', 'ysq'); ?></p>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary"><?php esc_html_e('Simpan Pengaturan', 'ysq'); ?></button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    <?php else : ?>
        <div class="public-dashboard">
            <section class="dashboard-card ysq-publication-section">
                <header class="card-header">
                    <h2><?php esc_html_e('Publikasi Terkini', 'ysq'); ?></h2>
                </header>
                <?php if (!empty($ysq_announcements)) : ?>
                    <div class="ysq-publication-grid ysq-publication-grid--home">
                        <?php foreach ($ysq_announcements as $announcement) : ?>
                            <?php
                            $announcement_id = isset($announcement->ID) ? absint($announcement->ID) : 0;
                            $thumbnail_url   = $announcement_id ? get_the_post_thumbnail_url($announcement_id, 'large') : '';
                            $date_iso        = $announcement_id ? get_post_time('c', false, $announcement) : '';
                            $date_display    = $announcement_id ? get_the_date('j M Y', $announcement) : '';
                            $terms           = $announcement_id ? wp_get_post_terms($announcement_id, 'ysq_publication_category') : array();
                            $primary_term    = (!empty($terms) && !is_wp_error($terms)) ? $terms[0] : null;
                            $primary_slug    = $primary_term ? sanitize_title($primary_term->slug) : '';
                            $primary_label   = '';

                            if ($primary_slug !== '') {
                                if (class_exists('HCISYSQ\\Announcements')) {
                                    $term_map = \HCISYSQ\Announcements::CATEGORY_TERMS;
                                    $primary_label = isset($term_map[$primary_slug]) ? $term_map[$primary_slug] : $primary_term->name;
                                } else {
                                    $primary_label = $primary_term->name;
                                }
                            }
                            ?>
                            <article class="ysq-publication-card<?php echo $thumbnail_url ? '' : ' is-placeholder'; ?>">
                                <div class="ysq-publication-card__media">
                                    <?php if ($thumbnail_url) : ?>
                                        <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr(get_the_title($announcement)); ?>">
                                    <?php else : ?>
                                        <span class="ysq-publication-card__placeholder"><?php esc_html_e('Tidak ada gambar', 'ysq'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="ysq-publication-card__body">
                                    <?php if ($primary_label !== '') : ?>
                                        <span class="ysq-publication-card__category"><?php echo esc_html($primary_label); ?></span>
                                    <?php endif; ?>
                                    <h3 class="ysq-publication-title"><?php echo esc_html(get_the_title($announcement)); ?></h3>
                                    <?php if ($date_display !== '') : ?>
                                        <time class="ysq-publication-date" datetime="<?php echo esc_attr($date_iso); ?>"><?php echo esc_html($date_display); ?></time>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="ysq-publication-empty"><?php esc_html_e('Belum ada publikasi untuk saat ini.', 'ysq'); ?></p>
                <?php endif; ?>

                <div class="announcement-feed__footer">
                    <a class="btn-secondary announcement-feed__more" href="<?php echo esc_url(home_url('/publikasi/')); ?>">
                        <?php esc_html_e('Lihat semua publikasi', 'ysq'); ?>
                    </a>
                </div>
            </section>

            <?php
            /**
             * The administrator login form has been removed from the public dashboard so that
             * a dedicated plugin can render the appropriate interface instead.
             */
            ?>
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
