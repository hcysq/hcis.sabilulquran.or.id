<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class GoogleSheetSettings {

  const DEFAULT_SHEET_ID = '1HCISYSQ_DEFAULT_SHEET_ID_SAMPLE000000000';

  const OPT_JSON_CREDS = 'hcis_google_json_creds';
  const OPT_SHEET_ID = 'hcis_google_sheet_id';
  const OPT_TAB_METRICS = 'hcis_gs_tab_metrics';
  const OPT_STATUS = 'hcis_gs_settings_status';
  const TAB_HASH_PREFIX = 'hcis_gs_tab_hash_';
  const OPT_TAB_COLUMN_ORDER_PREFIX = 'hcis_gs_tab_col_order_';
  const OPT_SETUP_KEYS = 'hcis_portal_setup_keys';

  private const TAB_MAP = [
    'users' => [
      'title' => 'Users',
      'gid_option' => 'hcis_gid_users',
      'range_end' => 'E',
    ],
    'profiles' => [
      'title' => 'Profiles',
      'gid_option' => 'hcis_gid_profiles',
      'range_end' => 'N',
    ],
    'payroll' => [
      'title' => 'Payroll',
      'gid_option' => 'hcis_gid_payroll',
      'range_end' => 'G',
    ],
    'keluarga' => [
      'title' => 'Keluarga',
      'gid_option' => 'hcis_gid_keluarga',
      'range_end' => 'F',
    ],
    'dokumen' => [
      'title' => 'Dokumen',
      'gid_option' => 'hcis_gid_dokumen',
      'range_end' => 'G',
    ],
    'pendidikan' => [
      'title' => 'Pendidikan',
      'gid_option' => 'hcis_gid_pendidikan',
      'range_end' => 'G',
    ],
    'pelatihan' => [
      'title' => 'Pelatihan',
      'gid_option' => 'hcis_gid_pelatihan',
      'range_end' => 'G',
    ],
  ];

  public static function init() {
    add_action('rest_api_init', [__CLASS__, 'register_rest_routes']);
  }

  public static function is_configured(): bool {
    $sheet = self::get_sheet_id();
    $creds = self::get_credentials();
    return !empty($sheet) && !empty($creds);
  }

  public static function get_sheet_id(): string {
    return trim((string) get_option(self::OPT_SHEET_ID, ''));
  }

  public static function get_credentials(): array {
    $raw = get_option(self::OPT_JSON_CREDS, '');
    if (is_array($raw)) {
      return $raw;
    }

    $decoded = json_decode((string) $raw, true);
    return is_array($decoded) ? $decoded : [];
  }

  public static function get_credentials_json(): string {
    $raw = get_option(self::OPT_JSON_CREDS, '');
    if (is_array($raw)) {
      return wp_json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    return (string) $raw;
  }

  public static function get_gid(string $tab): string {
    $config = self::TAB_MAP[$tab] ?? null;
    if (!$config) {
      return '';
    }
    $option = $config['gid_option'];
    return trim((string) get_option($option, ''));
  }

  public static function get_gid_map(): array {
    $map = [];
    foreach (self::TAB_MAP as $slug => $config) {
      $map[$slug] = trim((string) get_option($config['gid_option'], ''));
    }
    return $map;
  }

  public static function get_tab_name(string $tab): string {
    $config = self::TAB_MAP[$tab] ?? null;
    if (!$config) {
      return ucfirst($tab);
    }
    return $config['title'];
  }

  public static function get_tab_labels(): array {
    $labels = [];
    foreach (self::TAB_MAP as $slug => $config) {
      $labels[$slug] = $config['title'] ?? ucfirst($slug);
    }
    return $labels;
  }

  public static function get_tab_range(string $tab): string {
    $name = self::get_tab_name($tab);
    $config = self::TAB_MAP[$tab] ?? null;
    $end = $config ? ($config['range_end'] ?? 'Z') : 'Z';
    return sprintf('%s!A:%s', $name, $end);
  }

  public static function get_tabs(): array {
    return self::TAB_MAP;
  }

  public static function get_tab_hash(string $tab): string {
    return (string) get_option(self::TAB_HASH_PREFIX . $tab, '');
  }

  public static function set_tab_hash(string $tab, string $hash): void {
    update_option(self::TAB_HASH_PREFIX . $tab, $hash, false);
  }

  public static function get_tab_column_order(string $tab): array {
    $setup_keys = self::get_effective_setup_keys();
    $headers = [];
    foreach ($setup_keys as $config) {
      if (($config['tab'] ?? '') !== $tab) {
        continue;
      }
      $header = trim((string) ($config['header'] ?? ''));
      if ($header === '') {
        continue;
      }
      $headers[] = [
        'order' => (int) ($config['order'] ?? 0),
        'header' => $header,
      ];
    }

    if (!empty($headers)) {
      usort($headers, function ($a, $b) {
        return $a['order'] <=> $b['order'];
      });

      $ordered_headers = array_map(static function ($row) {
        return $row['header'];
      }, $headers);

      return array_values(array_unique($ordered_headers));
    }

    $option_name = self::OPT_TAB_COLUMN_ORDER_PREFIX . $tab;
    $order_string = get_option($option_name, '');
    if (empty($order_string)) {
      return [];
    }
    return array_map('trim', explode(',', $order_string));
  }

  public static function get_setup_key_definitions(): array {
    return [
      'employee_id_number' => [
        'label' => 'employee_id_number',
        'plugin' => __('Dashboard Profil', 'hcis-ysq'),
        'description' => __('Ditampilkan di Dashboard → Profil sebagai NIP utama pegawai.', 'hcis-ysq'),
        'default_tab' => 'users',
        'default_header' => 'NIP',
        'default_order' => 1,
      ],
      'full_name' => [
        'label' => 'full_name',
        'plugin' => __('Portal HCIS', 'hcis-ysq'),
        'description' => __('Nama lengkap yang digunakan di kartu profil dan halaman depan portal.', 'hcis-ysq'),
        'default_tab' => 'users',
        'default_header' => 'Nama',
        'default_order' => 2,
      ],
      'phone_number' => [
        'label' => 'phone_number',
        'plugin' => __('WhatsApp & Login', 'hcis-ysq'),
        'description' => __('Nomor HP yang dipakai login dan pengiriman OTP WhatsApp.', 'hcis-ysq'),
        'default_tab' => 'users',
        'default_header' => 'No HP',
        'default_order' => 3,
      ],
      'email' => [
        'label' => 'email',
        'plugin' => __('WordPress User', 'hcis-ysq'),
        'description' => __('Email akun WordPress serta kontak resmi pegawai.', 'hcis-ysq'),
        'default_tab' => 'users',
        'default_header' => 'Email',
        'default_order' => 4,
      ],
      'password_hash' => [
        'label' => 'password_hash',
        'plugin' => __('Portal Login', 'hcis-ysq'),
        'description' => __('Hash password yang disinkronisasi dengan akun WordPress.', 'hcis-ysq'),
        'default_tab' => 'users',
        'default_header' => 'Password Hash',
        'default_order' => 5,
      ],
      'nik' => [
        'label' => 'nik',
        'plugin' => __('Dashboard Profil', 'hcis-ysq'),
        'description' => __('Nomor induk kependudukan yang tampil di detail profil.', 'hcis-ysq'),
        'default_tab' => 'users',
        'default_header' => 'NIK',
        'default_order' => 6,
      ],
    ];
  }

  public static function get_setup_key_settings(): array {
    $stored = get_option(self::OPT_SETUP_KEYS, []);
    return is_array($stored) ? $stored : [];
  }

  public static function get_effective_setup_keys(): array {
    $definitions = self::get_setup_key_definitions();
    $stored = self::get_setup_key_settings();
    $effective = [];

    foreach ($definitions as $key => $definition) {
      $saved = is_array($stored[$key] ?? null) ? $stored[$key] : [];
      $effective[$key] = array_merge($definition, [
        'tab' => isset($saved['tab']) ? sanitize_key($saved['tab']) : $definition['default_tab'],
        'header' => isset($saved['header']) ? sanitize_text_field($saved['header']) : $definition['default_header'],
        'order' => isset($saved['order']) ? absint($saved['order']) : (int) $definition['default_order'],
      ]);
      if (!isset(self::TAB_MAP[$effective[$key]['tab']])) {
        $effective[$key]['tab'] = $definition['default_tab'];
      }
      if ($effective[$key]['header'] === '') {
        $effective[$key]['header'] = $definition['default_header'];
      }
      if ($effective[$key]['order'] === 0) {
        $effective[$key]['order'] = (int) $definition['default_order'];
      }
    }

    return $effective;
  }

  protected static function normalize_setup_keys(array $setup_keys): array {
    $definitions = self::get_setup_key_definitions();
    $normalized = [];

    foreach ($definitions as $key => $definition) {
      $raw = $setup_keys[$key] ?? [];
      $tab = isset($raw['tab']) ? sanitize_key($raw['tab']) : $definition['default_tab'];
      if (!isset(self::TAB_MAP[$tab])) {
        $tab = $definition['default_tab'];
      }
      $header = isset($raw['header']) ? sanitize_text_field($raw['header']) : $definition['default_header'];
      if ($header === '') {
        $header = $definition['default_header'];
      }
      $order = isset($raw['order']) ? absint($raw['order']) : 0;
      if ($order === 0) {
        $order = (int) $definition['default_order'];
      }

      $normalized[$key] = [
        'tab' => $tab,
        'header' => $header,
        'order' => $order,
      ];
    }

    return $normalized;
  }

  protected static function persist_setup_keys(array $setup_keys): void {
    $normalized = self::normalize_setup_keys($setup_keys);
    update_option(self::OPT_SETUP_KEYS, $normalized, false);

    $grouped = [];
    foreach ($normalized as $config) {
      $tab = $config['tab'];
      if (!isset($grouped[$tab])) {
        $grouped[$tab] = [];
      }
      $grouped[$tab][] = $config;
    }

    foreach (self::TAB_MAP as $slug => $config) {
      $headers = $grouped[$slug] ?? [];
      if (empty($headers)) {
        continue;
      }

      usort($headers, function ($a, $b) {
        return $a['order'] <=> $b['order'];
      });

      $labels = array_map(static function ($row) {
        return $row['header'];
      }, $headers);

      update_option(self::OPT_TAB_COLUMN_ORDER_PREFIX . $slug, implode(', ', $labels), false);
    }
  }

  public static function record_tab_metrics(string $tab, array $data): void {
    $metrics = get_option(self::OPT_TAB_METRICS, []);
    if (!is_array($metrics)) {
      $metrics = [];
    }
    $metrics[$tab] = array_merge([
      'last_sync' => current_time('mysql'),
      'rows' => 0,
      'applied' => 0,
      'hash' => '',
    ], $data);
    update_option(self::OPT_TAB_METRICS, $metrics, false);
  }

  public static function get_tab_metrics(): array {
    $metrics = get_option(self::OPT_TAB_METRICS, []);
    return is_array($metrics) ? $metrics : [];
  }

  public static function get_status(): array {
    $status = get_option(self::OPT_STATUS, []);
    if (!is_array($status)) {
      $status = [];
    }

    return array_merge([
      'valid' => false,
      'message' => '',
      'last_checked' => 0,
    ], $status);
  }

  public static function save_settings(string $credentials_json, string $sheet_id, array $gids, array $setup_keys): array {
    $status = [
      'valid' => true,
      'message' => __('Kredensial valid.', 'hcis-ysq'),
      'last_checked' => time(),
    ];

    $credentials_json = trim($credentials_json);
    $sheet_id = trim($sheet_id);

    if ($sheet_id === '') {
      $sheet_id = self::DEFAULT_SHEET_ID;
    }

    update_option(self::OPT_SHEET_ID, $sheet_id, false);
    update_option(self::OPT_JSON_CREDS, $credentials_json, false);

    if ($credentials_json === '') {
      $status['valid'] = false;
      $status['message'] = __('Credential JSON kosong.', 'hcis-ysq');
    } else {
      $decoded = json_decode($credentials_json, true);
      if (!is_array($decoded)) {
        $status['valid'] = false;
        $status['message'] = __('Credential JSON tidak valid.', 'hcis-ysq');
      } elseif (empty($decoded['type']) || empty($decoded['client_email'])) {
        $status['valid'] = false;
        $status['message'] = __('Credential JSON tidak lengkap.', 'hcis-ysq');
      }
    }

    foreach (self::TAB_MAP as $slug => $config) {
      $gid_value = isset($gids[$slug]) ? trim((string) $gids[$slug]) : '';
      if ($gid_value !== '' && !preg_match('/^-?\d+$/', $gid_value)) {
        $status['valid'] = false;
        $status['message'] = sprintf(__('GID %s tidak valid.', 'hcis-ysq'), $config['title']);
      }
      update_option($config['gid_option'], $gid_value, false);
    }

    self::persist_setup_keys($setup_keys);

    update_option(self::OPT_STATUS, $status, false);

    return $status;
  }

  public static function register_rest_routes() {
    register_rest_route('hcis/v1', '/sheets/(?P<tab>[a-z_\-]+)/?', [
      'methods' => 'GET',
      'callback' => [__CLASS__, 'rest_get_tab_data'],
      'permission_callback' => function () {
        return current_user_can('manage_hcis_portal') || current_user_can('manage_options');
      },
      'args' => [
        'tab' => [
          'validate_callback' => function ($param) {
            return isset(self::TAB_MAP[$param]);
          }
        ],
      ],
    ]);
  }

  public static function rest_get_tab_data(WP_REST_Request $request) {
    $tab = $request->get_param('tab');
    if (!self::is_configured()) {
      return new WP_Error('hcisysq_sheet_unconfigured', __('Google Sheet belum dikonfigurasi.', 'hcis-ysq'), ['status' => 400]);
    }

    $class = self::repository_class_for($tab);
    if (!$class || !class_exists($class)) {
      return new WP_Error('hcisysq_repo_missing', __('Repository tidak ditemukan untuk tab ini.', 'hcis-ysq'), ['status' => 404]);
    }

    try {
        $repo = new $class(new SheetCache());
    } catch (\Exception $e) {
        return new WP_Error('hcisysq_sheet_auth', $e->getMessage(), ['status' => 500]);
    }

    $data = $repo->all();

    return new WP_REST_Response([
      'tab' => $tab,
      'count' => count($data),
      'rows' => $data,
    ]);
  }

  public static function repository_class_for(string $tab): ?string {
    $map = [
      'users' => '\\HCISYSQ\\Repositories\\UserRepository',
      'profiles' => '\\HCISYSQ\\Repositories\\ProfileRepository',
      'payroll' => '\\HCISYSQ\\Repositories\\PayrollRepository',
      'keluarga' => '\\HCISYSQ\\Repositories\\KeluargaRepository',
      'dokumen' => '\\HCISYSQ\\Repositories\\DokumenRepository',
      'pendidikan' => '\\HCISYSQ\\Repositories\\PendidikanRepository',
      'pelatihan' => '\\HCISYSQ\\Repositories\\PelatihanRepository',
    ];
    return $map[$tab] ?? null;
  }
}
