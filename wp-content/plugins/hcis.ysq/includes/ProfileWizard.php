<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class ProfileWizard {
  const QUERY_VAR = 'ysq_profile_wizard';
  const SLUG = 'lengkapi-profil';

  public static function init() {
    add_action('init', [__CLASS__, 'register_rewrite']);
    add_filter('query_vars', [__CLASS__, 'register_query_var']);
    add_action('template_redirect', [__CLASS__, 'handle_template']);
    add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
  }

  public static function register_rewrite() {
    add_rewrite_rule('^' . self::SLUG . '/?$', 'index.php?' . self::QUERY_VAR . '=1', 'top');
    add_rewrite_tag('%' . self::QUERY_VAR . '%', '([0-1])');
  }

  public static function register_query_var($vars) {
    $vars[] = self::QUERY_VAR;
    return $vars;
  }

  protected static function is_wizard_page() {
    return (int) get_query_var(self::QUERY_VAR) === 1;
  }

  public static function enqueue_assets() {
    if (!self::is_wizard_page()) {
      return;
    }

    wp_enqueue_style('hcisysq-profile-wizard', HCISYSQ_URL . 'assets/css/profile-wizard.css', [], HCISYSQ_VER);
    wp_enqueue_script('hcisysq-profile-wizard', HCISYSQ_URL . 'assets/js/profile-wizard.js', ['jquery'], HCISYSQ_VER, true);
  }

  public static function handle_template() {
    if (!self::is_wizard_page()) {
      return;
    }

    if (!is_user_logged_in()) {
      wp_safe_redirect(wp_login_url(home_url('/' . self::SLUG . '/')));
      exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      self::handle_submission();
    }

    self::render_wizard();
    exit;
  }

  protected static function handle_submission() {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field($_POST['_wpnonce']), 'ysq_profile_wizard')) {
      wp_die(__('Permintaan tidak valid.', 'hcis-ysq'));
    }

    $user_id = get_current_user_id();
    if (!$user_id) {
      wp_die(__('Pengguna tidak ditemukan.', 'hcis-ysq'));
    }

    global $wpdb;
    $employees_table = $wpdb->prefix . 'ysq_employees';
    $employee = $wpdb->get_row($wpdb->prepare("SELECT * FROM $employees_table WHERE wp_user_id = %d", $user_id));

    $employee_data_raw = wp_unslash($_POST['employee'] ?? []);
    $employee_data = [
      'title_prefix' => sanitize_text_field($employee_data_raw['title_prefix'] ?? ''),
      'full_name' => sanitize_text_field($employee_data_raw['full_name'] ?? ''),
      'title_suffix' => sanitize_text_field($employee_data_raw['title_suffix'] ?? ''),
      'employee_id_number' => sanitize_text_field($employee_data_raw['employee_id_number'] ?? ''),
      'ktp_number' => sanitize_text_field($employee_data_raw['ktp_number'] ?? ''),
      'email' => sanitize_email($employee_data_raw['email'] ?? ''),
      'phone_number' => sanitize_text_field($employee_data_raw['phone_number'] ?? ''),
      'birth_place' => sanitize_text_field($employee_data_raw['birth_place'] ?? ''),
      'birth_date' => sanitize_text_field($employee_data_raw['birth_date'] ?? ''),
      'gender' => sanitize_text_field($employee_data_raw['gender'] ?? ''),
      'marital_status' => sanitize_text_field($employee_data_raw['marital_status'] ?? ''),
      'address' => sanitize_textarea_field($employee_data_raw['address'] ?? ''),
      'join_date' => sanitize_text_field($employee_data_raw['join_date'] ?? ''),
      'status' => sanitize_text_field($employee_data_raw['status'] ?? 'Aktif'),
      'emergency_contact_name' => sanitize_text_field($employee_data_raw['emergency_contact_name'] ?? ''),
      'emergency_contact_phone' => sanitize_text_field($employee_data_raw['emergency_contact_phone'] ?? ''),
      'profile_picture_url' => esc_url_raw($employee_data_raw['profile_picture_url'] ?? ''),
      'bank_name' => sanitize_text_field($employee_data_raw['bank_name'] ?? ''),
      'bank_account_number' => sanitize_text_field($employee_data_raw['bank_account_number'] ?? ''),
      'npwp_number' => sanitize_text_field($employee_data_raw['npwp_number'] ?? ''),
    ];

    if ($employee_data['full_name'] === '' || $employee_data['employee_id_number'] === '') {
      wp_die(__('Nama lengkap dan NIP wajib diisi.', 'hcis-ysq'));
    }
    if ($employee_data['join_date'] === '') {
      $employee_data['join_date'] = current_time('Y-m-d');
    }

    if (!$employee) {
      $employee_data['wp_user_id'] = $user_id;
      $wpdb->insert($employees_table, $employee_data);
      $employee_id = (int) $wpdb->insert_id;
    } else {
      $employee_id = (int) $employee->id;
      $wpdb->update($employees_table, $employee_data, ['id' => $employee_id]);
    }

    if (!$employee_id) {
      wp_die(__('Gagal menyimpan data pegawai.', 'hcis-ysq'));
    }

    self::sync_family_members($employee_id, wp_unslash($_POST['family'] ?? []));
    self::sync_education($employee_id, wp_unslash($_POST['education'] ?? []));
    self::sync_work_history($employee_id, wp_unslash($_POST['work_history'] ?? []));
    self::sync_training_history($employee_id, wp_unslash($_POST['training_history'] ?? []));
    self::sync_employment_history($employee_id, wp_unslash($_POST['employment_history'] ?? []));
    self::sync_quran($employee_id, wp_unslash($_POST['quran_memorization'] ?? []));
    self::sync_islamic($employee_id, wp_unslash($_POST['islamic_studies'] ?? []));

    update_user_meta($user_id, 'profile_complete', 1);

    wp_safe_redirect(home_url('/dashboard'));
    exit;
  }

  protected static function normalize_repeater(array $entries, array $fields) {
    $normalized = [];
    foreach ($entries as $entry) {
      $clean = [];
      $isEmpty = true;
      foreach ($fields as $key => $callback) {
        $value = isset($entry[$key]) ? call_user_func($callback, $entry[$key]) : '';
        if ($value !== '') {
          $isEmpty = false;
        }
        $clean[$key] = $value;
      }
      if (!$isEmpty) {
        $normalized[] = $clean;
      }
    }
    return $normalized;
  }

  protected static function sync_family_members($employee_id, array $entries) {
    global $wpdb;
    $table = $wpdb->prefix . 'ysq_family_members';
    $normalized = self::normalize_repeater($entries, [
      'name' => 'sanitize_text_field',
      'relationship' => 'sanitize_text_field',
      'birth_date' => 'sanitize_text_field',
    ]);

    $wpdb->delete($table, ['employee_id' => $employee_id]);
    foreach ($normalized as $row) {
      $row['employee_id'] = $employee_id;
      $wpdb->insert($table, $row);
    }
  }

  protected static function sync_education($employee_id, array $entries) {
    global $wpdb;
    $table = $wpdb->prefix . 'ysq_education_history';
    $normalized = self::normalize_repeater($entries, [
      'level' => 'sanitize_text_field',
      'institution_name' => 'sanitize_text_field',
      'major' => 'sanitize_text_field',
      'end_year' => 'sanitize_text_field',
    ]);

    $wpdb->delete($table, ['employee_id' => $employee_id]);
    foreach ($normalized as $row) {
      $row['employee_id'] = $employee_id;
      $wpdb->insert($table, $row);
    }
  }

  protected static function sync_work_history($employee_id, array $entries) {
    global $wpdb;
    $table = $wpdb->prefix . 'ysq_work_history';
    $normalized = self::normalize_repeater($entries, [
      'company_name' => 'sanitize_text_field',
      'position' => 'sanitize_text_field',
      'start_date' => 'sanitize_text_field',
      'end_date' => 'sanitize_text_field',
      'reference_contact' => 'sanitize_text_field',
    ]);

    $wpdb->delete($table, ['employee_id' => $employee_id]);
    foreach ($normalized as $row) {
      $row['employee_id'] = $employee_id;
      $wpdb->insert($table, $row);
    }
  }

  protected static function sync_training_history($employee_id, array $entries) {
    global $wpdb;
    $table = $wpdb->prefix . 'ysq_training_history';
    $normalized = self::normalize_repeater($entries, [
      'course_name' => 'sanitize_text_field',
      'organizer' => 'sanitize_text_field',
      'training_date' => 'sanitize_text_field',
      'venue' => 'sanitize_text_field',
      'cost' => function($value) { return sanitize_text_field($value); },
      'funding_source' => 'sanitize_text_field',
      'payment_method' => 'sanitize_text_field',
      'payment_proof_file' => 'esc_url_raw',
      'status' => 'sanitize_text_field',
      'certificate_file' => 'esc_url_raw',
    ]);

    $wpdb->delete($table, ['employee_id' => $employee_id]);
    foreach ($normalized as $row) {
      $row['employee_id'] = $employee_id;
      if ($row['status'] === '') {
        $row['status'] = 'Diajukan';
      }
      $wpdb->insert($table, $row);
    }
  }

  protected static function sync_employment_history($employee_id, array $entries) {
    global $wpdb;
    $table = $wpdb->prefix . 'ysq_employment_history';
    $units_table = $wpdb->prefix . 'ysq_units';
    $positions_table = $wpdb->prefix . 'ysq_positions';

    $normalized = [];
    foreach ($entries as $entry) {
      $unit_name = sanitize_text_field($entry['unit_name'] ?? '');
      $position_name = sanitize_text_field($entry['position_name'] ?? '');
      $employment_status = sanitize_text_field($entry['employment_status'] ?? '');
      $start_date = sanitize_text_field($entry['start_date'] ?? '');
      $end_date = sanitize_text_field($entry['end_date'] ?? '');
      if ($unit_name === '' && $position_name === '' && $start_date === '' && $employment_status === '') {
        continue;
      }

      $unit_id = self::upsert_lookup($units_table, $unit_name);
      $position_id = self::upsert_lookup($positions_table, $position_name);

      $normalized[] = [
        'employee_id' => $employee_id,
        'unit_id' => $unit_id,
        'position_id' => $position_id,
        'employment_status' => $employment_status,
        'start_date' => $start_date,
        'end_date' => $end_date,
      ];
    }

    $wpdb->delete($table, ['employee_id' => $employee_id]);
    foreach ($normalized as $row) {
      $wpdb->insert($table, $row);
    }
  }

  protected static function upsert_lookup($table, $name) {
    global $wpdb;
    if ($name === '') {
      return 0;
    }
    $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE name = %s", $name));
    if ($existing) {
      return (int) $existing;
    }
    $wpdb->insert($table, ['name' => $name]);
    return (int) $wpdb->insert_id;
  }

  protected static function sync_quran($employee_id, array $entry) {
    global $wpdb;
    $table = $wpdb->prefix . 'ysq_quran_memorization';
    $wpdb->delete($table, ['employee_id' => $employee_id]);

    $juz = isset($entry['juz_memorized']) ? sanitize_text_field($entry['juz_memorized']) : '';
    $last_tested = isset($entry['last_tested_date']) ? sanitize_text_field($entry['last_tested_date']) : '';
    $examiner = isset($entry['examiner_name']) ? sanitize_text_field($entry['examiner_name']) : '';
    $notes = isset($entry['notes']) ? sanitize_textarea_field($entry['notes']) : '';

    if ($juz !== '' || $last_tested !== '' || $examiner !== '' || $notes !== '') {
      $wpdb->insert($table, [
        'employee_id' => $employee_id,
        'juz_memorized' => $juz !== '' ? floatval(str_replace(',', '.', $juz)) : 0,
        'last_tested_date' => $last_tested,
        'examiner_name' => $examiner,
        'notes' => $notes,
      ]);
    }
  }

  protected static function sync_islamic($employee_id, array $entries) {
    global $wpdb;
    $table = $wpdb->prefix . 'ysq_islamic_studies_history';
    $normalized = self::normalize_repeater($entries, [
      'study_topic_or_book' => 'sanitize_text_field',
      'teacher_name' => 'sanitize_text_field',
      'organizer' => 'sanitize_text_field',
      'study_type' => 'sanitize_text_field',
      'start_date' => 'sanitize_text_field',
      'end_date' => 'sanitize_text_field',
    ]);

    $wpdb->delete($table, ['employee_id' => $employee_id]);
    foreach ($normalized as $row) {
      $row['employee_id'] = $employee_id;
      $wpdb->insert($table, $row);
    }
  }

  protected static function render_wizard() {
    $user_id = get_current_user_id();
    global $wpdb;
    $employees_table = $wpdb->prefix . 'ysq_employees';
    $employee = $wpdb->get_row($wpdb->prepare("SELECT * FROM $employees_table WHERE wp_user_id = %d", $user_id));
    $employee_data = $employee ? (array) $employee : [];
    $force_password_change = get_user_meta($user_id, 'ysq_force_password_change', true);

    $family = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ysq_family_members WHERE employee_id = %d", $employee->id ?? 0), ARRAY_A);
    $education = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ysq_education_history WHERE employee_id = %d", $employee->id ?? 0), ARRAY_A);
    $work_history = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ysq_work_history WHERE employee_id = %d", $employee->id ?? 0), ARRAY_A);
    $training_history = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ysq_training_history WHERE employee_id = %d", $employee->id ?? 0), ARRAY_A);
    $employment_history = $wpdb->get_results($wpdb->prepare("SELECT eh.*, u.name AS unit_name, p.name AS position_name FROM {$wpdb->prefix}ysq_employment_history eh LEFT JOIN {$wpdb->prefix}ysq_units u ON eh.unit_id = u.id LEFT JOIN {$wpdb->prefix}ysq_positions p ON eh.position_id = p.id WHERE eh.employee_id = %d", $employee->id ?? 0), ARRAY_A);
    $quran = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ysq_quran_memorization WHERE employee_id = %d", $employee->id ?? 0), ARRAY_A);
    $islamic = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ysq_islamic_studies_history WHERE employee_id = %d", $employee->id ?? 0), ARRAY_A);

    include HCISYSQ_DIR . 'templates/profile-wizard.php';
  }
}
