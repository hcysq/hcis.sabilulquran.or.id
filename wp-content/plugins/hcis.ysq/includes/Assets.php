<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Assets {

  public static function init() {
    add_action('init', [__CLASS__, 'register']);
    add_action('wp_enqueue_scripts', [__CLASS__, 'conditional_enqueue']);
  }

  public static function register() {
    wp_register_style('hcisysq', HCISYSQ_URL . 'assets/app.css', [], HCISYSQ_VER);
    wp_register_script('hcisysq', HCISYSQ_URL . 'assets/app.js', ['jquery'], HCISYSQ_VER, true);

    $settings = self::get_settings();

    wp_localize_script('hcisysq', 'hcisysq', [
      'ajax'          => admin_url('admin-ajax.php', 'relative'),
      'nonce'         => wp_create_nonce('hcisysq_nonce'),
      'loginSlug'     => HCISYSQ_LOGIN_SLUG,
      'dashboardSlug' => HCISYSQ_DASHBOARD_SLUG,
      'gas_url'       => $settings['gas_url'],
    ]);
  }

  public static function conditional_enqueue() {
    if (!is_singular()) return;

    global $post;
    if (!$post || empty($post->post_content)) return;

    $shortcodes = [
      'hcis_ysq_login', 'hcis_ysq_dashboard', 'hcis_ysq_form',
      'hcisysq_login', 'hcisysq_dashboard', 'hcisysq_form',
      'hrissq_login', 'hrissq_dashboard', 'hrissq_form'
    ];

    $has_shortcode = false;
    foreach ($shortcodes as $tag) {
      if (has_shortcode($post->post_content, $tag)) {
        $has_shortcode = true;
        break;
      }
    }

    $dotShortcodes = ['hcis.ysq_login', 'hcis.ysq_dashboard', 'hcis.ysq_form'];
    foreach ($dotShortcodes as $tag) {
      $regex = '/\[\/?' . preg_quote($tag, '/') . '(?:\b[^\]]*)?\]/i';
      if (preg_match($regex, $post->post_content)) {
        $has_shortcode = true;
        break;
      }
    }

    if (!$has_shortcode) return;

    if (wp_style_is('hcisysq', 'registered')) {
      wp_enqueue_style('hcisysq');
    }
    if (wp_script_is('hcisysq', 'registered')) {
      wp_enqueue_script('hcisysq');
    }
  }

  private static function get_settings() {
    return [
      'admin_wa' => get_option('hcisysq_admin_wa', HCISYSQ_SS_HC ?? ''),
      'wa_token' => get_option('hcisysq_wa_token', HCISYSQ_SS_KEY ?? ''),
      'gas_url'  => home_url('/' . trim(HCISYSQ_FORM_SLUG, '/') . '/'),
    ];
  }

  public static function get_admin_wa() {
    return get_option('hcisysq_admin_wa', HCISYSQ_SS_HC ?? '');
  }

  public static function get_wa_token() {
    return get_option('hcisysq_wa_token', HCISYSQ_SS_KEY ?? '');
  }

  public static function get_gas_url() {
    return home_url('/' . trim(HCISYSQ_FORM_SLUG, '/') . '/');
  }
}
