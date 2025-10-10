<?php
namespace HCISYSQ {

use WP_Query;

if (!defined('ABSPATH')) exit;

class Publikasi_Post_Type {
  private const STYLE_HANDLE = 'hcisysq-publikasi';

  public static function init() {
    add_action('init', [__CLASS__, 'register_post_type']);
    add_action('init', [__CLASS__, 'register_rewrites']);
    add_filter('post_type_link', [__CLASS__, 'filter_post_type_link'], 10, 2);
    add_filter('query_vars', [__CLASS__, 'register_query_vars']);
    add_filter('request', [__CLASS__, 'resolve_request']);
    add_action('transition_post_status', [__CLASS__, 'assign_sequence'], 10, 3);
    add_action('wp', [__CLASS__, 'maybe_increment_views']);
    add_action('wp_enqueue_scripts', [__CLASS__, 'maybe_enqueue_assets']);
    add_filter('template_include', [__CLASS__, 'template_loader']);
    add_shortcode('ysq_publikasi_terbaru', [__CLASS__, 'shortcode_latest']);
  }

  public static function on_activation() {
    self::register_post_type();
    self::register_rewrites();
    flush_rewrite_rules();
  }

  public static function register_post_type() {
    $labels = [
      'name'               => __('Publikasi', 'hcisysq'),
      'singular_name'      => __('Publikasi', 'hcisysq'),
      'add_new'            => __('Tambah Publikasi', 'hcisysq'),
      'add_new_item'       => __('Tambah Publikasi Baru', 'hcisysq'),
      'edit_item'          => __('Edit Publikasi', 'hcisysq'),
      'new_item'           => __('Publikasi Baru', 'hcisysq'),
      'view_item'          => __('Lihat Publikasi', 'hcisysq'),
      'view_items'         => __('Publikasi', 'hcisysq'),
      'search_items'       => __('Cari Publikasi', 'hcisysq'),
      'not_found'          => __('Publikasi tidak ditemukan', 'hcisysq'),
      'not_found_in_trash' => __('Publikasi tidak ditemukan di tong sampah', 'hcisysq'),
      'all_items'          => __('Semua Publikasi', 'hcisysq'),
      'archives'           => __('Arsip Publikasi', 'hcisysq'),
    ];

    $args = [
      'label'               => __('Publikasi', 'hcisysq'),
      'labels'              => $labels,
      'public'              => true,
      'has_archive'         => true,
      'show_in_rest'        => true,
      'supports'            => ['title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions'],
      'taxonomies'          => ['category'],
      'rewrite'             => ['slug' => 'publikasi', 'with_front' => false],
      'menu_position'       => 20,
      'menu_icon'           => 'dashicons-media-document',
    ];

    register_post_type('publikasi', $args);
  }

  public static function register_rewrites() {
    add_rewrite_tag('%ysq_publikasi_cat%', '(.+?)');
    add_rewrite_tag('%ysq_publikasi_date%', '([0-9]{2}-[0-9]{2}-[0-9]{2})');
    add_rewrite_tag('%ysq_publikasi_seq%', '([0-9]+)');

    add_rewrite_rule(
      '^publikasi/(.+?)/([0-9]{2}-[0-9]{2}-[0-9]{2})/([0-9]+)/?$',
      'index.php?post_type=publikasi&ysq_publikasi_cat=$matches[1]&ysq_publikasi_date=$matches[2]&ysq_publikasi_seq=$matches[3]',
      'top'
    );

    add_rewrite_rule('^publikasi/page/([0-9]+)/?$', 'index.php?post_type=publikasi&paged=$matches[1]', 'top');
    add_rewrite_rule('^publikasi/?$', 'index.php?post_type=publikasi', 'top');
  }

  public static function register_query_vars($vars) {
    $vars[] = 'ysq_publikasi_cat';
    $vars[] = 'ysq_publikasi_date';
    $vars[] = 'ysq_publikasi_seq';
    return $vars;
  }

  public static function filter_post_type_link($permalink, $post) {
    if ($post->post_type !== 'publikasi') {
      return $permalink;
    }

    $category = self::get_primary_category_slug($post->ID);
    $date     = get_post_time('d-m-y', false, $post);
    $seq      = get_post_meta($post->ID, '_ysq_seq', true);

    if (!$category) {
      $category = 'uncategorized';
    }

    $sequence = $seq ? (int) $seq : 0;
    $structure = sprintf('publikasi/%s/%s/%d', $category, $date, $sequence);
    return home_url('/' . $structure . '/');
  }

  public static function resolve_request($vars) {
    if (isset($vars['post_type']) && $vars['post_type'] === 'publikasi' && !empty($vars['ysq_publikasi_seq']) && !empty($vars['ysq_publikasi_date'])) {
      $seq  = absint($vars['ysq_publikasi_seq']);
      $date = sanitize_text_field($vars['ysq_publikasi_date']);
      $cat  = isset($vars['ysq_publikasi_cat']) ? sanitize_text_field($vars['ysq_publikasi_cat']) : '';
      $cat_slug = '';

      if ($cat !== '') {
        $parts = array_filter(array_map('sanitize_title', explode('/', $cat)));
        if (!empty($parts)) {
          $cat_slug = end($parts);
        }
      }

      $dt = \DateTime::createFromFormat('d-m-y', $date);
      if ($dt) {
        $query_args = [
          'post_type'      => 'publikasi',
          'post_status'    => 'publish',
          'posts_per_page' => 1,
          'meta_query'     => [
            [
              'key'     => '_ysq_seq',
              'value'   => $seq,
              'compare' => '=',
              'type'    => 'NUMERIC',
            ],
          ],
          'date_query'     => [
            [
              'year'  => (int) $dt->format('Y'),
              'month' => (int) $dt->format('n'),
              'day'   => (int) $dt->format('j'),
            ],
          ],
          'fields'         => 'ids',
        ];

        if ($cat_slug) {
          $query_args['tax_query'] = [
            [
              'taxonomy' => 'category',
              'field'    => 'slug',
              'terms'    => $cat_slug,
            ],
          ];
        }

        $query = new WP_Query($query_args);
        if (!empty($query->posts)) {
          $post_id           = (int) $query->posts[0];
          $vars['p']         = $post_id;
          $vars['name']      = get_post_field('post_name', $post_id);
          $vars['page']      = '';
          $vars['post_type'] = 'publikasi';
        }
        wp_reset_postdata();
      }
    }

    return $vars;
  }

  public static function assign_sequence($new_status, $old_status, $post) {
    if ($post->post_type !== 'publikasi') {
      return;
    }
    if ($new_status !== 'publish' || $old_status === 'publish') {
      return;
    }

    $existing = get_post_meta($post->ID, '_ysq_seq', true);
    if ($existing) {
      return;
    }

    $timestamp = get_post_time('U', false, $post);
    $year      = (int) wp_date('Y', $timestamp);
    $month     = (int) wp_date('n', $timestamp);
    $day       = (int) wp_date('j', $timestamp);

    $query = new WP_Query([
      'post_type'      => 'publikasi',
      'post_status'    => 'publish',
      'fields'         => 'ids',
      'posts_per_page' => -1,
      'post__not_in'   => [$post->ID],
      'date_query'     => [
        [
          'year'      => $year,
          'month'     => $month,
          'day'       => $day,
          'inclusive' => true,
        ],
      ],
      'meta_query'     => [
        [
          'key'     => '_ysq_seq',
          'compare' => 'EXISTS',
        ],
      ],
    ]);

    $count = is_array($query->posts) ? count($query->posts) : 0;
    wp_reset_postdata();

    $sequence = $count + 1;
    update_post_meta($post->ID, '_ysq_seq', $sequence);
  }

  public static function maybe_increment_views() {
    if (is_admin() || !is_singular('publikasi')) {
      return;
    }

    $post_id = get_queried_object_id();
    if (!$post_id) {
      return;
    }

    $views = (int) get_post_meta($post_id, '_ysq_views', true);
    $views++;
    update_post_meta($post_id, '_ysq_views', $views);
  }

  public static function maybe_enqueue_assets() {
    if (is_post_type_archive('publikasi') || is_singular('publikasi')) {
      self::enqueue_style();
    }
  }

  public static function enqueue_style() {
    wp_enqueue_style(
      self::STYLE_HANDLE,
      HCISYSQ_URL . 'assets/ysq-publikasi.css',
      [],
      defined('HCISYSQ_VER') ? HCISYSQ_VER : false
    );
  }

  public static function shortcode_latest($atts) {
    if (!is_user_logged_in()) {
      return '';
    }

    $atts = shortcode_atts([
      'posts' => 10,
    ], $atts, 'ysq_publikasi_terbaru');

    $limit = (int) $atts['posts'];
    if ($limit <= 0) {
      $limit = 10;
    }

    self::enqueue_style();

    $query = new WP_Query([
      'post_type'      => 'publikasi',
      'post_status'    => 'publish',
      'posts_per_page' => $limit,
      'orderby'        => 'date',
      'order'          => 'DESC',
    ]);

    if (!$query->have_posts()) {
      wp_reset_postdata();
      return '';
    }

    ob_start();
    echo '<div class="ysq-publikasi-list">';
    while ($query->have_posts()) {
      $query->the_post();
      $post_id    = get_the_ID();
      $permalink  = get_permalink($post_id);
      $title      = get_the_title();
      $meta       = esc_html(self::format_meta(get_post(), false));

      echo '<article class="ysq-publikasi-item">';
      echo '<div class="ysq-publikasi-thumb">';
      if (has_post_thumbnail()) {
        the_post_thumbnail('medium_large', [
          'loading' => 'lazy',
          'alt'     => esc_attr($title),
        ]);
      }
      echo '</div>';
      echo '<div class="ysq-publikasi-body">';
      echo '<div class="ysq-publikasi-meta">' . $meta . '</div>';
      echo '<h3 class="ysq-publikasi-title"><a href="' . esc_url($permalink) . '">' . esc_html($title) . '</a></h3>';
      echo '</div>';
      echo '</article>';
    }
    echo '</div>';

    wp_reset_postdata();

    return ob_get_clean();
  }

  public static function template_loader($template) {
    if (is_post_type_archive('publikasi')) {
      $theme_template = locate_template('archive-publikasi.php');
      if ($theme_template) {
        return $theme_template;
      }
      self::enqueue_style();
      return HCISYSQ_DIR . 'templates/archive-publikasi.php';
    }

    if (is_singular('publikasi')) {
      $theme_template = locate_template('single-publikasi.php');
      if ($theme_template) {
        return $theme_template;
      }
      self::enqueue_style();
      return HCISYSQ_DIR . 'templates/single-publikasi.php';
    }

    return $template;
  }

  public static function format_meta($post, $include_views = true) {
    $timestamp    = get_post_time('U', false, $post);
    $date         = wp_date('l, d F Y H:i', $timestamp) . ' WIB';
    $author       = get_the_author_meta('display_name', $post->post_author);
    $meta         = $date . ' • ' . $author;

    if ($include_views) {
      $views_count   = ysq_get_views($post->ID);
      $views_display = number_format_i18n($views_count);
      $meta         .= ' • ' . sprintf(_n('%s kali dilihat', '%s kali dilihat', $views_count, 'hcisysq'), $views_display);
    }

    return $meta;
  }

  private static function get_primary_category_slug($post_id) {
    $terms = get_the_terms($post_id, 'category');
    if (!$terms || is_wp_error($terms)) {
      return '';
    }

    usort($terms, function ($a, $b) {
      return (int) $a->term_id - (int) $b->term_id;
    });

    $term = $terms[0];
    return $term ? $term->slug : '';
  }
}

if (!function_exists(__NAMESPACE__ . '\\ysq_get_views')) {
  function ysq_get_views($post_id) {
    return (int) get_post_meta($post_id, '_ysq_views', true);
  }
}
}

namespace {
  if (!function_exists('ysq_get_views')) {
    function ysq_get_views($post_id) {
      return \HCISYSQ\ysq_get_views($post_id);
    }
  }
}
