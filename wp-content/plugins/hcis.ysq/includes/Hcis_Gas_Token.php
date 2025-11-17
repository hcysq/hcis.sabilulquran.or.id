<?php
namespace HCISYSQ;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if (!defined('ABSPATH')) exit;

class Hcis_Gas_Token {
  const OPTION_API_KEY = 'HCIS_GAS_API_KEY';
  const TRANSIENT_PREFIX = 'hcis_gas_token_';
  const TOKEN_TTL = 300; // 5 minutes

  public static function init() {
    add_action('init', [__CLASS__, 'bootstrap_option']);
    add_action('rest_api_init', [__CLASS__, 'register_routes']);
  }

  public static function bootstrap_option() {
    if (false === get_option(self::OPTION_API_KEY, false)) {
      add_option(self::OPTION_API_KEY, '', '', 'no');
    }
  }

  public static function register_routes() {
    register_rest_route('hcis/v1', '/gas-token/new', [
      'methods'             => WP_REST_Server::CREATABLE,
      'callback'            => Security::wrap_rest_callback([__CLASS__, 'handle_new_token'], [
        'channel' => 'gas_token_new',
      ]),
      'permission_callback' => [__CLASS__, 'rest_user_permission'],
    ]);

    register_rest_route('hcis/v1', '/gas-token/exchange', [
      'methods'             => WP_REST_Server::READABLE,
      'callback'            => Security::wrap_rest_callback([__CLASS__, 'handle_exchange_token'], [
        'channel' => 'gas_token_exchange',
      ]),
      'permission_callback' => '__return_true',
      'args'                => [
        'token' => [
          'required' => true,
          'sanitize_callback' => 'sanitize_text_field',
        ],
      ],
    ]);
  }

  public static function rest_user_permission() {
    if (is_user_logged_in()) {
      return true;
    }
    if (class_exists(__NAMESPACE__ . '\\Auth') && Auth::current_identity()) {
      return true;
    }
    return false;
  }

  public static function handle_new_token(WP_REST_Request $request) {
    $nip = self::get_user_nip_from_session();
    if (!$nip) {
      return new WP_REST_Response([
        'ok'      => false,
        'message' => 'NIP tidak ditemukan dalam sesi.',
      ], 400);
    }

    $token = self::create_token_for_nip($nip);
    if (is_wp_error($token)) {
      return new WP_REST_Response([
        'ok'      => false,
        'message' => $token->get_error_message(),
      ], (int) $token->get_error_data('status') ?: 500);
    }

    return new WP_REST_Response([
      'ok'    => true,
      'token' => $token,
    ], 200);
  }

  public static function handle_exchange_token(WP_REST_Request $request) {
    $provided_key = trim((string) $request->get_header('x-hcis-gas-key'));
    $stored_key   = trim((string) get_option(self::OPTION_API_KEY, ''));

    if ($stored_key === '' || !hash_equals($stored_key, $provided_key)) {
      return new WP_REST_Response([
        'ok'      => false,
        'message' => 'Unauthorized',
      ], 401);
    }

    $token = trim((string) $request->get_param('token'));
    if ($token === '') {
      return new WP_REST_Response([
        'ok'      => false,
        'message' => 'Token wajib diisi.',
      ], 400);
    }

    $payload = self::get_and_invalidate_token($token);
    if (!$payload) {
      return new WP_REST_Response([
        'ok' => false,
      ], 400);
    }

    return new WP_REST_Response([
      'ok'  => true,
      'nip' => $payload['nip'],
    ], 200);
  }

  public static function get_user_nip_from_session() {
    if (function_exists('wp_get_session_token')) {
      // no-op, placeholder for compatibility
    }

    self::maybe_start_native_session();

    if (!empty($_SESSION['nip'])) {
      return sanitize_text_field(wp_unslash($_SESSION['nip']));
    }

    if (class_exists(__NAMESPACE__ . '\\Auth')) {
      $identity = Auth::current_identity();
      if ($identity && ($identity['type'] ?? '') === 'user') {
        $user = $identity['user'] ?? null;
        if (is_object($user) && isset($user->nip)) {
          return sanitize_text_field($user->nip);
        }
        if (is_array($user) && isset($user['nip'])) {
          return sanitize_text_field($user['nip']);
        }
      }
    }

    if (is_user_logged_in()) {
      $user_id = get_current_user_id();
      if ($user_id) {
        $nip = get_user_meta($user_id, 'nip', true);
        if (!$nip) {
          $nip = get_user_meta($user_id, 'username', true);
        }
        if ($nip) {
          return sanitize_text_field($nip);
        }
      }
    }

    return null;
  }

  public static function create_token_for_nip($nip) {
    $nip = sanitize_text_field($nip);
    if ($nip === '') {
      return new WP_Error('invalid_nip', 'NIP kosong.', ['status' => 400]);
    }

    $token = self::random_token();
    $saved = self::save_token_transient($token, [
      'nip'       => $nip,
      'issued_at' => time(),
      'used'      => false,
    ]);

    if (!$saved) {
      return new WP_Error('token_save_failed', 'Gagal menyimpan token.', ['status' => 500]);
    }

    return $token;
  }

  public static function create_token_for_current_user() {
    $nip = self::get_user_nip_from_session();
    if (!$nip) {
      return new WP_Error('missing_nip', 'NIP tidak ditemukan dalam sesi.', ['status' => 400]);
    }

    return self::create_token_for_nip($nip);
  }

  public static function get_and_invalidate_token($token) {
    $token = sanitize_text_field($token);
    if ($token === '') {
      return null;
    }

    $key = self::TRANSIENT_PREFIX . $token;
    $payload = get_transient($key);
    if (!is_array($payload) || empty($payload['nip'])) {
      return null;
    }

    delete_transient($key);

    $payload['used'] = true;
    return $payload;
  }

  public static function random_token() {
    try {
      return bin2hex(random_bytes(32));
    } catch (\Exception $e) {
      $fallback = wp_generate_password(64, false, false);
      return substr(preg_replace('/[^a-f0-9]/i', '', strtolower($fallback)), 0, 64);
    }
  }

  protected static function save_token_transient($token, array $payload) {
    $key = self::TRANSIENT_PREFIX . $token;
    return set_transient($key, $payload, self::TOKEN_TTL);
  }

  protected static function maybe_start_native_session() {
    if (php_sapi_name() === 'cli') {
      return;
    }
    if (function_exists('is_admin') && is_admin()) {
      // let WP admin handle sessions
    }
    if (session_status() === PHP_SESSION_NONE) {
      if (!headers_sent()) {
        session_start();
      }
    }
  }
}
