<?php
namespace HCISYSQ\Logging;

if (!defined('ABSPATH')) exit;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\LogRecord;

/**
 * WordPress Database Handler for Monolog
 *
 * Stores logs in wp_hcisysq_logs table
 * Used for ERROR and CRITICAL level logs
 *
 * @package HCISYSQ
 */
class DatabaseHandler extends AbstractProcessingHandler {

  protected $table;

  public function __construct($level = Logger::WARNING, $bubble = true) {
    parent::__construct($level, $bubble);
    $this->table = 'wp_hcisysq_logs';
  }

  /**
   * Write log record to database
   *
   * @param LogRecord $record
   */
  protected function write(LogRecord $record): void {
    global $wpdb;

    $table_name = $wpdb->prefix . $this->table;

    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
      return; // Table doesn't exist yet, skip
    }

    $data = [
      'level' => $record->level->getName(),
      'message' => $record->formatted ?? $record->message,
      'context' => json_encode($record->context ?? []),
      'created_at' => current_time('mysql'),
      'user_id' => get_current_user_id() ?: null,
      'ip_address' => sanitize_text_field(
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
        $_SERVER['HTTP_CLIENT_IP'] ?? 
        $_SERVER['REMOTE_ADDR'] ?? 
        'UNKNOWN'
      )
    ];

    $format = [
      'level' => '%s',
      'message' => '%s',
      'context' => '%s',
      'created_at' => '%s',
      'user_id' => '%d',
      'ip_address' => '%s'
    ];

    $wpdb->insert($table_name, $data, $format);
  }
}