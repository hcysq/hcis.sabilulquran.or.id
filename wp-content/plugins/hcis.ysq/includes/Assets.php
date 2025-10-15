<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Assets {

  public static function init() {
    add_action('init', [__CLASS__, 'register']);
    add_action('wp_enqueue_scripts', [__CLASS__, 'conditional_enqueue']);
  }

  public static function register() {
    wp_register_style('hcisysq-shared', HCISYSQ_URL . 'assets/css/shared.css', [], HCISYSQ_VER);
    wp_register_style('hcisysq-login', HCISYSQ_URL . 'assets/css/login.css', ['hcisysq-shared'], HCISYSQ_VER);
    wp_register_style('hcisysq-dashboard', HCISYSQ_URL . 'assets/css/dashboard.css', ['hcisysq-shared'], HCISYSQ_VER);
    wp_register_style('hcisysq-admin', HCISYSQ_URL . 'assets/css/admin.css', ['hcisysq-dashboard'], HCISYSQ_VER);

    wp_register_script('hcisysq-shared', HCISYSQ_URL . 'assets/js/shared.js', ['jquery'], HCISYSQ_VER, true);
    wp_register_script('hcisysq-login', HCISYSQ_URL . 'assets/js/login.js', ['hcisysq-shared'], HCISYSQ_VER, true);
    wp_register_script('hcisysq-dashboard', HCISYSQ_URL . 'assets/js/dashboard.js', ['hcisysq-shared'], HCISYSQ_VER, true);
    wp_register_script('hcisysq-admin', HCISYSQ_URL . 'assets/js/admin.js', ['hcisysq-dashboard'], HCISYSQ_VER, true);

    $settings = self::get_settings();

    wp_localize_script('hcisysq-shared', 'hcisysq', [
      'ajax'          => admin_url('admin-ajax.php', 'relative'),
      'nonce'         => wp_create_nonce('hcisysq_nonce'),
      'loginSlug'     => HCISYSQ_LOGIN_SLUG,
      'dashboardSlug' => HCISYSQ_DASHBOARD_SLUG,
      'gas_url'       => $settings['gas_url'],
    ]);
  }

  public static function conditional_enqueue() {
    $needs_login = false;
    $needs_dashboard = false;
    $needs_reset = false;

    if (is_singular()) {
      global $post;
      $content = $post ? $post->post_content : '';
      $shortcodes = [
        'hcis_ysq_login', 'hcisysq_login', 'hrissq_login',
        'hcis_ysq_dashboard', 'hcisysq_dashboard', 'hrissq_dashboard',
        'hcis_ysq_form', 'hcisysq_form', 'hrissq_form',
        'hcis_ysq_reset_password', 'hcisysq_reset_password', 'hrissq_reset_password'
      ];

      foreach ($shortcodes as $tag) {
        if ($content && has_shortcode($content, $tag)) {
          if (strpos($tag, 'login') !== false) $needs_login = true;
          if (strpos($tag, 'dashboard') !== false || strpos($tag, 'form') !== false) $needs_dashboard = true;
          if (strpos($tag, 'reset_password') !== false) $needs_reset = true;
        }
      }

      $dotShortcodes = ['hcis.ysq_login', 'hcis.ysq_dashboard', 'hcis.ysq_form', 'hcis.ysq_reset_password'];
      foreach ($dotShortcodes as $tag) {
        $regex = '/\[\/?' . preg_quote($tag, '/') . '(?:\b[^\]]*)?\]/i';
        if ($content && preg_match($regex, $content)) {
          if (strpos($tag, 'login') !== false) $needs_login = true;
          if (strpos($tag, 'dashboard') !== false || strpos($tag, 'form') !== false) $needs_dashboard = true;
          if (strpos($tag, 'reset_password') !== false) $needs_reset = true;
        }
      }
    }

    if (is_page(HCISYSQ_LOGIN_SLUG)) {
      $needs_login = true;
    }

    if (is_page(HCISYSQ_DASHBOARD_SLUG) || is_page(HCISYSQ_FORM_SLUG)) {
      $needs_dashboard = true;
    }

    if (is_page(HCISYSQ_RESET_SLUG)) {
      $needs_reset = true;
    }

    $identity = Auth::current_identity();
    $hasIdentity = !empty($identity);
    $isAdmin = $hasIdentity && ($identity['type'] ?? '') === 'admin';

    if (is_front_page() || is_home()) {
      if ($hasIdentity) {
        $needs_dashboard = true;
      } else {
        $needs_login = true;
      }
    }

    $isTaskHistory = get_query_var(\HCISYSQ\Tasks::QUERY_VAR_DATE) && get_query_var(\HCISYSQ\Tasks::QUERY_VAR_INDEX);

    if ($isTaskHistory) {
      $needs_dashboard = true;
    }

    $needs_admin = ($isAdmin || current_user_can('manage_options')) && $needs_dashboard;

    if (!($needs_login || $needs_dashboard || $needs_reset)) {
      return;
    }

    wp_enqueue_style('hcisysq-shared');
    wp_enqueue_script('hcisysq-shared');

    if ($needs_login || $needs_reset) {
      wp_enqueue_style('hcisysq-login');
    }

    if ($needs_login) {
      wp_enqueue_script('hcisysq-login');
    }

    if ($needs_dashboard) {
      wp_enqueue_style('hcisysq-dashboard');
      wp_enqueue_script('hcisysq-dashboard');
    }

    if ($needs_admin) {
      wp_enqueue_style('hcisysq-admin');
      wp_enqueue_script('hcisysq-admin');
    }
  }

  private static function get_settings() {
    return [
      'admin_wa' => Config::get('admin_wa', 'option'),
      'wa_token' => Config::get('wa_token', 'option'),
      'gas_url'  => home_url('/' . trim(HCISYSQ_FORM_SLUG, '/') . '/'),
    ];
  }

  public static function get_admin_wa() {
    return Config::get('admin_wa');
  }

  public static function get_wa_token() {
    return Config::get('wa_token');
  }

  public static function get_gas_url() {
    return home_url('/' . trim(HCISYSQ_FORM_SLUG, '/') . '/');
  }
}
