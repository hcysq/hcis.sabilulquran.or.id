<?php
/**
 * Plugin Name: HCIS YSQ (hcis.ysq)
 * Description: Login NIP+HP, Dashboard Pegawai, Form Pelatihan dengan Google Sheets Integration + SSO ke Google Apps Script.
 * Version: 1.5
 * Author: samijaya
 */

if (!defined('ABSPATH')) exit;

/* =======================================================
 *  Logger lokal (independen dari WP_DEBUG)
 * ======================================================= */
if (!defined('HCISYSQ_LOG_FILE')) {
  define('HCISYSQ_LOG_FILE', WP_CONTENT_DIR . '/hcisysq.log');
}
if (!function_exists('hcisysq_log')) {
  function hcisysq_log($data) {
    $msg = '[HCIS.YSQ ' . date('Y-m-d H:i:s') . '] ';
    $msg .= is_scalar($data) ? $data : print_r($data, true);
    $msg .= PHP_EOL;
    @error_log($msg, 3, HCISYSQ_LOG_FILE); // tulis ke wp-content/hcisysq.log
  }
}
// tangkap warning/notice
set_error_handler(function($errno, $errstr, $errfile, $errline){
  hcisysq_log("PHP[$errno] $errstr @ $errfile:$errline");
  return false;
});
// tangkap fatal error
register_shutdown_function(function(){
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
    hcisysq_log("FATAL {$e['message']} @ {$e['file']}:{$e['line']}");
  }
});
hcisysq_log('hcis.ysq plugin boot...');

/* =======================================================
 *  Konstanta plugin
 * ======================================================= */
if (!defined('HCISYSQ_VER')) define('HCISYSQ_VER', '1.5');
if (!defined('HCISYSQ_DIR')) define('HCISYSQ_DIR', plugin_dir_path(__FILE__));
if (!defined('HCISYSQ_URL')) define('HCISYSQ_URL', plugin_dir_url(__FILE__));

// Slug halaman
if (!defined('HCISYSQ_LOGIN_SLUG'))     define('HCISYSQ_LOGIN_SLUG', 'masuk');
if (!defined('HCISYSQ_DASHBOARD_SLUG')) define('HCISYSQ_DASHBOARD_SLUG', 'dashboard');
if (!defined('HCISYSQ_FORM_SLUG'))      define('HCISYSQ_FORM_SLUG', 'pelatihan');

// StarSender (opsional – untuk "lupa password")
if (!defined('HCISYSQ_SS_URL')) define('HCISYSQ_SS_URL', 'https://starsender.online/api/sendText');
if (!defined('HCISYSQ_SS_KEY')) define('HCISYSQ_SS_KEY', '4a74d8ae-8d5d-4e95-8f14-9429409c9eda'); // ganti sesuai
if (!defined('HCISYSQ_SS_HC'))  define('HCISYSQ_SS_HC',  '6285175201627'); // ganti sesuai

/* =======================================================
 *  Includes
 * ======================================================= */
require_once HCISYSQ_DIR . 'includes/Installer.php';
require_once HCISYSQ_DIR . 'includes/Auth.php';
require_once HCISYSQ_DIR . 'includes/Api.php';
require_once HCISYSQ_DIR . 'includes/View.php';
require_once HCISYSQ_DIR . 'includes/Profiles.php';
require_once HCISYSQ_DIR . 'includes/Users.php';
require_once HCISYSQ_DIR . 'includes/Trainings.php';
require_once HCISYSQ_DIR . 'includes/RichText.php';
require_once HCISYSQ_DIR . 'includes/Announcements.php';
require_once HCISYSQ_DIR . 'includes/Admin.php';
require_once HCISYSQ_DIR . 'includes/Assets.php';
require_once HCISYSQ_DIR . 'includes/Shortcodes.php';
require_once HCISYSQ_DIR . 'includes/Forgot.php';
require_once HCISYSQ_DIR . 'includes/Publikasi.php';
require_once HCISYSQ_DIR . 'includes/Hcis_Gas_Token.php';
require_once HCISYSQ_DIR . 'includes/Legacy_Admin_Bridge.php';

/* =======================================================
 *  Activation (create tables)
 * ======================================================= */
register_activation_hook(__FILE__, ['HCISYSQ\\Installer', 'activate']);

/* =======================================================
 *  Init modules
 * ======================================================= */
HCISYSQ\Assets::init();
HCISYSQ\Shortcodes::init();
HCISYSQ\Forgot::init();
HCISYSQ\Announcements::init();
HCISYSQ\Publikasi::init();
HCISYSQ\Hcis_Gas_Token::init();
HCISYSQ\Legacy_Admin_Bridge::init();

/* =======================================================
 *  AJAX endpoints
 * ======================================================= */
add_action('wp_ajax_nopriv_hcisysq_login',    ['HCISYSQ\\Api', 'login']);
add_action('wp_ajax_hcisysq_logout',          ['HCISYSQ\\Api', 'logout']);
add_action('wp_ajax_nopriv_hcisysq_logout',   ['HCISYSQ\\Api', 'logout']);
add_action('wp_ajax_hcisysq_submit_training', ['HCISYSQ\\Api', 'submit_training']);
add_action('wp_ajax_nopriv_hcisysq_submit_training', function(){
  wp_send_json(['ok'=>false,'msg'=>'Unauthorized']);
});
add_action('wp_ajax_hcisysq_admin_create_announcement', ['HCISYSQ\\Api', 'admin_create_announcement']);
add_action('wp_ajax_nopriv_hcisysq_admin_create_announcement', ['HCISYSQ\\Api', 'admin_create_announcement']);
add_action('wp_ajax_hcisysq_admin_update_announcement', ['HCISYSQ\\Api', 'admin_update_announcement']);
add_action('wp_ajax_nopriv_hcisysq_admin_update_announcement', ['HCISYSQ\\Api', 'admin_update_announcement']);
add_action('wp_ajax_hcisysq_admin_delete_announcement', ['HCISYSQ\\Api', 'admin_delete_announcement']);
add_action('wp_ajax_nopriv_hcisysq_admin_delete_announcement', ['HCISYSQ\\Api', 'admin_delete_announcement']);
add_action('wp_ajax_hcisysq_admin_set_announcement_status', ['HCISYSQ\\Api', 'admin_set_announcement_status']);
add_action('wp_ajax_nopriv_hcisysq_admin_set_announcement_status', ['HCISYSQ\\Api', 'admin_set_announcement_status']);
add_action('wp_ajax_hcisysq_admin_save_settings', ['HCISYSQ\\Api', 'admin_save_settings']);
add_action('wp_ajax_nopriv_hcisysq_admin_save_settings', ['HCISYSQ\\Api', 'admin_save_settings']);
add_action('wp_ajax_hcisysq_admin_save_home_settings', ['HCISYSQ\\Api', 'admin_save_home_settings']);
add_action('wp_ajax_nopriv_hcisysq_admin_save_home_settings', ['HCISYSQ\\Api', 'admin_save_home_settings']);

/* =======================================================
 *  Cron (jika pakai import)
 * ======================================================= */
add_action('hcisysq_profiles_cron', function(){
  $url = \HCISYSQ\Profiles::get_csv_url();
  if ($url) \HCISYSQ\Profiles::import_from_csv($url);
});
add_action('hcisysq_users_cron', function(){
  $sheet_id = \HCISYSQ\Users::get_sheet_id();
  if ($sheet_id) {
    $url = "https://docs.google.com/spreadsheets/d/{$sheet_id}/export?format=csv&gid=0";
    \HCISYSQ\Users::import_from_csv($url);
  }
});

add_action('admin_menu', ['HCISYSQ\\Admin','menu']);

/* =======================================================
 *  Proteksi halaman (guard)
 * ======================================================= */
add_action('template_redirect', function () {
  $to = function ($slug) { return home_url('/' . ltrim($slug, '/') . '/'); };

  $identity = HCISYSQ\Auth::current_identity();
  $hasUser  = $identity && ($identity['type'] ?? null) === 'user';
  $hasAdmin = $identity && ($identity['type'] ?? null) === 'admin';

  if (is_page([HCISYSQ_DASHBOARD_SLUG, HCISYSQ_FORM_SLUG])) {
    if (!$hasUser && !$hasAdmin) {
      hcisysq_log('guard: not logged, redirect to /' . HCISYSQ_LOGIN_SLUG);
      wp_safe_redirect($to(HCISYSQ_LOGIN_SLUG));
      exit;
    }
  }
  if (($hasUser || $hasAdmin) && (is_front_page() || is_home())) {
    hcisysq_log('guard: active session, redirect home to /' . HCISYSQ_DASHBOARD_SLUG);
    wp_safe_redirect($to(HCISYSQ_DASHBOARD_SLUG));
    exit;
  }
  if (is_page(HCISYSQ_LOGIN_SLUG) && ($hasUser || $hasAdmin)) {
    hcisysq_log('guard: already logged, redirect to /' . HCISYSQ_DASHBOARD_SLUG);
    wp_safe_redirect($to(HCISYSQ_DASHBOARD_SLUG));
    exit;
  }
});

/* =======================================================
 *  Selesai
 * ======================================================= */
