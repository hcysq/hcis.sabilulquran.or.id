# Session Persistence - Quick Reference

## Quick Start

### Basic Usage

```php
<?php
use HCISYSQ\SessionHandler;

// Create a session
$token = SessionHandler::create([
  'type' => 'user',
  'nip'  => '123456',
  'nama' => 'John Doe',
]);

// Read a session
$session = SessionHandler::read($token);
if ($session !== false) {
  echo $session['nama']; // John Doe
}

// Update a session
SessionHandler::update($token, [
  'needs_password_reset' => true,
]);

// Delete a session
SessionHandler::destroy($token);

// Clean up expired sessions
$deleted = SessionHandler::cleanup();
echo "Cleaned: $deleted sessions";
```

## API Quick Reference

### SessionHandler Methods

| Method | Purpose | Returns |
|--------|---------|---------|
| `create($payload, $ttl)` | Create new session | Token (string) or false |
| `read($token)` | Get session data | Payload (array) or false |
| `update($token, $payload)` | Modify session | Boolean |
| `destroy($token)` | Delete session | Boolean |
| `cleanup()` | Remove expired | Count (int) |
| `get_active_sessions()` | List all active | Array of objects |
| `verify_table_exists()` | Check table | Boolean |

## Database Queries

### Check Active Sessions
```sql
SELECT token, created_at, expires_at, ip_address 
FROM wp_hcisysq_sessions 
WHERE expires_at > NOW() 
ORDER BY last_activity DESC;
```

### Count Sessions
```sql
SELECT 
  COUNT(*) as total,
  SUM(CASE WHEN expires_at > NOW() THEN 1 ELSE 0 END) as active,
  SUM(CASE WHEN expires_at <= NOW() THEN 1 ELSE 0 END) as expired
FROM wp_hcisysq_sessions;
```

### Find User Sessions
```sql
SELECT * FROM wp_hcisysq_sessions 
WHERE identity LIKE '%"nip":"123456"%' 
AND expires_at > NOW();
```

## Common Tasks

### Check if Table Exists
```php
if (!SessionHandler::verify_table_exists()) {
  // Table creation might have failed
  error_log('Sessions table missing!');
}
```

### Monitor Sessions
```php
$sessions = SessionHandler::get_active_sessions();
echo "Active sessions: " . count($sessions);

foreach ($sessions as $session) {
  printf("Token: %s | IP: %s | Expires: %s\n",
    $session->token,
    $session->ip_address,
    $session->expires_at
  );
}
```

### Manual Cleanup
```php
$deleted = SessionHandler::cleanup();
hcisysq_log("Cleanup deleted $deleted expired sessions");
```

### Create Session with Custom TTL
```php
// 1 hour TTL
$token = SessionHandler::create($payload, 1 * HOUR_IN_SECONDS);

// 24 hours TTL
$token = SessionHandler::create($payload, 24 * HOUR_IN_SECONDS);

// 7 days TTL
$token = SessionHandler::create($payload, 7 * DAY_IN_SECONDS);
```

## Testing

### Run All Tests
```bash
cd wp-content/plugins/hcis.ysq
./vendor/bin/phpunit tests/
```

### Run Specific Test
```bash
./vendor/bin/phpunit tests/Unit/SessionHandlerTest.php::test_create_session
```

### Run with Verbose Output
```bash
./vendor/bin/phpunit tests/ -v
```

## Debugging

### Enable Debug Logging
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('HCISYSQ_LOG_FILE', WP_CONTENT_DIR . '/hcisysq.log');
```

### Check Logs
```bash
tail -f wp-content/hcisysq.log | grep SessionHandler
```

### Test Session Creation
```php
<?php
// In WordPress admin or shell

$test_payload = [
  'type' => 'user',
  'nip'  => 'TEST001',
  'test' => true,
];

$token = \HCISYSQ\SessionHandler::create($test_payload);
if ($token) {
  echo "✓ Session created: $token\n";
  
  $read = \HCISYSQ\SessionHandler::read($token);
  echo "✓ Session read: " . json_encode($read) . "\n";
  
  \HCISYSQ\SessionHandler::destroy($token);
  echo "✓ Session destroyed\n";
} else {
  echo "✗ Failed to create session\n";
}
```

## Troubleshooting

### Session Not Persisting
```php
// 1. Check table exists
if (!SessionHandler::verify_table_exists()) {
  die('ERROR: Sessions table missing!');
}

// 2. Check database connection
global $wpdb;
if ($wpdb->get_var("SHOW TABLES LIKE 'wp_hcisysq_sessions'") === null) {
  die('ERROR: Cannot access sessions table');
}

// 3. Check plugin is active
if (!is_plugin_active('hcis.ysq/hcis.ysq.php')) {
  die('ERROR: Plugin not active');
}
```

### Sessions Expiring Too Fast
```php
// Check default TTL (should be 12 hours)
// In Auth::store_session():
SessionHandler::create($payload, 12 * HOUR_IN_SECONDS);
```

### Cron Not Running
```bash
# Check WordPress cron
wp cron test

# List scheduled events
wp cron event list

# Verify cleanup event exists
wp cron event list | grep hcisysq_session_cleanup_cron

# Run event manually
wp cron event run hcisysq_session_cleanup_cron
```

## Configuration

### Default Settings
```php
// Default TTL: 12 hours
12 * HOUR_IN_SECONDS

// Session token format: UUID v4
'12345678-1234-4567-8901-234567890123'

// Cleanup frequency: Hourly
'hourly'

// Table name
'{$wpdb->prefix}hcisysq_sessions'
```

### Customization

#### Change Default TTL
In `includes/SessionHandler.php`, modify:
```php
public static function create(array $payload, $expires_in_seconds = null) {
  if ($expires_in_seconds === null) {
    $expires_in_seconds = 24 * HOUR_IN_SECONDS; // Change here
  }
  // ...
}
```

#### Change Cleanup Frequency
In `hcis.ysq.php`, modify:
```php
// Change from 'hourly' to 'daily' or custom interval
wp_schedule_event(time(), 'daily', 'hcisysq_session_cleanup_cron');
```

## Performance Tips

1. **Index Usage** - Queries use idx_token and idx_expires indexes
2. **Cleanup Regularly** - Daily cleanup recommended via cron
3. **Monitor Size** - Check table size monthly
4. **Archive Old Data** - Consider archiving sessions older than 30 days

## Security Notes

- IP addresses are logged (max 45 chars, supports IPv6)
- User agents are logged (max 255 chars)
- Payloads are JSON-encoded (human-readable)
- Tokens are UUID v4 format (cryptographically secure)
- Sessions expire automatically (configurable TTL)
- No sensitive data should be stored in payloads

## Files Reference

| File | Purpose |
|------|---------|
| `includes/SessionHandler.php` | Core session management |
| `includes/Auth.php` | Auth integration |
| `includes/Installer.php` | Database setup |
| `hcis.ysq.php` | Plugin init + cron |
| `docs/SESSION_PERSISTENCE.md` | Full documentation |
| `tests/Unit/SessionHandlerTest.php` | Unit tests |
| `tests/Integration/SessionPersistenceTest.php` | Integration tests |

## Contact & Support

For issues:
1. Check logs: `wp-content/hcisysq.log`
2. Run tests: `./vendor/bin/phpunit tests/`
3. Review docs: `docs/SESSION_PERSISTENCE.md`
4. Contact: Development Team

---

Last Updated: November 16, 2025  
Version: 1.0
