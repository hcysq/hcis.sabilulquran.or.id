# Legacy Admin & WordPress Admin Coexistence Regression

This checklist verifies that both the legacy HCIS.YSQ admin pages and the new WordPress-role
administration paths continue to function during the coexistence period.

## 1. Legacy Session (PHP session-based)

1. **Reset to legacy credentials**
   - In `wp-admin`, open **Tools → HCIS.YSQ Settings** and record the current admin username.
   - In the database (via `wp_options`) set `ysq_admin_username` and `ysq_admin_password_hash`
     to known legacy values (or import a previous backup).
   - Reload the front-end legacy login page and confirm the credentials migrate into
     the `hcisysq_admin_settings` option automatically.
2. **Authenticate as legacy admin**
   - Visit the legacy admin login endpoint (e.g. `/hcis-admin/`), submit the migrated username
     and password, and confirm you receive the success notice “Login administrator berhasil.”
   - Refresh the page and confirm the notice clears after one view, validating session-backed
     notices.
3. **Legacy CRUD flows**
   - Create, update, and delete an announcement using the legacy forms and confirm the
     respective success/failure notices appear.
   - Trigger the “logout” link and verify the logout notice displays and the session terminates.

## 2. WordPress-role Session (WordPress capabilities)

1. **Login with a WordPress administrator**
   - Sign in to `/wp-admin/` with a user that has the `manage_options` capability.
   - Confirm no PHP session (`PHPSESSID`) cookie is created for the front-end.
2. **Verify WordPress admin tooling**
   - Visit **Tools → HCIS.YSQ Settings** and perform a save action to ensure notices render via
     standard WordPress admin notices.
   - Run an import (Profiles/Users/Training) and confirm success/error feedback appears via
     WordPress notices without triggering legacy notices.
3. **Front-end sanity check**
   - While still logged in as the WordPress administrator, open the legacy admin URL.
   - Confirm you are not prompted for the legacy login form and that no PHP session is created,
     demonstrating the guard prevents the legacy bridge from taking over WordPress-role sessions.

Document any deviations in the release checklist so regressions can be triaged before deployment.
