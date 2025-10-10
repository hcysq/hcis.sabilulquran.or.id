<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Installer {
  public static function activate(){
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $t_users = $wpdb->prefix.'hcisysq_users';
    $t_tr    = $wpdb->prefix.'hcisysq_trainings';
    $t_pf    = $wpdb->prefix.'hcisysq_profiles';

    // Tabel users (untuk autentikasi)
    $sql1 = "CREATE TABLE IF NOT EXISTS $t_users (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      nip VARCHAR(32) NOT NULL UNIQUE,
      nama VARCHAR(191) NOT NULL,
      jabatan VARCHAR(191) DEFAULT '',
      unit VARCHAR(191) DEFAULT '',
      no_hp VARCHAR(32) DEFAULT '',
      password VARCHAR(255) DEFAULT '',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $charset;";

    // Tabel trainings (rekam data pelatihan)
    $sql2 = "CREATE TABLE IF NOT EXISTS $t_tr (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      user_id BIGINT UNSIGNED NOT NULL,
      nama_pelatihan VARCHAR(255) NOT NULL,
      tahun INT NOT NULL,
      pembiayaan VARCHAR(32) NOT NULL,
      kategori VARCHAR(32) NOT NULL,
      file_url TEXT DEFAULT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX (user_id),
      CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES $t_users(id) ON DELETE CASCADE
    ) $charset;";

    // Tabel profiles (mirror dari CSV profil pegawai)
    $sql3 = "CREATE TABLE IF NOT EXISTS $t_pf (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      nip VARCHAR(32) NOT NULL UNIQUE,
      nama VARCHAR(191) NOT NULL,
      unit VARCHAR(191) DEFAULT '',
      jabatan VARCHAR(191) DEFAULT '',
      tempat_lahir VARCHAR(191) DEFAULT '',
      tanggal_lahir VARCHAR(32) DEFAULT '',
      alamat_ktp TEXT,
      desa VARCHAR(191) DEFAULT '',
      kecamatan VARCHAR(191) DEFAULT '',
      kota VARCHAR(191) DEFAULT '',
      kode_pos VARCHAR(16) DEFAULT '',
      email VARCHAR(191) DEFAULT '',
      hp VARCHAR(64) DEFAULT '',
      tmt VARCHAR(64) DEFAULT '',
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $charset;";

    require_once(ABSPATH.'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);
    dbDelta($sql3);

    // Jadwalkan import harian (kalau belum)
    if (!wp_next_scheduled('hcisysq_profiles_cron')) {
      wp_schedule_event(time() + 600, 'daily', 'hcisysq_profiles_cron');
    }
    if (!wp_next_scheduled('hcisysq_users_cron')) {
      wp_schedule_event(time() + 600, 'daily', 'hcisysq_users_cron');
    }

    self::add_roles_and_capabilities();

    Publikasi_Post_Type::on_activation();
    Tasks::on_activation();
  }

  public static function deactivate(){
    self::remove_roles_and_capabilities();
  }

  protected static function add_roles_and_capabilities(){
    if (!function_exists('add_role') || !function_exists('get_role')) {
      return;
    }

    $role = get_role('hcis_admin');
    if (!$role) {
      add_role('hcis_admin', 'HCIS Admin', [
        'read' => true,
        'manage_hcis_portal' => true,
      ]);
      $role = get_role('hcis_admin');
    }

    if ($role) {
      $role->add_cap('read', true);
      $role->add_cap('manage_hcis_portal', true);
    }
  }

  protected static function remove_roles_and_capabilities(){
    if (!function_exists('remove_role') || !function_exists('get_role')) {
      return;
    }

    $role = get_role('hcis_admin');
    if ($role) {
      $role->remove_cap('manage_hcis_portal');
      $role->remove_cap('read');
    }

    remove_role('hcis_admin');
  }
}
