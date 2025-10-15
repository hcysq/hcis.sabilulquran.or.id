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
if (!defined('HCISYSQ_RESET_SLUG'))     define('HCISYSQ_RESET_SLUG', 'ganti-password');

// StarSender (opsional – untuk "lupa password")
if (!defined('HCISYSQ_SS_URL')) define('HCISYSQ_SS_URL', 'https://starsender.online/api/sendText');

/* =======================================================
 * Includes Composer Autoloader
 * ======================================================= */

require_once HCISYSQ_DIR . 'vendor/autoload.php';

/* =======================================================
 *  Activation / Deactivation hooks
 * ======================================================= */
register_activation_hook(__FILE__, ['HCISYSQ\\Installer', 'activate']);
register_deactivation_hook(__FILE__, ['HCISYSQ\\Installer', 'deactivate']);

add_action('plugins_loaded', function(){
  if (get_option('hcisysq_schema_version') !== \HCISYSQ\Installer::SCHEMA_VERSION) {
    \HCISYSQ\Installer::activate();
  }
});

/* =======================================================
 *  Init modules
 * ======================================================= */
HCISYSQ\Config::init();
HCISYSQ\Assets::init();
HCISYSQ\Shortcodes::init();
HCISYSQ\Publikasi::init();
HCISYSQ\Publikasi_Post_Type::init();
HCISYSQ\Hcis_Gas_Token::init();
HCISYSQ\Tasks::init();
HCISYSQ\Legacy_Admin_Bridge::init();
HCISYSQ\Migration::init();
HCISYSQ\NipAuthentication::init();
HCISYSQ\ProfileWizard::init();

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
add_action('wp_ajax_hcisysq_admin_create_publication', ['HCISYSQ\\Api', 'admin_create_publication']);
add_action('wp_ajax_nopriv_hcisysq_admin_create_publication', ['HCISYSQ\\Api', 'admin_create_publication']);
add_action('wp_ajax_hcisysq_admin_update_publication', ['HCISYSQ\\Api', 'admin_update_publication']);
add_action('wp_ajax_nopriv_hcisysq_admin_update_publication', ['HCISYSQ\\Api', 'admin_update_publication']);
add_action('wp_ajax_hcisysq_admin_delete_publication', ['HCISYSQ\\Api', 'admin_delete_publication']);
add_action('wp_ajax_nopriv_hcisysq_admin_delete_publication', ['HCISYSQ\\Api', 'admin_delete_publication']);
add_action('wp_ajax_hcisysq_admin_set_publication_status', ['HCISYSQ\\Api', 'admin_set_publication_status']);
add_action('wp_ajax_nopriv_hcisysq_admin_set_publication_status', ['HCISYSQ\\Api', 'admin_set_publication_status']);
add_action('wp_ajax_hcisysq_admin_save_settings', ['HCISYSQ\\Api', 'admin_save_settings']);
add_action('wp_ajax_nopriv_hcisysq_admin_save_settings', ['HCISYSQ\\Api', 'admin_save_settings']);
add_action('wp_ajax_hcisysq_admin_save_home_settings', ['HCISYSQ\\Api', 'admin_save_home_settings']);
add_action('wp_ajax_nopriv_hcisysq_admin_save_home_settings', ['HCISYSQ\\Api', 'admin_save_home_settings']);
add_action('wp_ajax_hcisysq_admin_create_task', ['HCISYSQ\\Api', 'admin_create_task']);
add_action('wp_ajax_hcisysq_admin_update_task', ['HCISYSQ\\Api', 'admin_update_task']);
add_action('wp_ajax_hcisysq_admin_delete_task', ['HCISYSQ\\Api', 'admin_delete_task']);
add_action('wp_ajax_hcisysq_admin_set_task_status', ['HCISYSQ\\Api', 'admin_set_task_status']);
add_action('wp_ajax_hcisysq_admin_update_assignment', ['HCISYSQ\\Api', 'admin_update_assignment']);
add_action('wp_ajax_ysq_get_employees_by_units', ['HCISYSQ\\Api', 'ysq_get_employees_by_units']);
add_action('wp_ajax_ysq_get_all_profiles', ['HCISYSQ\\Api', 'ysq_api_get_all_profiles']);
add_action('wp_ajax_ysq_update_profile', ['HCISYSQ\\Api', 'ysq_api_update_profile']);

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
    $tab_name = \HCISYSQ\Users::get_tab_name();
    $url = \HCISYSQ\Users::build_csv_url($sheet_id, $tab_name);
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



/**
 * Memblokir akses ke area /wp-admin untuk peran 'hcis_admin'
 * dan mengalihkan mereka ke /dashboard.
 * Ini menggantikan fungsi hcisysq_hide_admin_menus yang lama.
 */
function hcisysq_block_wp_admin_access() {
  // Jalankan hanya di area admin dan bukan untuk request AJAX
  if (is_admin() && !wp_doing_ajax()) {
    // Dapatkan info pengguna saat ini
    $user = wp_get_current_user();

    // Kondisi: Punya peran 'hcis_admin' TAPI BUKAN 'administrator'
    if (in_array('hcis_admin', (array) $user->roles, true) && !in_array('administrator', (array) $user->roles, true)) {
      // Alihkan paksa ke halaman dashboard frontend
      wp_redirect(home_url('/dashboard'));
      exit;
    }
  }
}
add_action('admin_init', 'hcisysq_block_wp_admin_access');

/* =======================================================
 *  Selesai
 * ======================================================= */
