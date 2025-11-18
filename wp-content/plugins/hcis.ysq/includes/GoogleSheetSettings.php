<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class GoogleSheetSettings {

  const OPT_JSON_CREDS = 'hcis_google_json_creds';
  const OPT_SHEET_ID = 'hcis_google_sheet_id';
  const OPT_TAB_METRICS = 'hcis_gs_tab_metrics';
  const TAB_HASH_PREFIX = 'hcis_gs_tab_hash_';

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

  public static function get_gid(string $tab): string {
    $config = self::TAB_MAP[$tab] ?? null;
    if (!$config) {
      return '';
    }
    $option = $config['gid_option'];
    return trim((string) get_option($option, ''));
  }

  public static function get_tab_name(string $tab): string {
    $config = self::TAB_MAP[$tab] ?? null;
    if (!$config) {
      return ucfirst($tab);
    }
    return $config['title'];
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

    $api = new GoogleSheetsAPI();
    if (!$api->authenticate(self::get_credentials())) {
      return new WP_Error('hcisysq_sheet_auth', __('Autentikasi Google Sheet gagal.', 'hcis-ysq'), ['status' => 500]);
    }

    $repo = new $class($api, new SheetCache());
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
