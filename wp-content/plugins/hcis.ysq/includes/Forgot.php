<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Forgot {

  public static function init() {
    add_action('wp_ajax_nopriv_hcisysq_forgot', [__CLASS__, 'handle']);
    add_action('wp_ajax_hcisysq_forgot', [__CLASS__, 'handle']);
  }

  public static function handle() {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'hcisysq_nonce')) {
      wp_send_json(['ok' => false, 'msg' => 'Invalid request']);
    }

    $identifier = sanitize_text_field($_POST['nip'] ?? '');
    if ($identifier === '') {
      wp_send_json(['ok' => false, 'msg' => 'Akun wajib diisi']);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'hcisysq_users';

    $employee = Auth::get_user_by_nip($identifier);

    if (!$employee) {
      $normalized = Auth::norm_phone($identifier);
      if ($normalized !== '') {
        $candidates = [$normalized];
        if (strpos($normalized, '62') === 0 && strlen($normalized) > 2) {
          $candidates[] = '0' . substr($normalized, 2);
        }
        $candidates = array_values(array_unique(array_filter($candidates)));

        if ($candidates) {
          $conditions = array_fill(0, count($candidates), "REPLACE(REPLACE(REPLACE(no_hp, '+', ''), '-', ''), ' ', '') = %s");
          $sql = 'SELECT * FROM ' . $table . ' WHERE ' . implode(' OR ', $conditions) . ' LIMIT 1';
          $employee = $wpdb->get_row($wpdb->prepare($sql, ...$candidates));
        }
      }
    }

    if (!$employee) {
      wp_send_json(['ok' => false, 'msg' => 'Data akun tidak ditemukan. Hubungi admin untuk bantuan.']);
    }

    $phone = Auth::norm_phone($employee->no_hp ?? '');
    if ($phone === '') {
      wp_send_json(['ok' => false, 'msg' => 'Nomor WhatsApp belum terdaftar. Hubungi admin HCM untuk memperbarui data Anda.']);
    }

    $waToken = Assets::get_wa_token();
    if (!$waToken) {
      wp_send_json(['ok' => false, 'msg' => 'Layanan WhatsApp belum dikonfigurasi. Hubungi admin.']);
    }

    $token = wp_generate_uuid4();
    $transientKey = self::build_transient_key($token);
    set_transient($transientKey, [
      'nip'       => $employee->nip,
      'issued_at' => time(),
    ], 10 * MINUTE_IN_SECONDS);

    $resetUrl = add_query_arg('token', rawurlencode($token), home_url('/' . trim(HCISYSQ_RESET_SLUG, '/') . '/'));

    $name = sanitize_text_field($employee->nama ?? '');
    if ($name === '') {
      $name = 'Pegawai SQ';
    }

    $message = "Halo {$name}, ini tautan untuk ganti password HCIS Anda. Tautan berlaku 10 menit:\n{$resetUrl}\n\nJika Anda tidak merasa meminta pergantian password, abaikan pesan ini.";

    $result = self::send_wa($phone, $message, $waToken);

    if ($result['ok']) {
      wp_send_json(['ok' => true, 'msg' => 'Tautan reset terkirim via WhatsApp ke nomor terdaftar.']);
    }

    wp_send_json(['ok' => false, 'msg' => $result['msg'] ?? 'Gagal mengirim tautan. Coba lagi.']);
  }

  private static function send_wa($tujuan, $message, $token) {
    $url = defined('HCISYSQ_SS_URL') ? HCISYSQ_SS_URL : 'https://starsender.online/api/sendText';

    $args = [
      'headers' => ['apikey' => $token],
      'body'    => ['tujuan' => $tujuan, 'message' => $message],
      'timeout' => 15,
    ];

    $res = wp_remote_post($url, $args);

    if (is_wp_error($res)) {
      return ['ok' => false, 'msg' => 'Gagal mengirim, coba lagi.'];
    }

    $code = wp_remote_retrieve_response_code($res);
    if ($code === 200) {
      return ['ok' => true];
    }

    return ['ok' => false, 'msg' => 'Gagal mengirim (HTTP ' . $code . ')'];
  }

  private static function build_transient_key($token) {
    return 'hcisysq_reset_' . sanitize_key(str_replace([' ', '{', '}', '/'], '', $token));
  }

  public static function get_token_payload($token) {
    $token = sanitize_text_field($token);
    if ($token === '') {
      return null;
    }

    $payload = get_transient(self::build_transient_key($token));
    return is_array($payload) ? $payload : null;
  }

  public static function handle_reset() {
    $token    = sanitize_text_field($_POST['token'] ?? '');
    $password = strval($_POST['password'] ?? '');
    $confirm  = strval($_POST['confirm'] ?? '');
    $nipInput = sanitize_text_field($_POST['nip'] ?? '');

    if ($token === '') {
      return ['ok' => false, 'msg' => 'Token tidak ditemukan atau sudah kadaluarsa.'];
    }

    $password = trim($password);
    $confirm  = trim($confirm);

    if ($password === '') {
      return ['ok' => false, 'msg' => 'Password baru wajib diisi.'];
    }

    if (strlen($password) < 6) {
      return ['ok' => false, 'msg' => 'Password minimal 6 karakter.'];
    }

    if ($confirm === '' || !hash_equals($password, $confirm)) {
      return ['ok' => false, 'msg' => 'Konfirmasi password tidak sama.'];
    }

    $transientKey = self::build_transient_key($token);
    $payload = get_transient($transientKey);
    if (!is_array($payload) || empty($payload['nip'])) {
      return ['ok' => false, 'msg' => 'Token tidak valid atau sudah kadaluarsa.'];
    }

    $nip = sanitize_text_field($payload['nip']);
    if ($nip === '') {
      delete_transient($transientKey);
      return ['ok' => false, 'msg' => 'Data token tidak lengkap.'];
    }

    if ($nipInput !== '' && !hash_equals($nip, $nipInput)) {
      return ['ok' => false, 'msg' => 'Data akun tidak sesuai dengan token.'];
    }

    $user = Auth::get_user_by_nip($nip);
    if (!$user) {
      delete_transient($transientKey);
      return ['ok' => false, 'msg' => 'Akun tidak ditemukan. Hubungi admin.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    global $wpdb;
    $updated = $wpdb->update(
      $wpdb->prefix . 'hcisysq_users',
      ['password' => $hash],
      ['nip' => $nip],
      ['%s'],
      ['%s']
    );

    delete_transient($transientKey);

    if ($updated === false) {
      return ['ok' => false, 'msg' => 'Gagal memperbarui password. Coba lagi.'];
    }

    return ['ok' => true, 'msg' => 'Password berhasil diperbarui. Silakan login kembali.'];
  }
}
