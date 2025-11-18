<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Users {

  const OPT_USERS_SHEET_ID = 'hcisysq_users_sheet_id';
  const OPT_USERS_TAB_NAME = 'hcisysq_users_tab_name';
  const CACHE_KEY = 'hcisysq_users_sheet_rows_v1';

  /** Simpan / Ambil config Google Sheet untuk users */
  public static function set_sheet_config($sheet_id, $tab_name = 'User'){
    update_option(self::OPT_USERS_SHEET_ID, sanitize_text_field($sheet_id), false);
    update_option(self::OPT_USERS_TAB_NAME, sanitize_text_field($tab_name), false);
    SheetCache::forget(self::CACHE_KEY);
  }

  public static function get_sheet_id(){
    $specific = get_option(self::OPT_USERS_SHEET_ID, '');
    if ($specific !== '') {
      return $specific;
    }
    return get_option('hcis_google_sheet_id', '');
  }

  public static function get_tab_name(){
    $tab = get_option(self::OPT_USERS_TAB_NAME, 'User');
    return $tab !== '' ? $tab : 'User';
  }

  /** Ambil seluruh data user dari Google Sheet */
  public static function get_all(){
    $sheet = self::get_sheet_snapshot();
    if (is_wp_error($sheet)) {
      hcisysq_log('Users::get_all - ' . $sheet->get_error_message(), 'ERROR');
      return [];
    }

    [$headers, $rows] = $sheet;
    $map = self::header_map($headers);
    $users = [];

    foreach ($rows as $index => $row) {
      $nip = self::col($row, $map, 'nip');
      $nama = self::col($row, $map, 'nama');
      if ($nip === '' || $nama === '') {
        continue;
      }

      $users[] = [
        'id'        => $index + 1,
        'row'       => $index + 2, // +1 header, +1 for 1-indexed sheet row
        'nip'       => $nip,
        'nama'      => $nama,
        'jabatan'   => self::col($row, $map, 'jabatan'),
        'unit'      => self::col($row, $map, 'unit'),
        'no_hp'     => Auth::norm_phone(self::col($row, $map, 'no hp')),
        'password'  => self::col($row, $map, 'password'),
        'raw_row'   => $row,
      ];
    }

    return $users;
  }

  public static function get_by_nip($nip){
    $nip = trim((string)$nip);
    if ($nip === '') {
      return null;
    }

    $users = self::get_all();
    foreach ($users as $user) {
      if ($user['nip'] === $nip) {
        return $user;
      }
    }

    return null;
  }

  public static function flush_cache(){
    SheetCache::forget(self::CACHE_KEY);
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
      $message = __('Konfigurasi Google Sheet belum lengkap untuk Users.', 'hcis-ysq');
      self::record_error($message);
      return new \WP_Error('hcis_gs_missing_config', $message);
    }

    $credentials = json_decode($creds_json, true);
    if (!is_array($credentials)) {
      $message = __('Format kredensial Google Sheet tidak valid.', 'hcis-ysq');
      self::record_error($message);
      return new \WP_Error('hcis_gs_invalid_credentials', $message);
    }

    $api = new GoogleSheetsAPI();
    if (!$api->authenticate($credentials)) {
      $message = __('Gagal mengautentikasi Google Sheets API untuk Users.', 'hcis-ysq');
      self::record_error($message);
      return new \WP_Error('hcis_gs_auth_failed', $message);
    }

    $api->setSpreadsheetId($sheet_id);
    $range = sprintf('%s!A:F', $tab_name);
    $values = $api->getRows($range);

    if (empty($values) || !is_array($values)) {
      $message = __('Sheet Users tidak memiliki data.', 'hcis-ysq');
      self::record_error($message);
      return new \WP_Error('hcis_gs_empty_users', $message);
    }

    $headers = array_shift($values);
    if (empty($headers)) {
      $message = __('Header Sheet Users tidak ditemukan.', 'hcis-ysq');
      self::record_error($message);
      return new \WP_Error('hcis_gs_missing_headers', $message);
    }

    self::record_success();
    return [$headers, $values];
  }

  private static function record_error($message){
    update_option('hcis_gs_last_error', $message, false);
    hcisysq_log('Users::sheet_error - ' . $message, 'ERROR');
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
