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
  const OPT_TAB_GID_PREFIX = 'hcis_gs_tab_gid_';
  const OPT_SETUP_KEYS = 'hcis_gs_setup_keys';
  private const LEGACY_GID_OPTIONS = [
    'hcis_gid_admins',
    'hcis_gid_users',
    'hcis_gid_profiles',
    'hcis_gid_payroll',
    'hcis_gid_keluarga',
    'hcis_gid_dokumen',
    'hcis_gid_pendidikan',
    'hcis_gid_pelatihan',
  ];

  const DEFAULT_SETUP_KEYS = [
    'user_nip' => [
      'label' => 'NIP',
      'tab' => 'users',
      'header' => 'NIP',
      'order' => 1,
      'description' => 'Nomor induk pegawai.',
      'gid' => '',
      'requires_gid' => true,
    ],
    'user_name' => [
      'label' => 'Nama',
      'tab' => 'users',
      'header' => 'Nama',
      'order' => 2,
      'description' => 'Nama pengguna.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_password' => [
      'label' => 'Password',
      'tab' => 'users',
      'header' => 'Password',
      'order' => 3,
      'description' => 'Kolom password (plaintext atau hash).',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_phone' => [
      'label' => 'No HP',
      'tab' => 'users',
      'header' => 'No HP',
      'order' => 4,
      'description' => 'Nomor telepon.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_email' => [
      'label' => 'Email',
      'tab' => 'users',
      'header' => 'Email',
      'order' => 5,
      'description' => 'Alamat email.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_nik' => [
      'label' => 'NIK',
      'tab' => 'users',
      'header' => 'NIK',
      'order' => 6,
      'description' => 'Nomor induk kependudukan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_unit' => [
      'label' => 'Unit',
      'tab' => 'users',
      'header' => 'Unit',
      'order' => 7,
      'description' => 'Unit kerja.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_position' => [
      'label' => 'Jabatan',
      'tab' => 'users',
      'header' => 'Jabatan',
      'order' => 8,
      'description' => 'Jabatan pegawai.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_birth_place' => [
      'label' => 'Tempat Lahir',
      'tab' => 'users',
      'header' => 'Tempat Lahir',
      'order' => 9,
      'description' => 'Tempat lahir pegawai.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_birth_date' => [
      'label' => 'Tanggal Lahir',
      'tab' => 'users',
      'header' => 'Tanggal Lahir',
      'order' => 10,
      'description' => 'Tanggal lahir pegawai.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_address' => [
      'label' => 'Alamat KTP',
      'tab' => 'users',
      'header' => 'Alamat KTP',
      'order' => 11,
      'description' => 'Alamat sesuai KTP.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_village' => [
      'label' => 'Desa/Kelurahan',
      'tab' => 'users',
      'header' => 'Desa/Kelurahan',
      'order' => 12,
      'description' => 'Nama desa atau kelurahan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_district' => [
      'label' => 'Kecamatan',
      'tab' => 'users',
      'header' => 'Kecamatan',
      'order' => 13,
      'description' => 'Nama kecamatan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_city' => [
      'label' => 'Kota/Kabupaten',
      'tab' => 'users',
      'header' => 'Kota/Kabupaten',
      'order' => 14,
      'description' => 'Nama kota atau kabupaten.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_postal_code' => [
      'label' => 'Kode Pos',
      'tab' => 'users',
      'header' => 'Kode Pos',
      'order' => 15,
      'description' => 'Kode pos alamat.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_join_date' => [
      'label' => 'TMT',
      'tab' => 'users',
      'header' => 'TMT',
      'order' => 16,
      'description' => 'Tanggal mulai tugas.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'payroll_period' => [
      'label' => 'Periode',
      'tab' => 'users',
      'header' => 'Periode',
      'order' => 17,
      'description' => 'Periode gaji.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'payroll_basic_salary' => [
      'label' => 'Gaji Pokok',
      'tab' => 'users',
      'header' => 'Gaji Pokok',
      'order' => 18,
      'description' => 'Nilai gaji pokok.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'payroll_allowance' => [
      'label' => 'Tunjangan',
      'tab' => 'users',
      'header' => 'Tunjangan',
      'order' => 20,
      'description' => 'Total tunjangan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'payroll_deduction' => [
      'label' => 'Potongan',
      'tab' => 'users',
      'header' => 'Potongan',
      'order' => 21,
      'description' => 'Total potongan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'payroll_take_home_pay' => [
      'label' => 'Take Home Pay',
      'tab' => 'users',
      'header' => 'Take Home Pay',
      'order' => 22,
      'description' => 'Nilai bersih gaji.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'payroll_status' => [
      'label' => 'Status',
      'tab' => 'users',
      'header' => 'Status',
      'order' => 23,
      'description' => 'Status pembayaran.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'admin_username' => [
      'label' => 'Username',
      'tab' => 'admins',
      'header' => 'Username',
      'order' => 1,
      'description' => 'Username admin.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'admin_display_name' => [
      'label' => 'Display Name',
      'tab' => 'admins',
      'header' => 'Display Name',
      'order' => 2,
      'description' => 'Nama tampilan admin.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'admin_password' => [
      'label' => 'Password',
      'tab' => 'admins',
      'header' => 'Password',
      'order' => 3,
      'description' => 'Kata sandi admin (plaintext).',
      'gid' => '',
      'requires_gid' => false,
    ],
    'admin_whatsapp' => [
      'label' => 'WhatsApp',
      'tab' => 'admins',
      'header' => 'WhatsApp',
      'order' => 4,
      'description' => 'Kontak WhatsApp admin.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'keluarga_nip' => [
      'label' => 'NIP',
      'tab' => 'keluarga',
      'header' => 'NIP',
      'order' => 1,
      'description' => 'Nomor induk pegawai.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'keluarga_nama' => [
      'label' => 'Nama Anggota',
      'tab' => 'keluarga',
      'header' => 'Nama Anggota',
      'order' => 2,
      'description' => 'Nama anggota keluarga.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'keluarga_hubungan' => [
      'label' => 'Hubungan',
      'tab' => 'keluarga',
      'header' => 'Hubungan',
      'order' => 3,
      'description' => 'Relasi anggota keluarga dengan pegawai.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'keluarga_tanggal_lahir' => [
      'label' => 'Tanggal Lahir',
      'tab' => 'keluarga',
      'header' => 'Tanggal Lahir',
      'order' => 4,
      'description' => 'Tanggal lahir anggota keluarga.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'keluarga_pekerjaan' => [
      'label' => 'Pekerjaan',
      'tab' => 'keluarga',
      'header' => 'Pekerjaan',
      'order' => 5,
      'description' => 'Pekerjaan anggota keluarga.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'keluarga_pendidikan' => [
      'label' => 'Pendidikan',
      'tab' => 'keluarga',
      'header' => 'Pendidikan',
      'order' => 6,
      'description' => 'Pendidikan terakhir anggota keluarga.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'dokumen_nip' => [
      'label' => 'NIP',
      'tab' => 'dokumen',
      'header' => 'NIP',
      'order' => 1,
      'description' => 'Nomor induk pegawai.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'dokumen_kategori' => [
      'label' => 'Kategori',
      'tab' => 'dokumen',
      'header' => 'Kategori',
      'order' => 2,
      'description' => 'Kategori dokumen.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'dokumen_judul' => [
      'label' => 'Judul Dokumen',
      'tab' => 'dokumen',
      'header' => 'Judul Dokumen',
      'order' => 3,
      'description' => 'Judul dokumen.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'dokumen_nomor' => [
      'label' => 'Nomor Dokumen',
      'tab' => 'dokumen',
      'header' => 'Nomor Dokumen',
      'order' => 4,
      'description' => 'Nomor unik dokumen.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'dokumen_tanggal_terbit' => [
      'label' => 'Tanggal Terbit',
      'tab' => 'dokumen',
      'header' => 'Tanggal Terbit',
      'order' => 5,
      'description' => 'Tanggal penerbitan dokumen.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'dokumen_tanggal_kadaluarsa' => [
      'label' => 'Tanggal Kedaluwarsa',
      'tab' => 'dokumen',
      'header' => 'Tanggal Kedaluwarsa',
      'order' => 6,
      'description' => 'Tanggal kedaluwarsa dokumen.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'dokumen_tautan' => [
      'label' => 'Tautan',
      'tab' => 'dokumen',
      'header' => 'Tautan',
      'order' => 7,
      'description' => 'Tautan menuju dokumen.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'pendidikan_nip' => [
      'label' => 'NIP',
      'tab' => 'pendidikan',
      'header' => 'NIP',
      'order' => 1,
      'description' => 'Nomor induk pegawai.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'pendidikan_jenjang' => [
      'label' => 'Jenjang',
      'tab' => 'pendidikan',
      'header' => 'Jenjang',
      'order' => 2,
      'description' => 'Jenjang pendidikan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'pendidikan_institusi' => [
      'label' => 'Institusi',
      'tab' => 'pendidikan',
      'header' => 'Institusi',
      'order' => 3,
      'description' => 'Nama institusi pendidikan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'pendidikan_jurusan' => [
      'label' => 'Jurusan',
      'tab' => 'pendidikan',
      'header' => 'Jurusan',
      'order' => 4,
      'description' => 'Jurusan atau program studi.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'pendidikan_tahun_masuk' => [
      'label' => 'Tahun Masuk',
      'tab' => 'pendidikan',
      'header' => 'Tahun Masuk',
      'order' => 5,
      'description' => 'Tahun mulai pendidikan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'pendidikan_tahun_lulus' => [
      'label' => 'Tahun Lulus',
      'tab' => 'pendidikan',
      'header' => 'Tahun Lulus',
      'order' => 6,
      'description' => 'Tahun kelulusan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'pendidikan_nilai' => [
      'label' => 'Nilai/IPK',
      'tab' => 'pendidikan',
      'header' => 'Nilai/IPK',
      'order' => 7,
      'description' => 'Nilai akhir atau IPK.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'pelatihan_nip' => [
      'label' => 'NIP',
      'tab' => 'pelatihan',
      'header' => 'NIP',
      'order' => 1,
      'description' => 'Nomor induk pegawai.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'pelatihan_nama' => [
      'label' => 'Nama Pelatihan',
      'tab' => 'pelatihan',
      'header' => 'Nama Pelatihan',
      'order' => 2,
      'description' => 'Nama kegiatan pelatihan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'pelatihan_penyelenggara' => [
      'label' => 'Penyelenggara',
      'tab' => 'pelatihan',
      'header' => 'Penyelenggara',
      'order' => 3,
      'description' => 'Penyelenggara pelatihan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'pelatihan_tanggal_mulai' => [
      'label' => 'Tanggal Mulai',
      'tab' => 'pelatihan',
      'header' => 'Tanggal Mulai',
      'order' => 4,
      'description' => 'Tanggal mulai pelatihan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'pelatihan_tanggal_selesai' => [
      'label' => 'Tanggal Selesai',
      'tab' => 'pelatihan',
      'header' => 'Tanggal Selesai',
      'order' => 5,
      'description' => 'Tanggal selesai pelatihan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'pelatihan_status' => [
      'label' => 'Status',
      'tab' => 'pelatihan',
      'header' => 'Status',
      'order' => 6,
      'description' => 'Status pelatihan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'pelatihan_sertifikat' => [
      'label' => 'Link Sertifikat',
      'tab' => 'pelatihan',
      'header' => 'Link Sertifikat',
      'order' => 7,
      'description' => 'Tautan sertifikat pelatihan.',
      'gid' => '',
      'requires_gid' => false,
    ],
  ];

  private const DEFAULT_TAB_HEADERS = [
    'users' => [
      'NIP',
      'Nama',
      'Password',
      'No HP',
      'Email',
      'NIK',
      'Unit',
      'Jabatan',
      'Tempat Lahir',
      'Tanggal Lahir',
      'Alamat KTP',
      'Desa/Kelurahan',
      'Kecamatan',
      'Kota/Kabupaten',
      'Kode Pos',
      'TMT',
      'Periode',
      'Gaji Pokok',
      'Tunjangan',
      'Potongan',
      'Take Home Pay',
      'Status',
    ],
    'admins' => [
      'Username',
      'Display Name',
      'Password',
      'WhatsApp',
    ],
    'keluarga' => [
      'NIP',
      'Nama Anggota',
      'Hubungan',
      'Tanggal Lahir',
      'Pekerjaan',
      'Pendidikan',
    ],
    'dokumen' => [
      'NIP',
      'Kategori',
      'Judul Dokumen',
      'Nomor Dokumen',
      'Tanggal Terbit',
      'Tanggal Kedaluwarsa',
      'Tautan',
    ],
    'pendidikan' => [
      'NIP',
      'Jenjang',
      'Institusi',
      'Jurusan',
      'Tahun Masuk',
      'Tahun Lulus',
      'Nilai/IPK',
    ],
    'pelatihan' => [
      'NIP',
      'Nama Pelatihan',
      'Penyelenggara',
      'Tanggal Mulai',
      'Tanggal Selesai',
      'Status',
      'Link Sertifikat',
    ],
  ];

  private const TAB_MAP = [
    'users' => [
      'title' => 'Users',
    ],
    'admins' => [
      'title' => 'Admins',
    ],
    'keluarga' => [
      'title' => 'Keluarga',
    ],
    'dokumen' => [
      'title' => 'Dokumen',
    ],
    'pendidikan' => [
      'title' => 'Pendidikan',
    ],
    'pelatihan' => [
      'title' => 'Pelatihan',
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
    $tab = sanitize_key($tab);
    $title = self::get_tab_name($tab);

    $configuredGid = self::get_tab_gid_option($tab);
    if ($configuredGid !== '') {
      return $configuredGid;
    }

    try {
      $api = GoogleSheetsAPI::getInstance();
      $sheetId = $api->getSheetIdByTitle($title);
      return $sheetId !== null ? (string) $sheetId : '';
    } catch (\Exception $e) {
      hcisysq_log('Unable to resolve sheet GID automatically: ' . $e->getMessage(), 'warning', [
        'tab' => $tab,
        'title' => $title,
      ]);
      return '';
    }
  }

  public static function get_gid_map(): array {
    $map = [];
    foreach (self::TAB_MAP as $slug => $config) {
      $map[$slug] = self::get_gid($slug);
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

  public static function get_setup_key_definitions(): array {
    $definitions = self::DEFAULT_SETUP_KEYS;

    // Drop deprecated password hash mappings to enforce single plaintext password column for all tabs.
    unset($definitions['user_password_hash']);
    unset($definitions['admin_password_hash']);

    return $definitions;
  }

  public static function get_setup_key_config(): array {
    return self::get_effective_setup_keys();
  }

  public static function get_tab_column_map(string $tab): array {
    $tab = sanitize_key($tab);
    $columns = [];
    foreach (self::get_effective_setup_keys() as $config) {
      if (($config['tab'] ?? '') !== $tab) {
        continue;
      }
      $header = trim((string) ($config['header'] ?? ''));
      if ($header === '') {
        continue;
      }
      $columns[] = [
        'header' => $header,
        'order' => (int) ($config['order'] ?? 0),
      ];
    }
    if (empty($columns)) {
      return self::DEFAULT_TAB_HEADERS[$tab] ?? [];
    }
    usort($columns, function ($a, $b) {
      $orderSort = ($a['order'] <=> $b['order']);
      if ($orderSort !== 0) {
        return $orderSort;
      }
      return strcasecmp($a['header'], $b['header']);
    });
    return array_values(array_map(function ($column) {
      return $column['header'];
    }, $columns));
  }

  public static function get_tab_range(string $tab): string {
    $name = self::get_tab_name($tab);
    $headers = self::get_tab_column_map($tab);
    if (!empty($headers)) {
      $end = self::column_letter(count($headers));
    } else {
      $config = self::TAB_MAP[$tab] ?? null;
      $end = $config ? ($config['range_end'] ?? 'Z') : 'Z';
    }

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
    return self::get_tab_column_map($tab);
  }

  public static function get_effective_setup_keys(): array {
    $definitions = self::get_setup_key_definitions();
    $overrides = get_option(self::OPT_SETUP_KEYS, []);

    if (!is_array($overrides)) {
      $overrides = [];
    }

    foreach ($overrides as $key => $override) {
      if (!isset($definitions[$key]) || !is_array($override)) {
        continue;
      }

      $definitions[$key] = array_merge($definitions[$key], $override);
    }

    foreach (self::TAB_MAP as $tab => $_config) {
      $customOrder = self::get_tab_column_order_option($tab);
      if (empty($customOrder)) {
        continue;
      }

      $validHeaders = [];
      foreach ($definitions as $config) {
        if (($config['tab'] ?? '') !== $tab) {
          continue;
        }

        $header = trim((string) ($config['header'] ?? ''));
        if ($header !== '') {
          $validHeaders[strtolower($header)] = true;
        }
      }

      $customOrder = array_values(array_filter($customOrder, function ($header) use ($validHeaders) {
        return isset($validHeaders[strtolower($header)]);
      }));

      $matchedKeys = [];

      foreach ($customOrder as $index => $header) {
        foreach ($definitions as $configKey => $config) {
          if (($config['tab'] ?? '') !== $tab) {
            continue;
          }

          $configHeader = (string) ($config['header'] ?? '');
          if ($configHeader === '') {
            continue;
          }

          if (strcasecmp($configHeader, $header) === 0) {
            $definitions[$configKey]['order'] = $index + 1;
            $matchedKeys[$configKey] = true;
          }
        }
      }

      $remaining = [];
      foreach ($definitions as $configKey => $config) {
        if (($config['tab'] ?? '') !== $tab || isset($matchedKeys[$configKey])) {
          continue;
        }

        $remaining[$configKey] = $config;
      }

      if (!empty($remaining)) {
        uasort($remaining, function ($a, $b) {
          $orderSort = ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0));
          if ($orderSort !== 0) {
            return $orderSort;
          }

          return strcasecmp((string) ($a['header'] ?? ''), (string) ($b['header'] ?? ''));
        });

        $nextOrder = count($customOrder) + 1;
        foreach (array_keys($remaining) as $configKey) {
          $definitions[$configKey]['order'] = $nextOrder++;
        }
      }
    }

    return $definitions;
  }

  private static function get_tab_column_order_option(string $tab): array {
    $tab = sanitize_key($tab);
    $raw = get_option(self::OPT_TAB_COLUMN_ORDER_PREFIX . $tab, []);

    if (is_string($raw)) {
      $raw = array_map('trim', explode(',', $raw));
    } elseif (!is_array($raw)) {
      return [];
    }

    $raw = array_map('trim', $raw);
    return array_values(array_filter($raw, function ($value) {
      return $value !== '';
    }));
  }

  private static function column_letter(int $count): string {
    $letter = '';
    while ($count > 0) {
      $remainder = ($count - 1) % 26;
      $letter = chr(65 + $remainder) . $letter;
      $count = (int) (($count - 1) / 26);
    }
    return $letter;
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

  public static function save_settings(string $credentials_json, string $sheet_id, array $gids = [], array $setup_keys = []): array {
    $status = [
      'valid' => true,
      'message' => __('Kredensial valid.', 'hcis-ysq'),
      'last_checked' => time(),
    ];

    $credentials_json = trim($credentials_json);
    $sheet_id = trim($sheet_id);

    update_option(self::OPT_JSON_CREDS, $credentials_json, false);
    update_option(self::OPT_SHEET_ID, $sheet_id, false);

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

    if ($sheet_id === '') {
      $status['valid'] = false;
      $status['message'] = __('Sheet ID wajib diisi.', 'hcis-ysq');
    }

    self::purge_legacy_gid_options();

    if (!empty($setup_keys)) {
      update_option(self::OPT_SETUP_KEYS, $setup_keys, false);
    }

    if (!empty($gids)) {
      foreach (self::TAB_MAP as $tab => $_config) {
        $gid = isset($gids[$tab]) ? trim((string) $gids[$tab]) : '';
        if ($gid !== '') {
          update_option(self::OPT_TAB_GID_PREFIX . $tab, $gid, false);
        } else {
          delete_option(self::OPT_TAB_GID_PREFIX . $tab);
        }
      }
    }

    update_option(self::OPT_STATUS, $status, false);

    return $status;
  }

  private static function purge_legacy_gid_options(): void {
    foreach (self::LEGACY_GID_OPTIONS as $option) {
      delete_option($option);
    }
  }

  private static function purge_setup_key_overrides(): void {
    delete_option(self::OPT_SETUP_KEYS);

    foreach (array_keys(self::TAB_MAP) as $tab) {
      delete_option(self::OPT_TAB_COLUMN_ORDER_PREFIX . $tab);
      delete_option(self::OPT_TAB_GID_PREFIX . $tab);
    }
  }

  private static function get_tab_gid_option(string $tab): string {
    $tab = sanitize_key($tab);
    $raw = get_option(self::OPT_TAB_GID_PREFIX . $tab, '');

    if (is_array($raw)) {
      return '';
    }

    $gid = trim((string) $raw);
    return $gid !== '' ? $gid : '';
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
      'admins' => '\\HCISYSQ\\Repositories\\AdminRepository',
      'keluarga' => '\\HCISYSQ\\Repositories\\KeluargaRepository',
      'dokumen' => '\\HCISYSQ\\Repositories\\DokumenRepository',
      'pendidikan' => '\\HCISYSQ\\Repositories\\PendidikanRepository',
      'pelatihan' => '\\HCISYSQ\\Repositories\\PelatihanRepository',
    ];
    return $map[$tab] ?? null;
  }
}
