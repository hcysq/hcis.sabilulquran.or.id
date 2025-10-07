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

$ysq_publications = null;

if (!$is_hcis_logged_in) {
    $ysq_publications = new WP_Query(
        array(
            'post_type'      => 'publikasi',
            'post_status'    => 'publish',
            'posts_per_page' => 6,
            'orderby'        => 'date',
            'order'          => 'DESC',
        )
    );
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

                <?php if ($ysq_publications instanceof WP_Query && $ysq_publications->have_posts()) : ?>
                    <div class="ysq-publication-grid ysq-publication-grid--home">
                        <?php
                        while ($ysq_publications->have_posts()) :
                            $ysq_publications->the_post();
                            $publication_id = get_the_ID();
                            $thumbnail_url  = get_the_post_thumbnail_url($publication_id, 'large');
                            $date_iso       = get_post_time('c', false, $publication_id);
                            $date_display   = get_the_date('j M Y', $publication_id);
                            $terms          = wp_get_post_terms($publication_id, 'category');
                            $primary_term   = (!empty($terms) && !is_wp_error($terms)) ? $terms[0] : null;
                            $primary_label  = $primary_term ? $primary_term->name : '';
                            ?>
                            <article class="ysq-publication-card<?php echo $thumbnail_url ? '' : ' is-placeholder'; ?>">
                                <div class="ysq-publication-card__media">
                                    <?php if ($thumbnail_url) : ?>
                                        <img src="<?php echo esc_url($thumbnail_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                                    <?php else : ?>
                                        <span class="ysq-publication-card__placeholder"><?php esc_html_e('Tidak ada gambar', 'ysq'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="ysq-publication-card__body">
                                    <?php if ($primary_label !== '') : ?>
                                        <span class="ysq-publication-card__category"><?php echo esc_html($primary_label); ?></span>
                                    <?php endif; ?>
                                    <h3 class="ysq-publication-title"><?php the_title(); ?></h3>
                                    <?php if ($date_display !== '') : ?>
                                        <time class="ysq-publication-date" datetime="<?php echo esc_attr($date_iso); ?>"><?php echo esc_html($date_display); ?></time>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
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

        </div>
    <?php endif; ?>
</div>

<?php
get_footer();
