<?php
/**
 * Unit Tests for SessionHandler
 *
 * @package HCISYSQ
 * @group SessionHandler
 */

namespace HCISYSQ\Tests\Unit;

use HCISYSQ\SessionHandler;

/**
 * Test SessionHandler CRUD operations
 *
 * These tests verify that sessions can be created, read, updated, and destroyed
 * properly with database persistence.
 *
 * @coversDefaultClass \HCISYSQ\SessionHandler
 */
class SessionHandlerTest extends \WP_UnitTestCase {

  /**
   * Set up before each test
   */
  public function setUp(): void {
    parent::setUp();
    // Clear any existing sessions
    global $wpdb;
    $table = $wpdb->prefix . 'hcisysq_sessions';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) {
      $wpdb->query("TRUNCATE TABLE $table");
    }
  }

  /**
   * Test session creation
   *
   * @covers \HCISYSQ\SessionHandler::create
   */
  public function test_create_session() {
    $payload = [
      'type' => 'user',
      'nip'  => '123456',
      'nama' => 'Test User',
    ];

    $token = SessionHandler::create($payload);

    // Assert token was generated
    $this->assertIsString($token);
    $this->assertNotEmpty($token);
    
    // Assert it's a valid UUID format
    $this->assertMatchesRegularExpression(
      '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
      $token
    );
  }

  /**
   * Test session creation with custom TTL
   *
   * @covers \HCISYSQ\SessionHandler::create
   */
  public function test_create_session_with_custom_ttl() {
    $payload = ['type' => 'user', 'nip' => '123456'];
    $ttl = 3600; // 1 hour

    $token = SessionHandler::create($payload, $ttl);

    $this->assertIsString($token);
    $this->assertNotEmpty($token);
  }

  /**
   * Test reading a valid session
   *
   * @covers \HCISYSQ\SessionHandler::read
   */
  public function test_read_valid_session() {
    $payload = [
      'type' => 'user',
      'nip'  => '987654',
      'nama' => 'Jane Doe',
    ];

    $token = SessionHandler::create($payload);
    $result = SessionHandler::read($token);

    // Assert payload is returned correctly
    $this->assertIsArray($result);
    $this->assertEquals('user', $result['type']);
    $this->assertEquals('987654', $result['nip']);
    $this->assertEquals('Jane Doe', $result['nama']);
  }

  /**
   * Test reading a non-existent session
   *
   * @covers \HCISYSQ\SessionHandler::read
   */
  public function test_read_invalid_session() {
    $result = SessionHandler::read('nonexistent-token-12345');
    $this->assertFalse($result);
  }

  /**
   * Test reading an empty token
   *
   * @covers \HCISYSQ\SessionHandler::read
   */
  public function test_read_empty_token() {
    $result = SessionHandler::read('');
    $this->assertFalse($result);
  }

  /**
   * Test updating a session
   *
   * @covers \HCISYSQ\SessionHandler::update
   */
  public function test_update_session() {
    $payload = [
      'type' => 'user',
      'nip'  => '111111',
      'nama' => 'Original Name',
    ];

    $token = SessionHandler::create($payload);

    // Update payload
    $updated_payload = [
      'type' => 'user',
      'nip'  => '111111',
      'nama' => 'Updated Name',
      'needs_password_reset' => true,
    ];

    $result = SessionHandler::update($token, $updated_payload);
    $this->assertTrue($result);

    // Verify update
    $retrieved = SessionHandler::read($token);
    $this->assertEquals('Updated Name', $retrieved['nama']);
    $this->assertTrue($retrieved['needs_password_reset']);
  }

  /**
   * Test updating a non-existent session
   *
   * @covers \HCISYSQ\SessionHandler::update
   */
  public function test_update_invalid_session() {
    $result = SessionHandler::update('nonexistent-token', ['test' => 'data']);
    $this->assertFalse($result);
  }

  /**
   * Test destroying a session
   *
   * @covers \HCISYSQ\SessionHandler::destroy
   */
  public function test_destroy_session() {
    $payload = ['type' => 'user', 'nip' => '222222'];
    $token = SessionHandler::create($payload);

    // Verify session exists
    $before = SessionHandler::read($token);
    $this->assertIsArray($before);

    // Destroy session
    $result = SessionHandler::destroy($token);
    $this->assertTrue($result);

    // Verify session is gone
    $after = SessionHandler::read($token);
    $this->assertFalse($after);
  }

  /**
   * Test destroying a non-existent session
   *
   * @covers \HCISYSQ\SessionHandler::destroy
   */
  public function test_destroy_invalid_session() {
    $result = SessionHandler::destroy('nonexistent-token');
    // destroy() returns true even for non-existent tokens (no-op)
    // This is acceptable as it idempotent
    $this->assertIsBool($result);
  }

  /**
   * Test session cleanup removes expired sessions
   *
   * @covers \HCISYSQ\SessionHandler::cleanup
   */
  public function test_cleanup_expired_sessions() {
    global $wpdb;

    // Create a session that will expire
    $payload = ['type' => 'user', 'nip' => '333333'];
    $token = SessionHandler::create($payload, 1); // 1 second TTL

    // Sleep to ensure expiration
    sleep(2);

    // Run cleanup
    $deleted = SessionHandler::cleanup();

    // Assert at least one session was deleted
    $this->assertGreaterThanOrEqual(1, $deleted);

    // Verify session is actually gone
    $result = SessionHandler::read($token);
    $this->assertFalse($result);
  }

  /**
   * Test cleanup doesn't remove active sessions
   *
   * @covers \HCISYSQ\SessionHandler::cleanup
   */
  public function test_cleanup_preserves_active_sessions() {
    // Create an active session (12 hour TTL)
    $payload = ['type' => 'user', 'nip' => '444444'];
    $token = SessionHandler::create($payload, 12 * HOUR_IN_SECONDS);

    // Run cleanup
    SessionHandler::cleanup();

    // Verify session still exists
    $result = SessionHandler::read($token);
    $this->assertIsArray($result);
  }

  /**
   * Test multiple sessions can be created and managed independently
   *
   * @covers \HCISYSQ\SessionHandler::create
   * @covers \HCISYSQ\SessionHandler::read
   */
  public function test_multiple_sessions() {
    $tokens = [];
    $payloads = [];

    // Create 5 different sessions
    for ($i = 1; $i <= 5; $i++) {
      $payload = [
        'type' => 'user',
        'nip'  => sprintf('NIP%06d', $i),
        'nama' => sprintf('User %d', $i),
      ];
      $payloads[$i] = $payload;
      $tokens[$i] = SessionHandler::create($payload);
    }

    // Verify each session is independent
    foreach ($tokens as $i => $token) {
      $result = SessionHandler::read($token);
      $this->assertIsArray($result);
      $this->assertEquals($payloads[$i]['nip'], $result['nip']);
      $this->assertEquals($payloads[$i]['nama'], $result['nama']);
    }
  }

  /**
   * Test get_active_sessions returns correct count
   *
   * @covers \HCISYSQ\SessionHandler::get_active_sessions
   */
  public function test_get_active_sessions() {
    // Create 3 active sessions
    for ($i = 1; $i <= 3; $i++) {
      SessionHandler::create(['type' => 'user', 'nip' => sprintf('NIP%d', $i)]);
    }

    $active = SessionHandler::get_active_sessions();

    $this->assertIsArray($active);
    $this->assertGreaterThanOrEqual(3, count($active));
  }

  /**
   * Test verify_table_exists
   *
   * @covers \HCISYSQ\SessionHandler::verify_table_exists
   */
  public function test_verify_table_exists() {
    $exists = SessionHandler::verify_table_exists();
    $this->assertIsBool($exists);
    // Should be true since we're running in test environment with proper setup
    $this->assertTrue($exists);
  }

  /**
   * Test payload with special characters is preserved
   *
   * @covers \HCISYSQ\SessionHandler::create
   * @covers \HCISYSQ\SessionHandler::read
   */
  public function test_payload_with_special_characters() {
    $payload = [
      'type'        => 'user',
      'nip'         => '123456',
      'nama'        => 'Haji Budi Santoso, S.E., M.M.',
      'unit'        => 'Departemen IT & Cloud Computing',
      'description' => 'Session with "quotes" and \'apostrophes\' & symbols: @#$%',
    ];

    $token = SessionHandler::create($payload);
    $result = SessionHandler::read($token);

    $this->assertEquals($payload['nama'], $result['nama']);
    $this->assertEquals($payload['unit'], $result['unit']);
    $this->assertEquals($payload['description'], $result['description']);
  }

  /**
   * Test session last_activity is updated on read
   *
   * @covers \HCISYSQ\SessionHandler::read
   */
  public function test_session_last_activity_updated() {
    global $wpdb;

    $payload = ['type' => 'user', 'nip' => '555555'];
    $token = SessionHandler::create($payload);

    $table = $wpdb->prefix . 'hcisysq_sessions';
    $before = $wpdb->get_var($wpdb->prepare(
      "SELECT last_activity FROM $table WHERE token = %s",
      $token
    ));

    // Wait a second
    sleep(1);

    // Read session
    SessionHandler::read($token);

    $after = $wpdb->get_var($wpdb->prepare(
      "SELECT last_activity FROM $table WHERE token = %s",
      $token
    ));

    // last_activity should be different (later)
    $this->assertNotEquals($before, $after);
  }
}
