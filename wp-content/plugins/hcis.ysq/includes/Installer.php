<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Installer {
  const SCHEMA_VERSION = '2';

  public static function activate(){
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $engine = 'ENGINE=InnoDB';

    $tables = [
      'ysq_employees' => "CREATE TABLE {$wpdb->prefix}ysq_employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        wp_user_id BIGINT UNSIGNED UNIQUE,
        title_prefix VARCHAR(50),
        full_name VARCHAR(255) NOT NULL,
        title_suffix VARCHAR(100),
        employee_id_number VARCHAR(50) UNIQUE,
        ktp_number VARCHAR(30) UNIQUE,
        email VARCHAR(255) UNIQUE,
        phone_number VARCHAR(20),
        birth_place VARCHAR(100),
        birth_date DATE,
        gender ENUM('Laki-laki','Perempuan'),
        marital_status VARCHAR(50),
        address TEXT,
        join_date DATE NOT NULL,
        status ENUM('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
        emergency_contact_name VARCHAR(255),
        emergency_contact_phone VARCHAR(20),
        profile_picture_url VARCHAR(255),
        bank_name VARCHAR(100),
        bank_account_number VARCHAR(50),
        npwp_number VARCHAR(25),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY wp_user_id (wp_user_id)
      ) $engine $charset;",
      'ysq_units' => "CREATE TABLE {$wpdb->prefix}ysq_units (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) UNIQUE NOT NULL
      ) $engine $charset;",
      'ysq_positions' => "CREATE TABLE {$wpdb->prefix}ysq_positions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) UNIQUE NOT NULL
      ) $engine $charset;",
      'ysq_employment_history' => "CREATE TABLE {$wpdb->prefix}ysq_employment_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        unit_id INT NOT NULL,
        position_id INT NOT NULL,
        employment_status VARCHAR(100),
        start_date DATE NOT NULL,
        end_date DATE,
        KEY employee_id (employee_id),
        KEY unit_id (unit_id),
        KEY position_id (position_id),
        CONSTRAINT `fk_ysq_employment_history_employee` FOREIGN KEY (`employee_id`) REFERENCES `{$wpdb->prefix}ysq_employees` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_ysq_employment_history_unit` FOREIGN KEY (`unit_id`) REFERENCES `{$wpdb->prefix}ysq_units` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_ysq_employment_history_position` FOREIGN KEY (`position_id`) REFERENCES `{$wpdb->prefix}ysq_positions` (`id`) ON DELETE CASCADE
      ) $engine $charset;",
      'ysq_family_members' => "CREATE TABLE {$wpdb->prefix}ysq_family_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        relationship VARCHAR(50) NOT NULL,
        birth_date DATE,
        KEY employee_id (employee_id),
        CONSTRAINT `fk_ysq_family_members_employee` FOREIGN KEY (`employee_id`) REFERENCES `{$wpdb->prefix}ysq_employees` (`id`) ON DELETE CASCADE
      ) $engine $charset;",
      'ysq_education_history' => "CREATE TABLE {$wpdb->prefix}ysq_education_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        level VARCHAR(50) NOT NULL,
        institution_name VARCHAR(255) NOT NULL,
        major VARCHAR(255),
        end_year CHAR(4),
        KEY employee_id (employee_id),
        CONSTRAINT `fk_ysq_education_history_employee` FOREIGN KEY (`employee_id`) REFERENCES `{$wpdb->prefix}ysq_employees` (`id`) ON DELETE CASCADE
      ) $engine $charset;",
      'ysq_work_history' => "CREATE TABLE {$wpdb->prefix}ysq_work_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        company_name VARCHAR(255) NOT NULL,
        position VARCHAR(255) NOT NULL,
        start_date DATE,
        end_date DATE,
        reference_contact VARCHAR(255),
        KEY employee_id (employee_id),
        CONSTRAINT `fk_ysq_work_history_employee` FOREIGN KEY (`employee_id`) REFERENCES `{$wpdb->prefix}ysq_employees` (`id`) ON DELETE CASCADE
      ) $engine $charset;",
      'ysq_quran_memorization' => "CREATE TABLE {$wpdb->prefix}ysq_quran_memorization (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        juz_memorized DECIMAL(4,2) NOT NULL,
        last_tested_date DATE,
        examiner_name VARCHAR(255),
        notes TEXT,
        KEY employee_id (employee_id),
        CONSTRAINT `fk_ysq_quran_memorization_employee` FOREIGN KEY (`employee_id`) REFERENCES `{$wpdb->prefix}ysq_employees` (`id`) ON DELETE CASCADE
      ) $engine $charset;",
      'ysq_training_history' => "CREATE TABLE {$wpdb->prefix}ysq_training_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        course_name VARCHAR(255) NOT NULL,
        organizer VARCHAR(255),
        training_date DATE,
        venue VARCHAR(255),
        cost DECIMAL(15,2),
        funding_source VARCHAR(100),
        payment_method VARCHAR(100),
        payment_proof_file VARCHAR(255),
        status ENUM('Diajukan','Disetujui','Ditolak','Selesai','Dibatalkan') NOT NULL,
        certificate_file VARCHAR(255),
        KEY employee_id (employee_id),
        CONSTRAINT `fk_ysq_training_history_employee` FOREIGN KEY (`employee_id`) REFERENCES `{$wpdb->prefix}ysq_employees` (`id`) ON DELETE CASCADE
      ) $engine $charset;",
      'ysq_islamic_studies_history' => "CREATE TABLE {$wpdb->prefix}ysq_islamic_studies_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        study_topic_or_book VARCHAR(255) NOT NULL,
        teacher_name VARCHAR(255),
        organizer VARCHAR(255),
        study_type VARCHAR(100),
        start_date DATE,
        end_date DATE,
        KEY employee_id (employee_id),
        CONSTRAINT `fk_ysq_islamic_studies_history_employee` FOREIGN KEY (`employee_id`) REFERENCES `{$wpdb->prefix}ysq_employees` (`id`) ON DELETE CASCADE
      ) $engine $charset;",
    ];

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    foreach ($tables as $sql) {
      dbDelta($sql);
    }

    update_option('hcisysq_schema_version', self::SCHEMA_VERSION);

    self::add_roles_and_capabilities();

    Publikasi_Post_Type::on_activation();
    Tasks::on_activation();

    flush_rewrite_rules();
  }

  public static function deactivate(){
    self::remove_roles_and_capabilities();
    flush_rewrite_rules();
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
