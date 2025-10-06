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
        'id'           => $item['id'] ?? '',
        'title'        => $item['title'] ?? '',
        'body'         => $item['body'] ?? '',
        'link_label'   => $item['link_label'] ?? '',
        'link_url'     => $item['link_url'] ?? '',
        'status'       => $item['status'] ?? 'published',
        'created_at'   => $item['created_at'] ?? '',
        'updated_at'   => $item['updated_at'] ?? '',
        'archived_at'  => $item['archived_at'] ?? null,
        'category'     => $item['category'] ?? null,
        'thumbnail'    => $item['thumbnail'] ?? null,
        'attachments'  => $item['attachments'] ?? [],
      ];
    }, $items);
  }

  private static function parse_id_list($value){
    if (is_string($value)) {
      $decoded = json_decode(wp_unslash($value), true);
      if (is_array($decoded)) {
        $value = $decoded;
      } else {
        $parts = array_filter(array_map('trim', explode(',', $value)));
        $value = $parts;
      }
    }

    if (!is_array($value)) {
      return [];
    }

    $ids = array_map('absint', $value);
    $ids = array_filter($ids, function($id){ return $id > 0; });

    return array_values(array_unique($ids));
  }

  private static function ensure_media_dependencies(){
    static $loaded = false;
    if ($loaded) {
      return;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $loaded = true;
  }

  private static function upload_files($field, $expect_image = false){
    if (empty($_FILES[$field]) || empty($_FILES[$field]['name'])) {
      return [];
    }

    self::ensure_media_dependencies();

    $files = $_FILES[$field];
    $names = $files['name'];
    $is_multi = is_array($names);
    $total = $is_multi ? count($names) : 1;
    $ids = [];

    for ($i = 0; $i < $total; $i++) {
      $name = $is_multi ? ($files['name'][$i] ?? '') : $files['name'];
      if (!$name) {
        continue;
      }

      $file = [
        'name'     => $is_multi ? $files['name'][$i] : $files['name'],
        'type'     => $is_multi ? $files['type'][$i] : $files['type'],
        'tmp_name' => $is_multi ? $files['tmp_name'][$i] : $files['tmp_name'],
        'error'    => $is_multi ? $files['error'][$i] : $files['error'],
        'size'     => $is_multi ? $files['size'][$i] : $files['size'],
      ];

      if (!empty($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
        return new \WP_Error('upload_error', __('Upload gagal, silakan coba lagi.', 'hcisysq'));
      }

      $temp_key = 'hcisysq_upload_' . wp_generate_password(6, false, false);
      $_FILES[$temp_key] = $file;
      $attachment_id = media_handle_upload($temp_key, 0);
      unset($_FILES[$temp_key]);

      if (is_wp_error($attachment_id)) {
        return $attachment_id;
      }

      if ($expect_image && !wp_attachment_is_image($attachment_id)) {
        wp_delete_attachment($attachment_id, true);
        return new \WP_Error('invalid_image', __('File thumbnail harus berupa gambar.', 'hcisysq'));
      }

      $ids[] = (int) $attachment_id;
    }

    return $ids;
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

  private static function sanitize_marquee_options(array $data){
    $allowed_speeds = ['0.5', '1', '2', '3'];
    $speed_raw = trim((string)($data['marquee_speed'] ?? '1'));
    if (!in_array($speed_raw, $allowed_speeds, true)) {
      $speed_raw = '1';
    }

    $background = isset($data['marquee_background']) ? sanitize_hex_color($data['marquee_background']) : '';
    if (!$background) {
      $background = '#ffffff';
    }

    $duplicates = absint($data['marquee_duplicates'] ?? 2);
    if ($duplicates < 1) {
      $duplicates = 1;
    } elseif ($duplicates > 6) {
      $duplicates = 6;
    }

    $letter_spacing = floatval(str_replace(',', '.', (string)($data['marquee_letter_spacing'] ?? 0)));
    if ($letter_spacing < 0) {
      $letter_spacing = 0;
    } elseif ($letter_spacing > 10) {
      $letter_spacing = 10;
    }

    $gap = absint($data['marquee_gap'] ?? 32);
    if ($gap < 8) {
      $gap = 8;
    } elseif ($gap > 160) {
      $gap = 160;
    }

    return [
      'speed'          => (float) $speed_raw,
      'background'     => $background,
      'duplicates'     => $duplicates,
      'letter_spacing' => $letter_spacing,
      'gap'            => $gap,
    ];
  }

  public static function admin_create_announcement(){
    self::check_nonce();
    self::require_admin();

    $title = sanitize_text_field($_POST['title'] ?? '');
    $rawBody = isset($_POST['body']) ? wp_unslash($_POST['body']) : '';
    $body = RichText::sanitize($rawBody);
    $plainBody = trim(wp_strip_all_tags($body));
    $linkType = sanitize_text_field($_POST['link_type'] ?? '');
    if ($linkType === 'external' && trim((string)($_POST['link_url'] ?? '')) === '') {
      wp_send_json(['ok' => false, 'msg' => 'Isi URL tautan terlebih dahulu.']);
    }
    [$label, $url] = self::normalize_link($_POST);

    if ($title === '' || $plainBody === '') {
      wp_send_json(['ok' => false, 'msg' => 'Judul dan isi pengumuman wajib diisi.']);
    }

    $category = sanitize_text_field($_POST['category'] ?? '');

    $thumbnail_ids = self::upload_files('announcement_thumbnail', true);
    if (is_wp_error($thumbnail_ids)) {
      wp_send_json(['ok' => false, 'msg' => $thumbnail_ids->get_error_message()]);
    }
    $thumbnail_id = !empty($thumbnail_ids) ? (int) $thumbnail_ids[0] : 0;

    $new_attachments = self::upload_files('announcement_attachments', false);
    if (is_wp_error($new_attachments)) {
      wp_send_json(['ok' => false, 'msg' => $new_attachments->get_error_message()]);
    }
    $attachments = array_values(array_unique(array_merge(self::parse_id_list($_POST['existing_attachments'] ?? []), $new_attachments)));

    $created = Announcements::create([
      'title'         => $title,
      'body'          => $body,
      'link_label'    => $label,
      'link_url'      => $url,
      'status'        => 'published',
      'category'      => $category,
      'thumbnail_id'  => $thumbnail_id,
      'attachments'   => $attachments,
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
    $rawBody = isset($_POST['body']) ? wp_unslash($_POST['body']) : '';
    $body = RichText::sanitize($rawBody);
    $plainBody = trim(wp_strip_all_tags($body));
    $linkType = sanitize_text_field($_POST['link_type'] ?? '');
    if ($linkType === 'external' && trim((string)($_POST['link_url'] ?? '')) === '') {
      wp_send_json(['ok' => false, 'msg' => 'Isi URL tautan terlebih dahulu.']);
    }
    [$label, $url] = self::normalize_link($_POST);

    $category = sanitize_text_field($_POST['category'] ?? '');

    $existing_thumbnail = absint($_POST['thumbnail_existing'] ?? 0);
    $thumbnail_action = sanitize_text_field($_POST['thumbnail_action'] ?? 'keep');
    $thumbnail_ids = self::upload_files('announcement_thumbnail', true);
    if (is_wp_error($thumbnail_ids)) {
      wp_send_json(['ok' => false, 'msg' => $thumbnail_ids->get_error_message()]);
    }
    if (!empty($thumbnail_ids)) {
      $thumbnail_id = (int) $thumbnail_ids[0];
    } else {
      $thumbnail_id = ($thumbnail_action === 'remove') ? 0 : $existing_thumbnail;
    }

    $keep_attachments = self::parse_id_list($_POST['existing_attachments'] ?? []);
    $new_attachments = self::upload_files('announcement_attachments', false);
    if (is_wp_error($new_attachments)) {
      wp_send_json(['ok' => false, 'msg' => $new_attachments->get_error_message()]);
    }
    $attachments = array_values(array_unique(array_merge($keep_attachments, $new_attachments)));

    $status = sanitize_text_field($_POST['status'] ?? '');
    $payload = [
      'title'         => $title,
      'body'          => $body,
      'link_label'    => $label,
      'link_url'      => $url,
      'category'      => $category,
      'thumbnail_id'  => $thumbnail_id,
      'attachments'   => $attachments,
    ];
    if ($plainBody === '') {
      wp_send_json(['ok' => false, 'msg' => 'Isi pengumuman tidak boleh kosong.']);
    }
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

  public static function admin_save_home_settings(){
    self::check_nonce();
    self::require_admin();

    $rawMarquee = isset($_POST['marquee_text']) ? wp_unslash($_POST['marquee_text']) : '';
    $marquee = RichText::sanitize($rawMarquee);
    $options = self::sanitize_marquee_options($_POST);

    update_option('hcisysq_home_marquee_text', $marquee, false);
    update_option('hcisysq_home_marquee_options', $options, false);

    wp_send_json([
      'ok'   => true,
      'msg'  => 'Pengaturan beranda tersimpan.',
      'home' => [
        'marquee_text' => $marquee,
        'options'      => $options,
      ],
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
