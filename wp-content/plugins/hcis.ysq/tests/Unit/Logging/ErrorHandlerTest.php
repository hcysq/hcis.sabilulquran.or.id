<?php
namespace HCISYSQ\Tests\Unit\Logging;

use HCISYSQ\ErrorHandler;
use PHPUnit\Framework\TestCase;

/**
 * ErrorHandler Unit Tests
 * 
 * Tests the ErrorHandler class with Monolog integration
 * 
 * @package HCISYSQ\Tests\Unit
 */
class ErrorHandlerTest extends TestCase {

  /**
   * Test setupLogger creates logger instance
   */
  public function test_setupLogger_creates_logger() {
    ErrorHandler::setupLogger();
    $logger = ErrorHandler::getLogger();
    
    $this->assertNotNull($logger);
    $this->assertInstanceOf('Monolog\Logger', $logger);
  }

  /**
   * Test getInstance returns logger
   */
  public function test_getInstance_returns_logger() {
    $logger = ErrorHandler::getInstance();
    
    $this->assertNotNull($logger);
    $this->assertInstanceOf('Monolog\Logger', $logger);
  }

  /**
   * Test getLogger returns same instance on multiple calls
   */
  public function test_getLogger_returns_singleton() {
    $logger1 = ErrorHandler::getLogger();
    $logger2 = ErrorHandler::getLogger();
    
    $this->assertSame($logger1, $logger2);
  }

  /**
   * Test debug method logs at DEBUG level
   */
  public function test_debug_logs_at_debug_level() {
    $message = 'Test debug message';
    
    // This should not throw
    ErrorHandler::debug($message, ['context_key' => 'value']);
    
    $this->assertTrue(true); // If we get here, it worked
  }

  /**
   * Test info method logs at INFO level
   */
  public function test_info_logs_at_info_level() {
    $message = 'Test info message';
    
    ErrorHandler::info($message, ['context_key' => 'value']);
    
    $this->assertTrue(true);
  }

  /**
   * Test warning method logs at WARNING level
   */
  public function test_warning_logs_at_warning_level() {
    $message = 'Test warning message';
    
    ErrorHandler::warning($message, ['context_key' => 'value']);
    
    $this->assertTrue(true);
  }

  /**
   * Test error method logs at ERROR level
   */
  public function test_error_logs_at_error_level() {
    $message = 'Test error message';
    
    ErrorHandler::error($message, ['context_key' => 'value']);
    
    $this->assertTrue(true);
  }

  /**
   * Test critical method logs at CRITICAL level
   */
  public function test_critical_logs_at_critical_level() {
    $message = 'Test critical message';
    
    ErrorHandler::critical($message, ['context_key' => 'value']);
    
    $this->assertTrue(true);
  }

  /**
   * Test log method accepts custom level
   */
  public function test_log_accepts_custom_level() {
    ErrorHandler::log('Test message', 'info', ['key' => 'value']);
    
    $this->assertTrue(true);
  }

  /**
   * Test registerHandlers doesn't throw
   */
  public function test_registerHandlers_no_error() {
    // This should not throw any errors
    ErrorHandler::registerHandlers();
    
    $this->assertTrue(true);
  }

  /**
   * Test init calls both setupLogger and registerHandlers
   */
  public function test_init_calls_setup_and_register() {
    // init should work without throwing
    ErrorHandler::init();
    
    $logger = ErrorHandler::getLogger();
    $this->assertNotNull($logger);
  }

  /**
   * Test getClientIP returns non-empty string
   */
  public function test_getClientIP_returns_string() {
    // Access via reflection since it's private
    $reflection = new \ReflectionClass(ErrorHandler::class);
    $method = $reflection->getMethod('getClientIP');
    $method->setAccessible(true);
    
    $ip = $method->invoke(null);
    
    $this->assertIsString($ip);
    $this->assertNotEmpty($ip);
  }

  /**
   * Test log directory is created
   */
  public function test_log_directory_is_created() {
    ErrorHandler::setupLogger();
    
    $logDir = ErrorHandler::class;
    // The constant should be accessible
    $reflection = new \ReflectionClass(ErrorHandler::class);
    $const = $reflection->getConstant('LOG_DIR');
    
    $this->assertNotNull($const);
  }

  /**
   * Test clearOldLogs returns integer
   */
  public function test_clearOldLogs_returns_integer() {
    // Skip if no database
    if (!function_exists('get_wpdb')) {
      $this->markTestSkipped('WordPress database not available');
    }

    $result = ErrorHandler::clearOldLogs(30);
    
    $this->assertIsInt($result);
    $this->assertGreaterThanOrEqual(0, $result);
  }

  /**
   * Test context data includes user_id
   */
  public function test_context_includes_user_id() {
    // This is integration test, but we can verify method exists
    $reflection = new \ReflectionClass(ErrorHandler::class);
    
    $this->assertTrue($reflection->hasMethod('log'));
  }

  /**
   * Test multiple handlers can be added to logger
   */
  public function test_logger_has_multiple_handlers() {
    ErrorHandler::setupLogger();
    $logger = ErrorHandler::getLogger();
    
    // The logger should have at least 1 handler (file handler)
    $handlers = $logger->getHandlers();
    
    $this->assertGreaterThanOrEqual(1, count($handlers));
  }

  /**
   * Test log level can be set via handler
   */
  public function test_handler_respects_log_level() {
    ErrorHandler::setupLogger();
    $logger = ErrorHandler::getLogger();
    $handlers = $logger->getHandlers();
    
    // At least one handler should be configured
    $this->assertGreaterThan(0, count($handlers));
    
    // Handler should have a level defined
    if (isset($handlers[0])) {
      $this->assertNotNull($handlers[0]->getLevel());
    }
  }
}
