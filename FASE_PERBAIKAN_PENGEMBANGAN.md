# 📋 FASE PERBAIKAN & PENGEMBANGAN HCIS.YSQ

## 🔧 FASE PERBAIKAN (IMPROVEMENT PHASES)

Fokus: **Stabilisasi, Keamanan, & Optimisasi Sistem Existing**

---

## FASE 1: STABILISASI KRITIS (1-2 Bulan)
**Timeline**: 12-15 hari | **Priority**: CRITICAL | **Impact**: HIGH

### 1.1 Session Persistence (Prioritas: CRITICAL)
**Problem**: Session hilang saat server restart
**Duration**: 3-4 hari
**Team**: 1 backend dev

#### Tasks:
```
1.1.1 Create database table
  └─ File: includes/Migration.php (tambah migration)
  └─ SQL:
     CREATE TABLE wp_hcisysq_sessions (
       id BIGINT PRIMARY KEY AUTO_INCREMENT,
       token VARCHAR(255) UNIQUE NOT NULL,
       identity LONGTEXT NOT NULL,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       expires_at TIMESTAMP NOT NULL,
       last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       ip_address VARCHAR(45),
       user_agent VARCHAR(255),
       INDEX idx_token (token),
       INDEX idx_expires (expires_at)
     );

1.1.2 Implement session handler
  └─ File: includes/SessionHandler.php (new class)
  └─ Methods:
     - public static function create($payload)
     - public static function read($token)
     - public static function update($token, $payload)
     - public static function destroy($token)
     - public static function cleanup()

1.1.3 Update Auth.php
  └─ Replace transient-based storage dengan database
  └─ Keep backward compatibility dengan transient fallback

1.1.4 Add cron job untuk session cleanup
  └─ File: hcis.ysq.php
  └─ Schedule: Hourly
  └─ Task: Delete expired sessions
  └─ Code:
     add_action('hcisysq_session_cleanup_cron', function() {
       global $wpdb;
       $wpdb->query(
         "DELETE FROM {$wpdb->prefix}hcisysq_sessions 
          WHERE expires_at < NOW()"
       );
     });

1.1.5 Testing
  └─ Unit test session creation/read
  └─ Integration test persistence after server restart
  └─ Performance test untuk 10k+ sessions
```

**Acceptance Criteria**:
- ✅ Sessions persist setelah server restart
- ✅ Cleanup cron berjalan setiap jam
- ✅ No performance degradation
- ✅ Backward compatible dengan old sessions

---

### 1.2 Error Handling & Structured Logging (Prioritas: CRITICAL)
**Problem**: Error tidak terkelola, logs plain text
**Duration**: 3-4 hari
**Team**: 1 backend dev

#### Tasks:
```
1.2.1 Setup Monolog library
  └─ File: composer.json (add dependency)
  └─ Command: composer require monolog/monolog
  └─ Version: ^3.0

1.2.2 Create error handler class
  └─ File: includes/ErrorHandler.php (new class)
  └─ Logger setup:
     - StreamHandler (stderr)
     - DatabaseHandler (custom wp_options)
     - RotatingFileHandler (daily rotation)
  └─ Log levels: DEBUG, INFO, WARNING, ERROR, CRITICAL

1.2.3 Create database handler
  └─ File: includes/Logging/DatabaseHandler.php
  └─ Table: wp_hcisysq_logs
  └─ SQL:
     CREATE TABLE wp_hcisysq_logs (
       id BIGINT PRIMARY KEY AUTO_INCREMENT,
       level VARCHAR(20),
       message TEXT,
       context LONGTEXT,
       created_at TIMESTAMP,
       user_id BIGINT NULL,
       ip_address VARCHAR(45),
       INDEX idx_level_date (level, created_at)
     );

1.2.4 Replace logging calls
  └─ File: hcis.ysq.php
  └─ Replace: hcisysq_log() → $logger->info()
  └─ Keep wrapper function untuk compatibility

1.2.5 Add error tracking (Sentry optional)
  └─ File: includes/ErrorHandler.php
  └─ Sentry integration (if budget allows)
  └─ Or: Custom dashboard di wp-admin

1.2.6 Testing
  └─ Log various error levels
  └─ Verify database storage
  └─ Test log rotation
  └─ Performance test dengan 10k+ logs
```

**Acceptance Criteria**:
- ✅ All errors logged ke database
- ✅ Error context captured (user, IP, etc)
- ✅ Logs queryable di wp-admin
- ✅ No sensitive data exposed
- ✅ Performance < 50ms per log entry

---

### 1.3 API Rate Limiting (Prioritas: CRITICAL)
**Problem**: No protection against brute force
**Duration**: 2-3 hari
**Team**: 1 backend dev

#### Tasks:
```
1.3.1 Create rate limiting class
  └─ File: includes/RateLimiter.php (new class)
  └─ Methods:
     - public static function check($endpoint, $ip)
     - public static function increment($endpoint, $ip)
     - public static function reset($endpoint, $ip)

1.3.2 Storage method (Redis preferred, fallback to DB)
  └─ Use: WordPress transients (built-in)
  └─ Key: hcisysq_ratelimit_{endpoint}_{ip}
  └─ TTL: 1 hour per endpoint

1.3.3 Add rate limits per endpoint
  └─ Login: 5 attempts per 15 minutes
  └─ Training submit: 10 per day
  └─ API endpoints: 100 per hour
  └─ Admin endpoints: 50 per hour

1.3.4 Add to AJAX handlers
  └─ File: includes/Api.php
  └─ Add check before each action:
     if (!RateLimiter::check($action, $_SERVER['REMOTE_ADDR'])) {
       wp_send_json(['ok'=>false,'msg'=>'Too many requests'], 429);
     }
     RateLimiter::increment($action, $_SERVER['REMOTE_ADDR']);

1.3.5 Add CAPTCHA for failed logins
  └─ Option 1: Google reCAPTCHA v3
  └─ Option 2: hCaptcha (privacy-friendly)
  └─ Track failed attempts
  └─ Show CAPTCHA after 3 failed attempts

1.3.6 Testing
  └─ Test rate limiting per endpoint
  └─ Test CAPTCHA triggering
  └─ Performance test dengan concurrent requests
  └─ Test whitelist/bypass logic
```

**Acceptance Criteria**:
- ✅ Rate limits enforced per endpoint
- ✅ CAPTCHA shows after 3 failed logins
- ✅ Clear error messages
- ✅ Admin can view rate limit logs
- ✅ No false positives

---

### 1.4 Input Validation & Sanitization (Prioritas: HIGH)
**Problem**: Some inputs not properly validated
**Duration**: 2-3 hari
**Team**: 1 backend dev

#### Tasks:
```
1.4.1 Create validation class
  └─ File: includes/Validator.php (new class)
  └─ Methods:
     - public static function validate($field, $rules, $value)
     - public static function validateEmail($email)
     - public static function validateNIP($nip)
     - public static function validatePhone($phone)
     - public static function sanitizeHTML($html)

1.4.2 Define validation rules per endpoint
  └─ File: includes/ValidationRules.php
  └─ Rules for:
     - Login: NIP (required, 18 chars), Password (required, 6-50 chars)
     - Training: Title (required, 50-500 chars), Description (required, HTML safe)
     - Publication: Title (required), Body (required, HTML safe)
     - Profile: Email (valid), Phone (11-13 digits)

1.4.3 Add validation to each AJAX handler
  └─ File: includes/Api.php
  └─ Add before processing:
     $validated = Validator::validate('training_form', [
       'title' => 'required|string|min:10|max:200',
       'description' => 'required|html_safe|min:50',
     ], $_POST);

1.4.4 Return validation errors
  └─ Return: ['ok'=>false, 'errors'=>[...]]
  └─ Format: {field: [error messages]}

1.4.5 Testing
  └─ Test valid inputs
  └─ Test invalid inputs
  └─ Test edge cases
  └─ Test XSS/SQL injection attempts
```

**Acceptance Criteria**:
- ✅ All inputs validated before processing
- ✅ Clear error messages returned
- ✅ XSS/SQL injection attempts blocked
- ✅ Sanitization applied correctly
- ✅ No data corruption

---

### 1.5 Security Headers & HTTPS (Prioritas: HIGH)
**Problem**: Missing security headers, HTTPS enforcement
**Duration**: 1-2 hari
**Team**: 1 backend dev + DevOps

#### Tasks:
```
1.5.1 Add security headers
  └─ File: hcis.ysq.php (add_action wp_loaded)
  └─ Headers:
     - X-Frame-Options: DENY
     - X-Content-Type-Options: nosniff
     - X-XSS-Protection: 1; mode=block
     - Referrer-Policy: strict-origin-when-cross-origin
     - Content-Security-Policy: [restrictive]
     - Strict-Transport-Security: max-age=31536000

1.5.2 Force HTTPS
  └─ File: wp-config.php
  └─ Add:
     define('FORCE_SSL_ADMIN', true);
     define('FORCE_SSL_LOGIN', true);

1.5.3 SSL certificate check
  └─ Verify: Valid, not expired, proper domain
  └─ Setup: Let's Encrypt (free) or premium

1.5.4 Testing
  └─ SSL Labs audit
  └─ Security header verification
  └─ Mixed content check
```

**Acceptance Criteria**:
- ✅ SSL Labs grade A minimum
- ✅ All security headers present
- ✅ No mixed content warnings
- ✅ HTTPS enforced globally

---

### 1.6 Code Cleanup & Documentation (Prioritas: MEDIUM)
**Problem**: Inconsistent code style, limited documentation
**Duration**: 2-3 hari
**Team**: 1 backend dev

#### Tasks:
```
1.6.1 Setup code standards
  └─ File: .phpcs.xml
  └─ Standard: PSR-12
  └─ Run: phpcs includes/ --standard=PSR12

1.6.2 Add docblocks to all public methods
  └─ Format:
     /**
      * Brief description
      *
      * Longer description if needed
      *
      * @param Type $param Description
      * @return Type Description
      * @throws Exception Condition
      */

1.6.3 Create API documentation
  └─ File: docs/API.md
  └─ Document all AJAX endpoints:
     - Method (POST/GET)
     - Required parameters
     - Response format
     - Error codes

1.6.4 Create deployment guide
  └─ File: docs/DEPLOYMENT.md
  └─ Steps for:
     - Production setup
     - Configuration
     - Database migration
     - Testing checklist

1.6.5 Testing
  └─ Run phpcs on all files
  └─ Verify docblocks complete
  └─ Check documentation accuracy
```

**Acceptance Criteria**:
- ✅ Zero PHPCS warnings
- ✅ All public methods documented
- ✅ API documentation complete
- ✅ Deployment guide accurate

---

## Phase 1 Summary Table

| Task | Duration | Priority | Impact | Owner |
|------|----------|----------|--------|-------|
| Session Persistence | 3-4 days | CRITICAL | HIGH | Backend Dev |
| Error Handling | 3-4 days | CRITICAL | HIGH | Backend Dev |
| Rate Limiting | 2-3 days | CRITICAL | MEDIUM | Backend Dev |
| Input Validation | 2-3 days | HIGH | MEDIUM | Backend Dev |
| Security Headers | 1-2 days | HIGH | MEDIUM | Backend + DevOps |
| Code Cleanup | 2-3 days | MEDIUM | LOW | Backend Dev |
| **TOTAL** | **12-15 days** | - | - | **1-2 devs** |

---

---

## FASE 2: SKALABILITAS & PERFORMA (2-3 Bulan)
**Timeline**: 15-18 hari | **Priority**: HIGH | **Impact**: HIGH

### 2.1 Database Schema Optimization
**Problem**: All data di WordPress options/transients
**Duration**: 5-6 hari

#### Tasks:
```
2.1.1 Create custom database tables
  └─ Table 1: wp_hcisysq_employees
  └─ Table 2: wp_hcisysq_trainings
  └─ Table 3: wp_hcisysq_tasks
  └─ Table 4: wp_hcisysq_task_assignments
  └─ Table 5: wp_hcisysq_audit_logs

2.1.2 Employee table schema
  CREATE TABLE wp_hcisysq_employees (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nip VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    unit_id BIGINT,
    position VARCHAR(255),
    password_hash VARCHAR(255),
    needs_reset BOOLEAN DEFAULT FALSE,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    synced_at TIMESTAMP NULL,
    INDEX idx_nip (nip),
    INDEX idx_email (email),
    INDEX idx_unit (unit_id),
    UNIQUE KEY uk_email (email)
  );

2.1.3 Training table schema
  CREATE TABLE wp_hcisysq_trainings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    employee_id BIGINT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT,
    status VARCHAR(50) DEFAULT 'draft',
    submitted_at TIMESTAMP NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee (employee_id),
    INDEX idx_status (status),
    FOREIGN KEY (employee_id) REFERENCES wp_hcisysq_employees(id)
  );

2.1.4 Task table schema
  CREATE TABLE wp_hcisysq_tasks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT,
    status VARCHAR(50) DEFAULT 'pending',
    due_date DATE,
    created_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
  );

2.1.5 Data migration script
  └─ File: includes/Migration.php
  └─ Migrate data dari options → new tables
  └─ Verify data integrity
  └─ Backup sebelum migrate

2.1.6 Update ORM/Repository layer
  └─ Create: includes/Repositories/EmployeeRepository.php
  └─ Create: includes/Repositories/TrainingRepository.php
  └─ Methods: find(), findAll(), create(), update(), delete()
```

**Acceptance Criteria**:
- ✅ All tables created with proper indexes
- ✅ Data migrated & verified
- ✅ Performance improved (< 100ms queries)
- ✅ No data loss

---

### 2.2 Data Import Queue System
**Problem**: CSV import synchronous, bisa timeout
**Duration**: 4-5 hari

#### Tasks:
```
2.2.1 Setup queue library
  └─ Option 1: wp-queue/wp-queue
  └─ Option 2: Custom queue with wp-cron
  └─ Choice: Custom dengan wp-cron (simpler)

2.2.2 Create import job class
  └─ File: includes/Jobs/ImportEmployeesJob.php
  └─ Methods:
     - public function handle()
     - public function getStatus()
     - public function retry()

2.2.3 Implement batch processing
  └─ Batch size: 500 records per batch
  └─ Use: wp_schedule_single_event()
  └─ Track: Progress di option

2.2.4 Add progress tracking
  └─ Store: Total, Processed, Failed, Status
  └─ Display: In admin UI (progress bar)
  └─ Email notification saat selesai

2.2.5 Error handling
  └─ Log errors per record
  └─ Retry failed records
  └─ Send error report ke admin

2.2.6 Testing
  └─ Test dengan 10k+ records
  └─ Test dengan corrupted data
  └─ Test server restart during import
```

**Acceptance Criteria**:
- ✅ Import tidak timeout
- ✅ Progress trackable
- ✅ Failed records logged
- ✅ Admin notified on completion

---

### 2.3 Pagination & Filtering
**Problem**: Employee list load semua data
**Duration**: 3-4 hari

#### Tasks:
```
2.3.1 Add pagination to API
  └─ File: includes/Api.php
  └─ Methods:
     - public static function ysq_get_employees_by_units()
     - Add: $page, $per_page parameters
     - Add: WP_Query pagination

2.3.2 Add filtering
  └─ By unit/department
  └─ By name (search)
  └─ By status (active/inactive)
  └─ Combine filters

2.3.3 Add sorting
  └─ By name, NIP, unit, date
  └─ Ascending/descending
  └─ Default: Sort by name

2.3.4 Update frontend
  └─ File: theme.js
  └─ Add filter buttons
  └─ Add search input
  └─ Add sort dropdown
  └─ Implement lazy loading

2.3.5 Testing
  └─ Test dengan 10k+ employees
  └─ Test filter combinations
  └─ Performance: < 500ms response
```

**Acceptance Criteria**:
- ✅ Pagination works (10 items/page default)
- ✅ Filters functional
- ✅ Sorting works
- ✅ Performance acceptable (< 500ms)

---

### 2.4 Caching Strategy
**Problem**: No caching layer
**Duration**: 2-3 hari

#### Tasks:
```
2.4.1 Setup object cache
  └─ Recommended: Redis or Memcached
  └─ Fallback: WordPress Transient API
  └─ Choice: Transient (simpler, no external dep)

2.4.2 Cache employee list
  └─ Key: hcisysq_employees_unit_{id}
  └─ TTL: 1 hour
  └─ Invalidate: On employee update

2.4.3 Cache settings
  └─ Key: hcisysq_settings_{key}
  └─ TTL: 24 hour
  └─ Invalidate: On settings save

2.4.4 Cache publications
  └─ Key: hcisysq_publications_{status}
  └─ TTL: 1 hour
  └─ Invalidate: On publication update

2.4.5 Implement cache warming
  └─ Cron job: Daily warm cache
  └─ Pre-populate: Hot data

2.4.6 Testing
  └─ Cache hit/miss ratios
  └─ Performance improvement
  └─ Invalidation timing
```

**Acceptance Criteria**:
- ✅ Cache working (verify via transients)
- ✅ Performance improved 30%+
- ✅ Invalidation working correctly
- ✅ No stale data issues

---

## Phase 2 Summary

| Task | Duration | Impact | Owner |
|------|----------|--------|-------|
| DB Schema | 5-6 days | HIGH | Backend Dev |
| Import Queue | 4-5 days | HIGH | Backend Dev |
| Pagination | 3-4 days | MEDIUM | Backend + Frontend Dev |
| Caching | 2-3 days | MEDIUM | Backend Dev |
| **TOTAL** | **15-18 days** | - | **2 devs** |

---

---

## FASE 3: MODERNISASI ARSITEKTUR (3-4 Bulan)
**Timeline**: 15-20 hari | **Priority**: MEDIUM | **Impact**: HIGH

### 3.1 Repository Pattern Implementation
**Duration**: 4-5 hari

#### Tasks:
```
3.1.1 Create repository interfaces
  └─ File: includes/Contracts/EmployeeRepository.php
  └─ File: includes/Contracts/TrainingRepository.php
  └─ File: includes/Contracts/TaskRepository.php

3.1.2 Implement concrete repositories
  └─ File: includes/Repositories/DatabaseEmployeeRepository.php
  └─ Methods: find(), findAll(), create(), update(), delete()
  └─ Handle: Pagination, filtering, sorting

3.1.3 Create database helper class
  └─ File: includes/Database/QueryBuilder.php
  └─ Methods: select(), where(), orderBy(), paginate()

3.1.4 Update data access layer
  └─ Replace direct queries dengan repository
  └─ Dependency injection via constructor

3.1.5 Testing
  └─ Unit tests untuk repository methods
  └─ Mock database calls
  └─ Test pagination/filtering
```

**Acceptance Criteria**:
- ✅ All data access via repository
- ✅ Unit tests passing
- ✅ Performance not degraded

---

### 3.2 Service Layer Refactor
**Duration**: 4-5 hari

#### Tasks:
```
3.2.1 Create service classes
  └─ File: includes/Services/AuthService.php
  └─ File: includes/Services/EmployeeService.php
  └─ File: includes/Services/TrainingService.php
  └─ File: includes/Services/NotificationService.php

3.2.2 Implement business logic in services
  └─ Move logic dari Api.php → Service classes
  └─ Services use repositories
  └─ Services emit events

3.2.3 Create service container/DI
  └─ File: includes/Container.php
  └─ Manage service instances
  └─ Constructor injection

3.2.4 Update API endpoints
  └─ File: includes/Api.php
  └─ Use services instead of direct logic
  └─ Cleaner, more maintainable

3.2.5 Testing
  └─ Unit tests for services
  └─ Mock repositories
  └─ Test business logic
```

**Acceptance Criteria**:
- ✅ Business logic in services
- ✅ Clean separation of concerns
- ✅ Unit tests passing

---

### 3.3 REST API v1
**Duration**: 4-5 hari

#### Tasks:
```
3.3.1 Design REST API
  └─ Version: v1
  └─ Endpoints:
     POST /api/v1/auth/login
     POST /api/v1/auth/logout
     GET /api/v1/employees
     POST /api/v1/trainings
     GET /api/v1/trainings/{id}
     PUT /api/v1/trainings/{id}

3.3.2 Implement API routes
  └─ File: includes/Api/Router.php
  └─ Use: WordPress REST API or custom routing

3.3.3 Add authentication
  └─ JWT tokens (optional) or session-based
  └─ Add Authorization header check

3.3.4 Create response formatter
  └─ File: includes/Api/ResponseFormatter.php
  └─ Consistent response format:
     {
       "ok": true/false,
       "data": {...},
       "errors": [...],
       "meta": {"page": 1, "total": 100}
     }

3.3.5 Add documentation
  └─ File: docs/API.md
  └─ Use: Swagger/OpenAPI format
  └─ Generate: Interactive docs

3.3.6 Testing
  └─ Test all endpoints
  └─ Test authentication
  └─ Test error responses
```

**Acceptance Criteria**:
- ✅ REST API v1 working
- ✅ Consistent response format
- ✅ Authentication working
- ✅ Documentation complete

---

### 3.4 Theme Modernization - Build Process
**Duration**: 3-4 hari
**Team**: Frontend Dev

#### Tasks:
```
3.4.1 Setup webpack/esbuild
  └─ File: webpack.config.js
  └─ Entry: assets/js/theme.js
  └─ Output: dist/theme.min.js

3.4.2 Setup PostCSS
  └─ File: postcss.config.js
  └─ Plugins:
     - autoprefixer
     - cssnano (minify)
     - postcss-import (modularize)

3.4.3 Modularize CSS
  └─ From: style.css (2000+ lines)
  └─ To: Multiple files:
     - css/base.css (resets, defaults)
     - css/layout.css (grid, flexbox)
     - css/components.css (cards, buttons)
     - css/pages.css (page-specific)
     - css/responsive.css (media queries)

3.4.4 Add npm scripts
  └─ package.json:
     {
       "scripts": {
         "build": "webpack && postcss css/*.css --output dist/",
         "watch": "webpack --watch",
         "serve": "webpack serve"
       }
     }

3.4.5 Update theme enqueue
  └─ File: functions.php
  └─ Load minified files from dist/

3.4.6 Testing
  └─ Build & verify output
  └─ CSS rendering correct
  └─ No broken styles
  └─ Performance improvement check
```

**Acceptance Criteria**:
- ✅ Build process working
- ✅ CSS minified (50%+ reduction)
- ✅ JS minified
- ✅ Source maps for debugging

---

## Phase 3 Summary

| Task | Duration | Impact | Owner |
|------|----------|--------|-------|
| Repository Pattern | 4-5 days | HIGH | Backend Dev |
| Service Layer | 4-5 days | HIGH | Backend Dev |
| REST API v1 | 4-5 days | HIGH | Backend Dev |
| Theme Build | 3-4 days | MEDIUM | Frontend Dev |
| **TOTAL** | **15-20 days** | - | **2 devs** |

---

---

## FASE 4: ENHANCEMENT & FEATURES (3-4 Bulan)
**Timeline**: 15-20 hari | **Priority**: MEDIUM | **Impact**: MEDIUM

### 4.1 Dark Mode Support
**Duration**: 2-3 hari
**Team**: Frontend Dev

#### Tasks:
```
4.1.1 Setup CSS variables
  └─ File: css/variables.css
  └─ Light theme (default):
     --bg-primary: #ffffff
     --text-primary: #0f172a
     --border: #e2e8f0
  └─ Dark theme:
     --bg-primary: #1a1a1a
     --text-primary: #e0e0e0
     --border: #333333

4.1.2 Add dark mode CSS
  └─ File: css/dark-mode.css
  └─ Use: @media (prefers-color-scheme: dark)
  └─ Override variables

4.1.3 Add toggle button
  └─ File: header.php
  └─ Button to toggle dark/light
  └─ Store preference: localStorage

4.1.4 Update JavaScript
  └─ File: theme.js
  └─ Detect system preference
  └─ Handle user toggle
  └─ Persist choice

4.1.5 Testing
  └─ Test light mode
  └─ Test dark mode
  └─ Test system preference detection
  └─ Test persistence
```

**Acceptance Criteria**:
- ✅ Dark mode toggle working
- ✅ Colors properly adjusted
- ✅ Text readable in both modes
- ✅ Preference persisted

---

### 4.2 Component Library
**Duration**: 4-5 hari
**Team**: Frontend Dev

#### Tasks:
```
4.2.1 Document existing components
  └─ File: docs/COMPONENTS.md
  └─ Components:
     - Button (primary, secondary)
     - Card (publication, dashboard)
     - Form field (input, textarea)
     - Alert (success, error, info)
     - Modal/Dialog
     - Pagination
     - Navigation
     - Footer

4.2.2 Create component examples
  └─ File: docs/component-showcase.html
  └─ HTML examples dengan CSS classes
  └─ Variations (sizes, states)

4.2.3 Create reusable Sass mixins
  └─ File: css/mixins.scss
  └─ Mixins:
     - @mixin button($style)
     - @mixin card($shadow)
     - @mixin form-field()
     - @mixin responsive-grid()

4.2.4 Create utility classes
  └─ File: css/utilities.css
  └─ Classes:
     - .text-center, .text-left, .text-right
     - .mt-*, .mb-*, .p-* (spacing)
     - .flex, .grid (layout)
     - .hidden, .visible (display)

4.2.5 Testing
  └─ Test all components
  └─ Verify consistency
  └─ Check responsiveness
```

**Acceptance Criteria**:
- ✅ All components documented
- ✅ Examples provided
- ✅ Utility classes available
- ✅ Consistent styling

---

### 4.3 Accessibility Improvements (WCAG 2.1 AA)
**Duration**: 3-4 hari
**Team**: Frontend Dev

#### Tasks:
```
4.3.1 Add skip links
  └─ File: header.php
  └─ Links:
     - Skip to main content
     - Skip to footer
  └─ Hidden until focused

4.3.2 Add ARIA labels
  └─ Forms: aria-label, aria-describedby
  └─ Buttons: aria-label untuk icon buttons
  └─ Live regions: aria-live, aria-atomic
  └─ Landmarks: role="main", role="navigation"

4.3.3 Keyboard navigation
  └─ Tab order: logical
  └─ Focus indicators: visible
  └─ Modals: focus trap

4.3.4 Color contrast check
  └─ Test: Text vs background
  └─ Target: WCAG AA (4.5:1 for normal text)
  └─ Tools: WebAIM contrast checker

4.3.5 Screen reader testing
  └─ Test with: NVDA (Windows), JAWS (Windows), VoiceOver (Mac)
  └─ Verify: All content readable
  └─ Fix: Alt text, labels, etc

4.3.6 Testing
  └─ Automated: axe, WAVE
  └─ Manual: Keyboard only
  └─ Screen reader: All major readers
```

**Acceptance Criteria**:
- ✅ WCAG 2.1 AA compliance
- ✅ Zero automated accessibility violations
- ✅ Keyboard navigation working
- ✅ Screen reader compatible

---

### 4.4 Performance Optimization
**Duration**: 3-4 hari
**Team**: Backend + Frontend Dev

#### Tasks:
```
4.4.1 Frontend optimization
  └─ Image optimization:
     - Compress PNG/JPG
     - Use WebP with fallback
     - Lazy load images
  └─ CSS optimization:
     - Critical CSS extraction
     - Defer non-critical CSS
  └─ JavaScript:
     - Code splitting
     - Defer non-critical JS
     - Tree shaking unused code

4.4.2 Backend optimization
  └─ Database:
     - Query optimization
     - Add missing indexes
     - Monitor slow queries
  └─ API:
     - Response compression (gzip)
     - Pagination (not all results)
     - Field filtering

4.4.3 Caching optimization
  └─ HTTP caching:
     - Cache-Control headers
     - ETag/Last-Modified
  └─ CDN:
     - Cloudflare (or similar)
     - Cache static assets (1 year TTL)
  └─ Application caching:
     - Improved strategy from Phase 2

4.4.4 Monitoring
  └─ Setup: Google Analytics
  └─ Track: Core Web Vitals
     - LCP (Largest Contentful Paint) < 2.5s
     - FID (First Input Delay) < 100ms
     - CLS (Cumulative Layout Shift) < 0.1
  └─ Monitor: Performance trends

4.4.5 Testing
  └─ Lighthouse audit (target: 90+)
  └─ PageSpeed Insights
  └─ Load testing (simulate 1000 users)
```

**Acceptance Criteria**:
- ✅ Lighthouse score 90+
- ✅ Core Web Vitals passing
- ✅ Load time < 2 seconds
- ✅ 50%+ reduction in bundle size

---

### 4.5 Advanced Features
**Duration**: 4-5 hari
**Team**: Backend Dev

#### Tasks:
```
4.5.1 Email notifications
  └─ File: includes/Services/NotificationService.php
  └─ Triggers:
     - Training submitted
     - Task assigned
     - Password reset
     - Admin actions
  └─ Template engine: Simple HTML templates

4.5.2 Audit trail
  └─ File: includes/AuditLog.php
  └─ Log:
     - User logins
     - Data changes (create, update, delete)
     - Admin actions
  └─ Storage: Database table

4.5.3 Two-factor authentication (2FA)
  └─ Option: TOTP (Time-based OTP)
  └─ File: includes/TwoFactorAuth.php
  └─ Setup via admin settings
  └─ Support: Google Authenticator, Authy

4.5.4 Data export
  └─ File: includes/Export.php
  └─ Format: CSV, Excel, PDF
  └─ Security: Require authentication
  └─ Scope: User's own data only

4.5.5 Testing
  └─ Test email delivery
  └─ Test audit logging
  └─ Test 2FA setup/login
  └─ Test export functionality
```

**Acceptance Criteria**:
- ✅ Emails sent reliably
- ✅ Audit trail complete
- ✅ 2FA working
- ✅ Exports secure & accessible

---

## Phase 4 Summary

| Task | Duration | Impact | Owner |
|------|----------|--------|-------|
| Dark Mode | 2-3 days | MEDIUM | Frontend Dev |
| Component Library | 4-5 days | MEDIUM | Frontend Dev |
| A11y (Accessibility) | 3-4 days | HIGH | Frontend Dev |
| Performance | 3-4 days | HIGH | Both |
| Advanced Features | 4-5 days | MEDIUM | Backend Dev |
| **TOTAL** | **15-20 days** | - | **2 devs** |

---

---

## FASE 5: TESTING & QA (2-3 Bulan)
**Timeline**: 15-18 hari | **Priority**: HIGH | **Impact**: HIGH

### 5.1 Unit Testing
**Duration**: 5-6 hari
**Team**: Backend Dev + QA

#### Tasks:
```
5.1.1 Setup PHPUnit
  └─ File: phpunit.xml
  └─ Bootstrap: WordPress test environment
  └─ Coverage target: 80%+

5.1.2 Write unit tests
  └─ File: tests/Unit/...
  └─ Test classes:
     - AuthServiceTest.php
     - ValidatorTest.php
     - RateLimiterTest.php
     - EmployeeRepositoryTest.php
  └─ Each test file: 20-50 test cases

5.1.3 Mock dependencies
  └─ Mock: Database, APIs, external services
  └─ Use: PHPUnit mocks
  └─ Isolate: Business logic

5.1.4 Run test suite
  └─ Command: ./vendor/bin/phpunit
  └─ Report: Coverage report (HTML)
  └─ CI: Run on every commit

5.1.5 Testing
  └─ Run all tests
  └─ Fix failures
  └─ Achieve 80%+ coverage
```

**Acceptance Criteria**:
- ✅ 80%+ code coverage
- ✅ All tests passing
- ✅ CI/CD integration
- ✅ No flaky tests

---

### 5.2 Integration Testing
**Duration**: 4-5 hari
**Team**: Backend Dev + QA

#### Tasks:
```
5.2.1 Setup integration test environment
  └─ Database: Test database (separate)
  └─ WordPress: Test installation
  └─ Config: Test wp-config.php

5.2.2 Write integration tests
  └─ File: tests/Integration/...
  └─ Test flows:
     - Complete login flow
     - Training submission end-to-end
     - Publication CRUD
     - Task assignment

5.2.3 Test database interactions
  └─ Create test data
  └─ Verify persistence
  └─ Test transactions
  └─ Cleanup after each test

5.2.4 Test external APIs
  └─ Mock Google Sheets API
  └─ Mock StarSender WhatsApp
  └─ Test fallback logic

5.2.5 Testing
  └─ Run all integration tests
  └─ Fix failures
  └─ Test against test database
```

**Acceptance Criteria**:
- ✅ All major workflows tested
- ✅ Tests passing
- ✅ No database corruption
- ✅ API integration verified

---

### 5.3 E2E Testing
**Duration**: 4-5 hari
**Team**: QA

#### Tasks:
```
5.3.1 Setup E2E framework
  └─ Framework: Playwright atau Cypress
  └─ Language: JavaScript/TypeScript
  └─ Config: Screenshots, videos on failure

5.3.2 Write E2E tests
  └─ File: tests/E2E/...
  └─ Scenarios:
     - User login flow
     - Training submission
     - Admin operations
     - Publication browsing
     - Profile update

5.3.3 Test different browsers
  └─ Chrome, Firefox, Safari, Edge
  └─ Mobile: iPhone, Android
  └─ Desktop: Windows, macOS, Linux

5.3.4 Test user journeys
  └─ Happy path
  └─ Error scenarios
  └─ Edge cases

5.3.5 Automated reporting
  └─ Screenshots on failure
  └─ Video recordings
  └─ HTML reports

5.3.6 Testing
  └─ Run E2E suite
  └─ Fix failures
  └─ Achieve 95%+ pass rate
```

**Acceptance Criteria**:
- ✅ All user journeys covered
- ✅ Cross-browser tested
- ✅ Pass rate 95%+
- ✅ Fast execution (< 5 min)

---

### 5.4 Performance Testing
**Duration**: 2-3 hari
**Team**: Backend Dev + DevOps

#### Tasks:
```
5.4.1 Load testing
  └─ Tool: Apache JMeter atau k6
  └─ Scenario: 1000 concurrent users
  └─ Duration: 10 minutes
  └─ Measure: Response time, throughput

5.4.2 Stress testing
  └─ Gradually increase load
  └─ Find breaking point
  └─ Identify bottlenecks

5.4.3 Database performance
  └─ Slow query log
  └─ Analyze slow queries
  └─ Add missing indexes
  └─ Optimize queries

5.4.4 Monitoring
  └─ Monitor: CPU, memory, disk I/O
  └─ Alert: On threshold breach
  └─ Report: Performance metrics

5.4.5 Testing
  └─ Run load tests
  └─ Fix bottlenecks
  └─ Achieve target: 500 concurrent users
```

**Acceptance Criteria**:
- ✅ Handles 500 concurrent users
- ✅ Response time < 1 second
- ✅ No database bottlenecks
- ✅ Memory stable

---

## Phase 5 Summary

| Task | Duration | Impact | Owner |
|------|----------|--------|-------|
| Unit Testing | 5-6 days | HIGH | Backend + QA |
| Integration Testing | 4-5 days | HIGH | Backend + QA |
| E2E Testing | 4-5 days | HIGH | QA |
| Performance Testing | 2-3 days | MEDIUM | Backend + DevOps |
| **TOTAL** | **15-18 days** | - | **2-3 people** |

---

---

## FASE 6: DEPLOYMENT & MONITORING (2-3 Bulan)
**Timeline**: 12-15 hari | **Priority**: HIGH | **Impact**: CRITICAL

### 6.1 Deployment Pipeline
**Duration**: 3-4 hari
**Team**: DevOps

#### Tasks:
```
6.1.1 Setup CI/CD pipeline
  └─ Platform: GitHub Actions, GitLab CI, atau Jenkins
  └─ Stages:
     1. Build (composer install, npm install)
     2. Test (phpunit, npm test)
     3. Security (phpcs, snyk)
     4. Deploy to staging
     5. Deploy to production

6.1.2 Staging environment
  └─ Identical to production
  └─ Automatic deploy dari main branch
  └─ Testing environment untuk QA

6.1.3 Production deployment
  └─ Manual approval required
  └─ Database migration scripts
  └─ Rollback capability
  └─ Health checks post-deploy

6.1.4 Deployment checklist
  └─ Backup database & files
  └─ Run migrations
  └─ Clear caches
  └─ Verify health checks
  └─ Monitor metrics

6.1.5 Testing
  └─ Test CI/CD pipeline
  └─ Test staging deploy
  └─ Test production deploy
  └─ Test rollback
```

**Acceptance Criteria**:
- ✅ Fully automated CI/CD
- ✅ Staging auto-deployment
- ✅ Production controlled deployment
- ✅ Rollback available

---

### 6.2 Infrastructure Setup
**Duration**: 3-4 hari
**Team**: DevOps

#### Tasks:
```
6.2.1 Server setup
  └─ OS: Ubuntu 20.04 LTS
  └─ Web server: Nginx
  └─ PHP: 8.0+ (FPM)
  └─ Database: MySQL 8.0
  └─ Redis: For caching (optional)

6.2.2 SSL/TLS
  └─ Certificate: Let's Encrypt (free)
  └─ Auto-renewal: Certbot
  └─ HSTS: Enabled
  └─ Certificate pinning: Optional

6.2.3 Backup strategy
  └─ Database: Daily full + hourly incremental
  └─ Files: Daily full
  └─ Retention: 30 days
  └─ Off-site: Cloud storage (S3, GCS)

6.2.4 Monitoring setup
  └─ Uptime monitoring: Pingdom, UptimeRobot
  └─ Performance: New Relic, Datadog
  └─ Logs: ELK stack, Splunk
  └─ Alerts: Slack, PagerDuty

6.2.5 Testing
  └─ Test deployment
  └─ Test backup/restore
  └─ Test monitoring alerts
```

**Acceptance Criteria**:
- ✅ Server configured & secured
- ✅ SSL working
- ✅ Backups automated
- ✅ Monitoring active

---

### 6.3 Monitoring & Observability
**Duration**: 2-3 hari
**Team**: DevOps + Backend Dev

#### Tasks:
```
6.3.1 Application monitoring
  └─ Metrics:
     - Request count
     - Response time
     - Error rate
     - Database connections
  └─ Dashboard: Custom Grafana dashboard

6.3.2 Error tracking
  └─ Tool: Sentry (recommended)
  └─ Capture: All unhandled exceptions
  └─ Release tracking
  └─ Sourcemap support

6.3.3 Logging
  └─ Aggregation: ELK, Splunk, atau Datadog
  └─ Structured logging: JSON format
  └─ Log levels: DEBUG, INFO, WARN, ERROR
  └─ Retention: 30 days

6.3.4 Alerting
  └─ Alert conditions:
     - Error rate > 5%
     - Response time > 2s
     - Database slow query
     - Disk space < 10%
     - Memory > 90%
  └─ Channels: Slack, Email, PagerDuty

6.3.5 Testing
  └─ Test metrics collection
  └─ Test alerts
  └─ Test dashboard
```

**Acceptance Criteria**:
- ✅ Metrics being collected
- ✅ Errors tracked & reported
- ✅ Logs aggregated & searchable
- ✅ Alerts working

---

### 6.4 Documentation & Knowledge Transfer
**Duration**: 2-3 hari
**Team**: Tech Lead + Backend Dev

#### Tasks:
```
6.4.1 Update documentation
  └─ Architecture guide
  └─ API reference (OpenAPI/Swagger)
  └─ Deployment guide
  └─ Configuration guide
  └─ Troubleshooting guide

6.4.2 Create runbooks
  └─ Emergency procedures
  └─ Common issues & fixes
  └─ Database maintenance
  └─ Backup & restore

6.4.3 Knowledge transfer
  └─ Code walkthrough
  └─ Architecture explanation
  └─ Development workflow
  └─ Troubleshooting procedures

6.4.4 Training
  └─ Developer training
  └─ Admin training
  └─ Support team training

6.4.5 Testing
  └─ New team member can deploy
  └─ Can troubleshoot common issues
  └─ Documentation is accurate
```

**Acceptance Criteria**:
- ✅ All documentation current & accurate
- ✅ Runbooks complete
- ✅ Team trained & comfortable
- ✅ Handover complete

---

## Phase 6 Summary

| Task | Duration | Impact | Owner |
|------|----------|--------|-------|
| CI/CD Pipeline | 3-4 days | HIGH | DevOps |
| Infrastructure | 3-4 days | HIGH | DevOps |
| Monitoring | 2-3 days | HIGH | DevOps + Backend |
| Documentation | 2-3 days | MEDIUM | Tech Lead + Backend |
| **TOTAL** | **12-15 days** | - | **1-2 people** |

---

---

## 📊 RINGKASAN SEMUA FASE

```
PERBAIKAN (Improvement):
├─ FASE 1: Stabilisasi           (12-15 hari)  CRITICAL
├─ FASE 2: Skalabilitas          (15-18 hari)  HIGH
├─ FASE 3: Modernisasi Arsitektur (15-20 hari) MEDIUM
├─ FASE 4: Enhancement           (15-20 hari)  MEDIUM
├─ FASE 5: Testing & QA          (15-18 hari)  HIGH
└─ FASE 6: Deployment            (12-15 hari)  HIGH

TOTAL TIMELINE: 84-106 hari (4-5 bulan)
TEAM SIZE: 2-3 developers + 1 DevOps
ESTIMATED COST: $40k-$50k USD
```

---

## 🎯 CRITICAL PATH (MUST DO FIRST)

1. **Phase 1** (Stabilisasi) - 12-15 hari
   - Session persistence ✅ (CRITICAL)
   - Error handling ✅ (CRITICAL)
   - Rate limiting ✅ (CRITICAL)
   - Input validation ✅ (HIGH)
   
2. **Phase 2** (Skalabilitas) - 15-18 hari
   - Database schema ✅ (HIGH)
   - Import queue ✅ (HIGH)
   - Pagination ✅ (MEDIUM)
   
3. **Phase 5** (Testing) - Start parallel dengan Phase 2
   - Unit tests ✅ (HIGH)
   - Integration tests ✅ (HIGH)
   
4. **Phase 6** (Deployment) - After Phase 3+5 complete
   - CI/CD pipeline ✅ (HIGH)
   - Monitoring ✅ (HIGH)

**OPTIONAL** (Nice to have):
- Phase 3: Modernisasi (can be later)
- Phase 4: Enhancement (can be later)

---

## 💰 BUDGET & TIMELINE ESTIMATE

### Conservative Plan (4-5 months)
- **Team**: 2 backend devs + 1 frontend dev + 1 DevOps
- **Cost**: $40k-$50k USD
- **All phases completed**

### Accelerated Plan (3 months)
- **Team**: 3 backend devs + 1 frontend dev + 1 DevOps
- **Cost**: $50k-$60k USD
- **All phases completed faster**

### Minimum Plan (2-3 months - Phase 1+2+5+6)
- **Team**: 2 backend devs + 0.5 DevOps
- **Cost**: $25k-$30k USD
- **Only critical phases**
- **Trade-off**: Skip modernization & enhancements

---

## ✅ SUCCESS METRICS

### Phase 1 Success
- ✅ Zero session loss
- ✅ Structured logging in place
- ✅ Rate limiting working
- ✅ All inputs validated

### Phase 2 Success
- ✅ Query response time < 100ms
- ✅ 10k+ employees can be imported
- ✅ Pagination working
- ✅ Cache hit rate > 50%

### Phase 3 Success
- ✅ Clean architecture (repository + service)
- ✅ REST API documented
- ✅ CSS modularized & minified
- ✅ Build process automated

### Phase 4 Success
- ✅ Dark mode working
- ✅ WCAG 2.1 AA compliant
- ✅ Lighthouse score 90+
- ✅ Advanced features working

### Phase 5 Success
- ✅ 80%+ code coverage
- ✅ All workflows E2E tested
- ✅ Handles 500 concurrent users
- ✅ < 1s response time

### Phase 6 Success
- ✅ Fully automated CI/CD
- ✅ Zero-downtime deployment
- ✅ Comprehensive monitoring
- ✅ Team trained & ready

---

*Dokumen Fase Perbaikan & Pengembangan HCIS.YSQ*
*Created: November 16, 2025*
*Version: 1.0 (Comprehensive)*
