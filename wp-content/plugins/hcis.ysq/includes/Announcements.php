<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Announcements {
  const OPTION = 'hcisysq_announcements';
  const POST_TYPE = 'ysq_announcement';
  const META_LINK_LABEL = '_hcisysq_link_label';
  const META_LINK_URL = '_hcisysq_link_url';
  const META_ARCHIVED_AT = '_hcisysq_archived_at';

  public static function init(){
    add_action('init', [__CLASS__, 'register_post_type'], 0);
    add_action('init', [__CLASS__, 'maybe_migrate'], 5);
    add_action('init', [__CLASS__, 'ensure_seed'], 11);
  }

  public static function register_post_type(){
    register_post_type(self::POST_TYPE, [
      'labels' => [
        'name' => \__('Announcements', 'hcisysq'),
        'singular_name' => \__('Announcement', 'hcisysq'),
      ],
      'public' => false,
      'show_ui' => false,
      'show_in_nav_menus' => false,
      'exclude_from_search' => true,
      'show_in_rest' => false,
      'rewrite' => false,
      'query_var' => false,
      'supports' => ['title', 'editor'],
    ]);
  }

  public static function maybe_migrate(){
    $items = get_option(self::OPTION, null);
    if ($items === null || $items === false) return;

    if (is_array($items) && !empty($items)) {
      foreach ($items as $item) {
        if (!is_array($item)) continue;

        self::create([
          'title'       => $item['title'] ?? '',
          'body'        => $item['body'] ?? '',
          'link_label'  => $item['link_label'] ?? '',
          'link_url'    => $item['link_url'] ?? '',
          'status'      => $item['status'] ?? 'published',
          'created_at'  => $item['created_at'] ?? '',
          'updated_at'  => $item['updated_at'] ?? '',
          'archived_at' => $item['archived_at'] ?? null,
        ]);
      }
    }

    delete_option(self::OPTION);
  }

  public static function ensure_seed(){
    $existing = get_posts([
      'post_type'      => self::POST_TYPE,
      'posts_per_page' => 1,
      'post_status'    => ['publish', 'draft'],
      'fields'         => 'ids',
    ]);

    if (!empty($existing)) return;

    $now = current_time('mysql');
    $defaults = [
      [
        'title'       => 'Pembaruan Data Pegawai',
        'body'        => 'Isi form pelatihan terbaru.',
        'link_label'  => 'Isi form pelatihan terbaru',
        'link_url'    => '__TRAINING_FORM__',
        'status'      => 'published',
        'created_at'  => $now,
        'updated_at'  => $now,
        'archived_at' => null,
      ],
      [
        'title'       => 'SPMB 2026/2027',
        'body'        => 'Pendaftaran telah dibuka.',
        'link_label'  => 'Pendaftaran telah dibuka',
        'link_url'    => 'https://ppdb.sabilulquran.or.id',
        'status'      => 'published',
        'created_at'  => $now,
        'updated_at'  => $now,
        'archived_at' => null,
      ],
      [
        'title'       => "Ikuti Sabilul Qur'an di Instagram",
        'body'        => '@sabilulquran.',
        'link_label'  => '@sabilulquran',
        'link_url'    => 'https://instagram.com/sabilulquran',
        'status'      => 'published',
        'created_at'  => $now,
        'updated_at'  => $now,
        'archived_at' => null,
      ],
    ];

    foreach ($defaults as $default) {
      self::create($default);
    }
  }

  public static function all(){
    $posts = get_posts([
      'post_type'      => self::POST_TYPE,
      'posts_per_page' => -1,
      'post_status'    => ['publish', 'draft'],
      'orderby'        => 'date',
      'order'          => 'ASC',
    ]);

    return array_map([__CLASS__, 'format_post'], $posts);
  }

  public static function create(array $data){
    $title = sanitize_text_field($data['title'] ?? '');
    $body  = RichText::sanitize($data['body'] ?? '');
    $link_label = sanitize_text_field($data['link_label'] ?? '');
    $link_url = self::sanitize_link_url($data['link_url'] ?? '');
    $status = self::normalize_status($data['status'] ?? 'published');

    $created_at = self::sanitize_datetime($data['created_at'] ?? '') ?: current_time('mysql');
    $updated_at = self::sanitize_datetime($data['updated_at'] ?? '') ?: $created_at;
    $archived_at = self::sanitize_datetime($data['archived_at'] ?? null);

    $postarr = [
      'post_title'        => $title,
      'post_content'      => $body,
      'post_type'         => self::POST_TYPE,
      'post_status'       => self::status_to_post_status($status),
      'post_date'         => $created_at,
      'post_date_gmt'     => get_gmt_from_date($created_at),
      'post_modified'     => $updated_at,
      'post_modified_gmt' => get_gmt_from_date($updated_at),
      'meta_input'        => [
        self::META_LINK_LABEL => $link_label,
        self::META_LINK_URL   => $link_url,
      ],
    ];

    if ($archived_at) {
      $postarr['meta_input'][self::META_ARCHIVED_AT] = $archived_at;
    }

    $post_id = wp_insert_post($postarr, true);
    if (is_wp_error($post_id) || !$post_id) {
      return null;
    }

    if ($status === 'archived' && !$archived_at) {
      update_post_meta($post_id, self::META_ARCHIVED_AT, current_time('mysql'));
    }

    if ($status === 'published') {
      delete_post_meta($post_id, self::META_ARCHIVED_AT);
    }

    return self::find($post_id);
  }

  public static function update($id, array $data){
    $post_id = absint($id);
    if (!$post_id) return null;

    $post = get_post($post_id);
    if (!$post || $post->post_type !== self::POST_TYPE) {
      return null;
    }

    $title = array_key_exists('title', $data) ? sanitize_text_field($data['title']) : $post->post_title;
    $body  = array_key_exists('body', $data) ? RichText::sanitize($data['body']) : $post->post_content;
    $status = array_key_exists('status', $data)
      ? self::normalize_status($data['status'])
      : self::status_from_post_status($post->post_status);

    $postarr = [
      'ID'           => $post_id,
      'post_title'   => $title,
      'post_content' => $body,
      'post_status'  => self::status_to_post_status($status),
    ];

    $result = wp_update_post($postarr, true);
    if (is_wp_error($result) || !$result) {
      return null;
    }

    if (array_key_exists('link_label', $data)) {
      update_post_meta($post_id, self::META_LINK_LABEL, sanitize_text_field($data['link_label']));
    }

    if (array_key_exists('link_url', $data)) {
      update_post_meta($post_id, self::META_LINK_URL, self::sanitize_link_url($data['link_url']));
    }

    $archived_at = null;
    if (array_key_exists('archived_at', $data)) {
      $archived_at = self::sanitize_datetime($data['archived_at']);
    } else {
      $archived_at = self::sanitize_datetime(get_post_meta($post_id, self::META_ARCHIVED_AT, true));
    }

    if ($status === 'archived') {
      if (!$archived_at) {
        $archived_at = current_time('mysql');
      }
      update_post_meta($post_id, self::META_ARCHIVED_AT, $archived_at);
    } else {
      delete_post_meta($post_id, self::META_ARCHIVED_AT);
    }

    return self::find($post_id);
  }

  public static function delete($id){
    $post_id = absint($id);
    if (!$post_id) return false;

    $result = wp_delete_post($post_id, true);
    return (bool)$result;
  }

  public static function set_status($id, $status){
    if (!in_array($status, ['published', 'archived'], true)) return null;
    return self::update($id, ['status' => $status]);
  }

  public static function published_for_user(array $context = []){
    $items = array_filter(self::all(), function($item){
      return ($item['status'] ?? 'published') === 'published';
    });

    $training_link = $context['training_link'] ?? '';
    $out = [];
    foreach ($items as $item) {
      $link_url = $item['link_url'] ?? '';
      if ($link_url === '__TRAINING_FORM__' && $training_link) {
        $link_url = $training_link;
      } elseif ($link_url === '__TRAINING_FORM__') {
        $link_url = '';
      }

      $out[] = [
        'id'         => $item['id'],
        'title'      => $item['title'],
        'body'       => $item['body'],
        'link_label' => $item['link_label'],
        'link_url'   => $link_url,
      ];
    }

    return $out;
  }

  private static function sanitize_link_url($url){
    if ($url === '__TRAINING_FORM__') {
      return '__TRAINING_FORM__';
    }

    $sanitized = esc_url_raw($url);
    if ($sanitized === esc_url_raw('__TRAINING_FORM__')) {
      return '__TRAINING_FORM__';
    }

    return $sanitized;
  }

  private static function sanitize_datetime($value){
    if ($value === null || $value === '') {
      return null;
    }

    return sanitize_text_field($value);
  }

  private static function normalize_status($status){
    return in_array($status, ['published', 'archived'], true) ? $status : 'published';
  }

  private static function status_to_post_status($status){
    return $status === 'archived' ? 'draft' : 'publish';
  }

  private static function status_from_post_status($post_status){
    return $post_status === 'publish' ? 'published' : 'archived';
  }

  private static function find($post_id){
    $post = get_post($post_id);
    if (!$post || $post->post_type !== self::POST_TYPE) {
      return null;
    }

    return self::format_post($post);
  }

  private static function format_post($post){
    $link_label = get_post_meta($post->ID, self::META_LINK_LABEL, true);
    $link_url = get_post_meta($post->ID, self::META_LINK_URL, true);
    $archived_at = get_post_meta($post->ID, self::META_ARCHIVED_AT, true);

    return [
      'id'          => strval($post->ID),
      'title'       => $post->post_title,
      'body'        => RichText::sanitize($post->post_content),
      'link_label'  => is_string($link_label) ? $link_label : '',
      'link_url'    => is_string($link_url) ? $link_url : '',
      'status'      => self::status_from_post_status($post->post_status),
      'created_at'  => $post->post_date,
      'updated_at'  => $post->post_modified,
      'archived_at' => $archived_at ? sanitize_text_field($archived_at) : null,
    ];
  }
}
