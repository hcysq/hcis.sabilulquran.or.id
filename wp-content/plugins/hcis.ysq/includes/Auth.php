<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Auth {
  const ADMIN_OPTION = 'hcisysq_admin_settings';
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
    $payload = array_merge(['type' => 'user'], $payload);

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
  public static function get_user_by_nip($nip){
    $repo = new \HCISYSQ\Repositories\UserRepository();
    $record = $repo->find($nip);

    if (!$record || !is_array($record)) {
      return null;
    }

    $user = new \stdClass();
    $user->id = (int)($record['row_index'] ?? 0); // Use row_index as a unique ID
    $user->nip = $record['nip'] ?? '';
    $user->nama = $record['nama'] ?? '';
    $user->jabatan = $record['jabatan'] ?? ''; // Jabatan might not be in this sheet, but keep for compatibility
    $user->unit = $record['unit'] ?? ''; // Unit might not be in this sheet, but keep for compatibility
    $user->no_hp = $record['phone'] ?? ''; // Use 'phone' from repository
    $user->password = $record['password_hash'] ?? ''; // Use 'password_hash' from repository
    $user->nik = $record['nik'] ?? ''; // Add NIK for default password check
    $user->row = (int)($record['row_index'] ?? 0) + 1; // row_index is 0-based, sheet rows are 1-based
    return $user;
  }

  public static function get_admin_settings(){
    $stored = get_option(self::ADMIN_OPTION, []);
    if (!is_array($stored)) $stored = [];

    $defaults = [
      'username'      => self::DEFAULT_ADMIN_USERNAME,
      'display_name'  => self::DEFAULT_ADMIN_DISPLAY,
      'password_hash' => self::DEFAULT_ADMIN_HASH,
    ];

    $raw = wp_parse_args($stored, $defaults);
    $dirty = false;

    $username = $raw['username'] ? sanitize_user($raw['username'], true) : self::DEFAULT_ADMIN_USERNAME;
    if ($username === '') {
      $username = self::DEFAULT_ADMIN_USERNAME;
      $dirty = true;
    }

    $displayName = $raw['display_name'] ? sanitize_text_field($raw['display_name']) : self::DEFAULT_ADMIN_DISPLAY;
    if ($displayName === '') {
      $displayName = self::DEFAULT_ADMIN_DISPLAY;
      $dirty = true;
    }

    $storedHashRaw = isset($raw['password_hash']) ? strval($raw['password_hash']) : '';
    $storedHash = trim($storedHashRaw);

    if ($storedHash === '' || in_array($storedHash, self::LEGACY_ADMIN_HASHES, true)) {
      $passwordHash = self::DEFAULT_ADMIN_HASH;
      if ($storedHash !== self::DEFAULT_ADMIN_HASH) {
        $dirty = true;
      }
    } else {
      $passwordHash = $storedHash;
      if ($storedHashRaw !== $storedHash) {
        $dirty = true;
      }
    }

    $settings = [
      'username'      => $username,
      'display_name'  => $displayName,
      'password_hash' => $passwordHash,
    ];

    if ($dirty || $stored !== $settings) {
      update_option(self::ADMIN_OPTION, $settings, false);
    }

    return $settings;
  }

  public static function save_admin_settings(array $settings){
    $current = self::get_admin_settings();

    if (!empty($settings['username'])) {
      $username = sanitize_user($settings['username'], true);
      if ($username !== '') {
        $current['username'] = $username;
      }
    }

    if (!empty($settings['display_name'])) {
      $display = sanitize_text_field($settings['display_name']);
      if ($display !== '') {
        $current['display_name'] = $display;
      }
    }

    if (!empty($settings['password'])) {
      $current['password_hash'] = password_hash($settings['password'], PASSWORD_DEFAULT);
    }

    update_option(self::ADMIN_OPTION, $current, false);
    return $current;
  }

  public static function get_admin_public_settings(){
    $settings = self::get_admin_settings();
    return [
      'username'     => $settings['username'],
      'display_name' => $settings['display_name'],
    ];
  }

  private static function looks_like_password_hash($hash) {
    $hash = (string) $hash;
    return (strpos($hash, '$2y$') === 0 || strpos($hash, '$argon2') === 0);
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
      if (password_verify($candidate, $hash)) {
        return true;
      }
    }

    return false;
  }

  public static function login($account, $plain_pass){
    $account = trim(strval($account));
    $plain_pass = trim(strval($plain_pass));

    if ($account === '' || $plain_pass === '') {
      return ['ok' => false, 'msg' => 'Akun & Password wajib diisi'];
    }

    $u = self::get_user_by_nip($account);
    if (!$u) {
      return ['ok'=>false, 'msg'=>'Akun tidak ditemukan'];
    }

    $passOk = false;
    $needsReset = false;

    // First, try to verify against the stored hash if it exists.
    if (!empty($u->password)) {
        $hash = strval($u->password);
        $looksHashed = self::looks_like_password_hash($hash);

        if ($looksHashed && password_verify($plain_pass, $hash)) {
            $passOk = true;
            if (self::is_password_based_on_phone($hash, $u->no_hp ?? '')) {
                $needsReset = true;
            }
        } elseif (!$looksHashed && hash_equals($hash, $plain_pass)) {
            // Handle legacy plain-text passwords
            $passOk = true;
            $needsReset = true;
        }
    }

    // If password hash failed or was empty, try checking against NIK as a fallback.
    if (!$passOk && !empty($u->nik) && hash_equals($u->nik, $plain_pass)) {
        $passOk = true;
        $needsReset = true; // Always force reset when logging in with NIK
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
  private static function build_admin_identity($username = null, $displayName = null, ?array $settings = null){
    if (!$settings) {
      $settings = self::get_admin_settings();
    }

    $username = $username ? sanitize_user($username, true) : '';
    if ($username === '') {
      $username = $settings['username'];
    }

    $displayName = $displayName ? sanitize_text_field($displayName) : '';
    if ($displayName === '') {
      $displayName = $settings['display_name'];
    }

    return [
      'type'         => 'admin',
      'username'     => $username,
      'display_name' => $displayName,
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
        $settings    = self::get_admin_settings();
        $username    = $payload['username'] ?? null;
        $displayName = $payload['display_name'] ?? null;
        return self::build_admin_identity($username, $displayName, $settings);
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
