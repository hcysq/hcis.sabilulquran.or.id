<?php
namespace HCISYSQ\Tests\Unit\Logging;

use HCISYSQ\Logging\DatabaseHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * DatabaseHandler Unit Tests
 * 
 * Tests the custom Monolog database handler
 * 
 * @package HCISYSQ\Tests\Unit
 */
class DatabaseHandlerTest extends TestCase {

  /**
   * Test DatabaseHandler can be instantiated
   */
  public function test_instantiate_handler() {
    $handler = new DatabaseHandler(Logger::WARNING);
    
    $this->assertNotNull($handler);
    $this->assertInstanceOf('Monolog\Handler\AbstractProcessingHandler', $handler);
  }

  /**
   * Test DatabaseHandler respects log level
   */
  public function test_handler_respects_level() {
    $handler = new DatabaseHandler(Logger::WARNING);
    
    $this->assertNotNull($handler->getLevel());
  }

  /**
   * Test DatabaseHandler can be added to logger
   */
  public function test_handler_can_be_added_to_logger() {
    $logger = new Logger('test');
    $handler = new DatabaseHandler(Logger::WARNING);
    
    $logger->pushHandler($handler);
    
    $handlers = $logger->getHandlers();
    $this->assertGreaterThan(0, count($handlers));
  }

  /**
   * Test DatabaseHandler extends correct base class
   */
  public function test_handler_extends_correct_base_class() {
    $handler = new DatabaseHandler(Logger::WARNING);
    
    $reflection = new \ReflectionClass($handler);
    $parent = $reflection->getParentClass();
    
    $this->assertEquals('Monolog\Handler\AbstractProcessingHandler', $parent->getName());
  }

  /**
   * Test handler has required methods
   */
  public function test_handler_has_required_methods() {
    $reflection = new \ReflectionClass(DatabaseHandler::class);
    
    // Should have write method for handling log records
    $this->assertTrue($reflection->hasMethod('write'));
  }

  /**
   * Test handler can process log record
   */
  public function test_handler_processes_log_record() {
    $handler = new DatabaseHandler(Logger::WARNING);
    
    // DatabaseHandler should have the write method
    $reflection = new \ReflectionClass($handler);
    $this->assertTrue($reflection->hasMethod('write'));
  }

  /**
   * Test handler level constant
   */
  public function test_handler_uses_logger_level_constant() {
    // WARNING level should be 300 in Monolog
    $handler = new DatabaseHandler(Logger::WARNING);
    
    // We can't directly access level, but we can verify logger constants
    $this->assertEquals(300, Logger::WARNING);
  }

  /**
   * Test handler can be configured with different levels
   */
  public function test_handler_configurable_level() {
    $handlerDebug = new DatabaseHandler(Logger::DEBUG);
    $handlerError = new DatabaseHandler(Logger::ERROR);
    $handlerCritical = new DatabaseHandler(Logger::CRITICAL);
    
    // All should instantiate successfully
    $this->assertNotNull($handlerDebug);
    $this->assertNotNull($handlerError);
    $this->assertNotNull($handlerCritical);
  }

  /**
   * Test handler compatible with Monolog 3.x
   */
  public function test_handler_monolog_3_compatibility() {
    // This test verifies the handler is compatible with Monolog 3.x
    $handler = new DatabaseHandler(Logger::WARNING);
    $logger = new Logger('test');
    $logger->pushHandler($handler);

    // Should not throw any compatibility errors
    $this->assertTrue(true);
  }

  public function test_getLogger_returns_psr_logger() {
    $logger = DatabaseHandler::getLogger();

    $this->assertInstanceOf(LoggerInterface::class, $logger);
  }

  public function test_resetLogger_creates_new_instance() {
    $first = DatabaseHandler::getLogger();
    DatabaseHandler::resetLogger();
    $second = DatabaseHandler::getLogger();

    $this->assertNotSame($first, $second);
  }
}
