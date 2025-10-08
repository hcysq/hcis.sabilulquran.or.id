# Implementation Tasks for WordPress Core Cleanup

## Preparation
- [ ] Audit the `docker-compose.yml` volume mounts and note any entries pointing to the WordPress core files scheduled for removal.
- [ ] Confirm that all theme and plugin customizations live under `wp-content/`.

## Remove redundant WordPress core files
- [ ] Delete the following files from the repository because the WordPress image already provides them:
  - `license.txt`
  - `readme.html`
  - `index.php`
  - `wp-blog-header.php`
  - `wp-load.php`
  - `wp-settings.php`
  - `wp-config-sample.php`
  - `.htaccess` (if present)
- [ ] Update `docker-compose.yml` to remove volume mounts referencing the deleted files.

## Verification
- [ ] Run `docker compose up` and ensure the containers start without missing-file errors.
- [ ] Access the local WordPress site to confirm that existing themes and plugins still work as expected.
- [ ] Commit the changes and push them for review.
