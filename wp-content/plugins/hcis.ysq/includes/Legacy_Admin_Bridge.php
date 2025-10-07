<?php
namespace HCISYSQ {

if (!defined('ABSPATH')) exit;

class Legacy_Admin_Bridge {
  const NOTICE_SESSION_KEY = 'hcisysq_admin_notices';

  public static function init() {
    add_action('init', [__CLASS__, 'start_session'], 1);
    add_action('after_switch_theme', [__CLASS__, 'ensure_default_credentials']);
    add_action('init', [__CLASS__, 'ensure_default_credentials'], 5);
    add_action('init', [__CLASS__, 'handle_requests'], 20);
  }

  public static function start_session() {
    if (is_admin()) return;
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $cookie_lifetime = (int) apply_filters('hcisysq_admin_session_lifetime', 6 * HOUR_IN_SECONDS);
    $session_args = apply_filters('hcisysq_admin_session_args', [
      'cookie_lifetime' => max($cookie_lifetime, HOUR_IN_SECONDS),
      'read_and_close'  => false,
      'use_strict_mode' => true,
    ]);

    if (function_exists('session_start')) {
      if (!headers_sent()) {
        session_start($session_args);
      } else {
        session_start();
      }
    }
  }

  public static function ensure_default_credentials() {
    Auth::get_admin_settings();
    self::maybe_migrate_legacy_credentials();
  }

  private static function maybe_migrate_legacy_credentials() {
    $legacy_username = get_option('ysq_admin_username', null);
    $legacy_hash     = get_option('ysq_admin_password_hash', null);

    if ($legacy_username === null && $legacy_hash === null) {
      return;
    }

    $settings = Auth::get_admin_settings();
    $dirty    = false;

    if ($legacy_username !== null && $legacy_username !== false) {
      $username = sanitize_user($legacy_username, true);
      if ($username !== '' && strcasecmp($username, $settings['username']) !== 0) {
        $settings['username'] = $username;
        $dirty = true;
      }
    }

    if ($legacy_hash !== null && $legacy_hash !== false && is_string($legacy_hash) && $legacy_hash !== '') {
      if (!hash_equals($settings['password_hash'], $legacy_hash)) {
        $settings['password_hash'] = $legacy_hash;
        $dirty = true;
      }
    }

    if ($dirty) {
      update_option(Auth::ADMIN_OPTION, $settings, false);
    }

    delete_option('ysq_admin_username');
    delete_option('ysq_admin_password_hash');
  }

  public static function handle_requests() {
    if (is_admin()) return;

    self::ensure_default_credentials();

    if (isset($_GET['ysq_admin_logout']) && self::is_admin_authenticated()) {
      Auth::logout();
      self::add_notice('success', __('Anda telah keluar dari sesi administrator.', 'ysq'));
      self::redirect();
    }

    if (isset($_POST['ysq_admin_login'])) {
      self::handle_login_request();
      return;
    }

    if (!self::is_admin_authenticated()) {
      return;
    }

    if (isset($_POST['ysq_save_announcement'])) {
      self::handle_announcement_save();
      return;
    }

    if (isset($_POST['ysq_delete_announcement'])) {
      self::handle_announcement_delete();
      return;
    }

    if (isset($_POST['ysq_update_credentials'])) {
      self::handle_credentials_update();
      return;
    }
  }

  private static function handle_login_request() {
    $nonce = isset($_POST['ysq_admin_login_nonce'])
      ? sanitize_text_field(wp_unslash($_POST['ysq_admin_login_nonce']))
      : '';

    if (!$nonce || !wp_verify_nonce($nonce, 'ysq_admin_login')) {
      self::add_notice('error', __('Sesi login tidak valid. Silakan coba lagi.', 'ysq'));
      self::redirect();
    }

    $username = isset($_POST['ysq_admin_username'])
      ? sanitize_text_field(wp_unslash($_POST['ysq_admin_username']))
      : '';
    $password = isset($_POST['ysq_admin_password'])
      ? wp_unslash($_POST['ysq_admin_password'])
      : '';

    $result = Auth::login($username, $password);
    if (!$result || empty($result['ok'])) {
      $message = is_array($result) && !empty($result['msg'])
        ? $result['msg']
        : __('Username atau password administrator salah.', 'ysq');
      self::add_notice('error', $message);
    } else {
      self::add_notice('success', __('Login administrator berhasil.', 'ysq'));
    }

    self::redirect();
  }

  private static function handle_announcement_save() {
    $nonce = isset($_POST['ysq_save_announcement_nonce'])
      ? sanitize_text_field(wp_unslash($_POST['ysq_save_announcement_nonce']))
      : '';

    if (!$nonce || !wp_verify_nonce($nonce, 'ysq_save_announcement')) {
      self::add_notice('error', __('Sesi tidak valid saat menyimpan pengumuman.', 'ysq'));
      self::redirect();
    }

    $announcement_id = isset($_POST['ysq_announcement_id']) ? absint($_POST['ysq_announcement_id']) : 0;
    $title = isset($_POST['ysq_announcement_title'])
      ? sanitize_text_field(wp_unslash($_POST['ysq_announcement_title']))
      : '';
    $content = isset($_POST['ysq_announcement_content'])
      ? wp_kses_post(wp_unslash($_POST['ysq_announcement_content']))
      : '';

    if ($title === '') {
      self::add_notice('error', __('Judul pengumuman wajib diisi.', 'ysq'));
      self::redirect();
    }

    if ($announcement_id > 0) {
      $result = Announcements::update($announcement_id, [
        'title' => $title,
        'body'  => $content,
      ]);

      if ($result) {
        self::add_notice('success', __('Pengumuman berhasil diperbarui.', 'ysq'));
        $referer = wp_get_referer();
        $target  = $referer ? remove_query_arg('ysq_edit', $referer) : '';
        self::redirect($target);
      } else {
        self::add_notice('error', __('Pengumuman tidak dapat diperbarui. Silakan coba lagi.', 'ysq'));
        self::redirect();
      }
    } else {
      $result = Announcements::create([
        'title' => $title,
        'body'  => $content,
      ]);

      if ($result) {
        self::add_notice('success', __('Pengumuman baru berhasil ditambahkan.', 'ysq'));
      } else {
        self::add_notice('error', __('Pengumuman baru gagal disimpan.', 'ysq'));
      }
      self::redirect();
    }
  }

  private static function handle_announcement_delete() {
    $announcement_id = isset($_POST['ysq_delete_announcement'])
      ? absint($_POST['ysq_delete_announcement'])
      : 0;
    $nonce = isset($_POST['ysq_delete_announcement_nonce'])
      ? sanitize_text_field(wp_unslash($_POST['ysq_delete_announcement_nonce']))
      : '';

    if (!$announcement_id || !$nonce || !wp_verify_nonce($nonce, 'ysq_delete_announcement_' . $announcement_id)) {
      self::add_notice('error', __('Sesi tidak valid saat menghapus pengumuman.', 'ysq'));
      self::redirect();
    }

    $deleted = Announcements::delete($announcement_id);
    if ($deleted) {
      self::add_notice('success', __('Pengumuman berhasil dihapus.', 'ysq'));
    } else {
      self::add_notice('error', __('Pengumuman tidak dapat dihapus.', 'ysq'));
    }

    self::redirect();
  }

  private static function handle_credentials_update() {
    $nonce = isset($_POST['ysq_update_credentials_nonce'])
      ? sanitize_text_field(wp_unslash($_POST['ysq_update_credentials_nonce']))
      : '';

    if (!$nonce || !wp_verify_nonce($nonce, 'ysq_update_credentials')) {
      self::add_notice('error', __('Sesi tidak valid saat memperbarui kredensial administrator.', 'ysq'));
      self::redirect();
    }

    $new_username = isset($_POST['ysq_new_username'])
      ? sanitize_user(wp_unslash($_POST['ysq_new_username']), true)
      : '';
    $new_password = isset($_POST['ysq_new_password'])
      ? wp_unslash($_POST['ysq_new_password'])
      : '';
    $confirm = isset($_POST['ysq_confirm_password'])
      ? wp_unslash($_POST['ysq_confirm_password'])
      : '';

    if ($new_password !== '' && $new_password !== $confirm) {
      self::add_notice('error', __('Konfirmasi password tidak cocok.', 'ysq'));
      self::redirect();
    }

    $payload = [];
    if ($new_username !== '') {
      $payload['username'] = $new_username;
    }
    if ($new_password !== '') {
      $payload['password'] = $new_password;
    }

    if (!empty($payload)) {
      Auth::save_admin_settings($payload);
      self::add_notice('success', __('Pengaturan administrator berhasil diperbarui.', 'ysq'));
    } else {
      self::add_notice('success', __('Tidak ada perubahan yang disimpan.', 'ysq'));
    }

    self::redirect();
  }

  public static function add_notice($type, $message) {
    self::start_session();
    if (!isset($_SESSION[self::NOTICE_SESSION_KEY]) || !is_array($_SESSION[self::NOTICE_SESSION_KEY])) {
      $_SESSION[self::NOTICE_SESSION_KEY] = [];
    }

    $type = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
    $_SESSION[self::NOTICE_SESSION_KEY][] = [
      'type'    => $type,
      'message' => wp_strip_all_tags((string) $message),
    ];
  }

  public static function get_notices() {
    self::start_session();
    if (empty($_SESSION[self::NOTICE_SESSION_KEY]) || !is_array($_SESSION[self::NOTICE_SESSION_KEY])) {
      return [];
    }

    $notices = $_SESSION[self::NOTICE_SESSION_KEY];
    unset($_SESSION[self::NOTICE_SESSION_KEY]);
    return array_map(function ($notice) {
      return [
        'type'    => isset($notice['type']) ? sanitize_key($notice['type']) : 'info',
        'message' => isset($notice['message']) ? sanitize_text_field($notice['message']) : '',
      ];
    }, $notices);
  }

  public static function redirect($url = '') {
    if ($url === '') {
      $referer = wp_get_referer();
      $url = $referer ? $referer : home_url('/');
    }

    wp_safe_redirect($url);
    exit;
  }

  private static function extract_legacy_admin_session() {
    self::start_session();

    $username = '';
    $authenticated = false;

    $composite = $_SESSION['ysq_admin'] ?? null;
    if (is_array($composite)) {
      if (!empty($composite['username'])) {
        $candidate = sanitize_user(wp_unslash($composite['username']), true);
        if ($candidate !== '') {
          $username = $candidate;
        }
      }

      foreach (['authenticated', 'logged_in', 'login', 'status'] as $key) {
        if (!empty($composite[$key])) {
          $authenticated = true;
          break;
        }
      }
    }

    $usernameKeys = [
      'ysq_admin_username',
      'ysq_admin_user',
      'ysq_admin_name',
      'admin_username',
      'username_admin',
    ];

    foreach ($usernameKeys as $key) {
      if (!empty($_SESSION[$key]) && is_string($_SESSION[$key])) {
        $candidate = sanitize_user(wp_unslash($_SESSION[$key]), true);
        if ($candidate !== '') {
          $username = $candidate;
          break;
        }
      }
    }

    if ($username === '' && !empty($_COOKIE['ysq_admin_username'])) {
      $candidate = sanitize_user(wp_unslash($_COOKIE['ysq_admin_username']), true);
      if ($candidate !== '') {
        $username = $candidate;
      }
    }

    $flagSources = [
      $_SESSION['ysq_admin_logged_in'] ?? null,
      $_SESSION['ysq_admin_login'] ?? null,
      $_SESSION['ysq_admin_authenticated'] ?? null,
      $_SESSION['ysq_admin_status'] ?? null,
      $_SESSION['admin_logged_in'] ?? null,
      is_array($composite) ? ($composite['flag'] ?? null) : null,
    ];

    foreach ($flagSources as $flag) {
      if ($flag) {
        $authenticated = true;
        break;
      }
    }

    if ($username === '' && !$authenticated) {
      return null;
    }

    return [
      'username'      => $username,
      'authenticated' => $authenticated || $username !== '',
    ];
  }

  public static function get_legacy_admin_identity() {
    $session = self::extract_legacy_admin_session();
    if (!$session || empty($session['authenticated'])) {
      return null;
    }

    $settings = Auth::get_admin_settings();
    $username = $session['username'] !== '' ? $session['username'] : $settings['username'];

    return [
      'type'         => 'admin',
      'username'     => $username,
      'display_name' => $settings['display_name'],
      'settings'     => $settings,
    ];
  }

  public static function get_username() {
    $settings = Auth::get_admin_settings();
    return $settings['username'];
  }

  public static function is_admin_authenticated() {
    $identity = Auth::current_identity(false);
    if ($identity && ($identity['type'] ?? '') === 'admin') {
      return true;
    }

    return self::get_legacy_admin_identity() !== null;
  }

}
}

namespace {
  if (!function_exists('ysq_get_admin_notices')) {
    function ysq_get_admin_notices() {
      return \HCISYSQ\Legacy_Admin_Bridge::get_notices();
    }
  }

  if (!function_exists('ysq_admin_is_logged_in')) {
    function ysq_admin_is_logged_in() {
      return \HCISYSQ\Legacy_Admin_Bridge::is_admin_authenticated();
    }
  }

  if (!function_exists('ysq_admin_get_username')) {
    function ysq_admin_get_username() {
      return \HCISYSQ\Legacy_Admin_Bridge::get_username();
    }
  }
}
