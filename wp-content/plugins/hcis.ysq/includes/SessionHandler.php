<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

/**
 * Session Handler
 *
 * Persists sessions to the dedicated hcisysq_sessions table while
 * gracefully falling back to the legacy transient-based store if the
 * table is not yet available during rollout.
 */
class SessionHandler {
  const TRANSIENT_PREFIX = 'hcisysq_sess_';
  const DEFAULT_TTL      = 12 * HOUR_IN_SECONDS;

  /** @var bool|null */
  private static $tableExists = null;

  /**
   * Create a new session payload.
   *
   * @param array $payload
   * @param int|null $expires_in_seconds
   * @return string|false
   */
  public static function create(array $payload, $expires_in_seconds = null) {
    $ttl = $expires_in_seconds ?? self::DEFAULT_TTL;
    $payload = self::normalize_payload($payload);
    $session_id = wp_generate_uuid4();

    if (self::should_use_database() && self::persist_to_database($session_id, $payload, $ttl)) {
      hcisysq_log('SessionHandler::create() - Session stored in database: ' . $session_id);
      return $session_id;
    }

    if (self::persist_to_transient($session_id, $payload, $ttl)) {
      hcisysq_log('SessionHandler::create() - Session stored via transient fallback: ' . $session_id);
      return $session_id;
    }

    hcisysq_log('SessionHandler::create() - Failed to create session', 'error');
    return false;
  }

  /**
   * Read a session payload by identifier.
   *
   * @param string $session_id
   * @return array|false
   */
  public static function read($session_id) {
    $session_id = sanitize_text_field($session_id);
    if ($session_id === '') {
      return false;
    }

    if (self::should_use_database()) {
      $payload = self::read_from_database($session_id);
      if ($payload !== false) {
        return $payload;
      }
    }

    return self::read_from_transient($session_id);
  }

  /**
   * Update an existing session.
   *
   * @param string $session_id
   * @param array $payload
   * @return bool
   */
  public static function update($session_id, array $payload) {
    $session_id = sanitize_text_field($session_id);
    if ($session_id === '') {
      return false;
    }

    $payload = self::normalize_payload($payload);

    if (self::should_use_database()) {
      $updated = self::update_database_record($session_id, $payload);
      if ($updated) {
        return true;
      }
    }

    $stored = self::persist_to_transient($session_id, $payload, self::DEFAULT_TTL);
    return (bool)$stored;
  }

  /**
   * Destroy a session.
   *
   * @param string $session_id
   * @return bool
   */
  public static function destroy($session_id) {
    $session_id = sanitize_text_field($session_id);
    if ($session_id === '') {
      return false;
    }

    $deleted = false;

    if (self::should_use_database()) {
      $deleted = self::delete_database_record($session_id);
    }

    $deleted_transient = self::delete_transient_session($session_id);

    return (bool)($deleted || $deleted_transient);
  }

  /**
   * Clean up expired sessions from both the database and transient store.
   *
   * @return int Number of sessions removed.
   */
  public static function cleanup() {
    $removed = 0;

    if (self::should_use_database()) {
      $table = self::table_name();
      global $wpdb;
      $now = gmdate('Y-m-d H:i:s', current_time('timestamp', true));
      $deleted = $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE expires_at <= %s", $now));
      if ($deleted === false) {
        self::handle_database_error('SessionHandler::cleanup()');
      } else {
        $removed += intval($deleted);
      }
    }

    $removed += self::cleanup_transient_store(true);
    hcisysq_log('SessionHandler::cleanup() - Removed ' . $removed . ' expired sessions');

    return $removed;
  }

  /**
   * Retrieve active sessions from the database for diagnostics.
   *
   * @return array
   */
  public static function get_active_sessions() {
    if (!self::should_use_database()) {
      return [];
    }

    global $wpdb;
    $table = self::table_name();
    $now = gmdate('Y-m-d H:i:s', current_time('timestamp', true));

    $sessions = $wpdb->get_results($wpdb->prepare(
      "SELECT session_id, user_id, expires_at, created_at, updated_at FROM $table WHERE expires_at > %s ORDER BY expires_at ASC",
      $now
    ));

    return is_array($sessions) ? $sessions : [];
  }

  /**
   * Invalidate every session for a specific user.
   *
   * @param int $user_id
   * @return int Number of sessions removed.
   */
  public static function invalidate_user_sessions($user_id) {
    $user_id = intval($user_id);
    if ($user_id <= 0) {
      return 0;
    }

    $removed = 0;

    if (self::should_use_database()) {
      global $wpdb;
      $table = self::table_name();
      $deleted = $wpdb->delete($table, ['user_id' => $user_id], ['%d']);
      if ($deleted === false) {
        self::handle_database_error('SessionHandler::invalidate_user_sessions()');
      } else {
        $removed += intval($deleted);
      }
    }

    $removed += self::delete_transients_for_user($user_id);

    return $removed;
  }

  /**
   * Invalidate every stored session regardless of owner.
   *
   * @return int Number of sessions removed.
   */
  public static function invalidate_all_sessions() {
    $removed = 0;

    if (self::should_use_database()) {
      global $wpdb;
      $table = self::table_name();
      $deleted = $wpdb->query("DELETE FROM $table");
      if ($deleted === false) {
        self::handle_database_error('SessionHandler::invalidate_all_sessions()');
      } else {
        $removed += intval($deleted);
      }
    }

    $removed += self::cleanup_transient_store(false);

    return $removed;
  }

  /**
   * Check if the database table exists.
   *
   * @return bool
   */
  public static function verify_table_exists() {
    global $wpdb;
    $table = self::table_name();
    $exists = ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table);
    self::$tableExists = $exists;
    return $exists;
  }

  /**
   * Determine if the table should be used.
   *
   * @return bool
   */
  private static function should_use_database() {
    if (self::$tableExists === null) {
      self::$tableExists = self::verify_table_exists();
    }
    return self::$tableExists;
  }

  private static function table_name() {
    global $wpdb;
    return $wpdb->prefix . 'hcisysq_sessions';
  }

  private static function persist_to_database($session_id, array $payload, $ttl) {
    global $wpdb;
    $table = self::table_name();

    $result = $wpdb->insert(
      $table,
      [
        'session_id' => $session_id,
        'user_id'    => self::extract_user_id($payload),
        'payload'    => wp_json_encode($payload),
        'expires_at' => self::expiration_for_ttl($ttl),
      ],
      ['%s', '%d', '%s', '%s']
    );

    if ($result === false) {
      self::handle_database_error('SessionHandler::create()');
      return false;
    }

    return true;
  }

  private static function persist_to_transient($session_id, array $payload, $ttl) {
    return set_transient(self::TRANSIENT_PREFIX . $session_id, $payload, $ttl);
  }

  private static function read_from_database($session_id) {
    global $wpdb;
    $table = self::table_name();

    $row = $wpdb->get_row($wpdb->prepare("SELECT payload, expires_at FROM $table WHERE session_id = %s LIMIT 1", $session_id));
    if ($row === null) {
      return false;
    }

    $expires = strtotime($row->expires_at . ' UTC');
    $now = current_time('timestamp', true);
    if ($expires !== false && $expires <= $now) {
      self::delete_database_record($session_id);
      return false;
    }

    $payload = json_decode($row->payload, true);
    if (!is_array($payload)) {
      $payload = [];
    }

    return $payload;
  }

  private static function read_from_transient($session_id) {
    $payload = get_transient(self::TRANSIENT_PREFIX . $session_id);
    if ($payload === false) {
      return false;
    }

    if (is_array($payload)) {
      return $payload;
    }

    if (is_object($payload)) {
      return (array)$payload;
    }

    if (is_string($payload) && trim($payload) !== '') {
      return ['type' => 'user', 'nip' => $payload];
    }

    return false;
  }

  private static function update_database_record($session_id, array $payload) {
    global $wpdb;
    $table = self::table_name();

    $result = $wpdb->update(
      $table,
      [
        'payload'    => wp_json_encode($payload),
        'user_id'    => self::extract_user_id($payload),
        'updated_at' => gmdate('Y-m-d H:i:s', current_time('timestamp', true)),
      ],
      ['session_id' => $session_id],
      ['%s', '%d', '%s'],
      ['%s']
    );

    if ($result === false) {
      self::handle_database_error('SessionHandler::update()');
      return false;
    }

    return (bool)$result;
  }

  private static function delete_database_record($session_id) {
    global $wpdb;
    $table = self::table_name();
    $deleted = $wpdb->delete($table, ['session_id' => $session_id], ['%s']);
    if ($deleted === false) {
      self::handle_database_error('SessionHandler::destroy()');
      return false;
    }
    return (bool)$deleted;
  }

  private static function delete_transient_session($session_id) {
    return delete_transient(self::TRANSIENT_PREFIX . $session_id);
  }

  private static function cleanup_transient_store($only_expired = true) {
    global $wpdb;
    $deleted = 0;
    $now = time();
    $timeout_prefix = '_transient_timeout_' . self::TRANSIENT_PREFIX;

    $rows = $wpdb->get_col($wpdb->prepare(
      "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
      $timeout_prefix . '%'
    ));

    foreach ($rows as $option_name) {
      $key = substr($option_name, strlen('_transient_timeout_'));
      $expires = intval(get_option($option_name));
      if (!$only_expired || $expires <= $now) {
        if (delete_transient($key)) {
          $deleted++;
        }
      }
    }

    return $deleted;
  }

  private static function delete_transients_for_user($user_id) {
    global $wpdb;
    $removed = 0;
    $prefix = '_transient_' . self::TRANSIENT_PREFIX;

    $rows = $wpdb->get_results($wpdb->prepare(
      "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
      $prefix . '%'
    ));

    foreach ($rows as $row) {
      $key = substr($row->option_name, strlen('_transient_'));
      $payload = maybe_unserialize($row->option_value);
      if (is_array($payload) && intval($payload['user_id'] ?? 0) === $user_id) {
        if (delete_transient($key)) {
          $removed++;
        }
      }
    }

    return $removed;
  }

  private static function normalize_payload(array $payload) {
    if (!isset($payload['type'])) {
      $payload['type'] = 'user';
    }
    if (!isset($payload['user_id']) && isset($payload['wp_user_id'])) {
      $payload['user_id'] = intval($payload['wp_user_id']);
    }
    return $payload;
  }

  private static function extract_user_id(array $payload) {
    if (isset($payload['user_id'])) {
      return intval($payload['user_id']);
    }
    if (isset($payload['wp_user_id'])) {
      return intval($payload['wp_user_id']);
    }
    return 0;
  }

  private static function expiration_for_ttl($ttl) {
    $timestamp = current_time('timestamp', true) + intval($ttl);
    return gmdate('Y-m-d H:i:s', $timestamp);
  }

  private static function handle_database_error($context) {
    global $wpdb;
    $message = sprintf('%s - Database error: %s', $context, $wpdb->last_error);
    hcisysq_log($message, 'error');

    if (strpos(strtolower($wpdb->last_error), 'doesn\'t exist') !== false) {
      self::$tableExists = false;
    }
  }
}
