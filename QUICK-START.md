# Quick Start - WordPress HCIS.YSQ

## 🚀 Fastest Way to Deploy

### Option 1: Docker (5 minutes)

```bash
# 1. Clone/download project files
cd /path/to/project

# 2. Update URLs in .env
sed -i 's|http://localhost:8080|http://your-domain.com|g' .env

# 3. Start containers
docker-compose up -d

# 4. Wait for containers to be ready (30 seconds)
docker-compose logs -f

# 5. Access WordPress
# Open: http://your-domain.com
# Follow WordPress installation wizard

# 6. Login to WP Admin
# Go to: http://your-domain.com/wp-admin

# 7. Activate Theme
# Appearance > Themes > Activate "YSQ Theme"

# 8. Activate Plugin
# Plugins > Activate "HCIS.YSQ"
```

### Option 2: cPanel Hosting (10 minutes)

```bash
# 1. Create MySQL Database in cPanel
# Database Name: hcis_preview
# Database User: boltuser
# Note the password

# 2. Upload files via File Manager or FTP
# Upload all files EXCEPT docker-compose.yml

# 3. Create wp-config.php
cp wp-config-sample.php wp-config.php

# 4. Edit wp-config.php (use File Manager editor)
# Set:
#   DB_NAME = 'hcis_preview'
#   DB_USER = 'boltuser'
#   DB_PASSWORD = 'your-password'
#   DB_HOST = 'localhost'
# Copy all AUTH_KEY values from .env file

# 5. Set Permissions
# Folders: 755
# Files: 644
# wp-content/uploads: 755

# 6. Access site
# http://your-domain.com

# 7. Activate theme and plugin in WP Admin
```

---

## ✅ Verification Checklist

After deployment, verify:

- [ ] Homepage loads successfully
- [ ] Theme "YSQ Theme" is active
- [ ] Plugin "HCIS.YSQ" is active
- [ ] Login page works at `/masuk`
- [ ] Dashboard accessible at `/dashboard`
- [ ] Training form at `/pelatihan`
- [ ] Assets loading (CSS, JS, images)
- [ ] No errors in browser console

---

## 🔑 Default Credentials

**Database:**
- Name: `hcis_preview`
- User: `boltuser`
- Pass: `HcisYsq2024!SecurePass`

**WordPress Admin:**
- Will be created during installation wizard
- Recommended username: `admin`
- Use strong password

---

## 📁 Key Files

```
project/
├── .env                    ← Database & WP config
├── docker-compose.yml      ← Docker setup
├── wp-content/
│   ├── themes/ysq-theme/  ← Custom theme
│   └── plugins/hcis.ysq/  ← HCIS plugin
```

---

## 🆘 Quick Troubleshooting

**Error: Database connection failed**
→ Check credentials in wp-config.php match database

**Error: 404 on all pages**
→ Go to Settings > Permalinks > Save Changes

**Theme/Plugin not showing**
→ Check folder names: `ysq-theme` and `hcis.ysq`
→ Check permissions: 755 for folders

**Folder plugin ganda (`hcis.ysq_`) muncul setelah update**
→ Hapus folder lama lalu ekstrak ulang paket sehingga hanya tersisa `wp-content/plugins/hcis.ysq/`
→ Jika paket dari GitHub (`hcis.ysq-main.zip`), rename folder internal menjadi `hcis.ysq` sebelum upload atau gunakan fitur *Upload Plugin* → **Replace current with uploaded**

**500 Internal Server Error**
→ Check wp-content/debug.log
→ Increase PHP memory: add to wp-config.php:
   `define('WP_MEMORY_LIMIT', '256M');`

---

## 📖 Full Documentation

See **DEPLOYMENT-GUIDE.md** for complete instructions.

---

## 🎯 Next Steps After Deployment

1. Configure HCIS plugin settings
2. Set up Google Sheets API (see plugin docs)
3. Test employee login with NIP + HP
4. Import employee data if needed
5. Configure training system
6. Set up SSL certificate
7. Install security plugin
8. Set up backups

---

Ready to deploy? Choose your option above and get started!
