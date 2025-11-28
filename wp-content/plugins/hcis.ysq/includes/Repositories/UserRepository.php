<?php
namespace HCISYSQ\Repositories;

if (!defined('ABSPATH')) exit;

use HCISYSQ\SheetCache;
use HCISYSQ\GoogleSheetsAPI;
use HCISYSQ\GoogleSheetSettings;

class UserRepository extends AbstractSheetRepository {

  protected $tab = 'users';
  protected $columns = [
    'nip' => 'NIP',
    'nama' => 'Nama',
    // Single plaintext password column; no password_hash column is defined or persisted.
    'password' => 'Password',
    'phone' => 'No HP',
    'email' => 'Email',
    'nik' => 'NIK',
    'unit' => 'Unit',
    'jabatan' => 'Jabatan',
    'tempat_lahir' => 'Tempat Lahir',
    'tanggal_lahir' => 'Tanggal Lahir',
    'alamat_ktp' => 'Alamat KTP',
    'desa' => 'Desa/Kelurahan',
    'kecamatan' => 'Kecamatan',
    'kota' => 'Kota/Kabupaten',
    'kode_pos' => 'Kode Pos',
    'tmt' => 'TMT',
    'periode' => 'Periode',
    'gaji_pokok' => 'Gaji Pokok',
    'tunjangan' => 'Tunjangan',
    'potongan' => 'Potongan',
    'take_home_pay' => 'Take Home Pay',
    'status' => 'Status',
  ];

  public function __construct(?SheetCache $cache = null) {
    parent::__construct($cache);

    $config = GoogleSheetSettings::get_setup_key_config();
    $configMap = [
      'nip' => 'user_nip',
      'nama' => 'user_name',
      // Only the plaintext password column is supported in the sheet mapping.
      'password' => 'user_password',
      'phone' => 'user_phone',
      'email' => 'user_email',
      'nik' => 'user_nik',
      'unit' => 'user_unit',
      'jabatan' => 'user_position',
      'tempat_lahir' => 'user_birth_place',
      'tanggal_lahir' => 'user_birth_date',
      'alamat_ktp' => 'user_address',
      'desa' => 'user_village',
      'kecamatan' => 'user_district',
      'kota' => 'user_city',
      'kode_pos' => 'user_postal_code',
      'tmt' => 'user_join_date',
      'periode' => 'payroll_period',
      'gaji_pokok' => 'payroll_basic_salary',
      'tunjangan' => 'payroll_allowance',
      'potongan' => 'payroll_deduction',
      'take_home_pay' => 'payroll_take_home_pay',
      'status' => 'payroll_status',
    ];

    foreach ($configMap as $columnKey => $configKey) {
      if (!empty($config[$configKey]['header'])) {
        $this->columns[$columnKey] = $config[$configKey]['header'];
      }
    }
  }

  public function create($user_id) {
    $user = get_userdata($user_id);
    if (!$user) {
      hcisysq_log('UserRepository::create - User not found: ' . $user_id, 'WARNING');
      return false;
    }

    $nip = get_user_meta($user_id, 'nip', true);
    if (!$nip) {
      hcisysq_log('UserRepository::create - NIP not found: ' . $user_id, 'WARNING');
      return false;
    }

    $row = $this->buildSheetRow($user_id, $user, $nip);
    return $this->append($row);
  }

  public function update($user_id) {
    $user = get_userdata($user_id);
    if (!$user) {
      hcisysq_log('UserRepository::update - User not found: ' . $user_id, 'WARNING');
      return false;
    }

    $nip = get_user_meta($user_id, 'nip', true);
    if (!$nip) {
      hcisysq_log('UserRepository::update - NIP not found: ' . $user_id, 'WARNING');
      return false;
    }

    $row = $this->buildSheetRow($user_id, $user, $nip);
    return $this->updateByPrimary($row);
  }

  public function delete($user_id) {
    $nip = get_user_meta($user_id, 'nip', true);
    if (!$nip) {
      return false;
    }
    return $this->deleteByPrimary($nip);
  }

  public function getByNIP($nip) {
    return $this->find($nip);
  }

  public function getAll() {
    return $this->all();
  }

  public function findMissingPasswordRows(int $limit = 10, bool $bypassCache = false): array {
    $rows = $this->all($bypassCache);
    $missing = [];

    foreach ($rows as $row) {
      // Readiness only depends on the plaintext password column; any legacy hash column is ignored.
      $password = trim((string) ($row['password'] ?? ''));
      if ($password !== '') {
        continue;
      }

      $missing[] = [
        'nip' => $row['nip'] ?? '',
        'row' => isset($row['row_index']) ? ((int) $row['row_index']) + 1 : null,
      ];

      if (count($missing) >= $limit) {
        break;
      }
    }

    return $missing;
  }

  public function setPassword(string $nip, string $password): bool {
    return $this->persistPlainPassword($nip, $password);
  }

  /**
   * @deprecated Use setPassword() instead. Kept for backwards compatibility.
   */
  public function setPasswordHash(string $nip, string $password): bool {
    // Alias maintained for compatibility; writes the plaintext password column.
    return $this->persistPlainPassword($nip, $password);
  }

  public function generateAndPersistPassword(string $nip, ?string $password = null): array {
    $password = $password ?: wp_generate_password(12, false);

    return [
      'password' => $password,
      'updated' => $this->persistPlainPassword($nip, $password),
    ];
  }

  private function persistPlainPassword(string $nip, string $password): bool {
    $nip = trim($nip);
    $password = trim($password);

    if ($nip === '' || $password === '') {
      return false;
    }

    $rows = $this->all(true);

    foreach ($rows as $row) {
      if (($row['nip'] ?? '') !== $nip) {
        continue;
      }

      $row['password'] = $password;
      return $this->updateByPrimary($row);
    }

    return false;
  }

  public function syncFromWordPress() {
    $wp_users = get_users(['number' => -1]);
    $synced = 0;

    foreach ($wp_users as $wp_user) {
      $nip = get_user_meta($wp_user->ID, 'nip', true);
      if (!$nip) {
        continue;
      }

      $sheet_user = $this->getByNIP($nip);
      if (empty($sheet_user)) {
        if ($this->create($wp_user->ID)) {
          $synced++;
        }
      } else {
        if ($this->hasChanges($wp_user, $sheet_user)) {
          if ($this->update($wp_user->ID)) {
            $synced++;
          }
        }
      }
    }

    hcisysq_log('UserRepository::syncFromWordPress - Synced ' . $synced . ' users');
    return $synced;
  }

  public function syncToWordPress(array $rows): array {
    global $wpdb;
    $table = $wpdb->prefix . 'hcisysq_users';
    $table_exists = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table);
    if (!$table_exists) {
      hcisysq_log('UserRepository::syncToWordPress - legacy table missing, skipping sync', 'warning');
      return 0;
    }
    $synced = 0;
    $failed = 0;

    foreach ($rows as $row) {
      $nip = $row['nip'] ?? '';
      if ($nip === '') {
        continue;
      }
      $data = [
        'nip' => $nip,
        'nama' => $row['nama'] ?? '',
        'jabatan' => '',
        'unit' => '',
        'no_hp' => $row['phone'] ?? '',
        'password' => $row['password'] ?? '',
        'updated_at' => current_time('mysql'),
      ];
      $formats = array_fill(0, count($data), '%s');
      $upserted = $wpdb->replace($table, $data, $formats);
      if ($upserted === false) {
        $failed++;
        hcisysq_log(
          sprintf(
            'UserRepository::syncToWordPress - Failed to upsert %s: %s',
            $nip,
            $wpdb->last_error ?: 'unknown error'
          ),
          'ERROR'
        );
        continue;
      }

      $users = get_users([
        'meta_key' => 'nip',
        'meta_value' => $nip,
        'number' => 1,
        'fields' => 'all',
      ]);
      if (!empty($users)) {
        $wpUser = $users[0];
        $update_result = wp_update_user([
          'ID' => $wpUser->ID,
          'display_name' => $row['nama'] ?? $wpUser->display_name,
        ]);
        if (is_wp_error($update_result)) {
          $failed++;
          hcisysq_log(
            sprintf(
              'UserRepository::syncToWordPress - Failed to update WordPress profile for %s: %s',
              $nip,
              $update_result->get_error_message()
            ),
            'ERROR'
          );
          continue;
        }
        update_user_meta($wpUser->ID, 'phone', $row['phone'] ?? '');
        update_user_meta($wpUser->ID, 'nip', $nip);
        if (!empty($row['email'])) {
          update_user_meta($wpUser->ID, 'email', $row['email']);
        }
        hcisysq_log(
          sprintf(
            'UserRepository::syncToWordPress - Updated WordPress profile for %s (user_id: %d)',
            $nip,
            $wpUser->ID
          ),
          'INFO'
        );
        $synced++;
      }
    }

    return [
      'synced' => $synced,
      'failed' => $failed,
    ];
  }

  private function buildSheetRow($user_id, $user, $nip): array {
    return [
      'nip' => $nip,
      'nama' => $user->display_name,
      // WordPress already stores the bcrypt hash in user_pass; persist it as-is.
      'password' => $user->user_pass,
      'phone' => get_user_meta($user_id, 'phone', true) ?: '',
      'email' => $user->user_email,
    ];
  }

  private function hasChanges($wp_user, $sheet_user) {
    $current_name = $wp_user->display_name;
    $current_phone = get_user_meta($wp_user->ID, 'phone', true) ?: '';
    return $current_name !== ($sheet_user['nama'] ?? '') || $current_phone !== ($sheet_user['phone'] ?? '');
  }
}
