<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Api {

  private static function check_nonce(){
    $nonce = $_POST['_wpnonce'] ?? $_POST['_nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'hcisysq_nonce')) {
      wp_send_json(['ok'=>false,'msg'=>'Invalid nonce']);
    }
  }


  /** POST: nip, pw */
  public static function login(){
    self::check_nonce();

    $account = sanitize_text_field($_POST['nip'] ?? '');
    $pw  = sanitize_text_field($_POST['pw']  ?? '');

    hcisysq_log("Login attempt for account={$account}");

    $res = Auth::login($account, $pw);

    if ($res['ok']) {
      $res['redirect'] = home_url('/' . HCISYSQ_DASHBOARD_SLUG . '/');
      hcisysq_log("Login success: account={$account}, redirect={$res['redirect']}");
    } else {
      hcisysq_log("Login failed: account={$account}, msg={$res['msg']}");
    }

    wp_send_json($res);
  }

  private static function require_admin(){
    $admin = Auth::current_admin();
    if (!$admin) {
      wp_send_json(['ok' => false, 'msg' => 'Unauthorized'], 403);
    }
    return $admin;
  }

  private static function format_admin_announcements(){
    $items = Announcements::all();
    return array_map(function($item){
      return [
        'id'          => $item['id'] ?? '',
        'title'       => $item['title'] ?? '',
        'body'        => $item['body'] ?? '',
        'link_label'  => $item['link_label'] ?? '',
        'link_url'    => $item['link_url'] ?? '',
        'status'      => $item['status'] ?? 'published',
        'created_at'  => $item['created_at'] ?? '',
        'updated_at'  => $item['updated_at'] ?? '',
        'archived_at' => $item['archived_at'] ?? null,
      ];
    }, $items);
  }

  private static function normalize_link(array $data){
    $type = sanitize_text_field($data['link_type'] ?? '');
    $label = sanitize_text_field($data['link_label'] ?? '');
    $url = esc_url_raw($data['link_url'] ?? '');

    if ($type === 'training') {
      $url = '__TRAINING_FORM__';
      if ($label === '') {
        $label = 'Isi form pelatihan terbaru';
      }
    } elseif ($type !== 'external') {
      $url = '';
      $label = $label;
    }

    return [$label, $url];
  }

  public static function admin_create_announcement(){
    self::check_nonce();
    self::require_admin();

    $title = sanitize_text_field($_POST['title'] ?? '');
    $body = sanitize_textarea_field($_POST['body'] ?? '');
    $linkType = sanitize_text_field($_POST['link_type'] ?? '');
    if ($linkType === 'external' && trim((string)($_POST['link_url'] ?? '')) === '') {
      wp_send_json(['ok' => false, 'msg' => 'Isi URL tautan terlebih dahulu.']);
    }
    [$label, $url] = self::normalize_link($_POST);

    if ($title === '' || $body === '') {
      wp_send_json(['ok' => false, 'msg' => 'Judul dan isi pengumuman wajib diisi.']);
    }

    $created = Announcements::create([
      'title'      => $title,
      'body'       => $body,
      'link_label' => $label,
      'link_url'   => $url,
      'status'     => 'published',
    ]);

    if (!$created) {
      wp_send_json(['ok' => false, 'msg' => 'Gagal membuat pengumuman.']);
    }

    wp_send_json([
      'ok' => true,
      'msg' => 'Pengumuman baru berhasil dibuat.',
      'announcements' => self::format_admin_announcements(),
    ]);
  }

  public static function admin_update_announcement(){
    self::check_nonce();
    self::require_admin();

    $id = sanitize_text_field($_POST['id'] ?? '');
    if ($id === '') {
      wp_send_json(['ok' => false, 'msg' => 'ID pengumuman tidak valid.']);
    }

    $title = sanitize_text_field($_POST['title'] ?? '');
    $body = sanitize_textarea_field($_POST['body'] ?? '');
    $linkType = sanitize_text_field($_POST['link_type'] ?? '');
    if ($linkType === 'external' && trim((string)($_POST['link_url'] ?? '')) === '') {
      wp_send_json(['ok' => false, 'msg' => 'Isi URL tautan terlebih dahulu.']);
    }
    [$label, $url] = self::normalize_link($_POST);

    $status = sanitize_text_field($_POST['status'] ?? '');
    $payload = [
      'title'      => $title,
      'body'       => $body,
      'link_label' => $label,
      'link_url'   => $url,
    ];
    if ($status !== '') {
      $payload['status'] = $status;
    }

    $updated = Announcements::update($id, $payload);
    if (!$updated) {
      wp_send_json(['ok' => false, 'msg' => 'Pengumuman tidak ditemukan.']);
    }

    wp_send_json([
      'ok' => true,
      'msg' => 'Pengumuman berhasil diperbarui.',
      'announcements' => self::format_admin_announcements(),
    ]);
  }

  public static function admin_delete_announcement(){
    self::check_nonce();
    self::require_admin();

    $id = sanitize_text_field($_POST['id'] ?? '');
    if ($id === '' || !Announcements::delete($id)) {
      wp_send_json(['ok' => false, 'msg' => 'Gagal menghapus pengumuman.']);
    }

    wp_send_json([
      'ok' => true,
      'msg' => 'Pengumuman dihapus.',
      'announcements' => self::format_admin_announcements(),
    ]);
  }

  public static function admin_set_announcement_status(){
    self::check_nonce();
    self::require_admin();

    $id = sanitize_text_field($_POST['id'] ?? '');
    $status = sanitize_text_field($_POST['status'] ?? '');

    $updated = Announcements::set_status($id, $status);
    if (!$updated) {
      wp_send_json(['ok' => false, 'msg' => 'Gagal memperbarui status pengumuman.']);
    }

    wp_send_json([
      'ok' => true,
      'msg' => 'Status pengumuman diperbarui.',
      'announcements' => self::format_admin_announcements(),
    ]);
  }

  public static function admin_save_settings(){
    self::check_nonce();
    self::require_admin();

    $username = sanitize_user($_POST['username'] ?? '', true);
    $display = sanitize_text_field($_POST['display_name'] ?? '');
    $password = trim(strval($_POST['password'] ?? ''));
    $payload = [
      'username'     => $username,
      'display_name' => $display,
    ];

    if ($password !== '') {
      $payload['password'] = $password;
    }

    $updated = Auth::save_admin_settings($payload);
    Auth::update_current_session([
      'type'     => 'admin',
      'username' => $updated['username'],
    ]);

    wp_send_json([
      'ok' => true,
      'msg' => 'Pengaturan administrator diperbarui.',
      'settings' => Auth::get_admin_public_settings(),
    ]);
  }

  public static function logout(){
    Auth::logout();
    wp_send_json(['ok'=>true]);
  }

  /** POST multipart: nama_pelatihan, tahun, pembiayaan, kategori, sertifikat (file) */
  public static function submit_training(){
    self::check_nonce();

    $me = Auth::current_user();
    if (!$me) wp_send_json(['ok'=>false,'msg'=>'Unauthorized']);

    // validasi sederhana
    $nama       = sanitize_text_field($_POST['nama_pelatihan'] ?? '');
    $tahun      = intval($_POST['tahun'] ?? 0);
    $pembiayaan = sanitize_text_field($_POST['pembiayaan'] ?? '');
    $kategori   = sanitize_text_field($_POST['kategori'] ?? '');

    if (!$nama || !$tahun || !$pembiayaan || !$kategori) {
      wp_send_json(['ok'=>false,'msg'=>'Lengkapi semua field.']);
    }

    // (opsional) upload file sertifikat
    $file_url = null;
    if (!empty($_FILES['sertifikat']['name'])) {
      require_once ABSPATH.'wp-admin/includes/file.php';
      $allowed = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png'
      ];
      $overrides = [
        'test_form' => false,
        'mimes'     => $allowed,
        'unique_filename_callback' => function($dir,$name,$ext){
          return 'sertif-'.wp_generate_password(8,false).$ext;
        }
      ];
      $upload = wp_handle_upload($_FILES['sertifikat'], $overrides);
      if (!empty($upload['error'])) {
        wp_send_json(['ok'=>false,'msg'=>'Upload gagal: '.$upload['error']]);
      }
      $file_url = $upload['url'];
    }

    global $wpdb;
    $t = $wpdb->prefix.'hcisysq_trainings';
    $wpdb->insert($t, [
      'user_id'        => intval($me->id),
      'nama_pelatihan' => $nama,
      'tahun'          => $tahun,
      'pembiayaan'     => $pembiayaan,
      'kategori'       => $kategori,
      'file_url'       => $file_url
    ], ['%d','%s','%d','%s','%s','%s']);

    // Kirim data ke Google Sheet
    $sheet_data = [
      'nip'                     => $me->nip,
      'nama'                    => $me->nama,
      'jabatan'                 => $me->jabatan,
      'unit_kerja'              => $me->unit,
      'nama_pelatihan'          => $nama,
      'tahun_penyelenggaraan'   => $tahun,
      'pembiayaan'              => $pembiayaan,
      'kategori'                => $kategori,
      'link_sertifikat'         => $file_url,
      'timestamp'               => current_time('mysql')
    ];

    $sheet_result = Trainings::submit_to_sheet($sheet_data);

    if ($sheet_result && empty($sheet_result['ok'])) {
      wp_send_json(['ok'=>false,'msg'=>$sheet_result['msg'] ?? 'Gagal mengirim data ke Google Sheet.']);
    }

    wp_send_json(['ok'=>true]);
  }
}
