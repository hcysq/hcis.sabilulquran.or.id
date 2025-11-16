<?php
namespace HCISYSQ;

use WP_Error;
use WP_User;

if (!defined('ABSPATH')) exit;

class NipAuthentication {
  public static function init() {
    add_filter('authenticate', [__CLASS__, 'handle_nip_login'], 20, 3);
    add_filter('login_redirect', [__CLASS__, 'maybe_redirect_after_login'], 20, 3);
    add_action('wp_login', [__CLASS__, 'after_login'], 10, 2);
    add_action('lostpassword_post', [__CLASS__, 'allow_reset_with_nip']);
  }

  public static function handle_nip_login($user, $username, $password) {
    if ($user instanceof WP_User || $user instanceof WP_Error) {
      return $user;
    }

    $username = trim((string) $username);
    $password = (string) $password;
    if ($username === '' || $password === '') {
      return $user;
    }

    if (is_email($username)) {
      return $user;
    }

    global $wpdb;
    $employees_table = $wpdb->prefix . 'ysq_employees';
    $employee = $wpdb->get_row($wpdb->prepare("SELECT wp_user_id, phone_number FROM $employees_table WHERE employee_id_number = %s", $username));
    if (!$employee || empty($employee->wp_user_id)) {
      return $user;
    }

    $wp_user = get_user_by('id', (int) $employee->wp_user_id);
    if (!$wp_user) {
      return $user;
    }

    remove_filter('authenticate', [__CLASS__, 'handle_nip_login'], 20);
    $authenticated = wp_authenticate_username_password(null, $wp_user->user_login, $password);
    add_filter('authenticate', [__CLASS__, 'handle_nip_login'], 20, 3);

    if ($authenticated instanceof WP_User) {
        // Check if the password is the default phone number
        if (!empty($employee->phone_number) && $password === $employee->phone_number) {
            update_user_meta($authenticated->ID, 'needs_password_reset', true);
        }
    }

    return $authenticated;
  }

  public static function maybe_redirect_after_login($redirect_to, $requested_redirect_to, $user) {
    if (!$user || is_wp_error($user)) {
      return $redirect_to;
    }

    if (!self::is_profile_complete($user->ID)) {
      return home_url('/lengkapi-profil/');
    }

    if (in_array('hcis_admin', (array) $user->roles, true)) {
      return home_url('/dashboard');
    }

    return $redirect_to;
  }

  public static function after_login($user_login, $user) {
    if (get_user_meta($user->ID, 'ysq_force_password_change', true)) {
      add_filter('login_message', function($message) {
        $message .= '<div class="notice notice-warning"><p>' . esc_html__('Silakan ganti password Anda pada profil WordPress.', 'hcis-ysq') . '</p></div>';
        return $message;
      });
    }
  }

  public static function is_profile_complete($user_id) {
    return (bool) get_user_meta($user_id, 'profile_complete', true);
  }

  public static function allow_reset_with_nip($errors) {
    if (!$errors instanceof WP_Error) {
      return;
    }

    if ($errors->has_errors()) {
      return;
    }

    if (empty($_POST['user_login'])) {
      return;
    }

    $user_login = trim((string) $_POST['user_login']);
    if ($user_login === '' || is_email($user_login)) {
      return;
    }

    global $wpdb;
    $employees_table = $wpdb->prefix . 'ysq_employees';
    $employee = $wpdb->get_row(
      $wpdb->prepare(
        "SELECT wp_user_id FROM {$employees_table} WHERE employee_id_number = %s",
        $user_login
      )
    );

    if (!$employee || empty($employee->wp_user_id)) {
      return;
    }

    $wp_user = get_user_by('id', (int) $employee->wp_user_id);
    if (!$wp_user || empty($wp_user->user_email)) {
      return;
    }

    $_POST['user_login'] = $wp_user->user_email;
  }
}
