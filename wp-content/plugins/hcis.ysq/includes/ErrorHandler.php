<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

use Monolog\Logger;
use Monolog\Handlers\StreamHandler;
use Monolog\Handlers\RotatingFileHandler;
use Monolog\Handlers\NullHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Error Handler with Structured Logging
 *
 * Centralized error handling with Monolog
 * Supports: File rotation, database logging, structured format
 *
 * @package HCISYSQ
 */
class ErrorHandler {

  private static $logger;
  private static $db_handler;
  
  const LOG_DIR = WP_CONTENT_DIR . '/hcisysq-logs';
  const LOG_TABLE = 'wp_hcisysq_logs';

  /**
   * Initialize error handler
   */
  public static function init() {
    self::setupLogger();
    self::registerHandlers();
    hcisysq_log('ErrorHandler initialized with Monolog');
  }

  /**
   * Setup Monolog logger
   */
  public static function setupLogger() {
    self::$logger = new Logger('HCISYSQ');

    // Rotating file handler (daily rotation)
    $file_handler = new RotatingFileHandler(
      self::LOG_DIR . '/hcisysq.log',
      30, // Keep 30 days of logs
      Logger::DEBUG
    );

    $formatter = new LineFormatter(
      "[%datetime%] %channel%.%level_name%: %message% %context%\n",
      "Y-m-d H:i:s",
      true,
      true
    );
    $file_handler->setFormatter($formatter);
    self::$logger->pushHandler($file_handler);

    // Database handler (custom)
    if (class_exists('\HCISYSQ\Logging\DatabaseHandler')) {
      self::$db_handler = new Logging\DatabaseHandler(Logger::WARNING);
      self::$logger->pushHandler(self::$db_handler);
    } else {
      // Fallback to null handler if DatabaseHandler not available
      self::$logger->pushHandler(new NullHandler());
    }

    // Create log directory if not exists
    if (!is_dir(self::LOG_DIR)) {
      @mkdir(self::LOG_DIR, 0755, true);
    }
  }

  /**
   * Register PHP error and exception handlers
   */
  public static function registerHandlers() {
    // Handle PHP errors
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
      self::$logger->error('PHP Error', [
        'errno' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline,
        'user_id' => get_current_user_id(),
        'ip_address' => self::getClientIP()
      ]);

      return false;
    });

    // Handle uncaught exceptions
    set_exception_handler(function(\Throwable $e) {
      self::$logger->critical('Uncaught Exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'user_id' => get_current_user_id(),
        'ip_address' => self::getClientIP()
      ]);
    });

    // Handle fatal errors
    register_shutdown_function(function() {
      $error = error_get_last();
      if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        self::$logger->critical('Fatal Error', [
          'message' => $error['message'],
          'file' => $error['file'],
          'line' => $error['line'],
          'user_id' => get_current_user_id(),
          'ip_address' => self::getClientIP()
        ]);
      }
    });
  }

  /**
   * Log a message
   *
   * @param string $message Message to log
   * @param string $level Log level (debug, info, warning, error, critical)
   * @param array $context Additional context data
   */
  public static function log($message, $level = 'info', $context = []) {
    if (!self::$logger) {
      self::setupLogger();
    }

    $context['user_id'] = $context['user_id'] ?? get_current_user_id();
    $context['ip_address'] = $context['ip_address'] ?? self::getClientIP();
    $context['timestamp'] = $context['timestamp'] ?? current_time('mysql');

    switch (strtolower($level)) {
      case 'debug':
        self::$logger->debug($message, $context);
        break;
      case 'info':
        self::$logger->info($message, $context);
        break;
      case 'warning':
        self::$logger->warning($message, $context);
        break;
      case 'error':
        self::$logger->error($message, $context);
        break;
      case 'critical':
        self::$logger->critical($message, $context);
        break;
      default:
        self::$logger->info($message, $context);
    }
  }

  /**
   * Convenience methods
   */
  public static function debug($message, $context = []) {
    self::log($message, 'debug', $context);
  }

  public static function info($message, $context = []) {
    self::log($message, 'info', $context);
  }

  public static function warning($message, $context = []) {
    self::log($message, 'warning', $context);
  }

  public static function error($message, $context = []) {
    self::log($message, 'error', $context);
  }

  public static function critical($message, $context = []) {
    self::log($message, 'critical', $context);
  }

  /**
   * Get client IP address
   *
   * @return string
   */
  private static function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }

    return sanitize_text_field($ip);
  }

  /**
   * Get logger instance (for advanced usage)
   *
   * @return Logger
   */
  public static function getLogger() {
    if (!self::$logger) {
      self::setupLogger();
    }
    return self::$logger;
  }

  /**
   * Alias for getLogger()
   *
   * @return Logger
   */
  public static function getInstance() {
    return self::getLogger();
  }

  /**
   * Get recent logs from database
   *
   * @param int $limit Number of logs to fetch
   * @param string $level Filter by level
   * @return array
   */
  public static function getRecentLogs($limit = 50, $level = null) {
    global $wpdb;

    $query = "SELECT * FROM {$wpdb->prefix}hcisysq_logs";
    $params = [];

    if ($level) {
      $query .= " WHERE level = %s";
      $params[] = $level;
    }

    $query .= " ORDER BY created_at DESC LIMIT %d";
    $params[] = $limit;

    $results = $wpdb->get_results(
      $wpdb->prepare($query, $params),
      ARRAY_A
    );

    return $results ?? [];
  }

  /**
   * Clear old logs (older than 30 days)
   *
   * @return int Number of deleted rows
   */
  public static function clearOldLogs($days = 30) {
    global $wpdb;

    $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    $result = $wpdb->query($wpdb->prepare(
      "DELETE FROM {$wpdb->prefix}hcisysq_logs WHERE created_at < %s",
      $cutoff
    ));

    self::info("Cleared {$result} old logs (older than {$days} days)");
    return $result ?: 0;
  }
}