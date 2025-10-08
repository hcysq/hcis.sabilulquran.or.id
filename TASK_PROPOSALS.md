# Follow-up Task Proposals

## Typo Fix
- **Issue**: The login error messages consistently misspell "Password" as "Pasword" in the authentication workflow, leading to user-facing typos.
- **Location**: `wp-content/plugins/hcis.ysq/includes/Auth.php`
- **Proposed Task**: Update the affected strings (and any front-end surfaces that reuse them) to use the correct spelling so the UI copy is professional and consistent.

## Bug Fix
- **Issue**: The Users import configuration allows administrators to specify a custom Google Sheets tab name, but both the saved URL helper and the manual import action hardcode `gid=0`, so any tab that is not the first sheet fails to import.
- **Location**: `wp-content/plugins/hcis.ysq/includes/Admin.php`, `wp-content/plugins/hcis.ysq/includes/Users.php`
- **Proposed Task**: Resolve the mismatch by either deriving the correct `gid` for the chosen tab or letting administrators supply a publish-to-web URL, ensuring imports honor the configured tab.

## Documentation Discrepancy
- **Issue**: The quickstart guide still instructs admins to fill in a "Default GAS URL" field inside the WhatsApp & SSO settings section, but the current settings screen exposes only WhatsApp fields plus the GAS API key.
- **Location**: `wp-content/plugins/hcis.ysq/QUICKSTART-v1.2.0.md`, `wp-content/plugins/hcis.ysq/includes/Admin.php`
- **Proposed Task**: Update the documentation (or reintroduce the missing field if it should exist) so the instructions accurately reflect the available settings.

## Test Improvement
- **Issue**: The `testPost` helper in the Google Apps Script training file merely logs the response, providing no automated verification that the webhook behaves correctly.
- **Location**: `wp-content/plugins/hcis.ysq/docs/google-apps-script-training.js`
- **Proposed Task**: Enhance the helper to assert on the HTTP status and parsed payload (or convert it into a small unit test) to catch regressions in the Apps Script endpoint behavior.

