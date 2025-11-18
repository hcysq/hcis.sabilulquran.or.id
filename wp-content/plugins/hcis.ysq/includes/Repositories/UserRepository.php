<?php
namespace HCISYSQ\Repositories;

if (!defined('ABSPATH')) exit;

use HCISYSQ\GoogleSheetsAPI;
use HCISYSQ\SheetCache;
use HCISYSQ\Users;

class UserRepository {

  private $api;
  private $cache;
  private $sheet_id;
  private $gid_users;
  const BATCH_SIZE = 50;

  public function __construct(GoogleSheetsAPI $api, SheetCache $cache = null) {
    $this->api = $api;
    $this->cache = $cache ?? new SheetCache();
    $this->sheet_id = get_option('hcis_google_sheet_id');
    $this->gid_users = get_option('hcis_gid_users');
    $this->api->setSpreadsheetId($this->sheet_id);
  }

  public function create($user_id) {
    $user = get_userdata($user_id);
    if (!$user) {
      hcisysq_log('UserRepository::create - User not found: ' . $user_id, 'WARNING');
      return false;
    }

    $nip = get_user_meta($user_id, 'nip', true);
    if (!$nip) {
      hcisysq_log('UserRepository::create - NIP not found for user: ' . $user_id, 'WARNING');
      return false;
    }

    $row = [
      $nip,
      $user->display_name,
      wp_hash_password($user->user_pass),
      get_user_meta($user_id, 'phone', true) ?: ''
    ];

    $result = $this->api->appendRows('Users!A:E', [$row]);

    if ($result) {
      $this->cache->forget('users_all');
      $this->cache->forget('user_' . $nip);
      Users::flush_cache();
      hcisysq_log('UserRepository::create - Synced: NIP=' . $nip);
    }

    return $result;
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

    $row = [
      $nip,
      $user->display_name,
      wp_hash_password($user->user_pass),
      get_user_meta($user_id, 'phone', true) ?: ''
    ];

    $rows = $this->api->getRows('Users!A:A');
    $row_index = $this->findRowByNIP($rows, $nip);

    if ($row_index === null) {
      return false;
    }

    $range = 'Users!A' . ($row_index + 1) . ':E' . ($row_index + 1);
    $result = $this->api->updateRows($range, [$row]);

    if ($result) {
      $this->cache->forget('users_all');
      $this->cache->forget('user_' . $nip);
      Users::flush_cache();
    }

    return $result;
  }

  public function delete($user_id) {
    $nip = get_user_meta($user_id, 'nip', true);
    if (!$nip) {
      return false;
    }

    $rows = $this->api->getRows('Users!A:A');
    $row_index = $this->findRowByNIP($rows, $nip);

    if ($row_index === null) {
      return false;
    }

    $result = $this->api->deleteRows('Users', $this->gid_users, $row_index, $row_index);

    if ($result) {
      $this->cache->forget('users_all');
      $this->cache->forget('user_' . $nip);
      Users::flush_cache();
    }

    return $result;
  }

  public function getByNIP($nip) {
    $cache_key = 'user_' . $nip;
    $cached = $this->cache->get($cache_key);
    
    if ($cached !== null) {
      return $cached;
    }

    $rows = $this->api->getRows('Users!A:E');
    
    if (empty($rows)) {
      return [];
    }

    foreach ($rows as $index => $row) {
      if (isset($row[0]) && $row[0] === $nip) {
        $data = [
          'row_index' => $index,
          'nip' => $row[0] ?? '',
          'nama' => $row[1] ?? '',
          'password_hash' => $row[2] ?? '',
          'no_hp' => $row[3] ?? ''
        ];

        $this->cache->put($cache_key, $data);
        return $data;
      }
    }

    return [];
  }

  public function getAll() {
    $cache_key = 'users_all';
    $cached = $this->cache->get($cache_key);
    
    if ($cached !== null) {
      return $cached;
    }

    $rows = $this->api->getRows('Users!A:E');
    $users = [];

    if (empty($rows)) {
      $this->cache->put($cache_key, $users);
      return $users;
    }

    foreach ($rows as $index => $row) {
      if (!empty($row[0])) {
        $users[] = [
          'row_index' => $index,
          'nip' => $row[0] ?? '',
          'nama' => $row[1] ?? '',
          'password_hash' => $row[2] ?? '',
          'no_hp' => $row[3] ?? ''
        ];
      }
    }

    $this->cache->put($cache_key, $users);
    return $users;
  }

  private function findRowByNIP($rows, $nip) {
    foreach ($rows as $index => $row) {
      if (isset($row[0]) && $row[0] === $nip) {
        return $index;
      }
    }
    return null;
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

  private function hasChanges($wp_user, $sheet_user) {
    $current_name = $wp_user->display_name;
    $current_phone = get_user_meta($wp_user->ID, 'phone', true) ?: '';

    return $current_name !== $sheet_user['nama'] || $current_phone !== $sheet_user['no_hp'];
  }
}