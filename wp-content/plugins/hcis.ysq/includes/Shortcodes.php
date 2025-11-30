<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Shortcodes {

  public static function init() {
    add_shortcode('hcis_ysq_login', [__CLASS__, 'login']);
    add_shortcode('hcis_ysq_dashboard', [__CLASS__, 'dashboard']);
    add_shortcode('hcis_ysq_form', [__CLASS__, 'form']);
    add_shortcode('hcis_ysq_form_button', [__CLASS__, 'form_button']);
    add_shortcode('hcis_ysq_reset_password', [__CLASS__, 'reset_password']);
    add_shortcode('hcis_lupa_password_form', [__CLASS__, 'lupa_password_form']);
    add_shortcode('hcis_reset_password_form', [__CLASS__, 'reset_password_form']);
    add_shortcode('hcis_ysq_admin_login', [__CLASS__, 'admin_login']);

    add_shortcode('hcisysq_login', [__CLASS__, 'login']);
    add_shortcode('hcisysq_dashboard', [__CLASS__, 'dashboard']);
    add_shortcode('hcisysq_form', [__CLASS__, 'form']);
    add_shortcode('hcisysq_form_button', [__CLASS__, 'form_button']);
    add_shortcode('hcisysq_reset_password', [__CLASS__, 'reset_password']);
    add_shortcode('hcisysq_lupa_password_form', [__CLASS__, 'lupa_password_form']);
    add_shortcode('hcisysq_reset_password_form', [__CLASS__, 'reset_password_form']);
    add_shortcode('hcisysq_admin_login', [__CLASS__, 'admin_login']);

    add_shortcode('hrissq_login', [__CLASS__, 'login']);
    add_shortcode('hrissq_dashboard', [__CLASS__, 'dashboard']);
    add_shortcode('hrissq_form', [__CLASS__, 'form']);
    add_shortcode('hrissq_form_button', [__CLASS__, 'form_button']);
    add_shortcode('hrissq_reset_password', [__CLASS__, 'reset_password']);
    add_shortcode('hrissq_lupa_password_form', [__CLASS__, 'lupa_password_form']);
    add_shortcode('hrissq_reset_password_form', [__CLASS__, 'reset_password_form']);
    add_shortcode('hrissq_admin_login', [__CLASS__, 'admin_login']);

    add_filter('the_content', [__CLASS__, 'fix_dot_shortcodes'], 9);
    add_filter('the_content', [__CLASS__, 'ensure_login_content'], 1);
    add_filter('the_content', [__CLASS__, 'ensure_admin_login_content'], 1);

    add_action('template_redirect', [__CLASS__, 'render_login_when_missing_page'], 1);
    add_action('template_redirect', [__CLASS__, 'render_admin_login_when_missing_page'], 1);
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

  public static function ensure_login_content($content) {
    if (!is_page(HCISYSQ_LOGIN_SLUG)) return $content;

    if (is_user_logged_in() || Auth::current_user()) return $content;

    $aliases = [
      'hcis_ysq_login',
      'hcisysq_login',
      'hrissq_login',
    ];

    foreach ($aliases as $alias) {
      $pattern = '/\[(\/?)' . preg_quote($alias, '/') . '\b/';
      if (preg_match($pattern, $content)) {
        return $content;
      }
    }

    foreach ($aliases as $alias) {
      $pattern = '/\[(\/?)' . preg_quote(str_replace('_', '.', $alias), '/') . '\b/';
      if (preg_match($pattern, $content)) {
        return $content;
      }
    }

    return View::login();
  }

  public static function login($atts) {
    return View::login();
  }

  public static function admin_login($atts) {
    return View::admin_login();
  }

  public static function dashboard($atts) {
    return View::dashboard();
  }

  public static function form($atts) {
    return View::form();
  }

  public static function form_button($atts) {
    if (!is_user_logged_in() && !Auth::current_user()) {
      $login = home_url('/' . HCISYSQ_LOGIN_SLUG . '/');
      return '<a class="button" href="' . esc_url($login) . '">Login untuk isi form</a>';
    }
    $href = home_url('/' . HCISYSQ_FORM_SLUG . '/');
    return '<a class="button button-primary" href="' . esc_url($href) . '">Isi Form Pelatihan</a>';
  }

  public static function reset_password($atts) {
    return View::reset_password();
  }

  public static function lupa_password_form($atts) {
    return View::lupa_password_form();
  }

  public static function reset_password_form($atts) {
    return View::reset_password_form();
  }

  public static function ensure_admin_login_content($content) {
    if (!is_page(HCISYSQ_ADMIN_LOGIN_SLUG)) return $content;

    $aliases = [
      'hcis_ysq_admin_login',
      'hcisysq_admin_login',
      'hrissq_admin_login',
    ];

    foreach ($aliases as $alias) {
      $pattern = '/\[(\/?)' . preg_quote($alias, '/') . '\b/';
      if (preg_match($pattern, $content)) {
        return $content;
      }
    }

    foreach ($aliases as $alias) {
      $pattern = '/\[(\/?)' . preg_quote(str_replace('_', '.', $alias), '/') . '\b/';
      if (preg_match($pattern, $content)) {
        return $content;
      }
    }

    return View::admin_login();
  }

  public static function render_login_when_missing_page() {
    if (is_admin() || wp_doing_ajax()) return;

    $slug = trim(HCISYSQ_LOGIN_SLUG, '/');
    if ($slug === '') return;

    global $wp, $wp_query;
    $requested = '';
    if (isset($wp->request)) {
      $requested = trim($wp->request, '/');
    } elseif (!empty($_SERVER['REQUEST_URI'])) {
      $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
      $requested = trim($path, '/');
    }

    if ($requested !== $slug) return;

    if ($wp_query && $wp_query->post_count > 0 && !$wp_query->is_404()) {
      return;
    }

    self::output_login_document();
  }

  protected static function output_login_document() {
    status_header(200);
    nocache_headers();

    $content = View::login();

    echo '<!DOCTYPE html><html ';
    language_attributes();
    echo '>';
    echo '<head>';
    echo '<meta charset="' . esc_attr(get_bloginfo('charset')) . '">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    wp_head();
    echo '</head>';
    echo '<body ';
    body_class('hcisysq-login-fallback');
    echo '>';
    echo '<main class="hcisysq-login-fallback__content">' . $content . '</main>';
    wp_footer();
    echo '</body></html>';
    exit;
  }

  public static function render_admin_login_when_missing_page() {
    if (is_admin() || wp_doing_ajax()) return;

    $slug = trim(HCISYSQ_ADMIN_LOGIN_SLUG, '/');
    if ($slug === '') return;

    $loginSlug = trim(HCISYSQ_LOGIN_SLUG, '/');
    if ($slug === $loginSlug) {
      $slug = $slug . '-admin';
    }

    global $wp, $wp_query;
    $requested = '';
    if (isset($wp->request)) {
      $requested = trim($wp->request, '/');
    } elseif (!empty($_SERVER['REQUEST_URI'])) {
      $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
      $requested = trim($path, '/');
    }

    if ($requested !== $slug) return;

    if ($wp_query && $wp_query->post_count > 0 && !$wp_query->is_404()) {
      return;
    }

    self::output_admin_login_document();
  }

  protected static function output_admin_login_document() {
    status_header(200);
    nocache_headers();

    $content = View::admin_login();

    echo '<!DOCTYPE html><html ';
    language_attributes();
    echo '>';
    echo '<head>';
    echo '<meta charset="' . esc_attr(get_bloginfo('charset')) . '">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    wp_head();
    echo '</head>';
    echo '<body ';
    body_class('hcisysq-admin-login-fallback');
    echo '>';
    echo '<main class="hcisysq-login-fallback__content">' . $content . '</main>';
    wp_footer();
    echo '</body></html>';
    exit;
  }
}
