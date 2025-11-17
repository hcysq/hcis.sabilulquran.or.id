<?php
/**
 * Unit Tests for SessionHandler
 *
 * @package HCISYSQ
 * @group SessionHandler
 */

namespace HCISYSQ\Tests\Unit;

use HCISYSQ\Installer;
use HCISYSQ\SessionHandler;

class SessionHandlerTest extends \WP_UnitTestCase {
  protected function setUp(): void {
    parent::setUp();
    $this->resetSessions();
  }

  private function resetSessions() {
    global $wpdb;
    $table = $wpdb->prefix . 'hcisysq_sessions';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) {
      $wpdb->query("DELETE FROM $table");
    }

    $prefix = '_transient_' . SessionHandler::TRANSIENT_PREFIX;
    $rows = $wpdb->get_col($wpdb->prepare(
      "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
      $prefix . '%'
    ));
    foreach ($rows as $option_name) {
      $key = substr($option_name, strlen('_transient_'));
      delete_transient($key);
    }
  }

  public function test_create_and_read_session() {
    $payload = [
      'type' => 'user',
      'user_id' => 99,
      'nip' => '1234567890',
    ];

    $session_id = SessionHandler::create($payload, HOUR_IN_SECONDS);
    $this->assertIsString($session_id);

    global $wpdb;
    $table = $wpdb->prefix . 'hcisysq_sessions';
    $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE session_id = %s", $session_id));
    $this->assertNotNull($row, 'Session should be persisted to database');

    $stored_payload = json_decode($row->payload, true);
    $this->assertEquals($payload['nip'], $stored_payload['nip']);

    $read_payload = SessionHandler::read($session_id);
    $this->assertIsArray($read_payload);
    $this->assertSame($payload['nip'], $read_payload['nip']);
  }

  public function test_cleanup_removes_expired_sessions() {
    $payload = ['type' => 'user', 'user_id' => 10, 'nip' => 'EXPIRE'];
    $session_id = SessionHandler::create($payload, 1);
    $this->assertNotEmpty($session_id);

    sleep(2);
    $removed = SessionHandler::cleanup();
    $this->assertGreaterThanOrEqual(1, $removed);
    $this->assertFalse(SessionHandler::read($session_id));
  }

  public function test_fallback_to_transient_when_table_missing() {
    global $wpdb;
    $table = $wpdb->prefix . 'hcisysq_sessions';
    $wpdb->query("DROP TABLE IF EXISTS $table");
    SessionHandler::verify_table_exists();

    $payload = ['type' => 'user', 'user_id' => 7, 'nip' => 'TRANSIENT'];
    $session_id = SessionHandler::create($payload, HOUR_IN_SECONDS);
    $this->assertIsString($session_id);

    $transient = get_transient(SessionHandler::TRANSIENT_PREFIX . $session_id);
    $this->assertIsArray($transient, 'Payload should be stored in transient when table missing');

    Installer::activate();
  }

  public function test_invalidate_user_sessions_removes_all_rows() {
    $user_id = 500;
    $other_user_id = 600;
    SessionHandler::create(['type' => 'user', 'user_id' => $user_id, 'nip' => 'A']);
    SessionHandler::create(['type' => 'user', 'user_id' => $user_id, 'nip' => 'B']);
    SessionHandler::create(['type' => 'user', 'user_id' => $other_user_id, 'nip' => 'C']);

    $removed = SessionHandler::invalidate_user_sessions($user_id);
    $this->assertEquals(2, $removed);

    global $wpdb;
    $table = $wpdb->prefix . 'hcisysq_sessions';
    $remaining = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE user_id = %d", $user_id));
    $this->assertEquals(0, intval($remaining));

    $still_there = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE user_id = %d", $other_user_id));
    $this->assertEquals(1, intval($still_there));
  }
}
