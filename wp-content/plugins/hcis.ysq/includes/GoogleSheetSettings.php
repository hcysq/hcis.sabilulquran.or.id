<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class GoogleSheetSettings {
  const OPT_JSON_CREDS = 'hcis_portal_credentials_json';
  const OPT_SHEET_ID   = 'hcis_portal_sheet_id';
  const OPT_GID_MAP    = 'hcis_portal_gids';
  const OPT_STATUS     = 'hcis_portal_config_status';

  const DEFAULT_SHEET_ID = '110MjkBJbBzFayIUZcA3ZhKuno8y5OcWEnn04TDVHW-Y';

  const REQUIRED_CREDENTIAL_KEYS = [
    'type',
    'project_id',
    'private_key',
    'client_email',
  ];

  private static $tab_labels = [
    'users'      => 'Users',
    'profiles'   => 'Profiles',
    'payroll'    => 'Payroll',
    'keluarga'   => 'Keluarga',
    'dokumen'    => 'Dokumen',
    'pendidikan' => 'Pendidikan',
    'pelatihan'  => 'Pelatihan',
  ];

  public static function init() {
    add_action('admin_init', [__CLASS__, 'maybe_initialize_options']);
    add_action('admin_init', [__CLASS__, 'register_settings']);
    self::maybe_initialize_options();
  }

  public static function register_settings() {
    register_setting('hcis_portal_settings', self::OPT_JSON_CREDS, [
      'type' => 'string',
      'sanitize_callback' => [__CLASS__, 'sanitize_credentials_field'],
    ]);

    register_setting('hcis_portal_settings', self::OPT_SHEET_ID, [
      'type' => 'string',
      'sanitize_callback' => 'sanitize_text_field',
    ]);

    register_setting('hcis_portal_settings', self::OPT_GID_MAP, [
      'type' => 'array',
      'sanitize_callback' => [__CLASS__, 'sanitize_gid_option'],
    ]);
  }

  public static function maybe_initialize_options() {
    $defaults = [
      self::OPT_JSON_CREDS => '',
      self::OPT_SHEET_ID   => self::DEFAULT_SHEET_ID,
      self::OPT_GID_MAP    => [],
      self::OPT_STATUS     => [
        'valid'       => false,
        'message'     => __('Google Sheets belum dikonfigurasi.', 'hcis-ysq'),
        'last_checked'=> 0,
      ],
    ];

    foreach ($defaults as $option => $default) {
      if (get_option($option, null) === null) {
        add_option($option, $default);
      }
    }
  }

  public static function get_tab_labels() {
    $labels = [];
    foreach (self::$tab_labels as $key => $label) {
      $labels[$key] = __($label, 'hcis-ysq');
    }
    return $labels;
  }

  public static function save_settings($credentials_json, $sheet_id, array $gids) {
    update_option(self::OPT_JSON_CREDS, $credentials_json);
    update_option(self::OPT_SHEET_ID, $sheet_id ?: '');
    update_option(self::OPT_GID_MAP, self::sanitize_gid_map_values($gids));

    $status = self::validate_credentials($credentials_json);
    self::store_status($status);

    return $status;
  }

  public static function get_credentials_json() {
    return trim((string) get_option(self::OPT_JSON_CREDS, ''));
  }

  public static function get_credentials() {
    $json = self::get_credentials_json();
    if ($json === '') {
      return [];
    }

    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : [];
  }

  public static function get_sheet_id() {
    $sheet_id = get_option(self::OPT_SHEET_ID, '');
    if (!$sheet_id) {
      $sheet_id = self::DEFAULT_SHEET_ID;
    }
    return $sheet_id;
  }

  public static function get_gid_map() {
    $map = get_option(self::OPT_GID_MAP, []);
    if (!is_array($map)) {
      $map = [];
    }

    $defaults = array_fill_keys(array_keys(self::$tab_labels), '');
    return array_merge($defaults, array_intersect_key($map, $defaults));
  }

  public static function get_gid($tab) {
    $map = self::get_gid_map();
    return isset($map[$tab]) ? (string) $map[$tab] : '';
  }

  public static function get_status() {
    $status = get_option(self::OPT_STATUS, []);
    if (!is_array($status)) {
      $status = [];
    }

    $defaults = [
      'valid'        => false,
      'message'      => __('Google Sheets belum dikonfigurasi.', 'hcis-ysq'),
      'last_checked' => 0,
    ];

    return array_merge($defaults, $status);
  }

  public static function is_configured() {
    $status = self::get_status();
    return !empty(self::get_sheet_id()) && !empty(self::get_credentials_json()) && !empty($status['valid']);
  }

  public static function sanitize_credentials_field($value) {
    if (is_array($value)) {
      $value = wp_json_encode($value);
    }

    return trim((string) $value);
  }

  public static function sanitize_gid_option($value) {
    return self::sanitize_gid_map_values(is_array($value) ? $value : []);
  }

  private static function sanitize_gid_map_values(array $gids) {
    $allowed = array_fill_keys(array_keys(self::$tab_labels), '');
    $sanitized = [];

    foreach ($allowed as $key => $default) {
      $sanitized[$key] = sanitize_text_field($gids[$key] ?? '');
    }

    return $sanitized;
  }

  private static function validate_credentials($credentials_json) {
    $credentials_json = trim($credentials_json);
    if ($credentials_json === '') {
      return [
        'valid'   => false,
        'message' => __('Credential JSON kosong.', 'hcis-ysq'),
      ];
    }

    $decoded = json_decode($credentials_json, true);
    if (!is_array($decoded)) {
      $error_message = json_last_error_msg();
      return [
        'valid'   => false,
        'message' => sprintf(__('Credential JSON tidak valid: %s', 'hcis-ysq'), $error_message),
      ];
    }

    foreach (self::REQUIRED_CREDENTIAL_KEYS as $key) {
      if (empty($decoded[$key])) {
        return [
          'valid'   => false,
          'message' => sprintf(__('Credential JSON tidak memiliki field %s.', 'hcis-ysq'), $key),
        ];
      }
    }

    if ($decoded['type'] !== 'service_account') {
      return [
        'valid'   => false,
        'message' => __('Credential harus bertipe service_account.', 'hcis-ysq'),
      ];
    }

    return [
      'valid'   => true,
      'message' => __('Credential JSON valid.', 'hcis-ysq'),
    ];
  }

  private static function store_status(array $status) {
    $payload = [
      'valid'        => !empty($status['valid']),
      'message'      => (string) ($status['message'] ?? ''),
      'last_checked' => current_time('timestamp'),
    ];

    update_option(self::OPT_STATUS, $payload);

    if (!$payload['valid']) {
      hcisysq_log('GoogleSheetSettings validation failed: ' . $payload['message'], 'error');
    } else {
      hcisysq_log('GoogleSheetSettings validation success.', 'info');
    }
  }
}
