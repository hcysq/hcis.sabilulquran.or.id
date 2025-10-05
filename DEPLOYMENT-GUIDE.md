# WordPress HCIS.YSQ - Deployment Guide

> **Status**: ✅ Struktur siap deploy | Konfigurasi lengkap | Cleanup selesai

## 📋 Ringkasan Persiapan

Semua persiapan deployment telah selesai dilakukan:

- ✅ Tema WordPress dibersihkan (hanya `ysq-theme`)
- ✅ Plugin WordPress dibersihkan (hanya `hcis.ysq`)
- ✅ File tidak diperlukan dihapus
- ✅ Environment variables dikonfigurasi (`.env`)
- ✅ Docker Compose file siap (`docker-compose.yml`)
- ✅ WordPress security keys generated
- ✅ Struktur folder sesuai best practice

---

## 🎯 Struktur Akhir

```
project/
├── .env                          ✅ Secrets & Config
├── .htaccess                     ✅ URL Rewriting
├── docker-compose.yml            ✅ Docker Orchestration
├── index.php                     ✅ WordPress Entry Point
├── wp-blog-header.php
├── wp-config-sample.php
├── wp-load.php
├── wp-settings.php
└── wp-content/
    ├── themes/
    │   ├── index.php
    │   └── ysq-theme/            ✅ Custom Theme (RENAMED)
    │       ├── style.css
    │       ├── functions.php
    │       ├── header.php
    │       ├── footer.php
    │       ├── assets/
    │       └── ...
    ├── plugins/
    │   ├── index.php
    │   └── hcis.ysq/             ✅ HCIS Plugin (RENAMED)
    │       ├── hcis.ysq.php
    │       ├── includes/
    │       ├── assets/
    │       └── ...
    ├── fonts/                    ✅ Custom Fonts
    └── uploads/                  ✅ Upload Directory
```

---

## 🚀 Opsi Deployment

### Opsi 1: Docker Deployment (Recommended)

**Requirements:**
- Server dengan Docker dan Docker Compose installed
- Port 8080 available (atau customize di docker-compose.yml)

**Steps:**

1. **Upload semua files ke server:**
   ```bash
   scp -r project/ user@your-server:/path/to/deployment/
   ```

2. **SSH ke server dan masuk ke folder:**
   ```bash
   ssh user@your-server
   cd /path/to/deployment/project
   ```

3. **Update URL di .env:**
   ```bash
   nano .env
   # Ganti:
   # WP_HOME=http://localhost:8080
   # WP_SITEURL=http://localhost:8080
   # Dengan:
   # WP_HOME=http://your-domain.com
   # WP_SITEURL=http://your-domain.com
   ```

4. **Start containers:**
   ```bash
   docker-compose up -d
   ```

5. **Monitor logs:**
   ```bash
   docker-compose logs -f
   ```

6. **Akses WordPress:**
   - URL: `http://your-domain.com` (atau `http://server-ip:8080`)
   - Ikuti WordPress installation wizard
   - Install WordPress dengan kredensial admin baru

7. **Aktivasi tema dan plugin:**
   - Login ke WP Admin: `/wp-admin`
   - Pergi ke **Appearance > Themes** → Activate `YSQ Theme`
   - Pergi ke **Plugins** → Activate `HCIS.YSQ`

---

### Opsi 2: Traditional PHP Hosting

**Requirements:**
- PHP 8.0+ (recommended 8.2)
- MySQL 5.7+ atau MariaDB 10.6+
- Apache/Nginx dengan mod_rewrite
- PHP Extensions: mysqli, gd, curl, mbstring, xml, zip

**Steps:**

1. **Buat database MySQL:**
   ```sql
   CREATE DATABASE hcis_preview CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'boltuser'@'localhost' IDENTIFIED BY 'HcisYsq2024!SecurePass';
   GRANT ALL PRIVILEGES ON hcis_preview.* TO 'boltuser'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Upload files via FTP/SFTP:**
   - Upload semua file kecuali `docker-compose.yml`
   - Pastikan `.htaccess` ter-upload

3. **Buat wp-config.php:**
   ```bash
   cp wp-config-sample.php wp-config.php
   nano wp-config.php
   ```

4. **Edit wp-config.php dengan nilai dari .env:**
   ```php
   define('DB_NAME', 'hcis_preview');
   define('DB_USER', 'boltuser');
   define('DB_PASSWORD', 'HcisYsq2024!SecurePass');
   define('DB_HOST', 'localhost');
   define('DB_CHARSET', 'utf8mb4');
   define('DB_COLLATE', '');

   $table_prefix = 'wpw3_';

   // Copy semua define() untuk AUTH_KEY, SECURE_AUTH_KEY, dll dari .env
   define('AUTH_KEY', 'E/o @XU91](0:|:y>L,{#b#z}O()LQQYO[IZU#Um|]ndAD}`WbVqlyo*@J+JE*!`');
   // ... dan seterusnya (8 keys total)

   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

5. **Set permissions:**
   ```bash
   chmod 755 wp-content
   chmod -R 755 wp-content/themes
   chmod -R 755 wp-content/plugins
   chmod 755 wp-content/uploads
   ```

6. **Akses dan install WordPress:**
   - Buka: `http://your-domain.com/wp-admin/install.php`
   - Ikuti installation wizard

7. **Aktivasi tema dan plugin:**
   - Login ke WP Admin
   - Activate `YSQ Theme` di Appearance > Themes
   - Activate `HCIS.YSQ` di Plugins

---

### Opsi 3: Platform as a Service

#### A. **Kinsta / WP Engine (Managed WordPress)**

1. Buat site baru di dashboard
2. Upload via SFTP:
   - `wp-content/themes/ysq-theme/`
   - `wp-content/plugins/hcis.ysq/`
3. Import database jika ada backup
4. Activate tema dan plugin via WP Admin

#### B. **DigitalOcean App Platform**

1. Push ke Git repository
2. Buat App dengan PHP buildpack
3. Tambahkan managed database (MySQL)
4. Set environment variables dari `.env`
5. Deploy

#### C. **Cloudways**

1. Launch server (PHP + MySQL)
2. Deploy via SFTP atau Git
3. Configure domain
4. Import database dan activate components

---

## 🔐 Keamanan

**WordPress Security Keys sudah di-generate:**
```
AUTH_KEY, SECURE_AUTH_KEY, LOGGED_IN_KEY, NONCE_KEY
AUTH_SALT, SECURE_AUTH_SALT, LOGGED_IN_SALT, NONCE_SALT
```

**Database Credentials:**
- Database: `hcis_preview`
- User: `boltuser`
- Password: `HcisYsq2024!SecurePass` (GANTI di production!)
- Prefix: `wpw3_`

**Environment:**
- WP_ENV: `staging`
- Debug enabled dengan log ke `wp-content/debug.log`
- File modifications disabled (`DISALLOW_FILE_MODS`)

---

## 🧪 Testing Checklist

Setelah deployment, test:

- [ ] Homepage loading dengan tema YSQ
- [ ] WP Admin login (`/wp-admin`)
- [ ] Tema `ysq-theme` aktif (Appearance > Themes)
- [ ] Plugin `hcis.ysq` aktif (Plugins)
- [ ] Custom endpoints:
  - [ ] `/masuk` (Login dengan NIP + HP)
  - [ ] `/dashboard` (Employee dashboard)
  - [ ] `/pelatihan` (Training form)
- [ ] Assets loading (CSS, JS, images)
- [ ] File upload functionality
- [ ] Database connection working
- [ ] Plugin logs di `wp-content/hcisysq.log`

---

## 🐛 Troubleshooting

### Error: "Error establishing database connection"
- Check database credentials di wp-config.php
- Verify database exists
- Check database user permissions

### Error: "The page you are looking for cannot be found"
- Check .htaccess file exists
- Verify mod_rewrite enabled (Apache)
- Try resave Permalinks (Settings > Permalinks)

### Error: Tema atau plugin tidak muncul
- Check file permissions (755 untuk folders, 644 untuk files)
- Verify folder names: `ysq-theme` dan `hcis.ysq`
- Clear WordPress cache jika ada caching plugin

### Error: White screen / 500 error
- Check `wp-content/debug.log`
- Verify PHP version (minimum 8.0)
- Check PHP extensions installed
- Increase PHP memory limit di wp-config.php:
  ```php
  define('WP_MEMORY_LIMIT', '256M');
  ```

---

## 📝 Post-Deployment Tasks

1. **Konfigurasi HCIS Plugin:**
   - Set Google Sheets API credentials (jika belum)
   - Configure employee database sync
   - Test NIP + HP login flow

2. **Permalinks:**
   - WP Admin > Settings > Permalinks
   - Set ke "Post name" atau custom structure

3. **Security Hardening:**
   - Ganti semua default passwords
   - Install security plugin (Wordfence/iThemes Security)
   - Setup SSL certificate (Let's Encrypt)
   - Configure firewall rules

4. **Performance:**
   - Install caching plugin (WP Rocket/W3 Total Cache)
   - Setup CDN jika diperlukan
   - Optimize images

5. **Backup:**
   - Setup automated database backups
   - Setup file backups (wp-content)
   - Test restore procedure

---

## 📞 Support

**Plugin HCIS.YSQ:**
- Dokumentasi: `wp-content/plugins/hcis.ysq/README.md`
- Changelog: `wp-content/plugins/hcis.ysq/CHANGELOG.md`
- Setup Guide: `wp-content/plugins/hcis.ysq/docs/SETUP-GOOGLE-SHEETS.md`

**Tema YSQ:**
- Style guide: `wp-content/themes/ysq-theme/README.txt`
- Package info: `wp-content/themes/ysq-theme/package.json`

---

## 🎉 Deployment Siap!

Semua file dan konfigurasi sudah siap untuk deployment. Pilih salah satu opsi di atas sesuai infrastruktur yang tersedia.

**Recommended Stack:**
- **Development**: Docker Compose (sudah configured)
- **Staging**: DigitalOcean Droplet + Docker
- **Production**: Managed WordPress (Kinsta/WP Engine)

Good luck! 🚀
