<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

use HCISYSQ\Logging\DatabaseHandler;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Centralized error handling and normalization layer.
 */
class ErrorHandler {

  /** @var LoggerInterface|null */
  private static $logger = null;

  /** @var bool */
  private static $handlersRegistered = false;

  /** @var string|null */
  private static $requestId = null;

  private const SENSITIVE_KEYS = [
    'password', 'pass', 'pwd', 'secret', 'api_key', 'apikey',
    'authorization', 'token', 'access_token', 'refresh_token',
    'client_secret', 'credit_card', 'ssn'
  ];

  /**
   * Initialize error handler and register hooks.
   */
  public static function init(): void {
    self::setupLogger();
    self::registerHandlers();
    self::log('ErrorHandler initialized', 'info', ['component' => 'bootstrap']);
  }

  /**
   * Setup PSR-3 logger instance backed by Monolog + database handler.
   */
  public static function setupLogger(): void {
    if (self::$logger) {
      return;
    }

    if (!class_exists(DatabaseHandler::class)) {
      return;
    }

    self::$logger = DatabaseHandler::getLogger();
  }

  /**
   * Register PHP error/exception handlers.
   */
  public static function registerHandlers(): void {
    if (self::$handlersRegistered) {
      return;
    }

    set_error_handler([__CLASS__, 'handlePhpError']);
    set_exception_handler([__CLASS__, 'handleException']);
    register_shutdown_function([__CLASS__, 'handleShutdown']);

    self::$handlersRegistered = true;
  }

  /**
   * PSR-3 compatible logging entry point.
   */
  public static function log($message, $level = 'info', array $context = [], ?Throwable $throwable = null): void {
    $logger = self::getLogger();
    if (!$logger) {
      return;
    }

    $normalized = self::normalizeLogData($message, $level, $context, $throwable);
    $logger->log($normalized['level'], $normalized['message'], $normalized['context']);

    if (function_exists('do_action')) {
      do_action('hcisysq/logging/log_created', $normalized);
    }
  }

  public static function debug($message, array $context = []): void {
    self::log($message, 'debug', $context);
  }

  public static function info($message, array $context = []): void {
    self::log($message, 'info', $context);
  }

  public static function warning($message, array $context = []): void {
    self::log($message, 'warning', $context);
  }

  public static function error($message, array $context = []): void {
    self::log($message, 'error', $context);
  }

  public static function critical($message, array $context = []): void {
    self::log($message, 'critical', $context);
  }

  /**
   * Normalize log data for storage/transport and expose to tests.
   */
  public static function normalizeLogData($message, $level = 'info', array $context = [], ?Throwable $throwable = null): array {
    $level = self::normalizeLevel($level);
    $timestamp = self::now();
    $stackTrace = $context['stack_trace'] ?? ($throwable ? $throwable->getTraceAsString() : self::buildStackTrace());
    $component = self::sanitizeField($context['component'] ?? 'core');
    $requestId = $context['request_id'] ?? self::generateRequestId();
    $userId = self::resolveUserId($context);
    $ipAddress = $context['ip_address'] ?? self::getClientIP();

    $metaKeys = ['component', 'request_id', 'stack_trace', 'user_id', 'ip_address', 'extra'];
    $cleanContext = array_diff_key($context, array_flip($metaKeys));
    $cleanContext = self::redactSensitiveData($cleanContext);

    $extra = isset($context['extra']) ? self::redactSensitiveData((array) $context['extra']) : [];

    return [
      'level' => $level,
      'message' => self::stringifyMessage($message),
      'context' => [
        'context' => $cleanContext,
        'component' => $component,
        'severity' => strtoupper($level),
        'stack_trace' => $stackTrace,
        'user_id' => $userId,
        'ip_address' => self::sanitizeField($ipAddress),
        'request_id' => $requestId,
        'timestamp' => $timestamp,
        'extra' => $extra,
      ],
    ];
  }

  /**
   * PHP error handler callback.
   */
  public static function handlePhpError(int $errno, string $errstr, string $errfile, int $errline): bool {
    $severity = self::severityFromErrorNumber($errno);

    self::log($errstr, $severity, [
      'component' => 'php-error',
      'error_code' => $errno,
      'error_type' => self::getErrorType($errno),
      'file' => $errfile,
      'line' => $errline,
      'stack_trace' => self::buildStackTrace(),
    ]);

    return false; // Allow PHP internal handler to continue
  }

  /**
   * Uncaught exception handler.
   */
  public static function handleException(Throwable $exception): void {
    self::log($exception->getMessage(), 'critical', [
      'component' => 'exception',
      'exception_class' => get_class($exception),
      'file' => $exception->getFile(),
      'line' => $exception->getLine(),
      'stack_trace' => $exception->getTraceAsString(),
    ], $exception);
  }

  /**
   * Shutdown handler for fatal errors.
   */
  public static function handleShutdown(): void {
    $error = error_get_last();
    if (!$error) {
      return;
    }

    if (in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
      self::log($error['message'], 'critical', [
        'component' => 'fatal-error',
        'error_code' => $error['type'],
        'error_type' => self::getErrorType($error['type']),
        'file' => $error['file'],
        'line' => $error['line'],
        'stack_trace' => $error['message'] . "\n" . self::buildStackTrace(),
      ]);
    }
  }

  /**
   * Get the logger instance.
   */
  public static function getLogger(): ?LoggerInterface {
    if (!self::$logger) {
      self::setupLogger();
    }

    return self::$logger;
  }

  /**
   * Alias for getLogger to maintain BC with existing calls.
   */
  public static function getInstance(): ?LoggerInterface {
    return self::getLogger();
  }

  /**
   * Fetch recent logs directly from database.
   */
  public static function getRecentLogs($limit = 50, $level = null): array {
    global $wpdb;

    if (!isset($wpdb)) {
      return [];
    }

    $table = $wpdb->prefix . 'hcisysq_logs';
    $query = "SELECT * FROM {$table}";
    $params = [];

    if ($level) {
      $query .= ' WHERE level = %s';
      $params[] = $level;
    }

    $query .= ' ORDER BY created_at DESC LIMIT %d';
    $params[] = intval($limit);

    $prepared = !empty($params) ? $wpdb->prepare($query, $params) : $query;
    $results = $wpdb->get_results($prepared, ARRAY_A);

    return $results ?? [];
  }

  public static function clearOldLogs($days = 30): int {
    global $wpdb;

    if (!isset($wpdb)) {
      return 0;
    }

    $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    $table = $wpdb->prefix . 'hcisysq_logs';

    $prepared = $wpdb->prepare("DELETE FROM {$table} WHERE created_at < %s", $cutoff);
    $result = $wpdb->query($prepared);

    self::info("Cleared {$result} logs older than {$days} days", ['component' => 'maintenance']);

    return $result ? intval($result) : 0;
  }

  /**
   * Map PHP error code to severity.
   */
  private static function severityFromErrorNumber(int $errno): string {
    switch ($errno) {
      case E_NOTICE:
      case E_USER_NOTICE:
      case E_DEPRECATED:
      case E_USER_DEPRECATED:
        return 'notice';
      case E_WARNING:
      case E_USER_WARNING:
      case E_STRICT:
        return 'warning';
      case E_USER_ERROR:
        return 'error';
      default:
        return 'critical';
    }
  }

  private static function getErrorType(int $errno): string {
    $map = [
      E_ERROR => 'E_ERROR',
      E_WARNING => 'E_WARNING',
      E_PARSE => 'E_PARSE',
      E_NOTICE => 'E_NOTICE',
      E_CORE_ERROR => 'E_CORE_ERROR',
      E_CORE_WARNING => 'E_CORE_WARNING',
      E_COMPILE_ERROR => 'E_COMPILE_ERROR',
      E_COMPILE_WARNING => 'E_COMPILE_WARNING',
      E_USER_ERROR => 'E_USER_ERROR',
      E_USER_WARNING => 'E_USER_WARNING',
      E_USER_NOTICE => 'E_USER_NOTICE',
      E_STRICT => 'E_STRICT',
      E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
      E_DEPRECATED => 'E_DEPRECATED',
      E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    ];

    return $map[$errno] ?? 'UNKNOWN';
  }

  private static function normalizeLevel($level): string {
    $allowed = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

    if (is_string($level)) {
      $level = strtolower($level);
    } elseif (is_int($level)) {
      if ($level >= 600) {
        $level = 'emergency';
      } elseif ($level >= 550) {
        $level = 'alert';
      } elseif ($level >= 500) {
        $level = 'critical';
      } elseif ($level >= 400) {
        $level = 'error';
      } elseif ($level >= 300) {
        $level = 'warning';
      } elseif ($level >= 250) {
        $level = 'notice';
      } else {
        $level = 'debug';
      }
    } else {
      $level = 'info';
    }

    return in_array($level, $allowed, true) ? $level : 'info';
  }

  private static function buildStackTrace(): string {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    array_shift($trace);

    $formatted = [];
    foreach ($trace as $frame) {
      $file = $frame['file'] ?? '[internal]';
      $line = $frame['line'] ?? 0;
      $function = $frame['function'] ?? '';
      $formatted[] = sprintf('%s:%s %s()', $file, $line, $function);

      if (count($formatted) >= 15) {
        break;
      }
    }

    return implode("\n", $formatted);
  }

  private static function getClientIP(): string {
    $ip = 'UNKNOWN';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
      $ip = $_SERVER['REMOTE_ADDR'];
    }

    return self::sanitizeField($ip);
  }

  private static function resolveUserId(array $context): int {
    if (isset($context['user_id'])) {
      return intval($context['user_id']);
    }

    if (function_exists('get_current_user_id')) {
      return intval(get_current_user_id());
    }

    return 0;
  }

  private static function generateRequestId(): string {
    if (self::$requestId) {
      return self::$requestId;
    }

    if (function_exists('wp_generate_uuid4')) {
      self::$requestId = wp_generate_uuid4();
    } else {
      self::$requestId = uniqid('hcisysq_', true);
    }

    return self::$requestId;
  }

  private static function now(): string {
    if (function_exists('current_time')) {
      return current_time('mysql');
    }

    return gmdate('Y-m-d H:i:s');
  }

  private static function sanitizeField($value): string {
    $value = is_scalar($value) ? (string) $value : '';

    if (function_exists('sanitize_text_field')) {
      return sanitize_text_field($value);
    }

    return preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
  }

  private static function redactSensitiveData($data) {
    if (is_array($data)) {
      foreach ($data as $key => $value) {
        if (self::isSensitiveKey($key)) {
          $data[$key] = '[REDACTED]';
        } else {
          $data[$key] = self::redactSensitiveData($value);
        }
      }
      return $data;
    }

    if (is_object($data)) {
      $objectVars = get_object_vars($data);
      foreach ($objectVars as $key => $value) {
        $data->$key = self::redactSensitiveData($value);
      }
      return $data;
    }

    return $data;
  }

  private static function isSensitiveKey($key): bool {
    if (!is_string($key)) {
      return false;
    }

    $key = strtolower($key);

    foreach (self::SENSITIVE_KEYS as $sensitive) {
      if (false !== strpos($key, $sensitive)) {
        return true;
      }
    }

    return false;
  }

  private static function stringifyMessage($message): string {
    if (is_scalar($message)) {
      return (string) $message;
    }

    if (function_exists('wp_json_encode')) {
      return wp_json_encode($message);
    }

    return json_encode($message, JSON_PARTIAL_OUTPUT_ON_ERROR);
  }
}
