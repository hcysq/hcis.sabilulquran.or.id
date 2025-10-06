<?php
/**
 * Archive template fallback for Publikasi.
 */

get_header();
?>
<div class="ysq-publikasi-archive">
  <header class="ysq-publikasi-archive-header">
    <h1><?php echo esc_html__('Publikasi', 'hcisysq'); ?></h1>
  </header>
  <?php if (have_posts()) : ?>
    <div class="ysq-publikasi-list">
      <?php while (have_posts()) : the_post(); ?>
        <?php $permalink = get_permalink(); ?>
        <article <?php post_class('ysq-publikasi-item'); ?>>
          <div class="ysq-publikasi-thumb">
            <?php if (has_post_thumbnail()) : ?>
              <?php the_post_thumbnail('medium_large', [
                'loading' => 'lazy',
                'alt'     => esc_attr(get_the_title()),
              ]); ?>
            <?php endif; ?>
          </div>
          <div class="ysq-publikasi-body">
            <div class="ysq-publikasi-meta">
              <?php echo esc_html(\HCISYSQ\Publikasi::format_meta(get_post(), false)); ?>
            </div>
            <h2 class="ysq-publikasi-title">
              <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html(get_the_title()); ?></a>
            </h2>
            <?php if (has_excerpt()) : ?>
              <div class="ysq-publikasi-excerpt">
                <?php echo wp_kses_post(get_the_excerpt()); ?>
              </div>
            <?php endif; ?>
          </div>
        </article>
      <?php endwhile; ?>
    </div>
    <div class="ysq-publikasi-pagination">
      <?php
      the_posts_pagination([
        'mid_size'  => 2,
        'prev_text' => esc_html__('&laquo; Sebelumnya', 'hcisysq'),
        'next_text' => esc_html__('Berikutnya &raquo;', 'hcisysq'),
      ]);
      ?>
    </div>
  <?php else : ?>
    <p class="ysq-publikasi-empty"><?php echo esc_html__('Belum ada publikasi.', 'hcisysq'); ?></p>
  <?php endif; ?>
</div>
<?php get_footer(); ?>
