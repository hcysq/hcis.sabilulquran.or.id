<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Installer {
  const SCHEMA_VERSION = '6';

  public static function activate(){
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $engine = 'ENGINE=InnoDB';

    $tables = [
      'hcisysq_sessions' => "CREATE TABLE {$wpdb->prefix}hcisysq_sessions (
        session_id VARCHAR(64) NOT NULL,
        user_id BIGINT UNSIGNED NULL,
        payload LONGTEXT NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (session_id),
        KEY idx_user_id (user_id),
        KEY idx_expires (expires_at)
      ) $engine $charset;",
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
      'hcisysq_logs' => "CREATE TABLE {$wpdb->prefix}hcisysq_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        level VARCHAR(20) NOT NULL,
        severity VARCHAR(20) NOT NULL,
        message TEXT NOT NULL,
        context LONGTEXT,
        extra LONGTEXT NULL,
        stack_trace LONGTEXT NULL,
        component VARCHAR(191) DEFAULT 'core',
        request_id VARCHAR(64) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        user_id BIGINT NULL,
        ip_address VARCHAR(45),
        KEY idx_level_date (level, created_at),
        KEY idx_component (component),
        KEY idx_request (request_id)
      ) $engine $charset;",
      'hcisysq_users' => "CREATE TABLE {$wpdb->prefix}hcisysq_users (
        nip VARCHAR(64) NOT NULL,
        nama VARCHAR(255) DEFAULT '',
        jabatan VARCHAR(255) DEFAULT '',
        unit VARCHAR(255) DEFAULT '',
        no_hp VARCHAR(64) DEFAULT '',
        password VARCHAR(255) DEFAULT '',
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (nip)
      ) $engine $charset;",
      'hcisysq_password_resets' => "CREATE TABLE {$wpdb->prefix}hcisysq_password_resets (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nip varchar(255) NOT NULL,
        token_hash varchar(255) NOT NULL,
        expires_at datetime NOT NULL,
        used_at datetime DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY ix_token_hash (token_hash)
      ) $engine $charset;",
    ];

    self::maybe_migrate_password_reset_table();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    foreach ($tables as $sql) {
      dbDelta($sql);
    }

    update_option('hcisysq_schema_version', self::SCHEMA_VERSION);
    if (class_exists(__NAMESPACE__ . '\\SessionHandler')) {
      \HCISYSQ\SessionHandler::verify_table_exists();
    }
    hcisysq_log('Installer::activate() - schema upgraded to version ' . self::SCHEMA_VERSION);

    self::add_roles_and_capabilities();

    Publikasi_Post_Type::on_activation();
    Tasks::on_activation();

    self::ensure_login_page();

    flush_rewrite_rules();
  }

  protected static function maybe_migrate_password_reset_table(): void {
    global $wpdb;

    if ($wpdb->prefix === 'wp_') {
      return;
    }

    $legacy_table = 'wp_hcisysq_password_resets';
    $target_table = $wpdb->prefix . 'hcisysq_password_resets';

    $legacy_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $legacy_table));
    if ($legacy_exists !== $legacy_table) {
      return;
    }

    $target_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $target_table));
    if ($target_exists === $target_table) {
      return;
    }

    $rename_sql = sprintf(
      'RENAME TABLE `%s` TO `%s`',
      esc_sql($legacy_table),
      esc_sql($target_table)
    );

    $wpdb->query($rename_sql);
    hcisysq_log(sprintf('Migrated password reset table to match prefix %s', $wpdb->prefix));
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

  /**
   * Ensure the login page exists and contains the required shortcode
   */
  public static function maybe_ensure_login_page() {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $page = get_page_by_path(sanitize_title(HCISYSQ_LOGIN_SLUG), OBJECT, 'page');
    if ($page && $page->post_status === 'publish' && self::login_page_has_shortcode($page->post_content)) {
      update_option('hcisysq_login_page_id', (int) $page->ID);
      return;
    }

    self::ensure_login_page();
  }

  protected static function login_page_has_shortcode($content) {
    if (empty($content)) return false;
    return (bool) preg_match('/\[hcis_ysq_login\b/i', $content);
  }

  /**
   * Ensure the dashboard page exists and contains the required shortcode
   */
  public static function maybe_ensure_dashboard_page() {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $page = get_page_by_path(sanitize_title(HCISYSQ_DASHBOARD_SLUG), OBJECT, 'page');
    if ($page && $page->post_status === 'publish' && self::dashboard_page_has_shortcode($page->post_content)) {
      update_option('hcisysq_dashboard_page_id', (int) $page->ID);
      return;
    }

    self::ensure_dashboard_page();
  }

  protected static function dashboard_page_has_shortcode($content) {
    if (empty($content)) return false;
    return (bool) preg_match('/\[(hcis_ysq_dashboard|hcisysq_dashboard|hrissq_dashboard)\b/i', $content);
  }

  /**
   * Ensure the admin login page exists and contains the required shortcode
   */
  public static function maybe_ensure_admin_login_page() {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $page = get_page_by_path(sanitize_title(HCISYSQ_ADMIN_LOGIN_SLUG), OBJECT, 'page');
    if ($page && $page->post_status === 'publish' && self::admin_login_page_has_shortcode($page->post_content)) {
      update_option('hcisysq_admin_login_page_id', (int) $page->ID);
      return;
    }

    self::ensure_admin_login_page();
  }

  protected static function admin_login_page_has_shortcode($content) {
    if (empty($content)) return false;
    return (bool) preg_match('/\[hcis_ysq_admin_login\b/i', $content);
  }

  public static function ensure_login_page() {
    if (!function_exists('wp_insert_post')) {
      require_once ABSPATH . 'wp-admin/includes/post.php';
    }

    $slug = sanitize_title(HCISYSQ_LOGIN_SLUG);
    $shortcode = '[hcis_ysq_login]';
    $page = get_page_by_path($slug, OBJECT, 'page');

    if (!$page) {
      $page_id = wp_insert_post([
        'post_title'   => __('Masuk', 'hcis-ysq'),
        'post_name'    => $slug,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => $shortcode,
        'post_author'  => get_current_user_id() ?: 1,
      ], true);

      if (!is_wp_error($page_id)) {
        update_option('hcisysq_login_page_id', (int) $page_id);
      }
      return;
    }

    if ($page->post_status === 'trash') {
      wp_untrash_post($page->ID);
      $page = get_post($page->ID);
    }

    $needs_update = false;
    $updated_post = ['ID' => $page->ID];

    if ($page->post_status !== 'publish') {
      $updated_post['post_status'] = 'publish';
      $needs_update = true;
    }

    if (!self::login_page_has_shortcode($page->post_content)) {
      $content = trim($page->post_content);
      $content = $content ? $content . "\n\n" . $shortcode : $shortcode;
      $updated_post['post_content'] = $content;
      $needs_update = true;
    }

    if ($needs_update) {
      wp_update_post($updated_post);
    }

    update_option('hcisysq_login_page_id', (int) $page->ID);
  }

  public static function ensure_dashboard_page() {
    if (!function_exists('wp_insert_post')) {
      require_once ABSPATH . 'wp-admin/includes/post.php';
    }

    $slug = sanitize_title(HCISYSQ_DASHBOARD_SLUG);
    $shortcode = '[hcis_ysq_dashboard]';
    $page = get_page_by_path($slug, OBJECT, 'page');

    if (!$page) {
      $page_id = wp_insert_post([
        'post_title'   => __('Dashboard', 'hcis-ysq'),
        'post_name'    => $slug,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => $shortcode,
        'post_author'  => get_current_user_id() ?: 1,
      ], true);

      if (!is_wp_error($page_id)) {
        update_option('hcisysq_dashboard_page_id', (int) $page_id);
      }
      return;
    }

    if ($page->post_status === 'trash') {
      wp_untrash_post($page->ID);
      $page = get_post($page->ID);
    }

    $needs_update = false;
    $updated_post = ['ID' => $page->ID];

    if ($page->post_status !== 'publish') {
      $updated_post['post_status'] = 'publish';
      $needs_update = true;
    }

    if (!self::dashboard_page_has_shortcode($page->post_content)) {
      $content = trim($page->post_content);
      $content = $content ? $content . "\n\n" . $shortcode : $shortcode;
      $updated_post['post_content'] = $content;
      $needs_update = true;
    }

    if ($needs_update) {
      wp_update_post($updated_post);
    }

    update_option('hcisysq_dashboard_page_id', (int) $page->ID);
  }

  public static function ensure_admin_login_page() {
    if (!function_exists('wp_insert_post')) {
      require_once ABSPATH . 'wp-admin/includes/post.php';
    }

    $slug = sanitize_title(HCISYSQ_ADMIN_LOGIN_SLUG);
    $shortcode = '[hcis_ysq_admin_login]';
    $page = get_page_by_path($slug, OBJECT, 'page');

    if (!$page) {
      $page_id = wp_insert_post([
        'post_title'   => __('Masuk Administrator', 'hcis-ysq'),
        'post_name'    => $slug,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => $shortcode,
        'post_author'  => get_current_user_id() ?: 1,
      ], true);

      if (!is_wp_error($page_id)) {
        update_option('hcisysq_admin_login_page_id', (int) $page_id);
      }
      return;
    }

    if ($page->post_status === 'trash') {
      wp_untrash_post($page->ID);
      $page = get_post($page->ID);
    }

    $needs_update = false;
    $updated_post = ['ID' => $page->ID];

    if ($page->post_status !== 'publish') {
      $updated_post['post_status'] = 'publish';
      $needs_update = true;
    }

    if (!self::admin_login_page_has_shortcode($page->post_content)) {
      $content = trim($page->post_content);
      $content = $content ? $content . "\n\n" . $shortcode : $shortcode;
      $updated_post['post_content'] = $content;
      $needs_update = true;
    }

    if ($needs_update) {
      wp_update_post($updated_post);
    }

    update_option('hcisysq_admin_login_page_id', (int) $page->ID);
  }
}
