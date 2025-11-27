<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

use HCISYSQ\Repositories\AdminRepository;

class Auth {
  const ADMIN_OPTION = AdminCredentials::OPTION;
  const DEFAULT_ADMIN_USERNAME = 'administrator';
  const DEFAULT_ADMIN_DISPLAY = 'Administrator';
  const DEFAULT_ADMIN_HASH = '$2y$12$7fBX0IxS.xqxJUVNYKDkEeMvHD8ecsBfSV6zCMf3vYmMAT6Bxfk5e';
  /**
   * Hash bawaan versi lama yang wajib dipaksa diganti.
   *
   * Tambahkan hash historis setelah diverifikasi dari rilis sebelumnya.
   */
  private const LEGACY_ADMIN_HASHES = [];
  private static function determine_cookie_domain(){
    if (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN) {
      return COOKIE_DOMAIN;
    }

    $host = parse_url(home_url(), PHP_URL_HOST);
    if (!$host) return '';

    $host = strtolower(trim($host));
    $host = trim($host, '.');
    if ($host === '') return '';

    $parts = explode('.', $host);
    if (count($parts) === 1) {
      return $host;
    }

    $suffix = implode('.', array_slice($parts, -2));
    $twoLevelTlds = apply_filters('hcisysq_two_level_tlds', [
      'co.id','or.id','ac.id','go.id','sch.id','net.id','web.id','my.id','biz.id','mil.id','ponpes.id'
    ]);

    if (in_array($suffix, $twoLevelTlds, true) && count($parts) >= 3) {
      $domainParts = array_slice($parts, -3);
    } else {
      $domainParts = array_slice($parts, -2);
    }

    $domain = '.' . implode('.', $domainParts);

    return apply_filters('hcisysq_cookie_domain', $domain, $host);
  }

  private static function get_session_token(){
    if (empty($_COOKIE['hcisysq_token'])) return null;
    return sanitize_text_field($_COOKIE['hcisysq_token']);
  }

  private static function store_session(array $payload){
    if (empty($payload['type'])) {
      $payload['type'] = 'user';
    }

    $token = null;

    // Try database storage first
    if (SessionHandler::verify_table_exists()) {
      $token = SessionHandler::create($payload, 12 * HOUR_IN_SECONDS);
      if (!$token) {
        hcisysq_log('Auth::store_session() - SessionHandler::create() failed to persist session', 'error');
        return false;
      }
    } else {
      // Fallback to transient if table doesn't exist (backward compatibility)
      $token = wp_generate_uuid4();
      $stored = set_transient('hcisysq_sess_' . $token, $payload, 12 * HOUR_IN_SECONDS);
      if (!$stored) {
        hcisysq_log('Auth::store_session() - Failed to persist transient fallback session', 'error');
        return false;
      }
      hcisysq_log('SessionHandler table not found, falling back to transient storage');
    }

    $domain = self::determine_cookie_domain();
    $options = [
      'expires'  => time() + (12 * HOUR_IN_SECONDS),
      'path'     => '/',
      'domain'   => $domain,
      'secure'   => is_ssl(),
      'httponly' => true,
      'samesite' => 'Lax'
    ];

    hcisysq_log('Setting session token ' . $token . ' for type ' . ($payload['type'] ?? 'user'));
    setcookie('hcisysq_token', $token, $options);
    return $token;
  }

  public static function update_current_session(array $payload){
    $token = self::get_session_token();
    if (!$token) return false;

    return SessionHandler::update($token, $payload);
  }

  private static function get_session_payload(){
    $token = self::get_session_token();
    if (!$token) return null;

    // Try database first
    if (SessionHandler::verify_table_exists()) {
      $sess = SessionHandler::read($token);
      if ($sess !== false) {
        return $sess;
      }
    }

    // Fallback to transient (backward compatibility)
    $sess = get_transient('hcisysq_sess_' . $token);
    if (!$sess) return null;

    if (is_array($sess)) return $sess;
    if (is_object($sess)) return (array)$sess;
    if (is_string($sess) && trim($sess) !== '') {
      return ['type' => 'user', 'nip' => $sess];
    }
    return null;
  }

  private static function should_force_fresh_user_lookup(string $context): bool {
    $env = getenv('HCISYSQ_FORCE_FRESH_USERS');
    $default = $env === false ? true : $env !== '0';

    return (bool) apply_filters('hcisysq_force_fresh_users_lookup', $default, $context);
  }

  // normalisasi no HP: keep digits only, leading 0/8 -> 62
  public static function norm_phone($s){
    $s = preg_replace('/\D+/', '', strval($s));
    if ($s === '') return '';

    if (strpos($s, '08') === 0) {
      $s = '62' . substr($s, 1);
    } elseif ($s[0] === '8') {
      $s = '62' . $s;
    }

    return $s;
  }

  /** Ambil user by NIP langsung dari Google Sheet menggunakan Repository */
  public static function get_user_by_nip($nip, bool $forceFresh = false){
    $repo = new \HCISYSQ\Repositories\UserRepository();
    $record = $repo->find($nip, $forceFresh);
    $source = 'sheet';

    if (!$record || !is_array($record)) {
      $record = self::find_user_in_local_table($nip);
      $source = 'database';
    }

    if (!$record || !is_array($record)) {
      return null;
    }

    return self::map_record_to_user($record, $source);
  }

  private static function find_user_in_local_table(string $nip): ?array {
    global $wpdb;

    if (!isset($wpdb)) {
      return null;
    }

    $table = $wpdb->prefix . 'hcisysq_users';
    $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));

    if ($table_exists !== $table) {
      return null;
    }

    $row = $wpdb->get_row($wpdb->prepare("SELECT id, nip, nama, jabatan, unit, no_hp, nik, password FROM {$table} WHERE nip = %s LIMIT 1", $nip), ARRAY_A);

    if (!$row) {
      return null;
    }

    $row['_source'] = 'database';

    hcisysq_log('Auth::get_user_by_nip() - Fallback to local table for NIP ' . $nip);

    return $row;
  }

  private static function map_record_to_user(array $record, string $source): \stdClass {
    $user = new \stdClass();
    $user->id = isset($record['row_index']) ? (int)$record['row_index'] : (int)($record['id'] ?? 0); // Use row_index or local id as a unique ID
    $user->nip = $record['nip'] ?? '';
    $user->nama = $record['nama'] ?? '';
    $user->jabatan = $record['jabatan'] ?? ''; // Jabatan might not be in this sheet, but keep for compatibility
    $user->unit = $record['unit'] ?? ''; // Unit might not be in this sheet, but keep for compatibility
    $user->no_hp = $record['phone'] ?? ($record['no_hp'] ?? ''); // Use 'phone' from repository or local 'no_hp'
    $user->password = $record['password_hash'] ?? ($record['password'] ?? ''); // Use 'password_hash' from repository or local 'password'
    $user->nik = $record['nik'] ?? ''; // Add NIK for default password check
    $user->row = isset($record['row_index']) ? (int)$record['row_index'] + 1 : (int)($record['row'] ?? 0); // row_index is 0-based, sheet rows are 1-based
    $user->source = $record['_source'] ?? $source;

    return $user;
  }

  private static function get_admin_repository(): AdminRepository {
    return new AdminRepository();
  }

  private static function get_admin_account(?string $username = null): ?array {
    $repo = self::get_admin_repository();

    if ($username !== null) {
      $normalized = sanitize_user($username, true);
      if ($normalized !== '') {
        $found = $repo->getByUsername($normalized);
        if (!empty($found)) {
          return $found;
        }
      }
    }

    return $repo->getPrimaryAdmin();
  }

  private static function normalize_admin_settings(array $account): array {
    $username = sanitize_user($account['username'] ?? '', true);
    $display = sanitize_text_field($account['display_name'] ?? $username);

    return [
      'id'            => $account['row_index'] ?? $username,
      'username'      => $username,
      'display_name'  => $display !== '' ? $display : $username,
      'password_hash' => trim((string) ($account['password_hash'] ?? '')),
      'whatsapp'      => self::norm_phone($account['whatsapp'] ?? ''),
      'user_id'       => 0,
    ];
  }

  public static function get_admin_settings($username = null){
    $selected = null;

    if ($username) {
      $selected = self::get_admin_account($username);
    }

    if (!$selected && is_user_logged_in()) {
      $wpUser = wp_get_current_user();
      if ($wpUser && $wpUser->exists()) {
        $selected = self::get_admin_account($wpUser->user_login);
      }
    }

    if (!$selected) {
      $selected = self::get_admin_account();
    }

    if ($selected) {
      return self::normalize_admin_settings($selected);
    }

    $legacy = AdminCredentials::get_primary_account();
    if ($legacy) {
      return $legacy;
    }

    return [
      'id'            => '',
      'username'      => self::DEFAULT_ADMIN_USERNAME,
      'display_name'  => self::DEFAULT_ADMIN_DISPLAY,
      'password_hash' => self::DEFAULT_ADMIN_HASH,
      'whatsapp'      => '',
      'user_id'       => 0,
    ];
  }

  public static function save_admin_settings(array $settings){
    $accounts = AdminCredentials::get_accounts();
    if (empty($accounts)) {
      $seedPassword = !empty($settings['password']) ? $settings['password'] : wp_generate_password(20, true, true);
      $username = !empty($settings['username']) ? sanitize_user($settings['username'], true) : self::DEFAULT_ADMIN_USERNAME;
      if ($username === '') {
        $username = self::DEFAULT_ADMIN_USERNAME;
      }
      $display = !empty($settings['display_name']) ? sanitize_text_field($settings['display_name']) : $username;
      AdminCredentials::add_account([
        'username'     => $username,
        'display_name' => $display,
        'whatsapp'     => $settings['whatsapp'] ?? '',
      ], $seedPassword);
      $accounts = AdminCredentials::get_accounts();
    }

    $target = null;
    if (!empty($settings['account_id'])) {
      $target = AdminCredentials::find_account_by_id($settings['account_id'], $accounts);
    }
    if (!$target && is_user_logged_in()) {
      $wpUser = wp_get_current_user();
      if ($wpUser && $wpUser->exists()) {
        $target = AdminCredentials::find_account_by_username($wpUser->user_login, $accounts);
      }
    }
    if (!$target) {
      $target = $accounts[0];
    }

    $payload = [];
    if (array_key_exists('username', $settings)) {
      $payload['username'] = $settings['username'];
    }
    if (array_key_exists('display_name', $settings)) {
      $payload['display_name'] = $settings['display_name'];
    }
    if (array_key_exists('whatsapp', $settings)) {
      $payload['whatsapp'] = $settings['whatsapp'];
    }

    $plain = null;
    if (!empty($settings['password'])) {
      $plain = $settings['password'];
    }

    $updated = AdminCredentials::update_account($target['id'], $payload, $plain);
    if (is_wp_error($updated)) {
      return $target;
    }

    return $updated;
  }

  public static function get_admin_public_settings(){
    $currentAdmin = self::current_admin();
    if ($currentAdmin) {
      return [
        'username'     => $currentAdmin['username'],
        'display_name' => $currentAdmin['display_name'],
      ];
    }

    $settings = self::get_admin_settings();
    return [
      'username'     => $settings['username'],
      'display_name' => $settings['display_name'],
    ];
  }

  private static function is_wordpress_password_hash($hash) {
    $hash = (string) $hash;
    return (strpos($hash, '$P$') === 0 || strpos($hash, '$S$') === 0);
  }

  private static function looks_like_password_hash($hash) {
    $hash = (string) $hash;
    if ($hash === '') return false;

    return (
      strpos($hash, '$2y$') === 0 ||
      strpos($hash, '$argon2') === 0 ||
      self::is_wordpress_password_hash($hash)
    );
  }

  private static function verify_password_against_hash(string $password, string $hash): bool {
    if ($hash === '') {
      return false;
    }

    if (self::is_wordpress_password_hash($hash)) {
      return wp_check_password($password, $hash);
    }

    if (self::looks_like_password_hash($hash)) {
      return password_verify($password, $hash);
    }

    // Fallback to WordPress password checker for unexpected hash formats
    return wp_check_password($password, $hash);
  }

  private static function is_password_based_on_phone($hash, $phoneRaw) {
    if (!self::looks_like_password_hash($hash)) {
      return false;
    }

    $candidates = [];
    $trimmed = trim((string) $phoneRaw);
    if ($trimmed !== '') {
      $candidates[] = $trimmed;
    }

    $digitsOnly = preg_replace('/\D+/', '', $trimmed);
    if ($digitsOnly !== '' && !in_array($digitsOnly, $candidates, true)) {
      $candidates[] = $digitsOnly;
    }

    $normalized = self::norm_phone($trimmed);
    if ($normalized !== '' && !in_array($normalized, $candidates, true)) {
      $candidates[] = $normalized;
    }

    if ($normalized !== '') {
      $plusNormalized = '+' . ltrim($normalized, '+');
      if (!in_array($plusNormalized, $candidates, true)) {
        $candidates[] = $plusNormalized;
      }
    }

    foreach ($candidates as $candidate) {
      if (self::verify_password_against_hash($candidate, $hash)) {
        return true;
      }
    }

    return false;
  }

  public static function login_admin($username, $plain_pass){
    $username = sanitize_user($username, true);
    $plain_pass = trim(strval($plain_pass));

    if ($username === '' || $plain_pass === '') {
      return ['ok' => false, 'msg' => __('Username dan password wajib diisi.', 'hcis-ysq')];
    }

    $account = self::get_admin_account($username);
    if (!$account) {
      return ['ok' => false, 'msg' => __('Akun administrator tidak ditemukan.', 'hcis-ysq')];
    }

    $hash = strval($account['password_hash'] ?? '');
    if (!self::looks_like_password_hash($hash) || !password_verify($plain_pass, $hash)) {
      return ['ok' => false, 'msg' => __('Password salah.', 'hcis-ysq')];
    }

    $displayName = sanitize_text_field($account['display_name'] ?? $username);
    if ($displayName === '') {
      $displayName = $username;
    }

    $payload = [
      'type'         => 'admin',
      'username'     => $account['username'] ?? $username,
      'display_name' => $displayName,
    ];

    $sessionToken = self::store_session($payload);
    if ($sessionToken === false) {
      return ['ok' => false, 'msg' => __('Sesi tidak dapat dibuat. Coba lagi nanti.', 'hcis-ysq')];
    }

    return [
      'ok'    => true,
      'msg'   => __('Login administrator berhasil.', 'hcis-ysq'),
      'admin' => [
        'username'     => $payload['username'],
        'display_name' => $payload['display_name'],
      ],
    ];
  }

  public static function login($account, $plain_pass){
    $account = trim(strval($account));
    $plain_pass = trim(strval($plain_pass));

    if ($account === '' || $plain_pass === '') {
      return ['ok' => false, 'msg' => 'Akun & Password wajib diisi'];
    }

    $forceFreshLookup = self::should_force_fresh_user_lookup('login');

    if ($forceFreshLookup) {
      SheetCache::flush();
    }

    $u = self::get_user_by_nip($account, $forceFreshLookup);
    if (!$u) {
      return ['ok'=>false, 'msg'=>'Akun tidak ditemukan'];
    }

    $envDisableHashing = getenv('HCISYSQ_DISABLE_PASSWORD_HASHING');
    $hashingDisabled = apply_filters(
      'hcisysq_disable_password_hashing',
      $envDisableHashing === false ? false : $envDisableHashing !== '0'
    );

    $passOk = false;
    $needsReset = false;

    // Prioritize explicit NIP + NIK combination before any hash checks.
    if (!empty($u->nik) && hash_equals($u->nik, $plain_pass)) {
      $passOk = true;
      $needsReset = true; // Always force reset when logging in with NIK
    }

    // Prioritize plaintext comparison first, only falling back to hash verify when explicitly enabled.
    if (!$passOk && !empty($u->password)) {
      $hash = strval($u->password);
      $looksHashed = self::looks_like_password_hash($hash);

      // Always try plaintext comparison (Google Sheet value/NIK mirror) before hash verification.
      if (hash_equals($hash, $plain_pass)) {
        $passOk = true;
        if (!$looksHashed) {
          $needsReset = true;
        }
      }

      // Only verify against hash when explicitly allowed.
      if (!$passOk && !$hashingDisabled && $looksHashed && self::verify_password_against_hash($plain_pass, $hash)) {
        $passOk = true;
        if (self::is_password_based_on_phone($hash, $u->no_hp ?? '')) {
          $needsReset = true;
        }
      }
    }

    if (!$passOk) {
      return ['ok'=>false, 'msg'=>'Password salah.'];
    }

    $payload = [
      'type' => 'user',
      'nip'  => $u->nip,
    ];

    if ($needsReset) {
      $payload['needs_password_reset'] = true;
    }

    $sessionToken = self::store_session($payload);
    if ($sessionToken === false) {
      return ['ok' => false, 'msg' => 'Sesi tidak dapat dibuat. Coba lagi nanti.'];
    }

    return [
      'ok'   => true,
      'user' => [
        'id'      => intval($u->id),
        'nip'     => $u->nip,
        'nama'    => $u->nama,
        'jabatan' => $u->jabatan,
        'unit'    => $u->unit,
      ],
      'force_password_reset' => $needsReset,
    ];
  }

  public static function logout(){
    $token = self::get_session_token();
    if ($token) {
      // Try database first
      if (SessionHandler::verify_table_exists()) {
        SessionHandler::destroy($token);
      }

      // Also delete transient (backward compatibility)
      delete_transient('hcisysq_sess_' . $token);

      $domain = self::determine_cookie_domain();
      $options = [
        'expires'  => time() - 3600,
        'path'     => '/',
        'domain'   => $domain,
        'secure'   => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax'
      ];
      setcookie('hcisysq_token', '', $options);
      unset($_COOKIE['hcisysq_token']);
    }
    return true;
  }
  private static function build_admin_identity($username = null, $displayName = null, ?array $account = null){
    if (!$account) {
      $account = self::get_admin_account($username);
    }

    $settings = $account ? self::normalize_admin_settings($account) : self::get_admin_settings($username);

    $resolvedUsername = $username ? sanitize_user($username, true) : '';
    if ($resolvedUsername === '') {
      $resolvedUsername = $settings['username'];
    }

    if ($resolvedUsername === '') {
      $resolvedUsername = self::DEFAULT_ADMIN_USERNAME;
    }

    $resolvedDisplay = $displayName ? sanitize_text_field($displayName) : '';
    if ($resolvedDisplay === '' && !empty($settings['display_name'])) {
      $resolvedDisplay = $settings['display_name'];
    }

    if ($resolvedDisplay === '') {
      $resolvedDisplay = $resolvedUsername;
    }

    return [
      'type'         => 'admin',
      'username'     => $resolvedUsername,
      'display_name' => $resolvedDisplay,
      'settings'     => $settings,
    ];
  }

  public static function current_identity(){
    if (is_user_logged_in() && current_user_can('manage_hcis_portal')) {
      $wpUser = wp_get_current_user();
      $username = $wpUser && $wpUser->exists() ? $wpUser->user_login : null;
      $displayName = $wpUser && $wpUser->exists() ? $wpUser->display_name : null;
      return self::build_admin_identity($username, $displayName);
    }

    $payload = self::get_session_payload();
    if ($payload) {
      $type = $payload['type'] ?? 'user';
      if ($type === 'admin') {
        $username    = $payload['username'] ?? null;
        $displayName = $payload['display_name'] ?? null;
        $account     = $username ? self::get_admin_account($username) : self::get_admin_account();
        return self::build_admin_identity($username, $displayName, $account);
      }

      $nip = $payload['nip'] ?? null;
      if (!$nip && isset($payload['username'])) {
        $nip = $payload['username'];
      }
      if ($nip) {
        $user = self::get_user_by_nip($nip);
        if ($user) {
          $skipGranted = !empty($payload['reset_skip_granted']);
          $needsResetSession = !empty($payload['needs_password_reset']);
          $calculatedNeedsReset = $needsResetSession;

          if (!$calculatedNeedsReset && !empty($user->password) && !self::looks_like_password_hash($user->password)) {
            $calculatedNeedsReset = true;
          }

          if (!empty($user->password) && self::looks_like_password_hash($user->password)) {
            if (!$calculatedNeedsReset) {
              $calculatedNeedsReset = self::is_password_based_on_phone($user->password, $user->no_hp ?? '');
            }
          }

          if ($calculatedNeedsReset === false && !empty($payload['needs_password_reset'])) {
            self::update_current_session(['needs_password_reset' => false]);
          } elseif ($calculatedNeedsReset === true && empty($payload['needs_password_reset'])) {
            self::update_current_session(['needs_password_reset' => true]);
          }

          $needsReset = $calculatedNeedsReset;
          if ($skipGranted && $calculatedNeedsReset) {
            $needsReset = false;
          } elseif ($skipGranted && !$calculatedNeedsReset) {
            self::update_current_session(['reset_skip_granted' => false]);
          }

          return [
            'type' => 'user',
            'user' => $user,
            'needs_password_reset' => $needsReset,
          ];
        }
      }
    }

    return null;
  }

  public static function current_user(){
    $identity = self::current_identity();
    if ($identity && $identity['type'] === 'user') {
      return $identity['user'];
    }
    return null;
  }

  public static function current_admin() {
    // Priority 1: Check for a logged-in WordPress user with the 'hcis_admin' role capability.
    if (is_user_logged_in() && current_user_can('manage_hcis_portal')) {
      $wpUser = wp_get_current_user();
      if ($wpUser && $wpUser->exists()) {
        return [
          'type'         => 'admin',
          'username'     => $wpUser->user_login,
          'display_name' => $wpUser->display_name,
          'settings'     => self::get_admin_settings($wpUser->user_login),
        ];
      }
    }

    // Priority 2 (Fallback): If the WordPress check fails, check for the legacy admin session.
    $identity = self::current_identity();
    if ($identity && isset($identity['type']) && $identity['type'] === 'admin') {
      return $identity;
    }

    // If both checks fail, no authorized admin is logged in.
    return null;
  }
}
