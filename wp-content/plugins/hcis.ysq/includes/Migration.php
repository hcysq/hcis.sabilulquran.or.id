<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Migration {
  const PAGE_SLUG = 'hcisysq-migration';
  const LOG_TRANSIENT = 'hcisysq_migration_log';
  const LOG_TTL = 15 * MINUTE_IN_SECONDS;

  public static function init() {
    add_action('admin_post_hcisysq_start_migration', [__CLASS__, 'handle_migration']);
  }

  public static function render_admin_page() {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to access this page.', 'hcis-ysq'));
    }

    $logs = get_transient(self::LOG_TRANSIENT);
    delete_transient(self::LOG_TRANSIENT);
    ?>
    <div class="wrap">
      <h1><?php esc_html_e('HCIS Data Migration', 'hcis-ysq'); ?></h1>
      <div class="notice notice-warning">
        <p><strong><?php esc_html_e('Penting:', 'hcis-ysq'); ?></strong> <?php esc_html_e('Pastikan melakukan backup database WordPress Anda sebelum menjalankan proses migrasi ini. Proses ini tidak dapat dibatalkan.', 'hcis-ysq'); ?></p>
      </div>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('hcisysq_start_migration'); ?>
        <input type="hidden" name="action" value="hcisysq_start_migration">
        <p>
          <button type="submit" class="button button-primary button-large"><?php esc_html_e('Start Migration', 'hcis-ysq'); ?></button>
        </p>
      </form>
      <h2><?php esc_html_e('Logs', 'hcis-ysq'); ?></h2>
      <textarea readonly rows="15" style="width:100%;max-width:900px;">
<?php
if (!empty($logs) && is_array($logs)) {
  foreach ($logs as $line) {
    echo esc_html($line) . "\n";
  }
}
?>
      </textarea>
    </div>
    <?php
  }

  public static function handle_migration() {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to perform this action.', 'hcis-ysq'));
    }
    check_admin_referer('hcisysq_start_migration');

    $logs = self::run();
    set_transient(self::LOG_TRANSIENT, $logs, self::LOG_TTL);

    wp_safe_redirect(add_query_arg('updated', '1', menu_page_url(self::PAGE_SLUG, false)));
    exit;
  }

  protected static function run() {
    global $wpdb;
    $logs = [];

    $uploads = wp_get_upload_dir();
    $spreadsheet_path = trailingslashit($uploads['basedir']) . 'Masterdata Pangkalan Data Pegawai.xlsx';
    if (!file_exists($spreadsheet_path)) {
      $logs[] = sprintf(__('ERROR: File spreadsheet tidak ditemukan di %s', 'hcis-ysq'), $spreadsheet_path);
      return $logs;
    }

    if (!class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')) {
      $logs[] = __('ERROR: Library PhpSpreadsheet tidak tersedia. Harap install dependensi sebelum menjalankan migrasi.', 'hcis-ysq');
      return $logs;
    }

    try {
      $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($spreadsheet_path);
    } catch (\Throwable $e) {
      $logs[] = sprintf(__('ERROR: Gagal membaca spreadsheet: %s', 'hcis-ysq'), $e->getMessage());
      return $logs;
    }

    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);
    if (empty($rows)) {
      $logs[] = __('Tidak ada data pada spreadsheet.', 'hcis-ysq');
      return $logs;
    }

    // Map header columns
    $header = array_shift($rows);
    $headerMap = [];
    foreach ($header as $key => $value) {
      $sanitized = sanitize_title_with_dashes($value);
      if ($sanitized) {
        $headerMap[$sanitized] = $key;
      }
    }

    $get = function(array $row, $label, $default = '') use ($headerMap) {
      $key = sanitize_title_with_dashes($label);
      if (isset($headerMap[$key])) {
        $col = $headerMap[$key];
        return isset($row[$col]) ? trim((string) $row[$col]) : $default;
      }
      return $default;
    };

    $old_profiles_table = $wpdb->prefix . 'hcis_user_profiles';
    $employees_table = $wpdb->prefix . 'ysq_employees';

    $old_users = [];
    $old_users_table = $wpdb->prefix . 'hcis_users';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $old_users_table)) === $old_users_table) {
      $rows_users = $wpdb->get_results("SELECT * FROM $old_users_table");
      foreach ($rows_users as $user_row) {
        if (!empty($user_row->nip)) {
          $old_users[$user_row->nip] = $user_row;
        }
      }
    }

    $old_profiles = [];
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $old_profiles_table)) === $old_profiles_table) {
      $rows_profiles = $wpdb->get_results("SELECT * FROM $old_profiles_table");
      foreach ($rows_profiles as $profile_row) {
        if (!empty($profile_row->nip)) {
          $old_profiles[$profile_row->nip] = $profile_row;
        }
      }
    }

    foreach ($rows as $row) {
      $migrasi = strtoupper($get($row, 'MIGRASI'));
      if ($migrasi !== 'YA') {
        continue;
      }

      $nip = $get($row, 'NIP');
      if ($nip === '') {
        $logs[] = __('SKIPPED: Baris tanpa NIP diabaikan.', 'hcis-ysq');
        continue;
      }

      $existing_employee = $wpdb->get_var($wpdb->prepare("SELECT id FROM $employees_table WHERE employee_id_number = %s", $nip));
      if ($existing_employee) {
        $logs[] = sprintf(__('SKIPPED: Data pegawai dengan NIP %s sudah ada di tabel ysq_employees.', 'hcis-ysq'), $nip);
        continue;
      }

      if (username_exists($nip) || email_exists($get($row, 'EMAIL'))) {
        $logs[] = sprintf(__('SKIPPED: Pegawai dengan NIP %s sudah ada di WordPress.', 'hcis-ysq'), $nip);
        continue;
      }

      $password = $get($row, 'NO HP');
      $email = $get($row, 'EMAIL');
      $display_name = $get($row, 'NAMA LENGKAP', $get($row, 'NAMA'));

      if ($email === '' || !is_email($email)) {
        $logs[] = sprintf(__('ERROR: Email tidak valid untuk NIP %s. Data dilewati.', 'hcis-ysq'), $nip);
        continue;
      }

      $user_id = wp_insert_user([
        'user_login' => $nip,
        'user_pass'  => $password ?: wp_generate_password(),
        'user_email' => $email,
        'display_name' => $display_name,
        'role' => 'subscriber',
      ]);

      if (is_wp_error($user_id)) {
        $logs[] = sprintf(__('ERROR: Gagal membuat user untuk NIP %s: %s', 'hcis-ysq'), $nip, $user_id->get_error_message());
        continue;
      }

      update_user_meta($user_id, 'ysq_force_password_change', 1);

      $profile = null;
      if (isset($old_profiles[$nip])) {
        $profile = $old_profiles[$nip];
      }
      $legacy_user = $old_users[$nip] ?? null;

      $employee_data = [
        'wp_user_id' => $user_id,
        'full_name'  => $display_name,
        'employee_id_number' => $nip,
        'email' => $email,
        'phone_number' => $get($row, 'NO HP'),
        'birth_place' => $get($row, 'TEMPAT LAHIR'),
        'birth_date'  => $get($row, 'TANGGAL LAHIR'),
        'gender'      => $get($row, 'JENIS KELAMIN'),
        'marital_status' => $get($row, 'STATUS PERNIKAHAN'),
        'address'     => $get($row, 'ALAMAT'),
        'join_date'   => $get($row, 'TANGGAL MASUK', current_time('mysql')),
        'status'      => 'Aktif',
        'bank_name'   => $get($row, 'BANK'),
        'bank_account_number' => $get($row, 'NOMOR REKENING'),
        'npwp_number' => $get($row, 'NPWP'),
      ];

      if ($profile) {
        if (!empty($profile->alamat)) {
          $employee_data['address'] = $profile->alamat;
        }
        if (!empty($profile->hp)) {
          $employee_data['phone_number'] = $profile->hp;
        }
      }

      if ($legacy_user) {
        if (!empty($legacy_user->nama)) {
          $employee_data['full_name'] = $legacy_user->nama;
        }
        if (!empty($legacy_user->no_hp) && empty($employee_data['phone_number'])) {
          $employee_data['phone_number'] = $legacy_user->no_hp;
        }
        if (!empty($legacy_user->jabatan)) {
          $employee_data['marital_status'] = $employee_data['marital_status'] ?: $legacy_user->jabatan;
        }
      }

      $employee_formats = ['%d','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s'];

      $result = $wpdb->insert($employees_table, $employee_data, $employee_formats);
      if (false === $result) {
        $logs[] = sprintf(__('ERROR: Gagal menyimpan data pegawai untuk NIP %s: %s', 'hcis-ysq'), $nip, $wpdb->last_error);
        wp_delete_user($user_id, true);
        continue;
      }

      $logs[] = sprintf(__('SUCCESS: Migrated %1$s (NIP: %2$s)', 'hcis-ysq'), $display_name, $nip);
    }

    if (empty($logs)) {
      $logs[] = __('Tidak ada baris yang memenuhi kriteria migrasi.', 'hcis-ysq');
    }

    return $logs;
  }
}
