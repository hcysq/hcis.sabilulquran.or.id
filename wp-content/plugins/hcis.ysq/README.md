# HCIS.YSQ Plugin - WordPress

Plugin HRIS (Human Resource Information System) untuk sistem kepegawaian dengan integrasi Google Sheets.

## Fitur

1. **Autentikasi Pegawai**
   - Login menggunakan NIP + Password/No HP
   - Logout manual & auto-logout setelah idle 15 menit
   - Lupa password via WhatsApp (StarSender)

2. **Dashboard Pegawai**
   - Profil ringkas pegawai
   - Status data & publikasi
   - Menu navigasi lengkap

3. **Form Pelatihan**
   - Input data pelatihan pegawai
   - Upload sertifikat (PDF, JPG, PNG)
   - Sinkronisasi otomatis ke Google Sheets

4. **Integrasi Google Sheets**
   - **Profil Pegawai**: Import dari CSV (auto-sync harian)
   - **Data Users**: Import dari Google Sheet (auto-sync harian)
   - **Form Pelatihan**: Submit data ke Google Sheet via Apps Script

## Instalasi

1. Upload folder plugin ke `wp-content/plugins/`
2. Aktifkan plugin melalui WordPress Admin
3. Buka **Tools → HCIS.YSQ Settings** untuk konfigurasi

## Konfigurasi Google Sheets

### 1. Profil Pegawai (CSV)

Konfigurasikan URL CSV melalui halaman **Portal HCIS → Import Data**. Pastikan sumber data berada di lokasi yang dibatasi (misalnya Google Drive dengan akses "Restricted") dan hanya dibagikan ke akun layanan yang memang digunakan oleh server WordPress.

**Struktur kolom yang dibutuhkan:**
- Nomor (NIP)
- NAMA
- UNIT
- JABATAN
- TEMPAT LAHIR
- TANGGAL LAHIR (TTTT-BB-HH)
- ALAMAT KTP
- DESA/KELURAHAN
- KECAMATAN
- KOTA/KABUPATEN
- KODE POS
- EMAIL
- NO HP
- TMT

### 2. Data Users (Google Sheet)

Masukkan **Sheet ID** dan **Tab Name** Anda sendiri melalui halaman pengaturan plugin. Simpan dokumen dalam mode privat dan bagikan hanya kepada akun layanan yang Anda kelola.

**Struktur kolom yang dibutuhkan:**
- NIP
- NAMA
- JABATAN
- UNIT
- NO HP
- PASSWORD (opsional – sangat dianjurkan mengisi password unik untuk setiap pengguna)

**Cara konfigurasi:**
1. Bagikan Google Sheet hanya kepada akun layanan yang dipakai oleh server WordPress.
2. Masukkan Sheet ID dan Tab Name di HCIS.YSQ Settings.
3. Klik "Import Sekarang" untuk sinkronisasi manual.
4. Import otomatis akan berjalan setiap hari via WP-Cron.

### 3. Form Pelatihan (Google Sheet)

Gunakan Google Sheet pribadi untuk menyimpan data pelatihan dan pastikan hanya akun layanan terpercaya yang memiliki akses baca/tulis.

**Setup Google Apps Script:**

1. Buka Google Sheet untuk data pelatihan
2. Klik **Extensions → Apps Script**
3. Copy-paste script dari file `docs/google-apps-script-training.js`
4. Deploy:
   - Klik **Deploy → New deployment**
   - Pilih **Web app**
   - Execute as: **Me**
   - Who has access: **Only trusted accounts**
   - Copy URL deployment
5. Paste URL ke **HCIS.YSQ Settings → Training → Web App URL**

**Struktur kolom yang akan dibuat otomatis:**
- Timestamp
- User ID
- NIP
- Nama
- Unit
- Jabatan
- Nama Pelatihan
- Tahun
- Pembiayaan
- Kategori
- File URL

## Flow Login-Logout

### Login
1. User mengakses halaman `/masuk`
2. Input NIP + Password akun
3. Plugin memverifikasi ke tabel `hcisysq_users`
4. Jika berhasil, session dibuat dengan cookie `hcisysq_token` (expired 1 jam)
5. Redirect ke `/dashboard`

### Logout
1. **Manual**: Klik tombol "Keluar" di dropdown user menu
2. **Auto**: Setelah idle 15 menit, popup warning muncul (countdown 30 detik)
3. Session dihapus dari transient & cookie
4. Redirect ke `/masuk`

### Guards
- Halaman `/dashboard` dan `/pelatihan` hanya bisa diakses jika sudah login
- Halaman `/masuk` akan redirect ke `/dashboard` jika sudah login

## Database Tables

### `wp_hcisysq_users`
Tabel autentikasi user (di-sync dari Google Sheet)

### `wp_hcisysq_profiles`
Tabel profil pegawai lengkap (di-sync dari CSV)

### `wp_hcisysq_trainings`
Tabel rekam data pelatihan yang diinput pegawai

## Shortcodes

```
[hcisysq_login]     - Halaman login
[hcisysq_dashboard] - Dashboard pegawai
[hcisysq_form]      - Form input pelatihan
```

## Cron Jobs

Plugin menggunakan WP-Cron untuk sinkronisasi otomatis:

- `hcisysq_profiles_cron` - Import profil pegawai (daily)
- `hcisysq_users_cron` - Import data users (daily)

## StarSender Integration

Untuk fitur "Lupa Password", plugin mengirim pesan ke Admin HCM via WhatsApp menggunakan StarSender API.

### Konfigurasi kredensial WhatsApp

Anda dapat menyediakan nomor admin dan token API melalui salah satu cara berikut:

1. **Halaman Admin WordPress** — buka **Portal HCIS → WhatsApp & SSO Settings** dan isi field "Admin WhatsApp" serta "WhatsApp API Token". Nilai ini disimpan sebagai opsi WordPress.
2. **`wp-config.php`** — definisikan konstanta berikut sebelum `/* That's all, stop editing! */`:
   ```php
   define('HCISYSQ_SS_HC', '62xxxxxxxxxxx');
   define('HCISYSQ_SS_KEY', 'your-starsender-token');
   ```
   Konstanta akan meng-override nilai dari opsi WordPress.
3. **Variabel lingkungan** — set `HCISYSQ_ADMIN_WA` atau `HCISYSQ_SS_HC` untuk nomor admin, dan `HCISYSQ_WA_TOKEN` atau `HCISYSQ_SS_KEY` untuk token API.

### Migrasi kredensial lama

Jika sebelumnya Anda menyimpan nilai di dalam plugin (`hcis.ysq.php`), jalankan perintah WP-CLI berikut setelah memperbarui plugin:

```
wp hcisysq migrate-secrets
```

Gunakan `--force` apabila ingin menimpa opsi yang sudah terisi. Perintah ini akan menyalin nilai konstanta lama ke opsi WordPress sehingga dapat dikelola dari halaman admin.

## Changelog

### v1.0.2
- Fixed login-logout flow
- Added Google Sheets integration (Users, Profiles, Training)
- Added auto-logout after 15 minutes idle
- Improved form UI/UX
- Fixed table structure consistency (hcisysq_users vs hcisysq_employees)

## Support

Untuk pertanyaan atau bug report, hubungi developer.

## License

Proprietary - Internal use only
