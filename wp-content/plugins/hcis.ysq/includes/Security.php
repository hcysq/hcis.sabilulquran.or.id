<?php
namespace HCISYSQ;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) exit;

class Security {
  const OPTION = 'hcisysq_security_settings';

  const DEFAULTS = [
    'rate_limit' => [
      'window'   => 300,
      'per_ip'   => 30,
      'per_user' => 60,
    ],
    'captcha' => [
      'provider'      => '',
      'site_key'      => '',
      'secret_key'    => '',
      'threshold'     => 0.5,
      'enabled_forms' => [
        'login'        => true,
        'registration' => true,
        'training'     => true,
      ],
    ],
  ];

  protected static $needs_frontend_assets = false;
  protected static $validatedTokens = [];
  protected static $rawBodyParams;

  public static function init() {
    add_action('init', [__CLASS__, 'bootstrap_option']);
    add_action('wp_enqueue_scripts', [__CLASS__, 'maybe_enqueue_frontend_assets'], 20);
  }

  public static function bootstrap_option() {
    if (false === get_option(self::OPTION, false)) {
      add_option(self::OPTION, self::DEFAULTS, '', 'no');
    }
  }

  public static function get_settings(): array {
    $stored = get_option(self::OPTION, []);
    if (!is_array($stored)) {
      $stored = [];
    }

    $settings = wp_parse_args($stored, self::DEFAULTS);
    $settings['rate_limit'] = wp_parse_args($settings['rate_limit'] ?? [], self::DEFAULTS['rate_limit']);
    $settings['captcha'] = wp_parse_args($settings['captcha'] ?? [], self::DEFAULTS['captcha']);

    $forms = $settings['captcha']['enabled_forms'] ?? [];
    if (!is_array($forms)) {
      $forms = [];
    }
    $settings['captcha']['enabled_forms'] = wp_parse_args($forms, self::DEFAULTS['captcha']['enabled_forms']);

    $settings['rate_limit']['window'] = max(10, absint($settings['rate_limit']['window']));
    $settings['rate_limit']['per_ip'] = max(1, absint($settings['rate_limit']['per_ip']));
    $settings['rate_limit']['per_user'] = max(0, absint($settings['rate_limit']['per_user']));
    $settings['captcha']['threshold'] = self::clamp((float) $settings['captcha']['threshold'], 0.0, 1.0);

    return $settings;
  }

  public static function save_settings(array $settings): array {
    $current = self::get_settings();

    if (isset($settings['rate_limit']) && is_array($settings['rate_limit'])) {
      $current['rate_limit'] = wp_parse_args($settings['rate_limit'], $current['rate_limit']);
      $current['rate_limit']['window'] = max(10, absint($current['rate_limit']['window']));
      $current['rate_limit']['per_ip'] = max(1, absint($current['rate_limit']['per_ip']));
      $current['rate_limit']['per_user'] = max(0, absint($current['rate_limit']['per_user']));
    }

    if (isset($settings['captcha']) && is_array($settings['captcha'])) {
      $current['captcha'] = wp_parse_args($settings['captcha'], $current['captcha']);
      $current['captcha']['threshold'] = self::clamp((float) ($current['captcha']['threshold'] ?? 0.0), 0.0, 1.0);

      if (isset($settings['captcha']['enabled_forms']) && is_array($settings['captcha']['enabled_forms'])) {
        $current['captcha']['enabled_forms'] = wp_parse_args(
          $settings['captcha']['enabled_forms'],
          $current['captcha']['enabled_forms']
        );
      }
    }

    update_option(self::OPTION, $current, false);

    return $current;
  }

  public static function get_frontend_config(): array {
    $captcha = self::get_settings()['captcha'];
    return [
      'provider' => $captcha['provider'] ?? '',
      'siteKey'  => $captcha['site_key'] ?? '',
      'forms'    => array_filter($captcha['enabled_forms'] ?? []),
    ];
  }

  public static function register_ajax_endpoint(array $endpoint): void {
    $action = isset($endpoint['action']) ? sanitize_key($endpoint['action']) : '';
    if ($action === '' || empty($endpoint['callback'])) {
      return;
    }

    $options = [
      'channel'        => $endpoint['channel'] ?? $action,
      'captcha_action' => $endpoint['captcha_action'] ?? null,
    ];

    $callback = self::wrap_ajax_handler($endpoint['callback'], $options);
    add_action('wp_ajax_' . $action, $callback, $endpoint['priority'] ?? 10);

    if (!empty($endpoint['nopriv'])) {
      add_action('wp_ajax_nopriv_' . $action, self::wrap_ajax_handler($endpoint['callback'], $options), $endpoint['priority'] ?? 10);
    }
  }

  public static function wrap_ajax_handler($callback, array $options = []): callable {
    return function() use ($callback, $options) {
      $result = self::enforce($options['channel'] ?? 'ajax', $options);
      if (is_wp_error($result)) {
        $status = (int) ($result->get_error_data('status') ?? 429);
        $response = [
          'ok'   => false,
          'msg'  => $result->get_error_message(),
        ];
        $retryAfter = $result->get_error_data('retry_after');
        if ($retryAfter) {
          $response['retry_after'] = (int) $retryAfter;
        }
        wp_send_json($response, $status);
      }

      return call_user_func($callback);
    };
  }

  public static function wrap_rest_callback($callback, array $options = []): callable {
    return function(...$args) use ($callback, $options) {
      if (!empty($args) && $args[0] instanceof WP_REST_Request) {
        $options['request'] = $args[0];
      }

      $result = self::enforce($options['channel'] ?? 'rest', $options);
      if (is_wp_error($result)) {
        $status = (int) ($result->get_error_data('status') ?? 429);
        $payload = [
          'ok'      => false,
          'message' => $result->get_error_message(),
        ];
        $retryAfter = $result->get_error_data('retry_after');
        if ($retryAfter) {
          $payload['retry_after'] = (int) $retryAfter;
        }
        return new WP_REST_Response($payload, $status);
      }

      return call_user_func_array($callback, $args);
    };
  }

  public static function enforce(string $channel, array $options = []) {
    $settings = self::get_settings();
    $channel = self::normalize_channel($channel);

    $rateResult = self::check_rate_limit($channel, $settings['rate_limit']);
    if (is_wp_error($rateResult)) {
      return $rateResult;
    }

    if (!empty($options['captcha_action'])) {
      $captchaResult = self::validate_captcha($options['captcha_action'], $settings['captcha'], $options);
      if (is_wp_error($captchaResult)) {
        return $captchaResult;
      }
    }

    return true;
  }

  protected static function check_rate_limit(string $channel, array $config) {
    $ip = self::get_client_ip();
    $userIdentifier = self::get_user_identifier();
    $window = max(10, absint($config['window'] ?? 300));

    if ($ip) {
      $result = self::hit_bucket('ip', $ip, $channel, $window, max(1, absint($config['per_ip'] ?? 30)));
      if (is_wp_error($result)) {
        return $result;
      }
    }

    if ($userIdentifier) {
      $limit = max(0, absint($config['per_user'] ?? 60));
      if ($limit > 0) {
        $result = self::hit_bucket('user', $userIdentifier, $channel, $window, $limit);
        if (is_wp_error($result)) {
          return $result;
        }
      }
    }

    return true;
  }

  protected static function hit_bucket(string $type, string $identifier, string $channel, int $window, int $limit) {
    $key = 'hcisysq_rl_' . substr(md5($type . '|' . $identifier . '|' . $channel), 0, 16);
    $now = time();
    $bucket = get_transient($key);

    if (!is_array($bucket) || empty($bucket['expires_at']) || (int) $bucket['expires_at'] <= $now) {
      $bucket = [
        'count'      => 0,
        'expires_at' => $now + $window,
      ];
    }

    if ($bucket['count'] >= $limit) {
      $retryAfter = max(1, (int) $bucket['expires_at'] - $now);
      return new WP_Error(
        'hcisysq_rate_limited',
        __('Terlalu banyak permintaan yang masuk. Coba lagi beberapa saat.', 'hcisysq'),
        [
          'status'      => 429,
          'retry_after' => $retryAfter,
        ]
      );
    }

    $bucket['count']++;
    $ttl = max(1, (int) $bucket['expires_at'] - $now);
    set_transient($key, $bucket, $ttl);

    return true;
  }

  protected static function validate_captcha(string $form, array $config, array $context = []) {
    $form = self::normalize_channel($form);
    if (!self::is_captcha_enabled_for($form, $config)) {
      return true;
    }

    $token = self::extract_captcha_token($context);
    if ($token === '') {
      return new WP_Error(
        'hcisysq_captcha_missing',
        __('Verifikasi CAPTCHA diperlukan sebelum melanjutkan.', 'hcisysq'),
        ['status' => 400]
      );
    }

    $tokenKey = md5($form . '|' . $token);
    if (isset(self::$validatedTokens[$tokenKey])) {
      return true;
    }

    $provider = $config['provider'] ?? '';
    $secret = trim((string) ($config['secret_key'] ?? ''));
    $siteKey = trim((string) ($config['site_key'] ?? ''));
    if ($provider === '' || $secret === '' || $siteKey === '') {
      return new WP_Error(
        'hcisysq_captcha_unconfigured',
        __('Konfigurasi CAPTCHA belum lengkap.', 'hcisysq'),
        ['status' => 500]
      );
    }

    $endpoint = $provider === 'hcaptcha'
      ? 'https://hcaptcha.com/siteverify'
      : 'https://www.google.com/recaptcha/api/siteverify';

    $body = [
      'secret'   => $secret,
      'response' => $token,
    ];

    $ip = self::get_client_ip();
    if ($ip) {
      $body['remoteip'] = $ip;
    }

    $response = wp_remote_post($endpoint, [
      'timeout'   => 8,
      'body'      => $body,
      'user-agent'=> 'HCISYSQ Security/' . HCISYSQ_VER,
    ]);

    if (is_wp_error($response)) {
      return new WP_Error(
        'hcisysq_captcha_http',
        __('CAPTCHA tidak dapat diverifikasi saat ini. Coba lagi nanti.', 'hcisysq'),
        ['status' => 500]
      );
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $payload = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($payload)) {
      return new WP_Error(
        'hcisysq_captcha_invalid',
        __('Respon CAPTCHA tidak valid.', 'hcisysq'),
        ['status' => 500]
      );
    }

    if (empty($payload['success'])) {
      $message = __('Verifikasi CAPTCHA gagal.', 'hcisysq');
      if (!empty($payload['error-codes']) && is_array($payload['error-codes'])) {
        $message .= ' ' . implode(', ', array_map('sanitize_text_field', $payload['error-codes']));
      }
      return new WP_Error(
        'hcisysq_captcha_failed',
        $message,
        ['status' => $code >= 400 ? $code : 400]
      );
    }

    $threshold = self::clamp((float) ($config['threshold'] ?? 0.0), 0.0, 1.0);
    if ($threshold > 0 && isset($payload['score'])) {
      $score = (float) $payload['score'];
      if ($score < $threshold) {
        return new WP_Error(
          'hcisysq_captcha_score',
          __('Skor CAPTCHA terlalu rendah. Silakan coba lagi.', 'hcisysq'),
          ['status' => 400]
        );
      }
    }

    self::$validatedTokens[$tokenKey] = true;
    return true;
  }

  protected static function extract_captcha_token(array $context = []): string {
    $token = '';
    if (isset($_POST['hcisysq_captcha_token'])) {
      $token = sanitize_text_field(wp_unslash($_POST['hcisysq_captcha_token']));
    } elseif (isset($_REQUEST['hcisysq_captcha_token'])) {
      $token = sanitize_text_field(wp_unslash($_REQUEST['hcisysq_captcha_token']));
    }

    if ($token === '' && !empty($context['request']) && $context['request'] instanceof WP_REST_Request) {
      $token = sanitize_text_field((string) $context['request']->get_param('hcisysq_captcha_token'));
    }

    if ($token === '') {
      $bodyParams = self::get_raw_body_params();
      if (is_array($bodyParams) && isset($bodyParams['hcisysq_captcha_token'])) {
        $token = sanitize_text_field((string) $bodyParams['hcisysq_captcha_token']);
      }
    }

    return $token;
  }

  protected static function get_raw_body_params() {
    if (self::$rawBodyParams !== null) {
      return self::$rawBodyParams;
    }

    $contentType = isset($_SERVER['CONTENT_TYPE']) ? strtolower((string) $_SERVER['CONTENT_TYPE']) : '';
    if (strpos($contentType, 'application/json') === false) {
      self::$rawBodyParams = [];
      return self::$rawBodyParams;
    }

    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    self::$rawBodyParams = is_array($decoded) ? $decoded : [];
    return self::$rawBodyParams;
  }

  protected static function is_captcha_enabled_for(string $form, array $config): bool {
    $provider = trim((string) ($config['provider'] ?? ''));
    $site = trim((string) ($config['site_key'] ?? ''));
    if ($provider === '' || $site === '') {
      return false;
    }
    $forms = $config['enabled_forms'] ?? [];
    if (!is_array($forms)) {
      $forms = [];
    }
    return !empty($forms[$form]);
  }

  public static function render_captcha_placeholder(string $form): string {
    $settings = self::get_settings();
    $form = self::normalize_channel($form);
    if (!self::is_captcha_enabled_for($form, $settings['captcha'])) {
      return '';
    }

    self::$needs_frontend_assets = true;
    $id = 'hcisysq-captcha-' . wp_generate_password(6, false, false);

    ob_start();
    ?>
    <div class="hcisysq-captcha-wrapper">
      <div id="<?= esc_attr($id); ?>" class="hcisysq-captcha" data-form="<?= esc_attr($form); ?>"></div>
      <input type="hidden" name="hcisysq_captcha_token" value="">
    </div>
    <?php
    return (string) ob_get_clean();
  }

  public static function maybe_enqueue_frontend_assets(): void {
    if (!self::$needs_frontend_assets) {
      return;
    }

    $config = self::get_settings()['captcha'];
    if (empty($config['provider']) || empty($config['site_key'])) {
      return;
    }

    if (!wp_script_is('hcisysq-security', 'registered')) {
      return;
    }

    wp_enqueue_script('hcisysq-security');
  }

  protected static function normalize_channel(string $channel): string {
    $channel = strtolower(preg_replace('/[^a-z0-9_\-]/', '_', $channel));
    return trim($channel, '_-') ?: 'default';
  }

  protected static function get_client_ip(): string {
    $candidates = [
      'HTTP_CLIENT_IP',
      'HTTP_X_FORWARDED_FOR',
      'HTTP_X_REAL_IP',
      'REMOTE_ADDR',
    ];

    foreach ($candidates as $key) {
      if (empty($_SERVER[$key])) {
        continue;
      }
      $value = trim((string) $_SERVER[$key]);
      if ($value === '') {
        continue;
      }
      if ($key === 'HTTP_X_FORWARDED_FOR') {
        $parts = explode(',', $value);
        if (!empty($parts)) {
          $value = trim($parts[0]);
        }
      }
      if (filter_var($value, FILTER_VALIDATE_IP)) {
        return $value;
      }
    }

    return '';
  }

  protected static function get_user_identifier(): ?string {
    if (class_exists(__NAMESPACE__ . '\\Auth')) {
      $identity = Auth::current_identity();
      if (is_array($identity)) {
        if (($identity['type'] ?? '') === 'admin') {
          $admin = $identity['user'] ?? [];
          $name = '';
          if (is_array($admin)) {
            $name = $admin['username'] ?? ($admin['display_name'] ?? 'admin');
          } elseif (is_object($admin) && isset($admin->user_login)) {
            $name = $admin->user_login;
          }
          return 'admin:' . sanitize_key($name ?: 'portal');
        }

        $user = $identity['user'] ?? null;
        if (is_object($user) && isset($user->ID)) {
          return 'wp:' . (int) $user->ID;
        }
        if (is_object($user) && isset($user->nip)) {
          return 'nip:' . sanitize_text_field($user->nip);
        }
        if (is_array($user)) {
          if (!empty($user['id'])) {
            return 'legacy:' . sanitize_text_field((string) $user['id']);
          }
          if (!empty($user['nip'])) {
            return 'nip:' . sanitize_text_field((string) $user['nip']);
          }
        }
      }
    }

    $wpUserId = get_current_user_id();
    if ($wpUserId) {
      return 'wp:' . (int) $wpUserId;
    }

    return null;
  }

  protected static function clamp(float $value, float $min, float $max): float {
    return max($min, min($max, $value));
  }
}
