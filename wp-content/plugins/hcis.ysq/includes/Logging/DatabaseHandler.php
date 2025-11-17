<?php
namespace HCISYSQ\Logging;

if (!defined('ABSPATH')) exit;

use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\LogRecord;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * WordPress database persistence for structured logs.
 */
class DatabaseHandler extends AbstractProcessingHandler {

  protected $table;

  /** @var LoggerInterface|null */
  private static $psrLogger = null;

  private const LOG_DIR = WP_CONTENT_DIR . '/hcisysq-logs';
  private const LOG_FILENAME = 'hcisysq.log';

  public function __construct($level = Logger::INFO, $bubble = true) {
    parent::__construct($level, $bubble);
    $this->table = 'hcisysq_logs';
  }

  /**
   * Build a Monolog\Logger instance that satisfies PSR-3 consumers.
   */
  public static function getLogger(): LoggerInterface {
    if (self::$psrLogger instanceof LoggerInterface) {
      return self::$psrLogger;
    }

    self::ensureLogDirectory();

    $logger = new Logger('hcisysq');

    $fileHandler = new RotatingFileHandler(self::getLogFilePath(), 30, Logger::DEBUG);
    $fileHandler->setFormatter(new LineFormatter("[%datetime%] %level_name%: %message% %context%\n", 'Y-m-d H:i:s', true, true));

    $databaseHandler = new self(Logger::INFO);
    $databaseHandler->setFormatter(new JsonFormatter());

    $logger->pushHandler($fileHandler);
    $logger->pushHandler($databaseHandler);

    self::$psrLogger = $logger;

    return self::$psrLogger;
  }

  public static function resetLogger(): void {
    self::$psrLogger = null;
  }

  protected static function getLogFilePath(): string {
    return rtrim(self::LOG_DIR, '/\\') . '/' . self::LOG_FILENAME;
  }

  protected static function ensureLogDirectory(): void {
    if (is_dir(self::LOG_DIR)) {
      return;
    }

    if (function_exists('wp_mkdir_p')) {
      wp_mkdir_p(self::LOG_DIR);
    } else {
      @mkdir(self::LOG_DIR, 0755, true);
    }
  }

  /**
   * Persist log record into the wp_hcisysq_logs table.
   */
  protected function write(LogRecord $record): void {
    global $wpdb;

    if (!isset($wpdb)) {
      return;
    }

    $tableName = $wpdb->prefix . $this->table;
    $tableExists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tableName));

    if ($tableExists !== $tableName) {
      return;
    }

    $context = $record->context ?? [];
    $userId = isset($context['user_id']) ? intval($context['user_id']) : null;
    $ipAddress = isset($context['ip_address']) ? $this->sanitize($context['ip_address']) : null;
    $component = isset($context['component']) ? $this->sanitize($context['component']) : 'core';
    $stackTrace = isset($context['stack_trace']) ? $context['stack_trace'] : '';
    $requestId = isset($context['request_id']) ? substr($this->sanitize($context['request_id']), 0, 64) : '';
    $severity = isset($context['severity']) ? strtoupper($context['severity']) : strtoupper($record->level->getName());

    $data = [
      'level' => strtoupper($record->level->getName()),
      'severity' => $severity,
      'message' => $record->message,
      'context' => self::encode($context['context'] ?? []),
      'extra' => self::encode($context['extra'] ?? []),
      'stack_trace' => $stackTrace,
      'component' => $component,
      'request_id' => $requestId,
      'created_at' => self::currentTime(),
      'user_id' => $userId,
      'ip_address' => $ipAddress,
    ];

    $format = [
      'level' => '%s',
      'severity' => '%s',
      'message' => '%s',
      'context' => '%s',
      'extra' => '%s',
      'stack_trace' => '%s',
      'component' => '%s',
      'request_id' => '%s',
      'created_at' => '%s',
      'user_id' => '%d',
      'ip_address' => '%s',
    ];

    $wpdb->insert($tableName, $data, $format);
  }

  private static function encode($value): string {
    if (function_exists('wp_json_encode')) {
      return wp_json_encode($value);
    }

    return json_encode($value, JSON_PARTIAL_OUTPUT_ON_ERROR);
  }

  private static function currentTime(): string {
    if (function_exists('current_time')) {
      return current_time('mysql');
    }

    return gmdate('Y-m-d H:i:s');
  }

  private function sanitize($value): string {
    $value = is_scalar($value) ? (string) $value : '';

    if (function_exists('sanitize_text_field')) {
      return sanitize_text_field($value);
    }

    return preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
  }
}
