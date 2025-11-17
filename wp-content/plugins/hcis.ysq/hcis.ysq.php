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
  function hcisysq_log($data, $level = 'info', array $context = []) {
    // Use new ErrorHandler if available, fall back to legacy logging
    if (class_exists('HCISYSQ\ErrorHandler')) {
      \HCISYSQ\ErrorHandler::log(
        is_scalar($data) ? $data : print_r($data, true),
        $level,
        $context
      );
    } else {
      // Legacy fallback: write to simple log file
      $msg = '[HCIS.YSQ ' . date('Y-m-d H:i:s') . '] ';
      $msg .= '[' . strtoupper($level) . '] ';
      $msg .= is_scalar($data) ? $data : print_r($data, true);
      $msg .= PHP_EOL;
      @error_log($msg, 3, HCISYSQ_LOG_FILE);
    }
  }
}
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
require_once HCISYSQ_DIR . 'includes/PasswordResetManager.php';
require_once HCISYSQ_DIR . 'includes/PasswordReset.php';
require_once HCISYSQ_DIR . 'includes/StarSender.php';

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

add_action('init', ['HCISYSQ\\Installer', 'maybe_ensure_login_page'], 1);

/* =======================================================
 *  Init modules
 * ======================================================= */
HCISYSQ\Config::init();
HCISYSQ\Security::init();

// Initialize error handling with structured logging (must be first)
if (class_exists('HCISYSQ\ErrorHandler')) {
  HCISYSQ\ErrorHandler::init();
  hcisysq_log('Structured error handling initialized', 'info', ['component' => 'bootstrap']);
}

HCISYSQ\Admin::init();
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
HCISYSQ\SessionMaintenance::init();

// Initialize the new Password Reset flow
if (class_exists('Hcis\Ysq\Includes\PasswordReset')) {
    Hcis\Ysq\Includes\PasswordReset::init();
    hcisysq_log('Password Reset module initialized');
}

// Initialize Google Sheets real-time sync hooks
if (class_exists('HCISYSQ\GoogleSheetsSync')) {
  HCISYSQ\GoogleSheetsSync::init();
  hcisysq_log('Google Sheets real-time sync hooks initialized');
}

// Initialize Google Sheets settings page
if (class_exists('HCISYSQ\GoogleSheetSettings')) {
  HCISYSQ\GoogleSheetSettings::init();
  hcisysq_log('Google Sheets settings page initialized');
}

// Initialize Google Sheets metrics dashboard
if (class_exists('HCISYSQ\GoogleSheetMetrics')) {
  HCISYSQ\GoogleSheetMetrics::init();
  hcisysq_log('Google Sheets metrics dashboard initialized');
}

// Initialize admin logs viewer
if (class_exists('HCISYSQ\AdminLogsViewer')) {
  HCISYSQ\AdminLogsViewer::init();
  hcisysq_log('Admin logs viewer initialized');
}

if (class_exists('HCISYSQ\Logging\LogsEndpoint')) {
  HCISYSQ\Logging\LogsEndpoint::init();
}

/* =======================================================
 *  AJAX endpoints + middleware inventory
 * ======================================================= */
$hcisysq_ajax_endpoints = [
  [
    'action'         => 'hcisysq_login',
    'callback'       => ['HCISYSQ\\Api', 'login'],
    'nopriv'         => true,
    'channel'        => 'login',
    'captcha_action' => 'login',
  ],
  [
    'action'   => 'hcisysq_logout',
    'callback' => ['HCISYSQ\\Api', 'logout'],
    'nopriv'   => true,
    'channel'  => 'logout',
  ],
  [
    'action'         => 'hcisysq_submit_training',
    'callback'       => ['HCISYSQ\\Api', 'submit_training'],
    'channel'        => 'training',
    'captcha_action' => 'training',
  ],
  [
    'action'   => 'hcisysq_admin_create_publication',
    'callback' => ['HCISYSQ\\Api', 'admin_create_publication'],
    'channel'  => 'admin_publications',
  ],
  [
    'action'   => 'hcisysq_admin_update_publication',
    'callback' => ['HCISYSQ\\Api', 'admin_update_publication'],
    'channel'  => 'admin_publications',
  ],
  [
    'action'   => 'hcisysq_admin_delete_publication',
    'callback' => ['HCISYSQ\\Api', 'admin_delete_publication'],
    'channel'  => 'admin_publications',
  ],
  [
    'action'   => 'hcisysq_admin_set_publication_status',
    'callback' => ['HCISYSQ\\Api', 'admin_set_publication_status'],
    'channel'  => 'admin_publications',
  ],
  [
    'action'   => 'hcisysq_admin_save_settings',
    'callback' => ['HCISYSQ\\Api', 'admin_save_settings'],
    'channel'  => 'admin_settings',
  ],
  [
    'action'   => 'hcisysq_admin_save_home_settings',
    'callback' => ['HCISYSQ\\Api', 'admin_save_home_settings'],
    'channel'  => 'admin_settings',
  ],
  [
    'action'   => 'hcisysq_admin_create_task',
    'callback' => ['HCISYSQ\\Api', 'admin_create_task'],
    'channel'  => 'admin_tasks',
  ],
  [
    'action'   => 'hcisysq_admin_update_task',
    'callback' => ['HCISYSQ\\Api', 'admin_update_task'],
    'channel'  => 'admin_tasks',
  ],
  [
    'action'   => 'hcisysq_admin_delete_task',
    'callback' => ['HCISYSQ\\Api', 'admin_delete_task'],
    'channel'  => 'admin_tasks',
  ],
  [
    'action'   => 'hcisysq_admin_set_task_status',
    'callback' => ['HCISYSQ\\Api', 'admin_set_task_status'],
    'channel'  => 'admin_tasks',
  ],
  [
    'action'   => 'hcisysq_admin_update_assignment',
    'callback' => ['HCISYSQ\\Api', 'admin_update_assignment'],
    'channel'  => 'admin_tasks',
  ],
  [
    'action'   => 'ysq_get_employees_by_units',
    'callback' => ['HCISYSQ\\Api', 'ysq_get_employees_by_units'],
    'channel'  => 'employee_lookup',
  ],
  [
    'action'   => 'ysq_get_all_profiles',
    'callback' => ['HCISYSQ\\Api', 'ysq_api_get_all_profiles'],
    'channel'  => 'employee_lookup',
  ],
  [
    'action'   => 'ysq_update_profile',
    'callback' => ['HCISYSQ\\Api', 'ysq_api_update_profile'],
    'channel'  => 'profile_update',
  ],
];

foreach ($hcisysq_ajax_endpoints as $endpoint) {
  HCISYSQ\Security::register_ajax_endpoint($endpoint);
}

add_action('wp_ajax_nopriv_hcisysq_submit_training', function(){
  wp_send_json(['ok' => false, 'msg' => 'Unauthorized'], 403);
});

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

/* =======================================================
 *  Session cleanup cron (hourly)
 * ======================================================= */
add_action('hcisysq_session_cleanup_cron', function() {
  if (class_exists('\HCISYSQ\SessionHandler')) {
    $deleted = \HCISYSQ\SessionHandler::cleanup();
    hcisysq_log('Session cleanup: ' . $deleted . ' expired sessions deleted');
  }
});

// Schedule cron if not already scheduled
if (!wp_next_scheduled('hcisysq_session_cleanup_cron')) {
  wp_schedule_event(time(), 'hourly', 'hcisysq_session_cleanup_cron');
}

// Cleanup old transient sessions (backward compatibility)
add_action('hcisysq_session_cleanup_cron', function() {
  global $wpdb;
  // Clean old transient sessions that may still exist
  $wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value LIKE %s",
    '%transient_hcisysq_sess_%',
    '%'
  ));
});

/* =======================================================
 *  Google Sheets bi-directional sync cron (every 15 minutes)
 * ======================================================= */
add_action('hcisysq_google_sheets_sync_cron', function() {
  if (class_exists('\HCISYSQ\GoogleSheetSettings') && class_exists('\HCISYSQ\Repositories\UserRepository')) {
    try {
      $is_configured = \HCISYSQ\GoogleSheetSettings::is_configured();
      
      if (!$is_configured) {
        hcisysq_log('Google Sheets sync: Not configured, skipping');
        return;
      }

      $api = new \HCISYSQ\GoogleSheetsAPI();
      $creds = json_decode(get_option(\HCISYSQ\GoogleSheetSettings::OPT_JSON_CREDS), true);
      
      if (!$api->authenticate($creds)) {
        hcisysq_log('Google Sheets sync: Authentication failed', 'WARNING');
        update_option('hcis_gs_last_error', 'Authentication failed');
        return;
      }

      // Sync Users from WordPress to Google Sheet (bi-directional)
      $repo = new \HCISYSQ\Repositories\UserRepository($api, new \HCISYSQ\SheetCache());
      $synced = $repo->syncFromWordPress();

      hcisysq_log('Google Sheets sync: Synced ' . $synced . ' users from WordPress');
      update_option('hcis_gs_last_sync', current_time('mysql'));

      // Log quota metrics
      $quota = $api->getQuotaMetrics();
      if ($quota['usage_percent'] > 80) {
        hcisysq_log('Google Sheets: Quota usage at ' . $quota['usage_percent'] . '%', 'WARNING');
      }
    } catch (\Exception $e) {
      hcisysq_log('Google Sheets sync error: ' . $e->getMessage(), 'ERROR');
      update_option('hcis_gs_last_error', $e->getMessage());
    }
  }
});

// Schedule Google Sheets sync if not already scheduled
if (!wp_next_scheduled('hcisysq_google_sheets_sync_cron')) {
  wp_schedule_event(time(), 'hcis_15_minutes', 'hcisysq_google_sheets_sync_cron');
}

// Register custom 15-minute schedule
add_filter('cron_schedules', function($schedules) {
  if (!isset($schedules['hcis_15_minutes'])) {
    $schedules['hcis_15_minutes'] = [
      'interval' => 15 * 60,
      'display'  => __('Every 15 Minutes', 'hcis-ysq')
    ];
  }
  return $schedules;
});

add_action('admin_menu', ['HCISYSQ\\Admin','menu']);
add_action('admin_menu', ['HCISYSQ\\Admin','add_settings_page']);
add_action('admin_init', ['HCISYSQ\\Admin', 'register_settings']);

/* =======================================================
 *  Proteksi halaman (guard)
 * ======================================================= */
add_action('template_redirect', function () {
  $to = function ($slug) { return home_url('/' . ltrim($slug, '/') . '/'); };

  $identity = HCISYSQ\Auth::current_identity();
  $hasUser  = $identity && ($identity['type'] ?? null) === 'user';
  $hasAdmin = $identity && ($identity['type'] ?? null) === 'admin';
  $needsReset = $hasUser && !empty($identity['needs_password_reset']);
  $resetUrl = $to(HCISYSQ_RESET_SLUG);

  if (is_page([HCISYSQ_DASHBOARD_SLUG, HCISYSQ_FORM_SLUG])) {
    if (!$hasUser && !$hasAdmin) {
      hcisysq_log('guard: not logged, redirect to /' . HCISYSQ_LOGIN_SLUG);
      wp_safe_redirect($to(HCISYSQ_LOGIN_SLUG));
      exit;
    } elseif ($needsReset && !is_page(HCISYSQ_RESET_SLUG)) {
      hcisysq_log('guard: force reset, redirect to /' . HCISYSQ_RESET_SLUG);
      wp_safe_redirect($resetUrl);
      exit;
    }
  }
  if ($hasUser && !$hasAdmin && $needsReset && !is_page(HCISYSQ_RESET_SLUG)) {
    if (is_page(HCISYSQ_LOGIN_SLUG) || is_front_page() || is_home()) {
      hcisysq_log('guard: force reset from login/home to /' . HCISYSQ_RESET_SLUG);
      wp_safe_redirect($resetUrl);
      exit;
    }
  }
  if (($hasUser || $hasAdmin) && (is_front_page() || is_home())) {
    if ($hasUser && !$hasAdmin && $needsReset) {
      hcisysq_log('guard: active session requires reset, redirect home to /' . HCISYSQ_RESET_SLUG);
      wp_safe_redirect($resetUrl);
      exit;
    }
    hcisysq_log('guard: active session, redirect home to /' . HCISYSQ_DASHBOARD_SLUG);
    wp_safe_redirect($to(HCISYSQ_DASHBOARD_SLUG));
    exit;
  }
  if (is_page(HCISYSQ_LOGIN_SLUG) && ($hasUser || $hasAdmin)) {
    if ($hasUser && !$hasAdmin && $needsReset) {
      hcisysq_log('guard: already logged but must reset, redirect to /' . HCISYSQ_RESET_SLUG);
      wp_safe_redirect($resetUrl);
    } else {
      hcisysq_log('guard: already logged, redirect to /' . HCISYSQ_DASHBOARD_SLUG);
      wp_safe_redirect($to(HCISYSQ_DASHBOARD_SLUG));
    }
    exit;
  }
  if ($needsReset && is_page(HCISYSQ_RESET_SLUG)) {
    hcisysq_log('guard: user flagged for password reset accessing reset page');
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
