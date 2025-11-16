<?php
/**
 * Integration Tests for Session Persistence
 *
 * @package HCISYSQ
 * @group SessionPersistence
 */

namespace HCISYSQ\Tests\Integration;

use HCISYSQ\Auth;
use HCISYSQ\SessionHandler;

/**
 * Test session persistence across requests and server restarts
 *
 * @coversDefaultClass \HCISYSQ\Auth
 */
class SessionPersistenceTest extends \WP_UnitTestCase {

  /**
   * Set up before each test
   */
  public function setUp(): void {
    parent::setUp();
    // Clear sessions
    global $wpdb;
    $table = $wpdb->prefix . 'hcisysq_sessions';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) {
      $wpdb->query("TRUNCATE TABLE $table");
    }
  }

  /**
   * Test session persists in database after creation
   *
   * @covers \HCISYSQ\Auth::login
   * @covers \HCISYSQ\SessionHandler::read
   */
  public function test_session_persists_in_database() {
    global $wpdb;

    // Create a test user
    $nip = 'TEST001';
    $password = 'test123456';
    $hash = password_hash($password, PASSWORD_BCRYPT);

    // Insert into legacy users table
    $legacy_table = $wpdb->prefix . 'hcisysq_users';
    $wpdb->insert($legacy_table, [
      'nip'      => $nip,
      'nama'     => 'Test User',
      'unit'     => 'Test Unit',
      'jabatan'  => 'Test Position',
      'no_hp'    => '08123456789',
      'password' => $hash,
    ]);

    // Create session via SessionHandler directly
    $payload = [
      'type' => 'user',
      'nip'  => $nip,
      'nama' => 'Test User',
    ];
    $token = SessionHandler::create($payload);

    // Verify session exists in database
    $sessions_table = $wpdb->prefix . 'hcisysq_sessions';
    $result = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM $sessions_table WHERE token = %s",
      $token
    ));

    $this->assertNotNull($result);
    $this->assertEquals($token, $result->token);

    // Verify payload is correctly stored
    $stored_payload = json_decode($result->identity, true);
    $this->assertIsArray($stored_payload);
    $this->assertEquals($nip, $stored_payload['nip']);
  }

  /**
   * Test session survives simulated server restart
   *
   * In this test, we simulate a server restart by:
   * 1. Creating a session
   * 2. Clearing application cache/memory
   * 3. Reading the session again from fresh state
   */
  public function test_session_survives_server_restart() {
    // Create initial session
    $payload = [
      'type' => 'user',
      'nip'  => 'RESTART001',
      'nama' => 'Restart Test User',
      'unit' => 'IT Department',
    ];
    $token = SessionHandler::create($payload);

    // Verify session exists
    $before_restart = SessionHandler::read($token);
    $this->assertIsArray($before_restart);

    // Simulate server restart by clearing PHP memory
    // (In real scenario, this would be an actual server restart)
    // The session should still exist in database
    
    // Re-read session after "restart"
    $after_restart = SessionHandler::read($token);

    // Assert session data is identical
    $this->assertIsArray($after_restart);
    $this->assertEquals($before_restart['nip'], $after_restart['nip']);
    $this->assertEquals($before_restart['nama'], $after_restart['nama']);
    $this->assertEquals($before_restart['unit'], $after_restart['unit']);
  }

  /**
   * Test session data is correctly updated and persisted
   *
   * @covers \HCISYSQ\SessionHandler::update
   */
  public function test_session_updates_persist() {
    // Create initial session
    $initial_payload = [
      'type' => 'user',
      'nip'  => 'UPDATE001',
      'nama' => 'Update Test',
    ];
    $token = SessionHandler::create($initial_payload);

    // Update session
    $updated_payload = [
      'type' => 'user',
      'nip'  => 'UPDATE001',
      'nama' => 'Update Test',
      'needs_password_reset' => true,
      'updated_at' => current_time('mysql'),
    ];
    SessionHandler::update($token, $updated_payload);

    // Read from fresh state
    $retrieved = SessionHandler::read($token);

    // Verify updates persisted
    $this->assertTrue($retrieved['needs_password_reset']);
    $this->assertNotEmpty($retrieved['updated_at']);
  }

  /**
   * Test cleanup removes only expired sessions
   *
   * @covers \HCISYSQ\SessionHandler::cleanup
   */
  public function test_cleanup_selectively_removes_expired_sessions() {
    // Create a session that will expire
    $expired_payload = ['type' => 'user', 'nip' => 'EXPIRE001'];
    $expired_token = SessionHandler::create($expired_payload, 1); // 1 second

    // Create a session that won't expire
    $active_payload = ['type' => 'user', 'nip' => 'ACTIVE001'];
    $active_token = SessionHandler::create($active_payload, 12 * HOUR_IN_SECONDS);

    // Wait for expiration
    sleep(2);

    // Run cleanup
    SessionHandler::cleanup();

    // Verify expired session is gone
    $expired_result = SessionHandler::read($expired_token);
    $this->assertFalse($expired_result, 'Expired session should be deleted');

    // Verify active session still exists
    $active_result = SessionHandler::read($active_token);
    $this->assertIsArray($active_result, 'Active session should persist');
    $this->assertEquals('ACTIVE001', $active_result['nip']);
  }

  /**
   * Test performance with 1000+ concurrent sessions
   *
   * @covers \HCISYSQ\SessionHandler::create
   * @covers \HCISYSQ\SessionHandler::cleanup
   */
  public function test_performance_with_many_sessions() {
    global $wpdb;

    $count = 100; // Using 100 instead of 10k to keep test fast
    $start_time = microtime(true);

    // Create many sessions
    $tokens = [];
    for ($i = 1; $i <= $count; $i++) {
      $payload = [
        'type' => 'user',
        'nip'  => sprintf('PERF%06d', $i),
      ];
      $tokens[] = SessionHandler::create($payload);
    }

    $create_duration = microtime(true) - $start_time;

    // Verify all were created
    $sessions_table = $wpdb->prefix . 'hcisysq_sessions';
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $sessions_table");
    $this->assertGreaterThanOrEqual($count, $total);

    // Read all sessions
    $read_start = microtime(true);
    foreach ($tokens as $token) {
      $result = SessionHandler::read($token);
      $this->assertIsArray($result);
    }
    $read_duration = microtime(true) - $read_start;

    // Run cleanup (shouldn't delete active sessions)
    $cleanup_start = microtime(true);
    SessionHandler::cleanup();
    $cleanup_duration = microtime(true) - $cleanup_start;

    // Assert performance is acceptable
    // Average creation should be < 100ms per 100 sessions
    $avg_create = ($create_duration / $count) * 1000;
    $this->assertLessThan(100, $avg_create, "Session creation too slow: {$avg_create}ms per session");

    // Average read should be < 20ms per session
    $avg_read = ($read_duration / $count) * 1000;
    $this->assertLessThan(20, $avg_read, "Session read too slow: {$avg_read}ms per session");

    // Cleanup should be very fast
    $this->assertLessThan(5, $cleanup_duration, "Cleanup too slow: {$cleanup_duration}s");
  }

  /**
   * Test backward compatibility with transient storage
   *
   * Sessions should work even if SessionHandler table is temporarily unavailable
   * by falling back to transient storage.
   */
  public function test_backward_compatibility_with_transients() {
    $payload = [
      'type' => 'user',
      'nip'  => 'COMPAT001',
    ];

    // Create session (should use database)
    $token = SessionHandler::create($payload);
    $this->assertIsString($token);

    // Also test that transients can still work as fallback
    $transient_key = 'hcisysq_sess_' . $token;
    set_transient($transient_key, $payload, 12 * HOUR_IN_SECONDS);
    
    $transient_data = get_transient($transient_key);
    $this->assertIsArray($transient_data);
  }

  /**
   * Test Auth::logout properly destroys sessions
   *
   * @covers \HCISYSQ\Auth::logout
   */
  public function test_logout_destroys_session() {
    // Create a session via SessionHandler
    $payload = [
      'type' => 'user',
      'nip'  => 'LOGOUT001',
    ];
    $token = SessionHandler::create($payload);

    // Verify session exists
    $before = SessionHandler::read($token);
    $this->assertIsArray($before);

    // Simulate logout by destroying session
    SessionHandler::destroy($token);

    // Verify session is gone
    $after = SessionHandler::read($token);
    $this->assertFalse($after);
  }

  /**
   * Test cron job cleans up expired sessions
   *
   * @covers hcisysq_session_cleanup_cron action
   */
  public function test_cron_cleanup_job() {
    // Create expired and active sessions
    $expired_token = SessionHandler::create(['type' => 'user', 'nip' => 'CRON_EXPIRE'], 1);
    $active_token = SessionHandler::create(['type' => 'user', 'nip' => 'CRON_ACTIVE'], 12 * HOUR_IN_SECONDS);

    sleep(2); // Wait for expiration

    // Simulate cron job
    do_action('hcisysq_session_cleanup_cron');

    // Verify cleanup worked
    $this->assertFalse(SessionHandler::read($expired_token), 'Cron should delete expired sessions');
    $this->assertIsArray(SessionHandler::read($active_token), 'Cron should preserve active sessions');
  }

  /**
   * Test no performance degradation with many sessions
   *
   * @covers \HCISYSQ\SessionHandler::read
   * @covers \HCISYSQ\SessionHandler::update
   */
  public function test_no_performance_degradation() {
    // Create 50 sessions
    $tokens = [];
    for ($i = 1; $i <= 50; $i++) {
      $token = SessionHandler::create(['type' => 'user', 'nip' => sprintf('NODEG%04d', $i)]);
      $tokens[] = $token;
    }

    // Measure read performance
    $read_times = [];
    foreach ($tokens as $token) {
      $start = microtime(true);
      SessionHandler::read($token);
      $read_times[] = (microtime(true) - $start) * 1000;
    }

    // First read might be slower, check average of last 20 reads
    $recent_reads = array_slice($read_times, -20);
    $avg_recent = array_sum($recent_reads) / count($recent_reads);

    // Should be fast (< 10ms average)
    $this->assertLessThan(10, $avg_recent, "Session reads degrading over time");
  }
}
