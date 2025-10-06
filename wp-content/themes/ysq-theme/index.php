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
    'posts_per_page' => -1,
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
            <section class="dashboard-card announcement-feed">
                <header class="card-header">
                    <h2><?php esc_html_e('Pengumuman Terbaru', 'ysq'); ?></h2>
                </header>
                <div class="announcement-feed__body">
                    <?php if (!empty($ysq_announcements)) : ?>
                        <ul class="announcement-list">
                            <?php foreach ($ysq_announcements as $announcement) : ?>
                            <?php
                            $announcement_id    = isset($announcement->ID) ? absint($announcement->ID) : 0;
                            $announcement_meta  = $announcement_id && isset($ysq_announcement_links[$announcement_id]) ? $ysq_announcement_links[$announcement_id] : null;
                            $announcement_label = '';
                            $announcement_link  = '';
                            $is_training_link   = false;

                            if (is_array($announcement_meta)) {
                                $announcement_label = isset($announcement_meta['link_label']) ? (string) $announcement_meta['link_label'] : '';
                                $announcement_link  = isset($announcement_meta['link_url']) ? (string) $announcement_meta['link_url'] : '';
                                $is_training_link   = ($announcement_link === '__TRAINING_FORM__');
                            }

                            $training_note_text = __('(tersedia dinamis di dashboard pegawai)', 'ysq');
                            $training_label     = $announcement_label !== '' ? $announcement_label : __('Form Pelatihan Terbaru', 'ysq');
                            $training_target    = '';

                            if ($is_training_link) {
                                if (defined('HCISYSQ_FORM_SLUG')) {
                                    $form_slug = trim((string) HCISYSQ_FORM_SLUG, '/');
                                    $training_target = home_url('/' . ($form_slug !== '' ? $form_slug . '/' : ''));
                                } else {
                                    $training_target = home_url('/pelatihan/');
                                }
                            }

                            $has_valid_link = false;
                            if ($announcement_link && !$is_training_link) {
                                $has_valid_link = (bool) wp_http_validate_url($announcement_link);
                            }
                            ?>
                            <li>
                                <h3><?php echo esc_html(get_the_title($announcement)); ?></h3>
                                <time datetime="<?php echo esc_attr(get_post_time('c', false, $announcement)); ?>"><?php echo esc_html(get_the_date('', $announcement)); ?></time>
                                <div class="announcement-body">
                                    <?php echo wp_kses_post(wpautop($announcement->post_content)); ?>
                                </div>
                                <?php if ($has_valid_link) : ?>
                                    <p class="announcement-link">
                                        <a href="<?php echo esc_url($announcement_link); ?>" target="_blank" rel="noopener">
                                            <?php echo esc_html($announcement_label !== '' ? $announcement_label : __('Buka tautan', 'ysq')); ?>
                                        </a>
                                    </p>
                                <?php elseif ($is_training_link && $training_target) : ?>
                                    <p class="announcement-link">
                                        <a href="<?php echo esc_url($training_target); ?>">
                                            <?php echo esc_html($training_label); ?>
                                        </a>
                                        <span class="announcement-note"><?php echo esc_html($training_note_text); ?></span>
                                    </p>
                                <?php elseif ($is_training_link) : ?>
                                    <p class="announcement-link">
                                        <?php echo esc_html($training_label); ?>
                                        <span class="announcement-note"><?php echo esc_html($training_note_text); ?></span>
                                    </p>
                                <?php elseif ($announcement_label !== '') : ?>
                                    <p class="announcement-link">
                                        <?php echo esc_html($announcement_label); ?>
                                    </p>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="announcement-empty"><?php esc_html_e('Belum ada pengumuman untuk saat ini.', 'ysq'); ?></p>
                    <?php endif; ?>
                </div>

                <div class="announcement-feed__footer">
                    <a class="btn-secondary announcement-feed__more" href="<?php echo esc_url(home_url('/publikasi/')); ?>">
                        <?php esc_html_e('Selengkapnya', 'ysq'); ?>
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
