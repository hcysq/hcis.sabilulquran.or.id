<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Profiles {

  const OPT_SHEET_ID = 'hcisysq_profiles_sheet_id';
  const OPT_TAB_NAME = 'hcisysq_profiles_tab_name';
  const CACHE_KEY = 'hcisysq_profiles_sheet_rows_v1';

  private static $field_map = [
    'nip'           => 'Nomor',
    'nama'          => 'NAMA',
    'unit'          => 'UNIT',
    'jabatan'       => 'JABATAN',
    'tempat_lahir'  => 'TEMPAT LAHIR',
    'tanggal_lahir' => 'TANGGAL LAHIR (TTTT-BB-HH)',
    'alamat_ktp'    => 'ALAMAT KTP',
    'desa'          => 'DESA/KELURAHAN',
    'kecamatan'     => 'KECAMATAN',
    'kota'          => 'KOTA/KABUPATEN',
    'kode_pos'      => 'KODE POS',
    'email'         => 'EMAIL',
    'hp'            => 'NO HP',
    'tmt'           => 'TMT',
  ];

  /** Simpan / Ambil konfigurasi sheet */
  public static function set_sheet_config($sheet_id, $tab_name = 'Profiles'){
    update_option(self::OPT_SHEET_ID, sanitize_text_field($sheet_id), false);
    update_option(self::OPT_TAB_NAME, sanitize_text_field($tab_name), false);
    SheetCache::forget(self::CACHE_KEY);
  }

  public static function get_sheet_id(){
    $specific = get_option(self::OPT_SHEET_ID, '');
    if ($specific !== '') {
      return $specific;
    }
    return get_option('hcis_google_sheet_id', Users::get_sheet_id());
  }

  public static function get_tab_name(){
    $tab = get_option(self::OPT_TAB_NAME, 'Profiles');
    return $tab !== '' ? $tab : 'Profiles';
  }

  public static function all(){
    $sheet = self::get_sheet_snapshot();
    if (is_wp_error($sheet)) {
      return $sheet;
    }

    [$headers, $rows] = $sheet;
    $map = self::header_map($headers);
    $profiles = [];

    foreach ($rows as $index => $row) {
      $nip = self::col($row, $map, self::$field_map['nip']);
      $nama = self::col($row, $map, self::$field_map['nama']);
      if ($nip === '' || $nama === '') {
        continue;
      }

      $profiles[] = self::normalize_row($row, $map, $index);
    }

    return $profiles;
  }

  public static function find($nip){
    $nip = trim((string)$nip);
    if ($nip === '') {
      return null;
    }

    $sheet = self::get_sheet_snapshot();
    if (is_wp_error($sheet)) {
      return $sheet;
    }

    [$headers, $rows] = $sheet;
    $map = self::header_map($headers);

    foreach ($rows as $index => $row) {
      if (self::col($row, $map, self::$field_map['nip']) === $nip) {
        return self::normalize_row($row, $map, $index);
      }
    }

    return null;
  }

  public static function get_by_nip($nip){
    $profile = self::find($nip);
    if (is_wp_error($profile) || !$profile) {
      return null;
    }
    return (object)$profile;
  }

  public static function update_profile($nip, array $data){
    $nip = trim((string)$nip);
    if ($nip === '') {
      return new \WP_Error('hcis_profile_invalid', __('NIP wajib diisi.', 'hcis-ysq'));
    }

    $sheet = self::get_sheet_snapshot();
    if (is_wp_error($sheet)) {
      return $sheet;
    }

    [$headers, $rows] = $sheet;
    $map = self::header_map($headers);
    $targetIndex = null;

    foreach ($rows as $index => $row) {
      if (self::col($row, $map, self::$field_map['nip']) === $nip) {
        $targetIndex = $index;
        break;
      }
    }

    if ($targetIndex === null) {
      return new \WP_Error('hcis_profile_not_found', __('Data profil tidak ditemukan di Google Sheet.', 'hcis-ysq'));
    }

    $rowValues = $rows[$targetIndex];
    foreach (self::$field_map as $field => $label) {
      if (!array_key_exists($field, $data)) {
        continue;
      }
      $idx = $map[strtolower($label)] ?? null;
      if ($idx === null) {
        continue;
      }
      $rowValues[$idx] = trim((string)$data[$field]);
    }

    $range = sprintf('%s!A%d:AF%d', self::get_tab_name(), $targetIndex + 2, $targetIndex + 2);
    $api = self::get_api();
    if (is_wp_error($api)) {
      return $api;
    }

    $updated = $api->updateRows($range, [$rowValues]);
    if (!$updated) {
      $message = __('Gagal memperbarui profil di Google Sheet.', 'hcis-ysq');
      self::record_error($message);
      return new \WP_Error('hcis_profile_update_failed', $message);
    }

    SheetCache::forget(self::CACHE_KEY);
    self::record_success();

    return self::normalize_row($rowValues, $map, $targetIndex);
  }

  private static function normalize_row($row, $map, $index){
    return [
      'id'            => $index + 1,
      'row'           => $index + 2,
      'nip'           => self::col($row, $map, self::$field_map['nip']),
      'nama'          => self::col($row, $map, self::$field_map['nama']),
      'unit'          => self::col($row, $map, self::$field_map['unit']),
      'jabatan'       => self::col($row, $map, self::$field_map['jabatan']),
      'tempat_lahir'  => self::col($row, $map, self::$field_map['tempat_lahir']),
      'tanggal_lahir' => self::col($row, $map, self::$field_map['tanggal_lahir']),
      'alamat_ktp'    => self::col($row, $map, self::$field_map['alamat_ktp']),
      'desa'          => self::col($row, $map, self::$field_map['desa']),
      'kecamatan'     => self::col($row, $map, self::$field_map['kecamatan']),
      'kota'          => self::col($row, $map, self::$field_map['kota']),
      'kode_pos'      => self::col($row, $map, self::$field_map['kode_pos']),
      'email'         => self::col($row, $map, self::$field_map['email']),
      'hp'            => self::col($row, $map, self::$field_map['hp']),
      'tmt'           => self::col($row, $map, self::$field_map['tmt']),
      'updated_at'    => current_time('mysql'),
    ];
  }

  /** Map header → index (case-insensitive) */
  private static function header_map($headers){
    $map = [];
    foreach ($headers as $i => $h) {
      $key = strtolower(trim($h));
      if ($key === '') continue;
      $map[$key] = $i;
    }
    return $map;
  }

  /** Helper ambil kolom aman */
  private static function col($row, $map, $label){
    $idx = $map[strtolower($label)] ?? null;
    if ($idx === null) return '';
    return isset($row[$idx]) ? trim((string)$row[$idx]) : '';
  }

  private static function get_sheet_snapshot(){
    $cached = SheetCache::get(self::CACHE_KEY);
    if ($cached !== null) {
      return $cached;
    }

    $result = self::request_sheet_values();
    if (is_wp_error($result)) {
      return $result;
    }

    SheetCache::put(self::CACHE_KEY, $result, SheetCache::CACHE_TTL);
    return $result;
  }

  private static function request_sheet_values(){
    $sheet_id = self::get_sheet_id();
    $tab_name = self::get_tab_name();
    $creds_json = get_option('hcis_google_json_creds', '');

    if ($sheet_id === '' || $creds_json === '') {
      $message = __('Konfigurasi Google Sheet belum lengkap untuk Profiles.', 'hcis-ysq');
      self::record_error($message);
      return new \WP_Error('hcis_profile_missing_config', $message);
    }

    $credentials = json_decode($creds_json, true);
    if (!is_array($credentials)) {
      $message = __('Format kredensial Google Sheet tidak valid.', 'hcis-ysq');
      self::record_error($message);
      return new \WP_Error('hcis_profile_invalid_creds', $message);
    }

    $api = new GoogleSheetsAPI();
    if (!$api->authenticate($credentials)) {
      $message = __('Gagal mengautentikasi Google Sheets API untuk Profiles.', 'hcis-ysq');
      self::record_error($message);
      return new \WP_Error('hcis_profile_auth_failed', $message);
    }

    $api->setSpreadsheetId($sheet_id);
    $range = sprintf('%s!A:AF', $tab_name);
    $values = $api->getRows($range);

    if (empty($values) || !is_array($values)) {
      $message = __('Sheet Profiles tidak memiliki data.', 'hcis-ysq');
      self::record_error($message);
      return new \WP_Error('hcis_profile_empty', $message);
    }

    $headers = array_shift($values);
    if (empty($headers)) {
      $message = __('Header Sheet Profiles tidak ditemukan.', 'hcis-ysq');
      self::record_error($message);
      return new \WP_Error('hcis_profile_missing_headers', $message);
    }

    self::record_success();
    return [$headers, $values];
  }

  private static function get_api(){
    $sheet_id = self::get_sheet_id();
    $creds_json = get_option('hcis_google_json_creds', '');

    if ($sheet_id === '' || $creds_json === '') {
      $message = __('Konfigurasi Google Sheet belum lengkap.', 'hcis-ysq');
      self::record_error($message);
      return new \WP_Error('hcis_gs_profile_config', $message);
    }

    $credentials = json_decode($creds_json, true);
    if (!is_array($credentials)) {
      $message = __('Format kredensial Google Sheet tidak valid.', 'hcis-ysq');
      self::record_error($message);
      return new \WP_Error('hcis_gs_profile_creds', $message);
    }

    $api = new GoogleSheetsAPI();
    if (!$api->authenticate($credentials)) {
      $message = __('Gagal mengautentikasi Google Sheets API.', 'hcis-ysq');
      self::record_error($message);
      return new \WP_Error('hcis_gs_profile_auth', $message);
    }

    $api->setSpreadsheetId($sheet_id);
    return $api;
  }

  private static function record_error($message){
    update_option('hcis_gs_last_error', $message, false);
    hcisysq_log('Profiles::sheet_error - ' . $message, 'ERROR');
    if (class_exists('HCISYSQ\\GoogleSheetMetrics')) {
      GoogleSheetMetrics::recordFailure();
    }
  }

  private static function record_success(){
    update_option('hcis_gs_last_error', '', false);
    update_option('hcis_gs_last_sync', current_time('mysql'), false);
    if (class_exists('HCISYSQ\\GoogleSheetMetrics')) {
      GoogleSheetMetrics::recordSuccess();
    }
  }
}
