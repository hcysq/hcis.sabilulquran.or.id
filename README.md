# HCIS YSQ Logging Guide

This plugin ships with a structured logging stack that captures every PHP warning, fatal error, and application event through a centralized PSR-3 logger.

## Requirements

- WordPress 6.x
- PHP 7.4, 8.0, or 8.1+ (Monolog 2.x keeps backward compatibility with PHP 7.4+)

## Key components

- **ErrorHandler** normalizes every log (timestamp, stack trace, IP address, user ID, component, and request ID) and automatically redacts sensitive fields such as `password`, `token`, or `secret` before persisting the entry.
- **DatabaseHandler** writes normalized records to the `{$table_prefix}hcisysq_logs` table and to rotating log files stored inside `wp-content/hcisysq-logs/`.
- **AdminLogsViewer** exposes a searchable UI (level, user, component, request ID, free-text search, and date range filters) with pagination, CSV export, and stack-trace expansion.
- **LogsEndpoint** registers `GET /wp-json/hcisysq/v1/logs` so Ops teams can mirror logs to an external system.

## Configuration

1. **Install dependencies**
   ```bash
   cd wp-content/plugins/hcis.ysq
   composer install
   ```
2. **Set the optional REST export token** (recommended for automation) by adding this constant to `wp-config.php`:
   ```php
   define('HCISYSQ_LOG_EXPORT_TOKEN', 'your-long-random-token-here');
   ```
   Requests must include the header `X-HCISYSQ-Token: your-long-random-token-here` (or a `token` query parameter). Administrators (`manage_options`) can always access the endpoint from authenticated requests.
3. **Listen for new log entries** via the `hcisysq/logging/log_created` action. Every normalized payload is passed to the hook so you can relay it to third-party services (Logstash, Grafana, etc.).
   ```php
   add_action('hcisysq/logging/log_created', function(array $log){
     // Ship to any external collector
   });
   ```
4. **Review logs in wp-admin** under *HCIS Portal → Error Logs*. Use the filter bar to scope by level, component, request ID, search term, or time window; pagination keeps queries efficient.

## REST export usage

```
curl -H "X-HCISYSQ-Token: your-long-random-token" \
     "https://example.com/wp-json/hcisysq/v1/logs?level=ERROR&component=session&per_page=20"
```
Response payload:

```json
{
  "data": [
    {
      "id": 123,
      "level": "ERROR",
      "severity": "ERROR",
      "component": "session",
      "message": "…",
      "context": {"context": {…}},
      "extra": {…},
      "stack_trace": "…",
      "created_at": "2024-06-01 12:00:00",
      "user_id": 15,
      "request_id": "hcisysq_..."
    }
  ],
  "meta": {"total": 200, "page": 1, "pages": 10}
}
```

## Automated tests

Run the PHPUnit suite (redaction tests included) from the plugin root:

```
./vendor/bin/phpunit
```

The new tests ensure sensitive fields are masked before the logger or REST endpoint can expose them.

## Google Sheets sync via WP-CLI

The Google Sheets cron now dispatches one sheet tab at a time via the `hcisysq_google_sheets_sync_tab` hook. Ops teams can still
force a full synchronization without waiting for loopback cron traffic by triggering the dispatcher and pending tab events from
WP-CLI:

```bash
# Kick off the dispatcher if nothing is queued yet
wp cron event run hcisysq_google_sheets_sync_cron --due-now

# Process each tab sequentially; rerun until WP-CLI reports "Did nothing"
wp cron event run hcisysq_google_sheets_sync_tab --due-now
```

Because each invocation only processes a single tab (`GoogleSheetSettings::get_tabs()` order), every cron request stays well
under 10 seconds while still allowing a full refresh when needed.
