<?php
namespace HCISYSQ\Repositories;

if (!defined('ABSPATH')) exit;

use HCISYSQ\SheetCache;
use HCISYSQ\GoogleSheetsAPI;

class UserRepository extends AbstractSheetRepository {

  protected $tab = 'users';
  protected $columns = [
    'nip' => 'NIP',
    'nama' => 'Nama',
    'password_hash' => 'Password Hash',
    'phone' => 'No HP',
    'email' => 'Email',
    'nik' => 'NIK',
  ];

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
        'password' => $row['password_hash'] ?? '',
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
      'password_hash' => $user->user_pass,
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
