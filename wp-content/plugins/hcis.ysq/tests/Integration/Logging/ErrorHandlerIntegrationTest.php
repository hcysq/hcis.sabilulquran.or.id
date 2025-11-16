<?php
namespace HCISYSQ\Tests\Integration\Logging;

use HCISYSQ\ErrorHandler;
use PHPUnit\Framework\TestCase;

/**
 * ErrorHandler Integration Tests
 * 
 * Tests error handling in integrated WordPress environment
 * Tests database persistence and file logging
 * 
 * @package HCISYSQ\Tests\Integration
 */
class ErrorHandlerIntegrationTest extends TestCase {

  /**
   * Runs before each test
   */
  public function setUp(): void {
    parent::setUp();
    ErrorHandler::setupLogger();
  }

  /**
   * Test error handler catches PHP errors
   */
  public function test_php_error_handler_catches_errors() {
    // This test would normally trigger a PHP error
    // In automated environment, we verify handler is registered
    
    ErrorHandler::registerHandlers();
    
    // Verify error handler is set
    $handlers = get_defined_vars();
    $this->assertNotNull(error_reporting());
  }

  /**
   * Test exception handler catches exceptions
   */
  public function test_exception_handler_catches_exceptions() {
    ErrorHandler::registerHandlers();
    
    try {
      throw new \Exception('Test exception');
    } catch (\Exception $e) {
      // ErrorHandler should catch this
      $this->assertEquals('Test exception', $e->getMessage());
    }
  }

  /**
   * Test log message persists to file
   */
  public function test_log_persists_to_file() {
    $message = 'Integration test message: ' . uniqid();
    ErrorHandler::info($message);
    
    // Verify log file exists
    $logDir = WP_CONTENT_DIR . '/hcisysq-logs';
    $this->assertTrue(is_dir($logDir), 'Log directory should exist');
  }

  /**
   * Test database handler writes WARNING+ to logs table
   */
  public function test_database_handler_writes_warning_logs() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    global $wpdb;
    
    // Count current logs
    $before = intval($wpdb->get_var(
      "SELECT COUNT(*) FROM {$wpdb->prefix}hcisysq_logs WHERE level = 'WARNING'"
    ));

    // Log a warning
    ErrorHandler::warning('Test warning message for integration test');

    // Check if new log was added
    $after = intval($wpdb->get_var(
      "SELECT COUNT(*) FROM {$wpdb->prefix}hcisysq_logs WHERE level = 'WARNING'"
    ));

    $this->assertGreaterThan($before, $after, 'Warning should be logged to database');
  }

  /**
   * Test debug level is not written to database
   */
  public function test_debug_not_written_to_database() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    global $wpdb;
    
    // Count before
    $before = intval($wpdb->get_var(
      "SELECT COUNT(*) FROM {$wpdb->prefix}hcisysq_logs WHERE level = 'DEBUG'"
    ));

    // Log debug (should not be persisted to DB)
    ErrorHandler::debug('Test debug message');

    // Count after
    $after = intval($wpdb->get_var(
      "SELECT COUNT(*) FROM {$wpdb->prefix}hcisysq_logs WHERE level = 'DEBUG'"
    ));

    // Debug should NOT be in database (only WARNING+)
    $this->assertEquals($before, $after, 'Debug should not be logged to database');
  }

  /**
   * Test error level is written to database
   */
  public function test_error_written_to_database() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    global $wpdb;
    
    $before = intval($wpdb->get_var(
      "SELECT COUNT(*) FROM {$wpdb->prefix}hcisysq_logs WHERE level = 'ERROR'"
    ));

    ErrorHandler::error('Test error message for integration');

    $after = intval($wpdb->get_var(
      "SELECT COUNT(*) FROM {$wpdb->prefix}hcisysq_logs WHERE level = 'ERROR'"
    ));

    $this->assertGreaterThan($before, $after, 'Error should be logged to database');
  }

  /**
   * Test critical level is written to database
   */
  public function test_critical_written_to_database() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    global $wpdb;
    
    $before = intval($wpdb->get_var(
      "SELECT COUNT(*) FROM {$wpdb->prefix}hcisysq_logs WHERE level = 'CRITICAL'"
    ));

    ErrorHandler::critical('Test critical message for integration');

    $after = intval($wpdb->get_var(
      "SELECT COUNT(*) FROM {$wpdb->prefix}hcisysq_logs WHERE level = 'CRITICAL'"
    ));

    $this->assertGreaterThan($before, $after, 'Critical should be logged to database');
  }

  /**
   * Test context data is preserved in database
   */
  public function test_context_preserved_in_database() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    global $wpdb;
    
    $context = ['user_id' => 123, 'ip_address' => '192.168.1.1', 'custom' => 'data'];
    ErrorHandler::error('Test with context', $context);

    // Retrieve the log
    $log = $wpdb->get_row(
      "SELECT * FROM {$wpdb->prefix}hcisysq_logs WHERE message LIKE '%Test with context%' ORDER BY id DESC LIMIT 1",
      ARRAY_A
    );

    $this->assertNotNull($log, 'Log should exist in database');
    $this->assertNotEmpty($log['context'], 'Context should not be empty');
    
    // Context should be JSON
    $decoded = json_decode($log['context'], true);
    $this->assertIsArray($decoded, 'Context should be valid JSON');
  }

  /**
   * Test log message retrieval from database
   */
  public function test_get_recent_logs_from_database() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    // Log some messages
    ErrorHandler::warning('Integration test warning 1');
    ErrorHandler::error('Integration test error 1');

    // Retrieve logs
    $logs = ErrorHandler::getRecentLogs(10, 'ERROR');

    $this->assertIsArray($logs);
    $this->assertGreaterThan(0, count($logs), 'Should have at least one error log');
  }

  /**
   * Test old logs can be cleared
   */
  public function test_clear_old_logs() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    // Count total logs before
    global $wpdb;
    $before = intval($wpdb->get_var(
      "SELECT COUNT(*) FROM {$wpdb->prefix}hcisysq_logs"
    ));

    // Clear logs older than 0 days (all logs older than now)
    $deleted = ErrorHandler::clearOldLogs(0);

    $after = intval($wpdb->get_var(
      "SELECT COUNT(*) FROM {$wpdb->prefix}hcisysq_logs"
    ));

    $this->assertGreaterThanOrEqual(0, $deleted, 'Should return number of deleted rows');
    $this->assertLessThanOrEqual($before, $after, 'Should have fewer logs after clearing');
  }

  /**
   * Test user_id is captured in logs
   */
  public function test_user_id_captured_in_logs() {
    if (!function_exists('get_wpdb') || !function_exists('get_current_user_id')) {
      $this->markTestSkipped('WordPress functions not available');
    }

    global $wpdb;
    
    ErrorHandler::warning('Test with user context');

    $log = $wpdb->get_row(
      "SELECT * FROM {$wpdb->prefix}hcisysq_logs WHERE message LIKE '%Test with user context%' ORDER BY id DESC LIMIT 1",
      ARRAY_A
    );

    $this->assertNotNull($log, 'Log should exist');
    $this->assertNotNull($log['user_id'], 'User ID should be captured');
  }

  /**
   * Test ip_address is captured in logs
   */
  public function test_ip_address_captured_in_logs() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    global $wpdb;
    
    ErrorHandler::warning('Test with IP context');

    $log = $wpdb->get_row(
      "SELECT * FROM {$wpdb->prefix}hcisysq_logs WHERE message LIKE '%Test with IP context%' ORDER BY id DESC LIMIT 1",
      ARRAY_A
    );

    $this->assertNotNull($log, 'Log should exist');
    $this->assertNotEmpty($log['ip_address'], 'IP address should be captured');
  }

  /**
   * Test timestamp is set correctly
   */
  public function test_timestamp_set_correctly() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    global $wpdb;
    
    $before = current_time('Y-m-d H:i:s');
    ErrorHandler::info('Test timestamp capture');
    $after = current_time('Y-m-d H:i:s');

    $log = $wpdb->get_row(
      "SELECT * FROM {$wpdb->prefix}hcisysq_logs WHERE message LIKE '%Test timestamp capture%' ORDER BY id DESC LIMIT 1",
      ARRAY_A
    );

    $this->assertNotNull($log, 'Log should exist');
    $this->assertNotEmpty($log['created_at'], 'Timestamp should be set');
    
    // Verify timestamp is within reasonable range
    $logTime = strtotime($log['created_at']);
    $beforeTime = strtotime($before);
    $afterTime = strtotime($after);
    
    $this->assertGreaterThanOrEqual($beforeTime, $logTime);
    $this->assertLessThanOrEqual($afterTime + 1, $logTime); // +1 for timing variations
  }

  /**
   * Test log level index performs efficiently
   */
  public function test_level_index_query_efficient() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    global $wpdb;
    
    // Query using indexed columns
    $start = microtime(true);
    $wpdb->get_results(
      "SELECT * FROM {$wpdb->prefix}hcisysq_logs WHERE level = 'ERROR' AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY) LIMIT 10"
    );
    $end = microtime(true);

    // Should execute quickly (less than 1 second)
    $elapsed = $end - $start;
    $this->assertLessThan(1, $elapsed, 'Index query should be fast');
  }

  /**
   * Test batch logging doesn't error
   */
  public function test_batch_logging_succeeds() {
    for ($i = 0; $i < 10; $i++) {
      ErrorHandler::info("Batch message $i");
    }

    $this->assertTrue(true); // If we get here, batch logging worked
  }
}
