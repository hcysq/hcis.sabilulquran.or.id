<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Tasks {
  const OPTION = 'hcisysq_tasks';
  const QUERY_VAR_DATE = 'hcisysq_task_date';
  const QUERY_VAR_INDEX = 'hcisysq_task_index';

  public static function init(){
    add_action('init', [__CLASS__, 'register_rewrite']);
    add_filter('query_vars', [__CLASS__, 'register_query_vars']);
    add_filter('template_include', [__CLASS__, 'maybe_use_history_template']);
    add_action('template_redirect', [__CLASS__, 'handle_history_actions'], 0);
  }

  public static function on_activation(){
    self::register_rewrite();
    flush_rewrite_rules(false);
  }

  public static function register_rewrite(){
    add_rewrite_rule('^tugas/([0-9]{4}-[0-9]{2}-[0-9]{2})/([0-9]+)/?$', 'index.php?' . self::QUERY_VAR_DATE . '=$1&' . self::QUERY_VAR_INDEX . '=$2', 'top');
  }

  public static function register_query_vars($vars){
    $vars[] = self::QUERY_VAR_DATE;
    $vars[] = self::QUERY_VAR_INDEX;
    return $vars;
  }

  private static function load_all(){
    $raw = get_option(self::OPTION, []);
    if (!is_array($raw)) {
      return [];
    }

    $tasks = [];
    foreach ($raw as $item) {
      if (!is_array($item) || empty($item['id'])) continue;
      $tasks[$item['id']] = $item;
    }

    return $tasks;
  }

  private static function save_all(array $tasks){
    $tasks = array_values(array_map(function($task){
      $task['assignments'] = isset($task['assignments']) && is_array($task['assignments']) ? $task['assignments'] : [];
      return $task;
    }, $tasks));
    update_option(self::OPTION, $tasks, false);
  }

  private static function sanitize_text($value){
    return sanitize_text_field($value ?? '');
  }

  private static function sanitize_deadline($value){
    $value = is_string($value) ? trim($value) : '';
    if ($value === '') return '';
    try {
      $date = new \DateTimeImmutable($value);
      return $date->format('Y-m-d');
    } catch (\Exception $e) {
      return '';
    }
  }

  private static function sanitize_units($units){
    if (is_string($units)) {
      $decoded = json_decode(wp_unslash($units), true);
      if (is_array($decoded)) {
        $units = $decoded;
      } else {
        $units = array_map('trim', explode(',', $units));
      }
    }
    if (!is_array($units)) return [];
    $list = [];
    foreach ($units as $unit) {
      $unit = trim((string)$unit);
      if ($unit === '') continue;
      $list[$unit] = true;
    }
    return array_keys($list);
  }

  private static function sanitize_employees($employees){
    if (is_string($employees)) {
      $decoded = json_decode(wp_unslash($employees), true);
      if (is_array($decoded)) {
        $employees = $decoded;
      } else {
        $employees = array_map('trim', explode(',', $employees));
      }
    }
    if (!is_array($employees)) return [];
    $list = [];
    foreach ($employees as $nip) {
      $nip = trim((string)$nip);
      if ($nip === '') continue;
      $list[$nip] = true;
    }
    return array_keys($list);
  }

  private static function get_employee_rows(){
    global $wpdb;
    $table = $wpdb->prefix . 'hcisysq_users';
    $rows = $wpdb->get_results("SELECT nip, nama, unit FROM {$table} ORDER BY unit ASC, nama ASC", ARRAY_A);
    if (!is_array($rows)) {
      return [];
    }
    return array_map(function($row){
      return [
        'nip'  => sanitize_text_field($row['nip'] ?? ''),
        'nama' => sanitize_text_field($row['nama'] ?? ''),
        'unit' => sanitize_text_field($row['unit'] ?? ''),
      ];
    }, $rows);
  }

  public static function get_unit_directory(){
    $rows = self::get_employee_rows();
    $units = [];
    foreach ($rows as $row) {
      $unit = $row['unit'] !== '' ? $row['unit'] : 'Tanpa Unit';
      if (!isset($units[$unit])) {
        $units[$unit] = 0;
      }
      $units[$unit]++;
    }
    ksort($units, SORT_NATURAL | SORT_FLAG_CASE);
    return array_map(function($name) use ($units){
      return [
        'id'    => self::slugify($name),
        'label' => $name,
        'name'  => $name,
        'count' => $units[$name],
      ];
    }, array_keys($units));
  }

  public static function get_employee_directory(){
    $rows = self::get_employee_rows();
    return array_map(function($row){
      $unitName = $row['unit'] !== '' ? $row['unit'] : 'Tanpa Unit';
      return [
        'nip'      => $row['nip'],
        'nama'     => $row['nama'],
        'unit'     => $unitName,
        'unit_id'  => self::slugify($unitName),
      ];
    }, $rows);
  }

  private static function slugify($value){
    $value = strtolower(trim($value));
    if ($value === '') {
      return 'unit';
    }
    $value = preg_replace('~[^a-z0-9]+~', '-', $value);
    $value = trim($value, '-');
    return $value === '' ? 'unit' : $value;
  }

  private static function next_sequence(array $tasks, $date){
    $max = 0;
    foreach ($tasks as $task) {
      if (($task['date'] ?? '') === $date) {
        $seq = isset($task['sequence']) ? (int)$task['sequence'] : 0;
        if ($seq > $max) {
          $max = $seq;
        }
      }
    }
    return $max + 1;
  }

  private static function normalize_task(array $data, ?array $existing = null){
    $title = self::sanitize_text($data['title'] ?? '');
    $description = RichText::sanitize($data['description'] ?? '');
    $deadline = self::sanitize_deadline($data['deadline'] ?? '');
    $linkLabel = self::sanitize_text($data['link_label'] ?? '');
    $linkUrl = esc_url_raw($data['link_url'] ?? '');
    $units = self::sanitize_units($data['units'] ?? []);
    $employees = self::sanitize_employees($data['employees'] ?? []);

    if (empty($units) || empty($employees)) {
      return new \WP_Error('invalid_assignment', __('Unit dan pegawai wajib dipilih.', 'hcisysq'));
    }

    $assignments = [];
    $now = current_time('mysql');
    if ($existing && isset($existing['assignments']) && is_array($existing['assignments'])) {
      $assignments = $existing['assignments'];
    }

    $newAssignments = [];
    foreach ($employees as $nip) {
      if (isset($assignments[$nip])) {
        $newAssignments[$nip] = $assignments[$nip];
      } else {
        $newAssignments[$nip] = [
          'status' => 'pending',
          'updated_at' => $now,
          'completed_at' => null,
        ];
      }
    }

    // remove assignments not selected anymore
    foreach ($assignments as $nip => $assignment) {
      if (!in_array($nip, $employees, true)) {
        unset($assignments[$nip]);
      }
    }

    $task = $existing ? $existing : [];
    $task['title'] = $title;
    $task['description'] = $description;
    $task['deadline'] = $deadline;
    $task['link_label'] = $linkLabel;
    $task['link_url'] = $linkUrl;
    $task['units'] = $units;
    $task['employees'] = $employees;
    $task['assignments'] = $newAssignments;
    $task['updated_at'] = $now;

    if (empty($task['created_at'])) {
      $task['created_at'] = $now;
    }

    if (empty($task['id'])) {
      $task['id'] = 'task_' . wp_generate_password(10, false, false);
    }

    $date = substr($task['created_at'], 0, 10);
    if (!isset($task['date']) || $task['date'] === '') {
      $task['date'] = $date;
    }

    if (!isset($task['sequence'])) {
      $task['sequence'] = 0;
    }

    return $task;
  }

  public static function create(array $data){
    $tasks = self::load_all();
    $normalized = self::normalize_task($data);
    if (is_wp_error($normalized)) {
      return $normalized;
    }

    $date = $normalized['date'];
    $normalized['sequence'] = self::next_sequence($tasks, $date);
    $normalized['archived_at'] = null;
    if (!isset($normalized['status'])) {
      $normalized['status'] = 'published';
    }
    $tasks[$normalized['id']] = $normalized;
    uasort($tasks, function($a, $b){
      return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
    });
    self::save_all($tasks);
    return $normalized;
  }

  public static function update($id, array $data){
    $tasks = self::load_all();
    if (!isset($tasks[$id])) {
      return new \WP_Error('not_found', __('Tugas tidak ditemukan.', 'hcisysq'));
    }
    $existing = $tasks[$id];
    $normalized = self::normalize_task($data, $existing);
    if (is_wp_error($normalized)) {
      return $normalized;
    }
    $normalized['id'] = $id;
    $normalized['date'] = $existing['date'] ?? substr($normalized['created_at'], 0, 10);
    $normalized['sequence'] = $existing['sequence'] ?? 1;
    $normalized['created_at'] = $existing['created_at'] ?? $normalized['created_at'];
    $normalized['archived_at'] = $existing['archived_at'] ?? null;
    $normalized['completed_at'] = $existing['completed_at'] ?? null;
    $normalized['status'] = $existing['status'] ?? 'published';
    $tasks[$id] = $normalized;
    uasort($tasks, function($a, $b){
      return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
    });
    self::save_all($tasks);
    return $normalized;
  }

  public static function delete($id){
    $tasks = self::load_all();
    if (!isset($tasks[$id])) {
      return false;
    }
    unset($tasks[$id]);
    self::save_all($tasks);
    return true;
  }

  public static function set_status($id, $status){
    $tasks = self::load_all();
    if (!isset($tasks[$id])) {
      return new \WP_Error('not_found', __('Tugas tidak ditemukan.', 'hcisysq'));
    }
    $status = in_array($status, ['archived', 'published', 'completed'], true) ? $status : 'published';
    $task = $tasks[$id];
    $now = current_time('mysql');

    if ($status === 'archived') {
      $task['status'] = 'archived';
      $task['archived_at'] = $now;
    } elseif ($status === 'completed') {
      $task['status'] = 'completed';
      $task['completed_at'] = $now;
      foreach ($task['assignments'] as $nip => $assignment) {
        if (($assignment['status'] ?? 'pending') !== 'completed') {
          $task['assignments'][$nip]['status'] = 'completed';
          $task['assignments'][$nip]['completed_at'] = $now;
          $task['assignments'][$nip]['updated_at'] = $now;
        }
      }
    } else {
      $task['status'] = 'published';
      $task['archived_at'] = null;
      if (isset($task['completed_at'])) {
        $task['completed_at'] = $task['completed_at'] ?? null;
      }
    }

    $task['updated_at'] = $now;
    $tasks[$id] = $task;
    self::save_all($tasks);
    return $task;
  }

  public static function mark_assignment($id, $nip, $status){
    $tasks = self::load_all();
    if (!isset($tasks[$id])) {
      return new \WP_Error('not_found', __('Tugas tidak ditemukan.', 'hcisysq'));
    }
    $task = $tasks[$id];
    if (!isset($task['assignments'][$nip])) {
      return new \WP_Error('not_found', __('Pegawai tidak ditemukan dalam tugas ini.', 'hcisysq'));
    }
    $status = $status === 'completed' ? 'completed' : 'pending';
    $now = current_time('mysql');
    $task['assignments'][$nip]['status'] = $status;
    $task['assignments'][$nip]['updated_at'] = $now;
    $task['assignments'][$nip]['completed_at'] = $status === 'completed' ? $now : null;

    if ($status === 'pending') {
      if (($task['status'] ?? 'published') === 'completed') {
        $task['status'] = 'published';
        $task['completed_at'] = null;
      }
    } else {
      $allCompleted = true;
      foreach ($task['assignments'] as $assignment) {
        if (($assignment['status'] ?? 'pending') !== 'completed') {
          $allCompleted = false;
          break;
        }
      }
      if ($allCompleted) {
        $task['status'] = 'completed';
        $task['completed_at'] = $now;
      }
    }

    $task['updated_at'] = $now;
    $tasks[$id] = $task;
    self::save_all($tasks);
    return $task;
  }

  private static function format_deadline_display($deadline){
    if (!$deadline) return '';
    try {
      $date = new \DateTimeImmutable($deadline);
    } catch (\Exception $e) {
      return $deadline;
    }

    if (class_exists('IntlDateFormatter')) {
      $formatter = new \IntlDateFormatter(
        'id_ID',
        \IntlDateFormatter::LONG,
        \IntlDateFormatter::NONE,
        'Asia/Jakarta',
        \IntlDateFormatter::GREGORIAN,
        'd MMMM yyyy'
      );
      $formatted = $formatter->format($date);
      if ($formatted !== false) {
        return $formatted;
      }
    }

    if (function_exists('wp_date')) {
      return wp_date('d F Y', $date->getTimestamp());
    }

    return $date->format('d F Y');
  }

  private static function build_history_url(array $task){
    $date = $task['date'] ?? substr($task['created_at'] ?? '', 0, 10);
    $sequence = (int)($task['sequence'] ?? 1);
    if (!$date || !$sequence) {
      return home_url('/');
    }
    return home_url(sprintf('/tugas/%s/%d/', rawurlencode($date), $sequence));
  }

  public static function get_admin_bootstrap(){
    $tasks = self::load_all();
    $units = self::get_unit_directory();
    $employees = self::get_employee_directory();

    $list = array_values(array_map(function($task){
      $status = $task['status'] ?? 'published';
      $assignments = isset($task['assignments']) && is_array($task['assignments']) ? $task['assignments'] : [];
      $totalAssignments = count($assignments);
      $completedAssignments = 0;
      foreach ($assignments as $assignment) {
        if (($assignment['status'] ?? 'pending') === 'completed') {
          $completedAssignments++;
        }
      }
      $deadlineDisplay = self::format_deadline_display($task['deadline'] ?? '');
      return [
        'id' => $task['id'],
        'title' => $task['title'] ?? '',
        'description' => $task['description'] ?? '',
        'deadline' => $task['deadline'] ?? '',
        'deadline_display' => $deadlineDisplay,
        'status' => $status,
        'archived' => $status === 'archived',
        'completed' => $status === 'completed',
        'total_assignments' => $totalAssignments,
        'completed_assignments' => $completedAssignments,
        'units' => array_map(function($unit){ return $unit; }, $task['units'] ?? []),
        'employees' => array_values(array_keys($assignments)),
        'link_label' => $task['link_label'] ?? '',
        'link_url' => $task['link_url'] ?? '',
        'history_url' => self::build_history_url($task),
        'created_at' => $task['created_at'] ?? '',
        'updated_at' => $task['updated_at'] ?? '',
      ];
    }, array_values($tasks)));

    return [
      'units' => $units,
      'employees' => $employees,
      'tasks' => $list,
    ];
  }

  public static function get_employee_tasks($nip){
    $nip = trim((string)$nip);
    if ($nip === '') {
      return ['items' => [], 'pending' => 0];
    }
    $tasks = self::load_all();
    $items = [];
    $pending = 0;

    foreach ($tasks as $task) {
      if (($task['status'] ?? 'published') === 'archived') {
        continue;
      }
      $assignments = isset($task['assignments']) && is_array($task['assignments']) ? $task['assignments'] : [];
      if (!isset($assignments[$nip])) {
        continue;
      }
      $assignment = $assignments[$nip];
      $status = $assignment['status'] ?? 'pending';
      if ($status !== 'completed') {
        $pending++;
      }
      $items[] = [
        'task'        => $task['title'] ?? '',
        'description' => $task['description'] ?? '',
        'deadline'    => $task['deadline'] ?? '',
        'deadline_display' => self::format_deadline_display($task['deadline'] ?? ''),
        'link_url'    => $task['link_url'] ?? '',
        'link_label'  => $task['link_label'] ?? '',
        'status'      => $status,
        'completed_at'=> $assignment['completed_at'] ?? '',
        'task_id'     => $task['id'],
      ];
    }

    usort($items, function($a, $b){
      $deadlineA = $a['deadline'] ?? '';
      $deadlineB = $b['deadline'] ?? '';
      return strcmp($deadlineA, $deadlineB);
    });

    return ['items' => $items, 'pending' => $pending];
  }

  public static function get_task_by_slug($date, $sequence){
    $tasks = self::load_all();
    foreach ($tasks as $task) {
      if (($task['date'] ?? '') === $date && (int)($task['sequence'] ?? 0) === (int)$sequence) {
        return $task;
      }
    }
    return null;
  }

  private static function is_history_request(){
    $date = get_query_var(self::QUERY_VAR_DATE);
    $index = get_query_var(self::QUERY_VAR_INDEX);
    return !empty($date) && !empty($index);
  }

  public static function maybe_use_history_template($template){
    if (!self::is_history_request()) {
      return $template;
    }

    $date = sanitize_text_field(get_query_var(self::QUERY_VAR_DATE));
    $index = (int) get_query_var(self::QUERY_VAR_INDEX);
    $task = self::get_task_by_slug($date, $index);
    if (!$task) {
      return $template;
    }

    $admin = Auth::current_admin();
    if (!$admin && !current_user_can('manage_options')) {
      wp_safe_redirect(home_url('/' . trim(HCISYSQ_LOGIN_SLUG, '/') . '/'));
      exit;
    }

    $context = self::build_history_context($task);
    set_query_var('hcisysq_task_context', $context);
    return HCISYSQ_DIR . 'templates/task-history.php';
  }

  public static function build_history_context(array $task){
    $employees = self::get_employee_directory();
    $employeeMap = [];
    foreach ($employees as $item) {
      $employeeMap[$item['nip']] = $item;
    }
    $unitDirectory = self::get_unit_directory();
    $unitMap = [];
    foreach ($unitDirectory as $unit) {
      $unitMap[$unit['id']] = $unit['label'];
    }
    $assignments = isset($task['assignments']) && is_array($task['assignments']) ? $task['assignments'] : [];
    $assignmentRows = [];
    $completedAssignments = 0;
    foreach ($assignments as $nip => $assignment) {
      $info = $employeeMap[$nip] ?? ['nama' => '-', 'unit' => '-'];
      $status = $assignment['status'] ?? 'pending';
      if ($status === 'completed') {
        $completedAssignments++;
      }
      $assignmentRows[] = [
        'nip' => $nip,
        'nama' => $info['nama'],
        'unit' => $info['unit'],
        'status' => $status,
        'completed_at' => $assignment['completed_at'] ?? '',
      ];
    }
    usort($assignmentRows, function($a, $b){
      return strcmp($a['nama'], $b['nama']);
    });

    $unitLabels = [];
    foreach ($task['units'] ?? [] as $unitId) {
      $unitId = (string) $unitId;
      $slug = self::slugify($unitId);
      if (isset($unitMap[$unitId])) {
        $unitLabels[] = $unitMap[$unitId];
      } elseif (isset($unitMap[$slug])) {
        $unitLabels[] = $unitMap[$slug];
      } else {
        $unitLabels[] = $unitId;
      }
    }

    return [
      'task' => $task,
      'assignments' => $assignmentRows,
      'history_url' => self::build_history_url($task),
      'deadline_display' => self::format_deadline_display($task['deadline'] ?? ''),
      'unit_labels' => $unitLabels,
      'total_assignments' => count($assignmentRows),
      'completed_assignments' => $completedAssignments,
    ];
  }

  public static function handle_history_actions(){
    if (!self::is_history_request()) {
      return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      return;
    }

    $admin = Auth::current_admin();
    if (!$admin && !current_user_can('manage_options')) {
      wp_die(__('Anda tidak memiliki akses ke halaman ini.', 'hcisysq'));
    }

    $date = sanitize_text_field(get_query_var(self::QUERY_VAR_DATE));
    $index = (int) get_query_var(self::QUERY_VAR_INDEX);
    $task = self::get_task_by_slug($date, $index);
    if (!$task) {
      return;
    }

    check_admin_referer('hcisysq_task_action_' . $task['id']);

    $action = sanitize_text_field($_POST['task_action'] ?? '');
    $redirect = self::build_history_url($task);
    $message = '';
    $error = false;

    if ($action === 'update') {
      $data = [
        'title' => $_POST['title'] ?? '',
        'description' => wp_unslash($_POST['description'] ?? ''),
        'deadline' => $_POST['deadline'] ?? '',
        'link_label' => $_POST['link_label'] ?? '',
        'link_url' => $_POST['link_url'] ?? '',
        'units' => $task['units'] ?? [],
        'employees' => $task['employees'] ?? array_keys($task['assignments'] ?? []),
      ];
      $result = self::update($task['id'], $data);
      if (is_wp_error($result)) {
        $message = $result->get_error_message();
        $error = true;
      } else {
        $message = __('Tugas diperbarui.', 'hcisysq');
      }
    } elseif ($action === 'archive' || $action === 'publish' || $action === 'complete') {
      $status = $action === 'archive' ? 'archived' : ($action === 'complete' ? 'completed' : 'published');
      $result = self::set_status($task['id'], $status);
      if (is_wp_error($result)) {
        $message = $result->get_error_message();
        $error = true;
      } else {
        $message = __('Status tugas diperbarui.', 'hcisysq');
      }
    } elseif ($action === 'delete') {
      self::delete($task['id']);
      wp_safe_redirect(add_query_arg(['task_deleted' => 1], home_url('/' . trim(HCISYSQ_DASHBOARD_SLUG, '/') . '/?view=tugas')));
      exit;
    } elseif ($action === 'assignment_complete' || $action === 'assignment_pending') {
      $nip = sanitize_text_field($_POST['assignment_nip'] ?? '');
      if ($nip !== '') {
        $status = $action === 'assignment_complete' ? 'completed' : 'pending';
        $result = self::mark_assignment($task['id'], $nip, $status);
        if (is_wp_error($result)) {
          $message = $result->get_error_message();
          $error = true;
        } else {
          $message = __('Status pegawai diperbarui.', 'hcisysq');
        }
      }
    }

    $args = [];
    if ($message !== '') {
      $args['task_msg'] = $message;
      if ($error) {
        $args['task_error'] = 1;
      }
    }
    wp_safe_redirect(add_query_arg($args, $redirect));
    exit;
  }
}
