# Front-end Assets

The plugin now ships with first-party CSS inside `assets/css/`. Every stylesheet is hand-authored (no bundler). When tweaking the UI, edit the corresponding file directly and keep the shared tokens inside `shared.css` in sync with the markup in `includes/View.php`.

| Handle              | File                                     | Depends On           | Purpose                          |
|--------------------|------------------------------------------|----------------------|----------------------------------|
| `hcisysq-shared`   | `assets/css/shared.css`                  | —                    | Base tokens, typography, layout |
| `hcisysq-login`    | `assets/css/login.css`                   | `hcisysq-shared`     | Auth pages & `/ganti-password/` |
| `hcisysq-dashboard`| `assets/css/dashboard.css`               | `hcisysq-shared`     | Pegawai & admin shell            |
| `hcisysq-admin`    | `assets/css/admin.css`                   | `hcisysq-dashboard`  | Admin-only widgets               |

## Regenerating / verifying assets

No build step is required. After editing the CSS run a lightweight server from the repository root to ensure every handle resolves without a `404`:

```bash
python3 -m http.server 8000 &
curl -I http://127.0.0.1:8000/wp-content/plugins/hcis.ysq/assets/css/shared.css
curl -I http://127.0.0.1:8000/wp-content/plugins/hcis.ysq/assets/css/login.css
curl -I http://127.0.0.1:8000/wp-content/plugins/hcis.ysq/assets/css/dashboard.css
curl -I http://127.0.0.1:8000/wp-content/plugins/hcis.ysq/assets/css/admin.css
```

Stop the server afterwards (`Ctrl+C`).
