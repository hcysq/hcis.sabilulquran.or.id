<?php
/**
 * Main Template File
 *
 * @package YSQ
 */

get_header();

$identity = null;
if (class_exists('HCISYSQ\\Auth') && method_exists('HCISYSQ\\Auth', 'current_identity')) {
    $identity = \HCISYSQ\Auth::current_identity();
}

$is_hcis_logged_in    = !empty($identity);
$can_render_dashboard = shortcode_exists('hcisysq_dashboard');
$can_render_login     = shortcode_exists('hcisysq_login');

$ysq_announcements = array();
$category_map      = array();

if (!$is_hcis_logged_in) {
    $announcement_args = array(
        'post_type'      => 'ysq_announcement',
        'posts_per_page' => 6,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    $ysq_announcements = get_posts($announcement_args);

    if (class_exists('HCISYSQ\\Announcements')) {
        $category_map = \HCISYSQ\Announcements::CATEGORY_TERMS;
    }
}
?>

<div class="content-wrapper">
    <?php if ($is_hcis_logged_in && $can_render_dashboard) : ?>
        <?php echo do_shortcode('[hcisysq_dashboard]'); ?>
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
                                if (!empty($category_map) && isset($category_map[$primary_slug])) {
                                    $primary_label = $category_map[$primary_slug];
                                } elseif ($primary_term) {
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

            <?php if ($can_render_login) : ?>
                <section class="dashboard-card ysq-login-section">
                    <?php echo do_shortcode('[hcisysq_login]'); ?>
                </section>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
