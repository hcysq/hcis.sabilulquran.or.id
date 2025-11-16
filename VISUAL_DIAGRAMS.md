# VISUAL DIAGRAMS - HCIS.YSQ ECOSYSTEM

## 1. SYSTEM ARCHITECTURE DIAGRAM

```
┌─────────────────────────────────────────────────────────────────────┐
│                         BROWSER (User Interface)                     │
│                  Chrome, Firefox, Safari, Edge                       │
└────────────────────────────────┬────────────────────────────────────┘
                                 │ HTTP/HTTPS
┌────────────────────────────────▼────────────────────────────────────┐
│                    YSQ-THEME (Frontend Layer)                        │
├─────────────────────────────────────────────────────────────────────┤
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ PHP Templates (WordPress Theme)                             │   │
│  │  • header.php, footer.php, page.php, etc.                  │   │
│  │  • Render plugin shortcodes & content                       │   │
│  └─────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ CSS (Modern Design System)                                  │   │
│  │  • style.css (2000+ lines)                                 │   │
│  │  • CSS Grid, Flexbox, Variables                            │   │
│  │  • Responsive Design (mobile-first)                        │   │
│  └─────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │ JavaScript (Vanilla ES6+)                                  │   │
│  │  • theme.js (interactions, form handling)                  │   │
│  │  • No jQuery, no framework dependencies                    │   │
│  └─────────────────────────────────────────────────────────────┘   │
└────────────────────────────────┬────────────────────────────────────┘
                                 │ WordPress Hooks
┌────────────────────────────────▼────────────────────────────────────┐
│              HCIS.YSQ PLUGIN (Business Logic Layer)                  │
├─────────────────────────────────────────────────────────────────────┤
│  ┌────────────────────────────────────────────────────────────┐    │
│  │ Core Modules (20 PHP Classes)                              │    │
│  │  • Auth.php         → Session management                  │    │
│  │  • Api.php          → 20+ AJAX endpoints                  │    │
│  │  • Admin.php        → Admin portal                        │    │
│  │  • Profiles.php     → Employee data                       │    │
│  │  • Trainings.php    → Training forms                      │    │
│  │  • Publikasi.php    → Announcements                       │    │
│  │  • Tasks.php        → Task management                     │    │
│  │  • [13 more...]                                           │    │
│  └────────────────────────────────────────────────────────────┘    │
│  ┌────────────────────────────────────────────────────────────┐    │
│  │ Security & Utilities                                       │    │
│  │  • Nonce verification                                      │    │
│  │  • Bcrypt password hashing                                │    │
│  │  • Input sanitization                                     │    │
│  │  • Role-based access control                              │    │
│  └────────────────────────────────────────────────────────────┘    │
└────────────────────────────────┬────────────────────────────────────┘
                                 │ DB Queries
┌────────────────────────────────▼────────────────────────────────────┐
│                    DATA LAYER (Persistence)                          │
├─────────────────────────────────────────────────────────────────────┤
│  ┌──────────────────────┐  ┌──────────────────────┐  ┌────────┐   │
│  │  WordPress Options   │  │  WordPress Transients │  │  DB   │   │
│  │  • Settings          │  │  • Session tokens    │  │        │   │
│  │  • Configuration     │  │  • Cache (12h TTL)   │  │ MySQL  │   │
│  │  • Credentials       │  │                      │  │ 5.7+   │   │
│  └──────────────────────┘  └──────────────────────┘  └────────┘   │
│                                                                     │
│  External Integrations:                                            │
│  ┌──────────────────────┐  ┌──────────────────────┐              │
│  │  Google Sheets API   │  │  StarSender (WhatsApp) │              │
│  │  • Import profiles   │  │  • Send SMS/WhatsApp │              │
│  │  • Export training   │  │  • Notifications     │              │
│  │  • GAS automation    │  │  • Authentication    │              │
│  └──────────────────────┘  └──────────────────────┘              │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 2. DATA FLOW DIAGRAM

```
EMPLOYEE REGISTRATION & LOGIN
═════════════════════════════════════════════════════════════════════

  Google Sheets
  (Employee List)
        │
        ├─ CSV Export
        │
        ▼
  Plugin: Profiles.php
  (Import data via cron)
        │
        ▼
  WordPress Database
  (hcisysq options)
        │
        ├─ Profiles stored
        ├─ Users created
        └─ Password hashes
        │
        ▼
  Theme: Dashboard Display
  (Show employee info)


TRAINING SUBMISSION WORKFLOW
═════════════════════════════════════════════════════════════════════

  Employee
        │
        ├─ Access /pelatihan/
        │
        ▼
  Theme: page.php + Plugin Shortcode
  (Form rendering with styling)
        │
        ├─ Employee fills form
        │
        ▼
  Plugin: Api::submit_training()
  (AJAX endpoint)
        │
        ├─ Validate input
        ├─ Create record
        │
        ▼
  Google Apps Script (GAS)
  (Receive data)
        │
        ├─ Process submission
        ├─ Store in Google Sheets
        │
        ▼
  Confirmation
  (Sent back to frontend)


ADMIN PUBLICATION WORKFLOW
═════════════════════════════════════════════════════════════════════

  Admin
        │
        ├─ Access /wp-admin/
        │
        ▼
  Plugin: Admin Portal
  (Custom admin page)
        │
        ├─ Create publication
        │
        ▼
  Plugin: Api::admin_create_publication()
  (AJAX endpoint)
        │
        ├─ Validate input
        ├─ Sanitize content
        │
        ▼
  WordPress Database
  (Custom post type)
        │
        ├─ Store publication
        │
        ▼
  Frontend: /publikasi/
  (Rendered by theme)
        │
        ├─ Theme: page-publikasi.php
        ├─ Plugin: Display shortcode
        │
        ▼
  User Sees Publication
  (Grid layout with styling)
```

---

## 3. MODULE DEPENDENCY DIAGRAM

```
┌──────────────────────────────────────────────────────────────┐
│                    hcis.ysq.php (Main)                        │
│  ├─ Defines constants & helpers                              │
│  ├─ Loads vendor autoloader                                  │
│  └─ Initializes all modules                                  │
└──────────────┬───────────────────────────────────────────────┘
               │
       ┌───────┴───────────────────────────────────┐
       │                                           │
       ▼                                           ▼
┌─────────────────┐                      ┌──────────────────┐
│ Core Modules    │                      │ Utility Modules  │
├─────────────────┤                      ├──────────────────┤
│ • Config.php    │                      │ • Assets.php     │
│ • Auth.php      │◄─────────────┐       │ • View.php       │
│ • NipAuth.php   │              │       │ • RichText.php   │
│ • Admin.php     │              │       │ • Shortcodes.php │
└─────────────────┘              │       └──────────────────┘
       │                         │
       ├─ depends on ────────────┤
       │                         │
       ▼                         ▼
┌──────────────────────────────────────────┐
│        Data Management Modules           │
├──────────────────────────────────────────┤
│ • Profiles.php      (Employee data)     │
│ • Users.php         (User import)       │
│ • Trainings.php     (Training forms)    │
│ • Publikasi.php     (Content CRUD)      │
│ • Tasks.php         (Task management)   │
│ • Hcis_Gas_Token.php (Google API)       │
└──────────────────────────────────────────┘
       │
       ├─ uses ─────────────┐
       │                    │
       ▼                    ▼
   WordPress DB      External APIs
   (options,         (Google Sheets)
    transients)      (StarSender)
```

---

## 4. REQUEST-RESPONSE CYCLE

```
USER ACTION (Frontend)
├─ Click "Login" button
│
▼
JAVASCRIPT (theme.js)
├─ Event listener triggered
├─ Get form data
├─ Validate input
├─ Create FormData object
│
▼
AJAX REQUEST
├─ POST /wp-admin/admin-ajax.php?action=hcisysq_login
├─ Include nonce token
├─ Include NIP + Password
│
▼
SERVER (WordPress)
├─ wp_ajax_nopriv hook triggered
├─ Call Api::login()
│
▼
PLUGIN (Api.php)
├─ Check nonce
├─ Sanitize input
├─ Call Auth::login()
├─ Validate credentials
├─ Create session token
├─ Store transient
├─ Set cookie
│
▼
RESPONSE (JSON)
├─ {
│    "ok": true,
│    "msg": "Login successful",
│    "redirect": "https://domain.com/dashboard/"
│  }
│
▼
JAVASCRIPT (theme.js)
├─ Receive JSON response
├─ Check "ok" status
├─ Redirect user to dashboard
│
▼
BROWSER
├─ GET /dashboard/
├─ WordPress checks session cookie
├─ Theme renders page.php
├─ Plugin renders shortcode
├─ User sees dashboard
```

---

## 5. PAGE RENDERING LIFECYCLE

```
REQUEST: /dashboard/
    │
    ├─ WordPress routing
    │  └─ Match page /dashboard/
    │
    ▼
CHECK GUARDS (template_redirect hook)
    │
    ├─ Is user logged in?
    │  └─ No → Redirect to /masuk/
    │  └─ Yes → Continue
    │
    ├─ Does user need password reset?
    │  └─ Yes → Redirect to /ganti-password/
    │  └─ No → Continue
    │
    ▼
LOAD TEMPLATE HIERARCHY
    │
    ├─ page-dashboard.php? → No
    ├─ page.php? → Yes ✓
    │
    ▼
ENQUEUE ASSETS
    │
    ├─ wp_enqueue_scripts hook
    │  ├─ Theme: style.css, theme.js
    │  ├─ Plugin: hcisysq.css, hcisysq.js
    │
    ▼
RENDER TEMPLATE
    │
    ├─ get_header() → header.php
    │  ├─ HTML structure
    │  ├─ Navigation menu
    │  ├─ Logo & branding
    │  └─ CSS loaded
    │
    ├─ Loop through page content
    │  ├─ get_template_part('content', 'page')
    │  ├─ Display page title
    │  ├─ Get page body (shortcode)
    │  │  └─ [hcisysq_dashboard]
    │  │
    │  └─ Plugin shortcode processing
    │     ├─ Check auth
    │     ├─ Fetch user data
    │     ├─ Fetch announcements
    │     ├─ Generate HTML
    │     └─ Return to theme
    │
    ├─ get_footer() → footer.php
    │  ├─ Footer content
    │  ├─ Footer menu
    │  └─ JS files loaded
    │
    ▼
SEND RESPONSE
    │
    ├─ Complete HTML document
    ├─ All CSS & JS included
    ├─ Browser renders
    │
    ▼
INTERACTIVE (JS)
    │
    ├─ theme.js executes
    ├─ Event listeners attached
    ├─ DOM ready for interaction
    │
    ▼
USER SEES DASHBOARD
```

---

## 6. SESSION MANAGEMENT FLOW

```
USER LOGS IN
    │
    ├─ Auth::login() called
    │
    ▼
PASSWORD VERIFICATION
    │
    ├─ Fetch user hash from DB
    ├─ password_verify(input, hash)
    │
    ├─ Match? Yes → Continue
    └─ Match? No → Return error
    │
    ▼
CREATE SESSION
    │
    ├─ Generate UUID4 token
    │  └─ wp_generate_uuid4()
    │
    ├─ Create session payload
    │  ├─ type: 'user' or 'admin'
    │  ├─ nip: employee NIP
    │  ├─ name: employee name
    │  ├─ email: employee email
    │  └─ [...other data]
    │
    ▼
STORE SESSION
    │
    ├─ WordPress Transient (12 hour TTL)
    │  └─ set_transient('hcisysq_sess_' . $token, $payload)
    │
    ├─ HTTP-Only Cookie
    │  ├─ Name: hcisysq_token
    │  ├─ Value: [UUID token]
    │  ├─ TTL: 12 hours
    │  ├─ Secure: Yes (HTTPS)
    │  ├─ HttpOnly: Yes
    │  └─ SameSite: Lax
    │
    ▼
USER SESSION ACTIVE (12 HOURS)
    │
    ├─ Each request:
    │  ├─ Browser sends cookie
    │  ├─ Server reads cookie
    │  ├─ Get transient from DB
    │  ├─ Validate payload
    │  └─ Allow access
    │
    ▼
SESSION EXPIRES
    │
    ├─ Transient expires (auto-delete)
    ├─ Cookie still in browser (stale)
    ├─ Next request fails validation
    ├─ Redirect to /masuk/
    │
    ▼
LOGOUT (Manual)
    │
    ├─ User clicks "Logout"
    ├─ Call Api::logout()
    ├─ Delete transient
    ├─ Delete cookie
    ├─ Redirect to /masuk/
```

---

## 7. PLUGIN-THEME INTEGRATION TOUCHPOINTS

```
PLUGIN                          THEME
═════════════════════════════════════════════════════════════

Provides:
  ├─ Shortcodes                 ───→ Renders in templates
  ├─ AJAX endpoints             ───→ Called by theme.js
  ├─ Hooks (actions/filters)    ───→ Used by theme
  ├─ CSS classes               ───→ Styled by style.css
  ├─ User data                 ───→ Displayed in pages
  └─ Post types                ───→ Rendered by custom templates


SHORTCODES MAPPING
═══════════════════════════════════════════════════════════════

[hcisysq_login]
    ├─ Page: /masuk/
    ├─ Template: page-blank.php
    └─ Style: .admin-login-card (style.css)

[hcisysq_dashboard]
    ├─ Page: /dashboard/
    ├─ Template: page.php
    ├─ Layout: .dashboard-layout
    └─ Styles: .dashboard-sidebar, .dashboard-card, .announcement-feed

[hcisysq_form_training]
    ├─ Page: /pelatihan/
    ├─ Template: page.php
    ├─ Layout: .form-stack
    └─ Styles: .form-field, .btn-primary, .btn-secondary

[hcisysq_publications]
    ├─ Page: /publikasi/
    ├─ Template: page-publikasi.php
    ├─ Layout: .ysq-publication-grid
    └─ Styles: .ysq-publication-card, .ysq-publication-filter


CSS CLASSES INTERACTION
═══════════════════════════════════════════════════════════════

Plugin outputs HTML with plugin-specific classes:
  ├─ .hcisysq-login-form
  ├─ .hcisysq-dashboard
  ├─ .hcisysq-publication

Theme provides base styling:
  ├─ .form-field (inherited by plugin forms)
  ├─ .btn-primary (inherited by plugin buttons)
  ├─ .announcement-history (inherited by plugin lists)


JAVASCRIPT INTERACTION
═══════════════════════════════════════════════════════════════

Plugin provides:
  ├─ AJAX endpoints
  ├─ Nonce tokens
  └─ Localized data

Theme JavaScript (theme.js):
  ├─ Listens for form submissions
  ├─ Calls plugin AJAX endpoints
  ├─ Handles responses
  ├─ Updates DOM
  └─ Provides feedback to user


DATA FLOW THROUGH THEME
═══════════════════════════════════════════════════════════════

Plugin stores data in:
  ├─ WordPress options table
  ├─ WordPress transients
  └─ WordPress posts (publications)

Theme retrieves via:
  ├─ get_option()
  ├─ get_transient()
  ├─ WP_Query()
  └─ Plugin API functions

Theme displays in:
  ├─ Template variables
  ├─ Loop structures
  └─ Shortcode callbacks
```

---

## 8. SECURITY LAYERS

```
┌────────────────────────────────────────────────────────┐
│                    USER REQUEST                         │
│            (HTTP/HTTPS from browser)                    │
└────────────┬───────────────────────────────────────────┘
             │
             ▼
┌────────────────────────────────────────────────────────┐
│           SSL/TLS ENCRYPTION (HTTPS)                   │
│          All data encrypted in transit                 │
└────────────┬───────────────────────────────────────────┘
             │
             ▼
┌────────────────────────────────────────────────────────┐
│              INPUT VALIDATION                          │
│    • Theme validation (client-side)                    │
│    • Plugin validation (server-side)                   │
│    • Sanitization (remove harmful content)             │
└────────────┬───────────────────────────────────────────┘
             │
             ▼
┌────────────────────────────────────────────────────────┐
│              NONCE VERIFICATION                        │
│    • Check CSRF token in AJAX requests                 │
│    • wp_verify_nonce()                                 │
│    • Per-session validation                            │
└────────────┬───────────────────────────────────────────┘
             │
             ▼
┌────────────────────────────────────────────────────────┐
│         CAPABILITY CHECK                               │
│    • manage_options (admin)                            │
│    • manage_hcis_portal (hcis_admin)                   │
│    • Logged-in user verification                       │
└────────────┬───────────────────────────────────────────┘
             │
             ▼
┌────────────────────────────────────────────────────────┐
│          DATA PROCESSING                               │
│    • Password hashing (Bcrypt)                         │
│    • SQL escape (WordPress preparedstatements)         │
│    • HTML escaping for output                          │
└────────────┬───────────────────────────────────────────┘
             │
             ▼
┌────────────────────────────────────────────────────────┐
│          SECURE STORAGE                                │
│    • Hashed passwords in database                      │
│    • Transient API for sensitive data                  │
│    • Encrypted cookies (optional)                      │
└────────────┬───────────────────────────────────────────┘
             │
             ▼
┌────────────────────────────────────────────────────────┐
│          RESPONSE BACK TO USER                         │
│    • Escaped HTML output                               │
│    • Security headers (X-Frame-Options, etc)           │
│    • No sensitive data in response                     │
└────────────────────────────────────────────────────────┘
```

---

## 9. DEPLOYMENT ARCHITECTURE

```
PRODUCTION ENVIRONMENT
═════════════════════════════════════════════════════════════

┌─────────────────────────────────────────────────────────┐
│                    CLOUDFLARE / CDN                     │
│              (Optional - Cache static assets)            │
└────────────┬────────────────────────────────────────────┘
             │
┌────────────▼────────────────────────────────────────────┐
│                    WEB SERVER                            │
│           (Apache/Nginx + PHP-FPM)                      │
│  ├─ SSL certificate (HTTPS)                            │
│  ├─ PHP 7.4+ runtime                                   │
│  └─ WordPress installation                             │
└────────────┬────────────────────────────────────────────┘
             │
┌────────────▼────────────────────────────────────────────┐
│              WORDPRESS APPLICATION                       │
│  ├─ Plugin: hcis.ysq (backend)                          │
│  ├─ Theme: ysq-theme (frontend)                         │
│  ├─ Extensions: WP-CLI, etc.                            │
│  └─ Configuration (wp-config.php)                       │
└────────────┬────────────────────────────────────────────┘
             │
┌────────────▼────────────────────────────────────────────┐
│              DATABASE SERVER                             │
│           (MySQL 5.7+ or MariaDB)                       │
│  ├─ WordPress tables                                    │
│  ├─ Options & metadata                                  │
│  └─ Backup: Daily snapshots                            │
└─────────────────────────────────────────────────────────┘

EXTERNAL SERVICES
═════════════════════════════════════════════════════════════

Google Sheets API        ← Data import/export
Google Apps Script       ← Training automation
StarSender WhatsApp API  ← Notifications
```

---

*Visual Diagrams for HCIS.YSQ Ecosystem*
*Complement to RINGKASAN_PLUGIN_HCIS_YSQ.md*
*Last Updated: November 16, 2025*
