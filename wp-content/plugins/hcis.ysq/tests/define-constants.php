<?php
if (!defined('ABSPATH')) {
  define('ABSPATH', dirname(__DIR__, 4) . '/');
}
if (!defined('WP_CONTENT_DIR')) {
  define('WP_CONTENT_DIR', dirname(__DIR__, 3));
}
if (!function_exists('sanitize_text_field')) {
  function sanitize_text_field($str) {
    $str = is_scalar($str) ? (string) $str : '';
    return preg_replace('/[\x00-\x1F\x7F]/u', '', $str);
  }
}
if (!function_exists('current_time')) {
  function current_time($type = 'mysql') {
    return gmdate('Y-m-d H:i:s');
  }
}
if (!function_exists('wp_json_encode')) {
  function wp_json_encode($data, $options = 0, $depth = 512) {
    return json_encode($data, $options, $depth);
  }
}
if (!function_exists('wp_mkdir_p')) {
  function wp_mkdir_p($dir) {
    return is_dir($dir) ? true : mkdir($dir, 0755, true);
  }
}
if (!function_exists('do_action')) {
  function do_action($tag, ...$args) {
    // No-op for unit tests
  }
}
if (!function_exists('get_current_user_id')) {
  function get_current_user_id() {
    return 0;
  }
}
