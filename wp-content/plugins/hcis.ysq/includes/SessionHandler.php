<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

/**
 * Session Handler
 *
 * Manages user sessions with persistent database storage.
 * Provides methods for creating, reading, updating, and destroying sessions.
 * Automatically handles session expiration and cleanup.
 *
 * @package HCISYSQ
 */
class SessionHandler {

  /**
   * Create a new session
   *
   * @param array $payload Session data to store (will include 'type' and timestamp fields)
   * @param int $expires_in_seconds TTL in seconds (default: 12 hours)
   * @return string|false Session token on success, false on failure
   */
  public static function create(array $payload, $expires_in_seconds = null) {
    global $wpdb;
    
    if ($expires_in_seconds === null) {
      $expires_in_seconds = 12 * HOUR_IN_SECONDS;
    }

    $token = wp_generate_uuid4();
    $now = current_time('mysql');
    $expires_at = date('Y-m-d H:i:s', time() + $expires_in_seconds);
    
    // Ensure payload contains type
    if (!isset($payload['type'])) {
      $payload['type'] = 'user';
    }

    $table = $wpdb->prefix . 'hcisysq_sessions';
    $data = [
      'token'         => $token,
      'identity'      => wp_json_encode($payload),
      'created_at'    => $now,
      'expires_at'    => $expires_at,
      'last_activity' => $now,
      'ip_address'    => self::get_client_ip(),
      'user_agent'    => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ];

    $formats = ['%s', '%s', '%s', '%s', '%s', '%s', '%s'];
    $result = $wpdb->insert($table, $data, $formats);

    if (false === $result) {
      hcisysq_log('SessionHandler::create() - Database insert failed: ' . $wpdb->last_error);
      return false;
    }

    hcisysq_log('SessionHandler::create() - Session created: ' . $token);
    return $token;
  }

  /**
   * Read an existing session
   *
   * @param string $token Session token
   * @return array|false Session payload on success, false if not found or expired
   */
  public static function read($token) {
    global $wpdb;

    if (empty($token)) {
      return false;
    }

    $token = sanitize_text_field($token);
    $table = $wpdb->prefix . 'hcisysq_sessions';
    $now = current_time('mysql');

    // Fetch and verify session is not expired
    $row = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $table WHERE token = %s AND expires_at > %s LIMIT 1",
      $token,
      $now
    ));

    if (!$row) {
      hcisysq_log('SessionHandler::read() - Session not found or expired: ' . $token);
      return false;
    }

    // Update last_activity timestamp
    $wpdb->update(
      $table,
      ['last_activity' => $now],
      ['id' => $row->id],
      ['%s'],
      ['%d']
    );

    // Decode and return payload
    $payload = json_decode($row->identity, true);
    if (!is_array($payload)) {
      $payload = [];
    }

    hcisysq_log('SessionHandler::read() - Session retrieved: ' . $token);
    return $payload;
  }

  /**
   * Update an existing session
   *
   * @param string $token Session token
   * @param array $payload Updated session data
   * @return bool True on success, false otherwise
   */
  public static function update($token, array $payload) {
    global $wpdb;

    if (empty($token)) {
      return false;
    }

    $token = sanitize_text_field($token);
    $table = $wpdb->prefix . 'hcisysq_sessions';
    $now = current_time('mysql');

    // Verify session exists and is not expired
    $exists = $wpdb->get_var($wpdb->prepare(
      "SELECT id FROM $table WHERE token = %s AND expires_at > %s LIMIT 1",
      $token,
      $now
    ));

    if (!$exists) {
      hcisysq_log('SessionHandler::update() - Session not found or expired: ' . $token);
      return false;
    }

    // Update identity and last_activity
    $result = $wpdb->update(
      $table,
      [
        'identity'      => wp_json_encode($payload),
        'last_activity' => $now,
      ],
      ['token' => $token],
      ['%s', '%s'],
      ['%s']
    );

    if (false === $result) {
      hcisysq_log('SessionHandler::update() - Database update failed: ' . $wpdb->last_error);
      return false;
    }

    hcisysq_log('SessionHandler::update() - Session updated: ' . $token);
    return true;
  }

  /**
   * Destroy (delete) a session
   *
   * @param string $token Session token
   * @return bool True on success, false otherwise
   */
  public static function destroy($token) {
    global $wpdb;

    if (empty($token)) {
      return false;
    }

    $token = sanitize_text_field($token);
    $table = $wpdb->prefix . 'hcisysq_sessions';

    $result = $wpdb->delete(
      $table,
      ['token' => $token],
      ['%s']
    );

    if (false === $result) {
      hcisysq_log('SessionHandler::destroy() - Database delete failed: ' . $wpdb->last_error);
      return false;
    }

    hcisysq_log('SessionHandler::destroy() - Session destroyed: ' . $token);
    return true;
  }

  /**
   * Clean up expired sessions
   *
   * Deletes all sessions where expires_at < NOW()
   *
   * @return int Number of sessions deleted
   */
  public static function cleanup() {
    global $wpdb;

    $table = $wpdb->prefix . 'hcisysq_sessions';
    $now = current_time('mysql');

    $deleted = $wpdb->query($wpdb->prepare(
      "DELETE FROM $table WHERE expires_at < %s",
      $now
    ));

    if ($deleted === false) {
      hcisysq_log('SessionHandler::cleanup() - Database cleanup failed: ' . $wpdb->last_error);
      return 0;
    }

    hcisysq_log('SessionHandler::cleanup() - Deleted ' . $deleted . ' expired sessions');
    return intval($deleted);
  }

  /**
   * Get all active sessions (for debugging/monitoring)
   *
   * @return array Array of session objects
   */
  public static function get_active_sessions() {
    global $wpdb;

    $table = $wpdb->prefix . 'hcisysq_sessions';
    $now = current_time('mysql');

    $sessions = $wpdb->get_results($wpdb->prepare(
      "SELECT id, token, created_at, expires_at, last_activity, ip_address FROM $table WHERE expires_at > %s ORDER BY last_activity DESC",
      $now
    ));

    return is_array($sessions) ? $sessions : [];
  }

  /**
   * Get client's IP address
   *
   * @return string Client IP address
   */
  private static function get_client_ip() {
    $ip = '';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
      $ip = $_SERVER['REMOTE_ADDR'];
    }

    $ip = sanitize_text_field($ip);
    $ip = substr($ip, 0, 45); // VARCHAR(45) in schema

    return $ip;
  }

  /**
   * Verify table exists and is properly structured
   *
   * @return bool True if table exists and is valid
   */
  public static function verify_table_exists() {
    global $wpdb;

    $table = $wpdb->prefix . 'hcisysq_sessions';
    return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
  }
}
