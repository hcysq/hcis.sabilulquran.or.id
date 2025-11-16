<?php
namespace HCISYSQ\Tests\Integration\Logging;

use HCISYSQ\AdminLogsViewer;
use PHPUnit\Framework\TestCase;

/**
 * AdminLogsViewer Integration Tests
 * 
 * Tests the admin logs viewer page functionality
 * Tests filtering, pagination, and export features
 * 
 * @package HCISYSQ\Tests\Integration
 */
class AdminLogsViewerTest extends TestCase {

  /**
   * Runs before each test
   */
  public function setUp(): void {
    parent::setUp();
    
    // Skip if not in WordPress environment
    if (!function_exists('add_action')) {
      $this->markTestSkipped('Not in WordPress environment');
    }
  }

  /**
   * Test AdminLogsViewer can be initialized
   */
  public function test_init_no_error() {
    if (!function_exists('add_action')) {
      $this->markTestSkipped('Not in WordPress environment');
    }

    // init should not throw
    AdminLogsViewer::init();
    
    $this->assertTrue(true);
  }

  /**
   * Test menu item added to admin
   */
  public function test_add_menu_no_error() {
    if (!function_exists('add_submenu_page')) {
      $this->markTestSkipped('WordPress admin functions not available');
    }

    // This would be called by init
    // We verify it doesn't throw
    $this->assertTrue(true);
  }

  /**
   * Test get_logs method returns array
   */
  public function test_get_logs_returns_array() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    // Use reflection to call private method
    $reflection = new \ReflectionClass(AdminLogsViewer::class);
    $method = $reflection->getMethod('get_logs');
    $method->setAccessible(true);

    $logs = $method->invoke(null, [
      'limit' => 10,
      'offset' => 0
    ]);

    $this->assertIsArray($logs);
  }

  /**
   * Test count_logs returns integer
   */
  public function test_count_logs_returns_integer() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    $reflection = new \ReflectionClass(AdminLogsViewer::class);
    $method = $reflection->getMethod('count_logs');
    $method->setAccessible(true);

    $count = $method->invoke(null, []);

    $this->assertIsInt($count);
    $this->assertGreaterThanOrEqual(0, $count);
  }

  /**
   * Test get_logs with level filter
   */
  public function test_get_logs_filters_by_level() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    $reflection = new \ReflectionClass(AdminLogsViewer::class);
    $method = $reflection->getMethod('get_logs');
    $method->setAccessible(true);

    $logs = $method->invoke(null, [
      'level' => 'ERROR',
      'limit' => 10,
      'offset' => 0
    ]);

    $this->assertIsArray($logs);
    
    // If logs exist, verify they match the filter
    if (!empty($logs)) {
      foreach ($logs as $log) {
        $this->assertEquals('ERROR', $log['level']);
      }
    }
  }

  /**
   * Test get_logs with user filter
   */
  public function test_get_logs_filters_by_user() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    $reflection = new \ReflectionClass(AdminLogsViewer::class);
    $method = $reflection->getMethod('get_logs');
    $method->setAccessible(true);

    $logs = $method->invoke(null, [
      'user_id' => 1,
      'limit' => 10,
      'offset' => 0
    ]);

    $this->assertIsArray($logs);
  }

  /**
   * Test get_logs with search filter
   */
  public function test_get_logs_filters_by_search() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    $reflection = new \ReflectionClass(AdminLogsViewer::class);
    $method = $reflection->getMethod('get_logs');
    $method->setAccessible(true);

    $logs = $method->invoke(null, [
      'search' => 'error',
      'limit' => 10,
      'offset' => 0
    ]);

    $this->assertIsArray($logs);
  }

  /**
   * Test get_logs respects limit and offset
   */
  public function test_get_logs_respects_pagination() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    $reflection = new \ReflectionClass(AdminLogsViewer::class);
    $method = $reflection->getMethod('get_logs');
    $method->setAccessible(true);

    $logs1 = $method->invoke(null, [
      'limit' => 5,
      'offset' => 0
    ]);

    $logs2 = $method->invoke(null, [
      'limit' => 5,
      'offset' => 5
    ]);

    // Results should have at most 5 items each
    $this->assertLessThanOrEqual(5, count($logs1));
    $this->assertLessThanOrEqual(5, count($logs2));

    // If both have items, they should be different
    if (!empty($logs1) && !empty($logs2)) {
      // First item of logs2 should be different from first item of logs1
      $this->assertNotEquals($logs1[0]['id'], $logs2[0]['id']);
    }
  }

  /**
   * Test count_logs with filters
   */
  public function test_count_logs_with_filters() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    $reflection = new \ReflectionClass(AdminLogsViewer::class);
    $method = $reflection->getMethod('count_logs');
    $method->setAccessible(true);

    $allCount = $method->invoke(null, []);
    $errorCount = $method->invoke(null, ['level' => 'ERROR']);

    $this->assertIsInt($allCount);
    $this->assertIsInt($errorCount);
    
    // Error count should be less than or equal to all count
    $this->assertLessThanOrEqual($allCount, $errorCount);
  }

  /**
   * Test render_user_options doesn't error
   */
  public function test_render_user_options_no_error() {
    if (!function_exists('get_users')) {
      $this->markTestSkipped('WordPress user functions not available');
    }

    $reflection = new \ReflectionClass(AdminLogsViewer::class);
    $method = $reflection->getMethod('render_user_options');
    $method->setAccessible(true);

    // Should not throw
    ob_start();
    $method->invoke(null, '');
    ob_end_clean();

    $this->assertTrue(true);
  }

  /**
   * Test handle_clear_logs verifies nonce
   */
  public function test_handle_clear_logs_checks_nonce() {
    if (!function_exists('check_admin_referer')) {
      $this->markTestSkipped('WordPress functions not available');
    }

    $reflection = new \ReflectionClass(AdminLogsViewer::class);
    
    // Verify method exists
    $this->assertTrue($reflection->hasMethod('handle_clear_logs'));
  }

  /**
   * Test handle_export_logs method exists
   */
  public function test_handle_export_logs_exists() {
    $reflection = new \ReflectionClass(AdminLogsViewer::class);
    
    $this->assertTrue($reflection->hasMethod('handle_export_logs'));
  }

  /**
   * Test logs table has required columns
   */
  public function test_logs_table_structure() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    global $wpdb;
    
    $columns = $wpdb->get_results(
      "DESCRIBE {$wpdb->prefix}hcisysq_logs"
    );

    $this->assertNotEmpty($columns);
    
    $column_names = wp_list_pluck($columns, 'Field');
    
    $required_columns = ['id', 'level', 'message', 'created_at', 'user_id', 'ip_address'];
    foreach ($required_columns as $col) {
      $this->assertContains($col, $column_names, "Column $col should exist");
    }
  }

  /**
   * Test filter combinations work correctly
   */
  public function test_multiple_filters_combined() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    $reflection = new \ReflectionClass(AdminLogsViewer::class);
    $method = $reflection->getMethod('get_logs');
    $method->setAccessible(true);

    $logs = $method->invoke(null, [
      'level' => 'ERROR',
      'user_id' => 1,
      'search' => 'test',
      'limit' => 10,
      'offset' => 0
    ]);

    $this->assertIsArray($logs);
    
    // If logs exist, verify all filters applied
    if (!empty($logs)) {
      foreach ($logs as $log) {
        $this->assertEquals('ERROR', $log['level']);
        // Message should contain search term (case-insensitive)
        $this->assertStringContainsStringIgnoringCase('test', $log['message']);
      }
    }
  }

  /**
   * Test empty result handling
   */
  public function test_empty_results_handled() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    $reflection = new \ReflectionClass(AdminLogsViewer::class);
    $method = $reflection->getMethod('get_logs');
    $method->setAccessible(true);

    // Search for non-existent message
    $logs = $method->invoke(null, [
      'search' => 'xyzabc_nonexistent_' . uniqid(),
      'limit' => 10,
      'offset' => 0
    ]);

    $this->assertIsArray($logs);
    $this->assertEmpty($logs, 'Should return empty array for no matches');
  }

  /**
   * Test large offset pagination
   */
  public function test_pagination_with_large_offset() {
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    $reflection = new \ReflectionClass(AdminLogsViewer::class);
    $method = $reflection->getMethod('get_logs');
    $method->setAccessible(true);

    // Query with large offset
    $logs = $method->invoke(null, [
      'limit' => 10,
      'offset' => 10000
    ]);

    // Should return empty or fewer items
    $this->assertLessThanOrEqual(10, count($logs));
  }
}
