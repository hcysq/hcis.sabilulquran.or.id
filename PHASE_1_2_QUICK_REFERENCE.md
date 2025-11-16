# Phase 1.2 Developer Quick Reference

## Error Handling & Structured Logging

### Quick Start

#### Using ErrorHandler
```php
// Basic logging
ErrorHandler::info('User successfully logged in');
ErrorHandler::warning('Suspicious login attempt from ' . $_SERVER['REMOTE_ADDR']);
ErrorHandler::error('Database connection failed');
ErrorHandler::critical('Critical system failure', ['error_code' => $code]);

// Legacy way still works (backward compatible)
hcisysq_log('Message'); // defaults to info
hcisysq_log('Warning message', 'warning');
```

#### Log Levels (Monolog)
```
DEBUG (100)   → Not persisted to database (file only)
INFO (200)    → Not persisted to database (file only)
WARNING (300) → ✅ Persisted to database
ERROR (400)   → ✅ Persisted to database
CRITICAL (500)→ ✅ Persisted to database
```

### Configuration

#### Log Directory
- **Location**: `wp-content/hcisysq-logs/`
- **File**: `hcisysq.log` (daily rotation)
- **Retention**: 30 days (auto-cleanup)
- **Permissions**: 755 (auto-created)

#### Database Table
```sql
SELECT * FROM wp_hcisysq_logs 
WHERE level IN ('WARNING', 'ERROR', 'CRITICAL')
ORDER BY created_at DESC;
```

#### Add to wp-admin
Menu: **HCIS Portal > Error Logs**
- Filter by level, user, search
- Expandable details
- CSV export
- Clear all logs

### Common Tasks

#### View Recent Errors
```php
$logs = ErrorHandler::getRecentLogs(50, 'ERROR');
foreach ($logs as $log) {
  echo $log['message'] . ' - ' . $log['created_at'];
}
```

#### Clear Old Logs
```php
// Remove logs older than 7 days
$deleted = ErrorHandler::clearOldLogs(7);
echo "Deleted $deleted old logs";
```

#### Get Logger Instance
```php
// For advanced Monolog usage
$logger = ErrorHandler::getInstance();
$logger->pushHandler(new CustomHandler());
```

#### Check Log Files
```bash
# View latest logs
tail -f wp-content/hcisysq-logs/hcisysq.log

# Check log size
du -h wp-content/hcisysq-logs/

# List rotated logs
ls -la wp-content/hcisysq-logs/
```

### Monitoring

#### Dashboard Widget
- Last sync timestamp
- Error count (last 24h)
- Critical count
- System health status

#### Database Queries
```php
// Count errors by level
global $wpdb;
$wpdb->get_results("
  SELECT level, COUNT(*) as count 
  FROM {$wpdb->prefix}hcisysq_logs 
  GROUP BY level
");

// Find user errors
$wpdb->get_results($wpdb->prepare("
  SELECT * FROM {$wpdb->prefix}hcisysq_logs 
  WHERE user_id = %d AND level = 'ERROR'
  ORDER BY created_at DESC
", $user_id));
```

### Troubleshooting

#### Logs not appearing in database?
```php
// Check if table exists
global $wpdb;
$exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}hcisysq_logs'");
if (!$exists) {
  echo "Table missing - Run plugin activation";
}

// Check if Monolog initialized
if (class_exists('HCISYSQ\ErrorHandler')) {
  $logger = ErrorHandler::getInstance();
  echo "Monolog: OK";
}
```

#### File permissions issue?
```bash
# Fix permissions
chmod 755 wp-content/hcisysq-logs/
chmod 644 wp-content/hcisysq-logs/*.log

# Check ownership
ls -la wp-content/hcisysq-logs/
```

#### Monolog not loading?
```bash
# Check composer autoload
php -r "require 'vendor/autoload.php'; echo 'OK';"

# Verify Monolog installed
composer show monolog/monolog
```

### Performance Tips

#### Reduce Database Logging
```php
// Only log WARNING+ (already default)
// DEBUG/INFO go to files only (lighter)
ErrorHandler::info('Not persisted'); // File only
ErrorHandler::warning('Persisted'); // File + DB
```

#### Optimize Queries
```php
// Use indexed columns in queries
$wpdb->get_results("
  SELECT * FROM {$wpdb->prefix}hcisysq_logs
  WHERE level = %s AND created_at > %s
  LIMIT 25
", ['ERROR', date('Y-m-d H:i:s', strtotime('-7 days'))]);
```

#### Auto-Cleanup
Add to WP-Cron or system cron:
```php
// Clean logs older than 30 days daily
if (function_exists('wp_schedule_event')) {
  if (!wp_next_scheduled('hcisysq_cleanup_logs')) {
    wp_schedule_event(time(), 'daily', 'hcisysq_cleanup_logs');
  }
}

add_action('hcisysq_cleanup_logs', function() {
  ErrorHandler::clearOldLogs(30);
});
```

### Integration Examples

#### Catch Specific Errors
```php
try {
  // Risky operation
  $result = dangerous_function();
} catch (Exception $e) {
  ErrorHandler::error('Operation failed', [
    'error' => $e->getMessage(),
    'code' => $e->getCode(),
    'file' => $e->getFile(),
    'line' => $e->getLine()
  ]);
}
```

#### Log User Actions
```php
ErrorHandler::info('User action logged', [
  'action' => 'profile_update',
  'user_id' => get_current_user_id(),
  'old_values' => $old_data,
  'new_values' => $new_data
]);
```

#### Monitor API Calls
```php
$start = microtime(true);
try {
  $result = $api->call();
  $duration = (microtime(true) - $start) * 1000;
  
  if ($duration > 1000) {
    ErrorHandler::warning('Slow API call', [
      'endpoint' => 'users',
      'duration_ms' => $duration,
      'result' => 'success'
    ]);
  }
} catch (Exception $e) {
  ErrorHandler::error('API call failed', [
    'endpoint' => 'users',
    'error' => $e->getMessage()
  ]);
}
```

### Admin Interface Guide

**URL**: `/wp-admin/admin.php?page=hcis-error-logs`

**Filters Available**:
- **Level**: DEBUG, INFO, WARNING, ERROR, CRITICAL
- **User**: Select specific user or system
- **Search**: Free text search in message

**Actions**:
- **View**: Click "View" to expand full message + context
- **Export**: Download all filtered logs as CSV
- **Clear**: Delete all logs (with confirmation)
- **Pagination**: Navigate through results

**Tips**:
- Combine multiple filters for precise results
- Export to Excel for analysis
- Use search for specific error messages
- Reset filters to show all logs

### Testing

#### Run Error Handler Tests
```bash
cd wp-content/plugins/hcis.ysq

# Run all tests
phpunit tests/Unit/Logging/ErrorHandlerTest.php
phpunit tests/Integration/Logging/ErrorHandlerIntegrationTest.php

# Run specific test
phpunit tests/Unit/Logging/ErrorHandlerTest.php --filter test_debug_logs
```

#### Test Example
```php
public function test_error_logged_to_database() {
  ErrorHandler::error('Test message', ['key' => 'value']);
  
  global $wpdb;
  $log = $wpdb->get_row(
    "SELECT * FROM {$wpdb->prefix}hcisysq_logs 
     WHERE message = 'Test message' LIMIT 1"
  );
  
  $this->assertNotNull($log);
  $this->assertEquals('ERROR', $log['level']);
}
```

### API Reference

#### ErrorHandler Class
```php
class ErrorHandler {
  // Setup
  public static function init()
  public static function setupLogger()
  public static function registerHandlers()
  
  // Logging methods
  public static function debug($msg, $context = [])
  public static function info($msg, $context = [])
  public static function warning($msg, $context = [])
  public static function error($msg, $context = [])
  public static function critical($msg, $context = [])
  public static function log($msg, $level = 'info', $context = [])
  
  // Access
  public static function getLogger() // Returns Monolog\Logger
  public static function getInstance() // Alias for getLogger()
  
  // Query
  public static function getRecentLogs($limit = 50, $level = null) // Returns []
  
  // Maintenance
  public static function clearOldLogs($days = 30) // Returns int (deleted count)
}
```

#### AdminLogsViewer Class
```php
class AdminLogsViewer {
  public static function init()
  public static function add_menu()
  public static function render_page()
  public static function handle_clear_logs()
  public static function handle_export_logs()
}
```

### Best Practices

✅ **DO**:
- Log errors with context for debugging
- Use appropriate log levels
- Clean up old logs regularly
- Monitor admin dashboard
- Export logs for analysis
- Use prepared statements in queries

❌ **DON'T**:
- Log sensitive data (passwords, tokens)
- Log entire user objects
- Ignore critical errors
- Leave logs to grow unbounded
- Log in tight loops (performance impact)
- Override global error handlers

### Configuration File

Location: `includes/ErrorHandler.php`

Key Constants:
```php
const LOG_DIR = WP_CONTENT_DIR . '/hcisysq-logs';
const LOG_TABLE = 'wp_hcisysq_logs';
```

Modify in code:
```php
// Keep 60 days instead of 30
ErrorHandler::clearOldLogs(60);

// Get DEBUG level logs (for development)
$logs = ErrorHandler::getRecentLogs(100, 'DEBUG');
```

### Support

**Issues?**
1. Check `wp-content/hcisysq-logs/hcisysq.log` for errors
2. Verify database table exists
3. Check file permissions (755)
4. Review admin dashboard for patterns
5. Export and analyze logs

**Questions?**
- See PHASE_1_2_COMPLETION_REPORT.md
- Review inline code comments
- Check test files for examples
- Search codebase for usage patterns

---

**Quick Links**:
- 📄 [Full Report](PHASE_1_2_COMPLETION_REPORT.md)
- 📋 [Checklist](PHASE_1_2_CHECKLIST.md)
- 📊 [Project Progress](PROJECT_PROGRESS_SUMMARY.md)
- 📁 Files: `includes/ErrorHandler.php`, `includes/AdminLogsViewer.php`
