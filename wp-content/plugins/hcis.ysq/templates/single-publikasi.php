<?php
/**
 * Single template fallback for Publikasi.
 */

get_header();
?>
<div class="ysq-publikasi-single-wrap">
  <?php while (have_posts()) : the_post(); ?>
    <div class="ysq-publikasi-single">
      <article <?php post_class('ysq-publikasi-article'); ?>>
        <?php if (has_post_thumbnail()) : ?>
          <div class="ysq-publikasi-single-hero">
            <?php the_post_thumbnail('large', [
              'loading' => 'lazy',
              'alt'     => esc_attr(get_the_title()),
            ]); ?>
          </div>
        <?php endif; ?>
        <header class="ysq-publikasi-single-header">
          <h1 class="ysq-publikasi-title"><?php echo esc_html(get_the_title()); ?></h1>
          <div class="ysq-publikasi-single-meta">
            <?php echo esc_html(\HCISYSQ\Publikasi_Post_Type::format_meta(get_post(), true)); ?>
          </div>
        </header>
        <div class="ysq-publikasi-content">
          <?php the_content(); ?>
        </div>
      </article>
      <aside class="ysq-publikasi-sidebar">
        <section class="ysq-publikasi-sidebar-section">
          <h2><?php echo esc_html__('Publikasi Terbaru', 'hcisysq'); ?></h2>
          <?php
          $recent = new WP_Query([
            'post_type'      => 'publikasi',
            'post_status'    => 'publish',
            'posts_per_page' => 6,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post__not_in'   => [get_the_ID()],
          ]);
          ?>
          <?php if ($recent->have_posts()) : ?>
            <ul class="ysq-publikasi-sidebar-list">
              <?php while ($recent->have_posts()) : $recent->the_post(); ?>
                <li>
                  <a href="<?php echo esc_url(get_permalink()); ?>"><?php echo esc_html(get_the_title()); ?></a>
                  <span class="ysq-publikasi-sidebar-date"><?php echo esc_html(get_the_date('d M Y')); ?></span>
                </li>
              <?php endwhile; ?>
            </ul>
          <?php else : ?>
            <p class="ysq-publikasi-empty"><?php echo esc_html__('Belum ada publikasi.', 'hcisysq'); ?></p>
          <?php endif; ?>
          <?php wp_reset_postdata(); ?>
        </section>
        <section class="ysq-publikasi-sidebar-section">
          <h2><?php echo esc_html__('Paling Banyak Dilihat', 'hcisysq'); ?></h2>
          <?php
          $popular = new WP_Query([
            'post_type'      => 'publikasi',
            'post_status'    => 'publish',
            'posts_per_page' => 6,
            'meta_key'       => '_ysq_views',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
            'post__not_in'   => [get_the_ID()],
          ]);
          ?>
          <?php if ($popular->have_posts()) : ?>
            <ul class="ysq-publikasi-sidebar-list">
              <?php while ($popular->have_posts()) : $popular->the_post(); ?>
                <li>
                  <a href="<?php echo esc_url(get_permalink()); ?>"><?php echo esc_html(get_the_title()); ?></a>
                  <span class="ysq-publikasi-sidebar-date"><?php echo esc_html(number_format_i18n(ysq_get_views(get_the_ID()))); ?> <?php echo esc_html__('kali dilihat', 'hcisysq'); ?></span>
                </li>
              <?php endwhile; ?>
            </ul>
          <?php else : ?>
            <p class="ysq-publikasi-empty"><?php echo esc_html__('Belum ada data.', 'hcisysq'); ?></p>
          <?php endif; ?>
          <?php wp_reset_postdata(); ?>
        </section>
      </aside>
    </div>
  <?php endwhile; ?>
</div>
<?php get_footer(); ?>
