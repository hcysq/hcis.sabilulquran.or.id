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
  const OPT_SETUP_KEYS = 'hcis_gs_setup_keys';

  private static $setup_keys_migrated = false;

  const DEFAULT_SETUP_KEYS = [
    'employee_id_number' => [
      'label' => 'Employee ID Number (NIP)',
      'tab' => 'profiles',
      'header' => 'NIP',
      'order' => 1,
      'description' => 'Nomor induk pegawai pada tab Profiles.',
      'gid' => '',
      'requires_gid' => true,
    ],
    'full_name' => [
      'label' => 'Full Name',
      'tab' => 'profiles',
      'header' => 'Nama',
      'order' => 2,
      'description' => 'Nama lengkap pegawai.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'unit_name' => [
      'label' => 'Unit',
      'tab' => 'profiles',
      'header' => 'Unit',
      'order' => 3,
      'description' => 'Nama unit kerja pegawai.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'position_name' => [
      'label' => 'Jabatan',
      'tab' => 'profiles',
      'header' => 'Jabatan',
      'order' => 4,
      'description' => 'Jabatan atau posisi pegawai.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'birth_place' => [
      'label' => 'Tempat Lahir',
      'tab' => 'profiles',
      'header' => 'Tempat Lahir',
      'order' => 5,
      'description' => 'Tempat lahir pegawai.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'birth_date' => [
      'label' => 'Tanggal Lahir',
      'tab' => 'profiles',
      'header' => 'Tanggal Lahir',
      'order' => 6,
      'description' => 'Tanggal lahir pegawai.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'address' => [
      'label' => 'Alamat KTP',
      'tab' => 'profiles',
      'header' => 'Alamat KTP',
      'order' => 7,
      'description' => 'Alamat sesuai KTP.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'village' => [
      'label' => 'Desa/Kelurahan',
      'tab' => 'profiles',
      'header' => 'Desa/Kelurahan',
      'order' => 8,
      'description' => 'Nama desa atau kelurahan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'district' => [
      'label' => 'Kecamatan',
      'tab' => 'profiles',
      'header' => 'Kecamatan',
      'order' => 9,
      'description' => 'Nama kecamatan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'city' => [
      'label' => 'Kota/Kabupaten',
      'tab' => 'profiles',
      'header' => 'Kota/Kabupaten',
      'order' => 10,
      'description' => 'Nama kota atau kabupaten.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'postal_code' => [
      'label' => 'Kode Pos',
      'tab' => 'profiles',
      'header' => 'Kode Pos',
      'order' => 11,
      'description' => 'Kode pos alamat pegawai.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'email' => [
      'label' => 'Email',
      'tab' => 'profiles',
      'header' => 'Email',
      'order' => 12,
      'description' => 'Alamat email pegawai.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'phone_number' => [
      'label' => 'No HP',
      'tab' => 'profiles',
      'header' => 'No HP',
      'order' => 13,
      'description' => 'Nomor ponsel pegawai.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'join_date' => [
      'label' => 'TMT',
      'tab' => 'profiles',
      'header' => 'TMT',
      'order' => 14,
      'description' => 'Tanggal mulai tugas (TMT).',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_employee_id' => [
      'label' => 'Users Tab NIP',
      'tab' => 'users',
      'header' => 'NIP',
      'order' => 1,
      'description' => 'NIP pada tab Users.',
      'gid' => '',
      'requires_gid' => true,
    ],
    'user_display_name' => [
      'label' => 'Users Tab Nama',
      'tab' => 'users',
      'header' => 'Nama',
      'order' => 2,
      'description' => 'Nama pengguna pada tab Users.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_password_hash' => [
      'label' => 'Password Hash',
      'tab' => 'users',
      'header' => 'Password Hash',
      'order' => 3,
      'description' => 'Hash kata sandi yang tersimpan di Sheet.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_phone' => [
      'label' => 'No HP Users',
      'tab' => 'users',
      'header' => 'No HP',
      'order' => 4,
      'description' => 'Nomor telepon pada tab Users.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_email' => [
      'label' => 'Email Users',
      'tab' => 'users',
      'header' => 'Email',
      'order' => 5,
      'description' => 'Alamat email pada tab Users.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'user_nik' => [
      'label' => 'NIK Users',
      'tab' => 'users',
      'header' => 'NIK',
      'order' => 6,
      'description' => 'NIK pada tab Users.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'payroll_nip' => [
      'label' => 'Payroll NIP',
      'tab' => 'payroll',
      'header' => 'NIP',
      'order' => 1,
      'description' => 'NIP untuk baris payroll.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'payroll_period' => [
      'label' => 'Periode Payroll',
      'tab' => 'payroll',
      'header' => 'Periode',
      'order' => 2,
      'description' => 'Periode gaji.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'payroll_basic_salary' => [
      'label' => 'Gaji Pokok',
      'tab' => 'payroll',
      'header' => 'Gaji Pokok',
      'order' => 3,
      'description' => 'Nilai gaji pokok.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'payroll_allowance' => [
      'label' => 'Tunjangan',
      'tab' => 'payroll',
      'header' => 'Tunjangan',
      'order' => 4,
      'description' => 'Nilai tunjangan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'payroll_deduction' => [
      'label' => 'Potongan',
      'tab' => 'payroll',
      'header' => 'Potongan',
      'order' => 5,
      'description' => 'Total potongan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'payroll_take_home_pay' => [
      'label' => 'Take Home Pay',
      'tab' => 'payroll',
      'header' => 'Take Home Pay',
      'order' => 6,
      'description' => 'Nilai take home pay.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'payroll_status' => [
      'label' => 'Status Payroll',
      'tab' => 'payroll',
      'header' => 'Status',
      'order' => 7,
      'description' => 'Status pembayaran.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'family_nip' => [
      'label' => 'Keluarga NIP',
      'tab' => 'keluarga',
      'header' => 'NIP',
      'order' => 1,
      'description' => 'NIP pemilik data keluarga.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'family_name' => [
      'label' => 'Nama Anggota',
      'tab' => 'keluarga',
      'header' => 'Nama Anggota',
      'order' => 2,
      'description' => 'Nama anggota keluarga.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'family_relationship' => [
      'label' => 'Hubungan',
      'tab' => 'keluarga',
      'header' => 'Hubungan',
      'order' => 3,
      'description' => 'Hubungan keluarga.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'family_birth_date' => [
      'label' => 'Tanggal Lahir Anggota',
      'tab' => 'keluarga',
      'header' => 'Tanggal Lahir',
      'order' => 4,
      'description' => 'Tanggal lahir anggota.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'family_occupation' => [
      'label' => 'Pekerjaan Anggota',
      'tab' => 'keluarga',
      'header' => 'Pekerjaan',
      'order' => 5,
      'description' => 'Pekerjaan anggota keluarga.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'family_education' => [
      'label' => 'Pendidikan Anggota',
      'tab' => 'keluarga',
      'header' => 'Pendidikan',
      'order' => 6,
      'description' => 'Pendidikan terakhir anggota.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'document_nip' => [
      'label' => 'Dokumen NIP',
      'tab' => 'dokumen',
      'header' => 'NIP',
      'order' => 1,
      'description' => 'NIP pemilik dokumen.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'document_category' => [
      'label' => 'Kategori Dokumen',
      'tab' => 'dokumen',
      'header' => 'Kategori',
      'order' => 2,
      'description' => 'Kategori dokumen.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'document_title' => [
      'label' => 'Judul Dokumen',
      'tab' => 'dokumen',
      'header' => 'Judul Dokumen',
      'order' => 3,
      'description' => 'Judul dokumen yang diunggah.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'document_number' => [
      'label' => 'Nomor Dokumen',
      'tab' => 'dokumen',
      'header' => 'Nomor Dokumen',
      'order' => 4,
      'description' => 'Nomor dokumen resmi.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'document_issue_date' => [
      'label' => 'Tanggal Terbit',
      'tab' => 'dokumen',
      'header' => 'Tanggal Terbit',
      'order' => 5,
      'description' => 'Tanggal terbit dokumen.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'document_expiry_date' => [
      'label' => 'Tanggal Kedaluwarsa',
      'tab' => 'dokumen',
      'header' => 'Tanggal Kedaluwarsa',
      'order' => 6,
      'description' => 'Tanggal kedaluwarsa dokumen.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'document_link' => [
      'label' => 'Tautan Dokumen',
      'tab' => 'dokumen',
      'header' => 'Tautan',
      'order' => 7,
      'description' => 'URL dokumen.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'education_nip' => [
      'label' => 'Pendidikan NIP',
      'tab' => 'pendidikan',
      'header' => 'NIP',
      'order' => 1,
      'description' => 'NIP untuk riwayat pendidikan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'education_level' => [
      'label' => 'Jenjang Pendidikan',
      'tab' => 'pendidikan',
      'header' => 'Jenjang',
      'order' => 2,
      'description' => 'Jenjang pendidikan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'education_institution' => [
      'label' => 'Institusi',
      'tab' => 'pendidikan',
      'header' => 'Institusi',
      'order' => 3,
      'description' => 'Nama institusi pendidikan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'education_major' => [
      'label' => 'Jurusan',
      'tab' => 'pendidikan',
      'header' => 'Jurusan',
      'order' => 4,
      'description' => 'Jurusan atau program studi.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'education_start_year' => [
      'label' => 'Tahun Masuk',
      'tab' => 'pendidikan',
      'header' => 'Tahun Masuk',
      'order' => 5,
      'description' => 'Tahun masuk pendidikan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'education_end_year' => [
      'label' => 'Tahun Lulus',
      'tab' => 'pendidikan',
      'header' => 'Tahun Lulus',
      'order' => 6,
      'description' => 'Tahun lulus pendidikan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'education_score' => [
      'label' => 'Nilai/IPK',
      'tab' => 'pendidikan',
      'header' => 'Nilai/IPK',
      'order' => 7,
      'description' => 'Nilai akhir atau IPK.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'training_nip' => [
      'label' => 'Pelatihan NIP',
      'tab' => 'pelatihan',
      'header' => 'NIP',
      'order' => 1,
      'description' => 'NIP untuk riwayat pelatihan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'training_name' => [
      'label' => 'Nama Pelatihan',
      'tab' => 'pelatihan',
      'header' => 'Nama Pelatihan',
      'order' => 2,
      'description' => 'Nama kursus atau pelatihan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'training_organizer' => [
      'label' => 'Penyelenggara',
      'tab' => 'pelatihan',
      'header' => 'Penyelenggara',
      'order' => 3,
      'description' => 'Penyelenggara pelatihan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'training_start_date' => [
      'label' => 'Tanggal Mulai',
      'tab' => 'pelatihan',
      'header' => 'Tanggal Mulai',
      'order' => 4,
      'description' => 'Tanggal mulai pelatihan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'training_end_date' => [
      'label' => 'Tanggal Selesai',
      'tab' => 'pelatihan',
      'header' => 'Tanggal Selesai',
      'order' => 5,
      'description' => 'Tanggal selesai pelatihan.',
      'gid' => '',
      'requires_gid' => false,
    ],
    'training_status' => [
      'label' => 'Status Pelatihan',
      'tab' => 'pelatihan',
      'header' => 'Status',
      'order' => 6,
      'description' => 'Status pelatihan (disetujui/selesai).',
      'gid' => '',
      'requires_gid' => false,
    ],
    'training_certificate' => [
      'label' => 'Link Sertifikat',
      'tab' => 'pelatihan',
      'header' => 'Link Sertifikat',
      'order' => 7,
      'description' => 'Tautan sertifikat pelatihan.',
      'gid' => '',
      'requires_gid' => false,
    ],
  ];

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
    $tab = sanitize_key($tab);
    $from_setup = self::get_tab_gid_from_setup($tab);
    if ($from_setup !== '') {
      return $from_setup;
    }
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
    return self::DEFAULT_SETUP_KEYS;
  }

  public static function get_setup_key_config(): array {
    self::maybe_migrate_setup_keys();
    $stored = get_option(self::OPT_SETUP_KEYS, []);
    if (is_string($stored)) {
      $decoded = json_decode($stored, true);
    } else {
      $decoded = $stored;
    }
    if (!is_array($decoded)) {
      $decoded = [];
    }
    $config = [];
    $definitions = self::get_setup_key_definitions();
    foreach ($definitions as $key => $definition) {
      $saved = isset($decoded[$key]) && is_array($decoded[$key]) ? $decoded[$key] : [];
      $tab = isset($saved['tab']) ? sanitize_key($saved['tab']) : $definition['tab'];
      if (!isset(self::TAB_MAP[$tab])) {
        $tab = $definition['tab'];
      }
      $gid = isset($saved['gid']) ? trim((string) $saved['gid']) : ($definition['gid'] ?? '');
      $header = isset($saved['header']) ? trim((string) $saved['header']) : $definition['header'];
      $order = isset($saved['order']) ? (int) $saved['order'] : (int) $definition['order'];
      $config[$key] = array_merge($definition, [
        'tab' => $tab,
        'gid' => $gid,
        'header' => ($header !== '') ? $header : $definition['header'],
        'order' => $order > 0 ? $order : (int) $definition['order'],
      ]);
    }
    return $config;
  }

  public static function get_tab_column_map(string $tab): array {
    $tab = sanitize_key($tab);
    $columns = [];
    foreach (self::get_setup_key_config() as $config) {
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
      return [];
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
    return self::get_tab_column_map($tab);
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
      $tab = isset($saved['tab']) ? sanitize_key($saved['tab']) : ($definition['tab'] ?? '');
      if (!isset(self::TAB_MAP[$tab])) {
        $tab = $definition['tab'];
      }

      $header = isset($saved['header']) ? sanitize_text_field($saved['header']) : ($definition['header'] ?? '');
      if ($header === '') {
        $header = $definition['header'];
      }

      $order = isset($saved['order']) ? absint($saved['order']) : (int) ($definition['order'] ?? 0);
      if ($order === 0) {
        $order = (int) ($definition['order'] ?? 0);
      }

      $effective[$key] = array_merge($definition, [
        'tab' => $tab,
        'header' => $header,
        'order' => $order,
      ]);
    }

    return $effective;
  }

  protected static function normalize_setup_keys(array $setup_keys): array {
    $definitions = self::get_setup_key_definitions();
    $normalized = [];

    foreach ($definitions as $key => $definition) {
      $raw = $setup_keys[$key] ?? [];
      $tab = isset($raw['tab']) ? sanitize_key($raw['tab']) : ($definition['tab'] ?? '');
      if (!isset(self::TAB_MAP[$tab])) {
        $tab = $definition['tab'];
      }
      $header = isset($raw['header']) ? sanitize_text_field($raw['header']) : ($definition['header'] ?? '');
      if ($header === '') {
        $header = $definition['header'];
      }
      $order = isset($raw['order']) ? absint($raw['order']) : 0;
      if ($order === 0) {
        $order = (int) ($definition['order'] ?? 0);
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

    $normalized_setup = self::normalize_setup_keys($setup_keys);
    $tab_gid_map = [];

    foreach ($normalized_setup as $key => &$entry) {
      $gid_value = trim((string) ($entry['gid'] ?? ''));
      if ($gid_value !== '' && !preg_match('/^-?\d+$/', $gid_value)) {
        $status['valid'] = false;
        $status['message'] = sprintf(__('GID %s tidak valid.', 'hcis-ysq'), $entry['label'] ?? $key);
        $gid_value = '';
      }
      $entry['gid'] = $gid_value;
      if ($gid_value !== '') {
        $tab_gid_map[$entry['tab']] = $gid_value;
      }
    }
    unset($entry);

    foreach ($normalized_setup as &$entry) {
      if ($entry['gid'] === '' && isset($tab_gid_map[$entry['tab']])) {
        $entry['gid'] = $tab_gid_map[$entry['tab']];
      }
    }
    unset($entry);

    if (!empty($gids)) {
      foreach ($gids as $slug => $value) {
        $tab_slug = sanitize_key($slug);
        if (!isset(self::TAB_MAP[$tab_slug])) {
          continue;
        }
        $value = trim((string) $value);
        if ($value === '') {
          continue;
        }
        if (!preg_match('/^-?\d+$/', $value)) {
          $status['valid'] = false;
          $status['message'] = sprintf(__('GID %s tidak valid.', 'hcis-ysq'), self::get_tab_name($tab_slug));
          continue;
        }
        $tab_gid_map[$tab_slug] = $value;
        foreach ($normalized_setup as &$entry) {
          if ($entry['tab'] === $tab_slug && $entry['gid'] === '') {
            $entry['gid'] = $value;
          }
        }
        unset($entry);
      }
    }

    foreach ($normalized_setup as $key => $entry) {
      if (!empty($entry['requires_gid']) && ($entry['gid'] ?? '') === '') {
        $status['valid'] = false;
        $status['message'] = sprintf(__('GID wajib untuk %s.', 'hcis-ysq'), $entry['label'] ?? $key);
        break;
      }
    }

    self::persist_setup_keys($normalized_setup);
    self::sync_tab_gid_options($tab_gid_map);

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

  private static function normalize_setup_keys(array $payload): array {
    $current = self::get_setup_key_config();
    $definitions = self::get_setup_key_definitions();
    foreach ($definitions as $key => $definition) {
      $incoming = isset($payload[$key]) && is_array($payload[$key]) ? $payload[$key] : [];
      $tab = isset($incoming['tab']) ? sanitize_key($incoming['tab']) : ($current[$key]['tab'] ?? $definition['tab']);
      if (!isset(self::TAB_MAP[$tab])) {
        $tab = $definition['tab'];
      }
      $header = isset($incoming['header']) ? trim((string) $incoming['header']) : ($current[$key]['header'] ?? $definition['header']);
      $order = isset($incoming['order']) ? (int) $incoming['order'] : (int) ($current[$key]['order'] ?? $definition['order']);
      $gid = isset($incoming['gid']) ? trim((string) $incoming['gid']) : ($current[$key]['gid'] ?? $definition['gid'] ?? '');
      $current[$key] = array_merge($definition, [
        'tab' => $tab,
        'gid' => $gid,
        'header' => $header !== '' ? $header : $definition['header'],
        'order' => $order > 0 ? $order : (int) $definition['order'],
      ]);
    }
    return $current;
  }

  private static function persist_setup_keys(array $config): void {
    update_option(self::OPT_SETUP_KEYS, wp_json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), false);
  }

  private static function sync_tab_gid_options(array $tab_gid_map): void {
    foreach (self::TAB_MAP as $slug => $config) {
      $gid_value = isset($tab_gid_map[$slug]) ? trim((string) $tab_gid_map[$slug]) : '';
      update_option($config['gid_option'], $gid_value, false);
    }
  }

  private static function get_tab_gid_from_setup(string $tab): string {
    $config = self::get_setup_key_config();
    foreach ($config as $entry) {
      if (($entry['tab'] ?? '') === $tab && !empty($entry['gid'])) {
        return trim((string) $entry['gid']);
      }
    }
    return '';
  }

  private static function get_legacy_column_order(string $tab): array {
    $option_name = self::OPT_TAB_COLUMN_ORDER_PREFIX . $tab;
    $order_string = get_option($option_name, '');
    if (empty($order_string)) {
      return [];
    }
    return array_map('trim', explode(',', $order_string));
  }

  private static function maybe_migrate_setup_keys(): void {
    if (self::$setup_keys_migrated) {
      return;
    }
    self::$setup_keys_migrated = true;
    $existing = get_option(self::OPT_SETUP_KEYS, false);
    if ($existing !== false) {
      return;
    }

    $config = self::get_setup_key_definitions();
    $gid_map = [];
    foreach (self::TAB_MAP as $tab => $tab_config) {
      $gid_map[$tab] = trim((string) get_option($tab_config['gid_option'], ''));
    }
    foreach ($config as $key => &$entry) {
      $tab = $entry['tab'];
      $entry['gid'] = $gid_map[$tab] ?? '';
    }
    unset($entry);

    foreach (array_keys(self::TAB_MAP) as $tab) {
      $legacy_order = self::get_legacy_column_order($tab);
      if (empty($legacy_order)) {
        continue;
      }
      $position = 1;
      foreach ($legacy_order as $header) {
        foreach ($config as &$entry) {
          if ($entry['tab'] === $tab && strcasecmp($entry['header'], $header) === 0) {
            $entry['order'] = $position++;
          }
        }
        unset($entry);
      }
    }

    self::persist_setup_keys($config);
  }
}
