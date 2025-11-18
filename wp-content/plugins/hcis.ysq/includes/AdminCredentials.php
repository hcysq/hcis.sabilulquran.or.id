<?php
namespace HCISYSQ;

use WP_Error;

if (!defined('ABSPATH')) exit;

class AdminCredentials {
  public const OPTION = 'hcisysq_admin_accounts';
  public const MAX_ACCOUNTS = 3;

  public static function init() {
    add_action('init', [__CLASS__, 'migrate_legacy_settings'], 1);
  }

  public static function migrate_legacy_settings() {
    $stored = get_option(self::OPTION, null);
    if (is_array($stored) && !empty($stored)) {
      return;
    }

    $legacy = get_option('hcisysq_admin_settings', []);
    $legacyUsername = isset($legacy['username']) ? sanitize_user($legacy['username'], true) : '';
    $legacyDisplay = isset($legacy['display_name']) ? sanitize_text_field($legacy['display_name']) : '';
    $legacyHash    = isset($legacy['password_hash']) ? trim((string) $legacy['password_hash']) : '';

    $fallbackUsername = $legacyUsername !== '' ? $legacyUsername : Auth::DEFAULT_ADMIN_USERNAME;
    $fallbackDisplay  = $legacyDisplay !== '' ? $legacyDisplay : Auth::DEFAULT_ADMIN_DISPLAY;
    $fallbackHash     = $legacyHash !== '' ? $legacyHash : Auth::DEFAULT_ADMIN_HASH;

    $legacyPhone = get_option('hcisysq_admin_wa', '');
    if ($legacyPhone === '' && isset($legacy['wa'])) {
      $legacyPhone = $legacy['wa'];
    }

    if ($fallbackUsername === '' && $fallbackHash === '') {
      $legacyUsername = sanitize_user(get_option('ysq_admin_username', ''), true);
      $legacyHash = trim((string) get_option('ysq_admin_password_hash', ''));
      if ($legacyUsername !== '') {
        $fallbackUsername = $legacyUsername;
      }
      if ($legacyHash !== '') {
        $fallbackHash = $legacyHash;
      }
    }

    $account = [
      'id'            => wp_generate_uuid4(),
      'username'      => $fallbackUsername,
      'display_name'  => $fallbackDisplay,
      'password_hash' => $fallbackHash,
      'whatsapp'      => Auth::norm_phone($legacyPhone),
      'created_at'    => time(),
      'updated_at'    => time(),
    ];

    update_option(self::OPTION, [$account], false);
    delete_option('hcisysq_admin_settings');
    delete_option('hcisysq_admin_wa');
    delete_option('ysq_admin_username');
    delete_option('ysq_admin_password_hash');
  }

  public static function get_accounts(): array {
    $stored = get_option(self::OPTION, []);
    if (!is_array($stored)) {
      $stored = [];
    }

    $accounts = [];
    foreach ($stored as $account) {
      if (!is_array($account)) {
        continue;
      }
      $accounts[] = self::sanitize_account($account);
    }

    return array_slice($accounts, 0, self::MAX_ACCOUNTS);
  }

  public static function get_primary_account(): ?array {
    $accounts = self::get_accounts();
    return $accounts[0] ?? null;
  }

  public static function get_whatsapp_numbers(): array {
    $accounts = self::get_accounts();
    $numbers = [];
    foreach ($accounts as $account) {
      if (!empty($account['whatsapp'])) {
        $numbers[] = $account['whatsapp'];
      }
    }
    return array_values(array_unique($numbers));
  }

  public static function get_primary_whatsapp(): string {
    $numbers = self::get_whatsapp_numbers();
    return $numbers[0] ?? '';
  }

  public static function add_account(array $data, string $plainPassword) {
    $accounts = self::get_accounts();
    if (count($accounts) >= self::MAX_ACCOUNTS) {
      return new WP_Error('hcis_admin_limit', sprintf(__('Maksimal %d akun administrator.', 'hcis-ysq'), self::MAX_ACCOUNTS));
    }

    $username = sanitize_user($data['username'] ?? '', true);
    if ($username === '') {
      return new WP_Error('hcis_admin_username', __('Username administrator wajib diisi.', 'hcis-ysq'));
    }

    foreach ($accounts as $account) {
      if (strcasecmp($account['username'], $username) === 0) {
        return new WP_Error('hcis_admin_exists', __('Username administrator sudah digunakan.', 'hcis-ysq'));
      }
    }

    $account = [
      'id'            => wp_generate_uuid4(),
      'username'      => $username,
      'display_name'  => sanitize_text_field($data['display_name'] ?? $username),
      'password_hash' => password_hash($plainPassword, PASSWORD_DEFAULT),
      'whatsapp'      => Auth::norm_phone($data['whatsapp'] ?? ''),
      'created_at'    => time(),
      'updated_at'    => time(),
    ];

    $accounts[] = $account;
    $saved = self::save_accounts($accounts, [$account['id'] => $plainPassword]);
    return self::find_account_by_id($account['id'], $saved);
  }

  public static function update_account(string $id, array $data, ?string $plainPassword = null) {
    $accounts = self::get_accounts();
    $index = self::find_account_index($id, $accounts);
    if ($index === null) {
      return new WP_Error('hcis_admin_not_found', __('Akun administrator tidak ditemukan.', 'hcis-ysq'));
    }

    $account = $accounts[$index];
    $username = isset($data['username']) ? sanitize_user($data['username'], true) : $account['username'];
    if ($username === '') {
      return new WP_Error('hcis_admin_username', __('Username administrator wajib diisi.', 'hcis-ysq'));
    }

    foreach ($accounts as $i => $existing) {
      if ($i === $index) {
        continue;
      }
      if (strcasecmp($existing['username'], $username) === 0) {
        return new WP_Error('hcis_admin_exists', __('Username administrator sudah digunakan.', 'hcis-ysq'));
      }
    }

    $accounts[$index]['username'] = $username;
    if (isset($data['display_name'])) {
      $accounts[$index]['display_name'] = sanitize_text_field($data['display_name']);
    }
    if (array_key_exists('whatsapp', $data)) {
      $accounts[$index]['whatsapp'] = Auth::norm_phone($data['whatsapp']);
    }
    if ($plainPassword !== null && $plainPassword !== '') {
      $accounts[$index]['password_hash'] = password_hash($plainPassword, PASSWORD_DEFAULT);
    }
    $accounts[$index]['updated_at'] = time();

    $plainMap = [];
    if ($plainPassword !== null && $plainPassword !== '') {
      $plainMap[$accounts[$index]['id']] = $plainPassword;
    }

    $saved = self::save_accounts($accounts, $plainMap);
    return self::find_account_by_id($accounts[$index]['id'], $saved);
  }

  public static function delete_account(string $id) {
    $accounts = self::get_accounts();
    $index = self::find_account_index($id, $accounts);
    if ($index === null) {
      return new WP_Error('hcis_admin_not_found', __('Akun administrator tidak ditemukan.', 'hcis-ysq'));
    }

    array_splice($accounts, $index, 1);
    self::save_accounts($accounts);
    return true;
  }

  public static function find_account_by_username(?string $username, ?array $accounts = null): ?array {
    if ($username === null || $username === '') {
      return null;
    }
    $accounts = $accounts ?? self::get_accounts();
    foreach ($accounts as $account) {
      if (strcasecmp($account['username'], $username) === 0) {
        return $account;
      }
    }
    return null;
  }

  public static function find_account_by_id(string $id, ?array $accounts = null): ?array {
    $accounts = $accounts ?? self::get_accounts();
    foreach ($accounts as $account) {
      if (!empty($account['id']) && hash_equals((string) $account['id'], (string) $id)) {
        return $account;
      }
    }
    return null;
  }

  public static function has_whatsapp_contact(): bool {
    return !empty(self::get_whatsapp_numbers());
  }

  private static function save_accounts(array $accounts, array $plainPasswords = []): array {
    $normalized = [];
    foreach ($accounts as $account) {
      $normalized[] = self::sanitize_account($account);
    }
    $normalized = array_slice($normalized, 0, self::MAX_ACCOUNTS);

    update_option(self::OPTION, $normalized, false);
    $synced = self::sync_wordpress_users($normalized, $plainPasswords);
    if ($synced !== $normalized) {
      update_option(self::OPTION, $synced, false);
    }
    return $synced;
  }

  private static function sanitize_account(array $account): array {
    $username = sanitize_user($account['username'] ?? '', true);
    if ($username === '') {
      $username = Auth::DEFAULT_ADMIN_USERNAME;
    }

    $display = sanitize_text_field($account['display_name'] ?? $username);
    if ($display === '') {
      $display = $username;
    }

    $hash = isset($account['password_hash']) ? trim((string) $account['password_hash']) : '';
    if ($hash === '') {
      $hash = Auth::DEFAULT_ADMIN_HASH;
    }

    $id = isset($account['id']) && $account['id'] !== '' ? (string) $account['id'] : wp_generate_uuid4();

    return [
      'id'            => $id,
      'username'      => $username,
      'display_name'  => $display,
      'password_hash' => $hash,
      'whatsapp'      => Auth::norm_phone($account['whatsapp'] ?? ''),
      'user_id'       => isset($account['user_id']) ? intval($account['user_id']) : 0,
      'created_at'    => isset($account['created_at']) ? intval($account['created_at']) : time(),
      'updated_at'    => isset($account['updated_at']) ? intval($account['updated_at']) : time(),
    ];
  }

  private static function sync_wordpress_users(array $accounts, array $plainPasswords = []): array {
    if (!function_exists('wp_insert_user')) {
      require_once ABSPATH . 'wp-admin/includes/user.php';
    }

    $usernames = array_map(function ($account) {
      return $account['username'];
    }, $accounts);

    $existingAdmins = get_users([
      'role__in' => ['hcis_admin'],
      'fields'   => ['ID', 'user_login', 'roles'],
      'number'   => -1,
    ]);

    foreach ($existingAdmins as $user) {
      if (in_array('administrator', (array) $user->roles, true)) {
        continue;
      }
      $login = $user->user_login ?? '';
      $found = false;
      foreach ($usernames as $username) {
        if (strcasecmp($username, $login) === 0) {
          $found = true;
          break;
        }
      }
      if (!$found) {
        wp_delete_user($user->ID);
      }
    }

    $domain = parse_url(home_url(), PHP_URL_HOST);
    if (!$domain) {
      $domain = 'example.com';
    }

    foreach ($accounts as $index => $account) {
      $existingUser = null;
      if (!empty($account['user_id'])) {
        $candidate = get_user_by('id', (int) $account['user_id']);
        if ($candidate && $candidate->exists()) {
          $existingUser = $candidate;
        }
      }

      if (!$existingUser) {
        $existingUser = get_user_by('login', $account['username']);
      }

      $needsNewAccount = !$existingUser;
      if ($existingUser && strcasecmp($existingUser->user_login, $account['username']) !== 0) {
        if (!in_array('administrator', (array) $existingUser->roles, true)) {
          wp_delete_user($existingUser->ID);
          $existingUser = null;
          $needsNewAccount = true;
        }
      }

      if ($needsNewAccount) {
        $email = self::generate_placeholder_email($account['username'], $domain);
        $password = $plainPasswords[$account['id']] ?? wp_generate_password(20, true, true);
        $user_id = wp_insert_user([
          'user_login'   => $account['username'],
          'user_pass'    => $password,
          'display_name' => $account['display_name'],
          'role'         => 'hcis_admin',
          'user_email'   => $email,
        ]);
        if (!is_wp_error($user_id)) {
          $accounts[$index]['user_id'] = (int) $user_id;
          update_user_meta($user_id, 'hcisysq_admin_whatsapp', $account['whatsapp']);
        }
        continue;
      }

      $userdata = [
        'ID'           => $existingUser->ID,
        'display_name' => $account['display_name'],
      ];
      if (!empty($plainPasswords[$account['id']])) {
        $userdata['user_pass'] = $plainPasswords[$account['id']];
      }

      wp_update_user($userdata);
      if (!in_array('hcis_admin', (array) $existingUser->roles, true)) {
        $existingUser->add_role('hcis_admin');
      }
      $accounts[$index]['user_id'] = (int) $existingUser->ID;
      update_user_meta($existingUser->ID, 'hcisysq_admin_whatsapp', $account['whatsapp']);
    }

    return $accounts;
  }

  private static function generate_placeholder_email(string $username, string $domain): string {
    $base = sanitize_title($username);
    if ($base === '') {
      $base = 'admin';
    }
    $email = $base . '@' . $domain;
    $counter = 1;
    while (email_exists($email)) {
      $email = $base . $counter . '@' . $domain;
      $counter++;
    }
    return $email;
  }

  private static function find_account_index(string $id, array $accounts): ?int {
    foreach ($accounts as $index => $account) {
      if (!empty($account['id']) && hash_equals((string) $account['id'], (string) $id)) {
        return $index;
      }
    }
    return null;
  }
}
