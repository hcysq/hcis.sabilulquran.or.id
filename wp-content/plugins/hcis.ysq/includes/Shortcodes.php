<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Shortcodes {

  public static function init() {
    add_shortcode('hcis_ysq_login', [__CLASS__, 'login']);
    add_shortcode('hcis_ysq_dashboard', [__CLASS__, 'dashboard']);
    add_shortcode('hcis_ysq_form', [__CLASS__, 'form']);
    add_shortcode('hcis_ysq_form_button', [__CLASS__, 'form_button']);

    add_shortcode('hcisysq_login', [__CLASS__, 'login']);
    add_shortcode('hcisysq_dashboard', [__CLASS__, 'dashboard']);
    add_shortcode('hcisysq_form', [__CLASS__, 'form']);
    add_shortcode('hcisysq_form_button', [__CLASS__, 'form_button']);

    add_shortcode('hrissq_login', [__CLASS__, 'login']);
    add_shortcode('hrissq_dashboard', [__CLASS__, 'dashboard']);
    add_shortcode('hrissq_form', [__CLASS__, 'form']);
    add_shortcode('hrissq_form_button', [__CLASS__, 'form_button']);

    add_filter('the_content', [__CLASS__, 'fix_dot_shortcodes'], 9);
  }

  public static function fix_dot_shortcodes($content) {
    if (empty($content)) return $content;

    $pattern = '/\[(\/?)hcis\.ysq_([a-z0-9_-]+)([^\]]*)\]/i';
    $content = preg_replace_callback($pattern, function ($matches) {
      $prefix = $matches[1] === '/' ? '[/' : '[';
      $tag    = 'hcis_ysq_' . $matches[2];
      $attrs  = $matches[3] ?? '';
      return $prefix . $tag . $attrs . ']';
    }, $content);

    return $content;
  }

  public static function login($atts) {
    return View::login($atts);
  }

  public static function dashboard($atts) {
    return View::dashboard($atts);
  }

  public static function form($atts) {
    return View::form($atts);
  }

  public static function form_button($atts) {
    if (!is_user_logged_in() && !Auth::current_user()) {
      $login = home_url('/' . HCISYSQ_LOGIN_SLUG . '/');
      return '<a class="button" href="' . esc_url($login) . '">Login untuk isi form</a>';
    }
    $href = home_url('/' . HCISYSQ_FORM_SLUG . '/');
    return '<a class="button button-primary" href="' . esc_url($href) . '">Isi Form Pelatihan</a>';
  }
}
