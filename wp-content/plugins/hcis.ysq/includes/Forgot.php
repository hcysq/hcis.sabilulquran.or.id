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

    $nip = sanitize_text_field($_POST['nip'] ?? '');
    if (!$nip) {
      wp_send_json(['ok' => false, 'msg' => 'Akun wajib diisi']);
    }

    global $wpdb;
    $t = $wpdb->prefix . 'hcisysq_users';
    $emp = $wpdb->get_row($wpdb->prepare("SELECT nama FROM $t WHERE nip=%s", $nip));
    $nama = $emp ? $emp->nama : '(NIP tidak terdaftar)';

    $admin_wa = Assets::get_admin_wa();
    $wa_token = Assets::get_wa_token();

    if (!$admin_wa || !$wa_token) {
      wp_send_json(['ok' => false, 'msg' => 'Konfigurasi WhatsApp belum diatur. Hubungi admin.']);
    }

    $message = "Permintaan reset pasword HCIS.YSQ\nAkun (NIP): {$nip}\nNama: {$nama}";

    $result = self::send_wa($admin_wa, $message, $wa_token);

    if ($result['ok']) {
      wp_send_json(['ok' => true, 'msg' => 'Permintaan terkirim ke Admin HCM via WhatsApp.']);
    } else {
      wp_send_json(['ok' => false, 'msg' => $result['msg']]);
    }
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
}
