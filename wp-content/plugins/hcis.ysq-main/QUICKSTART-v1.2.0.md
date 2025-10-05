# QUICKSTART - HCIS YSQ v1.2.0

## Setup Login di Home (Root) untuk hcis.sabilulquran.or.id

---

## LANGKAH CEPAT (5 Menit)

### 1. Upload & Aktivasi Plugin

```bash
# Upload folder plugin ke server
wp-content/plugins/hcis.ysq/

# Via WP Admin
Plugins → Add New → Upload Plugin → Pilih ZIP → Install Now → Activate
```

### 2. Konfigurasi Settings

**Admin → Tools → HCIS.YSQ Settings**

Scroll ke **Section 4: WhatsApp & SSO Settings**, isi:

| Field | Value | Wajib? |
|-------|-------|--------|
| Admin WhatsApp | `6285175201627` | ✅ (untuk lupa password) |
| WhatsApp API Token | `4a74d8ae-...` | ✅ (Starsender key) |
| Default GAS URL | `https://script.google.com/.../exec` | ✅ (form pelatihan SSO) |

Klik **Simpan**.

### 3. Setup Halaman

Buat/edit 2 halaman di WordPress:

#### A. Halaman Home (Login)
- **URL**: `https://hcis.sabilulquran.or.id/` (root)
- **Title**: Masuk / Login
- **Slug**: Kosongkan (agar jadi homepage) atau `masuk`
- **Content**:
  ```
  [hcis_ysq_login]
  ```
- **Settings → Reading**: Set sebagai "Homepage"

#### B. Halaman Dashboard
- **URL**: `https://hcis.sabilulquran.or.id/dashboard`
- **Title**: Dashboard Pegawai
- **Slug**: `dashboard`
- **Content**:
  ```
  [hcis_ysq_dashboard]
  ```

### 4. Permalink Settings

**Settings → Permalinks** → Pilih **"Post name"** → Save Changes.

---

## TESTING

### Login Test
1. Buka `https://hcis.sabilulquran.or.id/`
2. Input:
   - **Akun**: NIP pegawai (contoh: `123456`)
   - **Pasword**: No HP (format: `628xxx` atau `08xxx`)
3. Klik **"Masuk"**
4. Jika berhasil → redirect ke `/dashboard`

### Lupa Password Test
1. Klik **"Lupa pasword?"**
2. Input NIP → Klik **"Kirim"**
3. Admin HCM akan menerima WA notifikasi

### Dashboard Test
1. Akses `/dashboard` saat belum login → redirect ke `/`
2. Login dulu → akses `/dashboard` → tampil profil & menu

---

## TROUBLESHOOTING

### ❌ Error "Invalid nonce"
**Solusi**: Hard-refresh halaman (Ctrl+Shift+R) atau clear cache browser.

### ❌ Redirect loop / Error 400
**Cek**:
1. Permalink bukan "Plain" (harus "Post name")
2. Halaman `masuk` dan `dashboard` sudah ada
3. Home page sudah di-set di **Settings → Reading**

### ❌ Tombol "Lupa pasword" tidak kirim WA
**Cek**:
1. **Settings → HCIS.YSQ** → Admin WhatsApp & Token sudah diisi
2. Token Starsender valid dan aktif
3. Cek log: `wp-content/hcisysq.log`

### ❌ Form Pelatihan (GAS) tidak redirect
**Cek**:
1. **Settings → HCIS.YSQ** → Default GAS URL sudah diisi
2. Google Apps Script deployed sebagai "Web app" (Execute as: Me, Access: Anyone)

---

## SHORTCODE REFERENCE

| Shortcode | Fungsi | Halaman |
|-----------|--------|---------|
| `[hcis_ysq_login]` | Form login | Home `/` |
| `[hcis_ysq_dashboard]` | Dashboard pegawai | `/dashboard` |
| `[hcis_ysq_form]` | Form input pelatihan manual | `/pelatihan` (opsional) |
| `[hcis_ysq_form_button]` | Tombol SSO ke GAS | Anywhere |

**Alias lama (backward compatible):**
- `[hcisysq_login]`, `[hrissq_login]`
- `[hcisysq_dashboard]`, `[hrissq_dashboard]`

---

## STRUKTUR URL

| URL | Fungsi | Login Required? |
|-----|--------|-----------------|
| `/` (home) | Login form | ❌ |
| `/dashboard` | Dashboard pegawai | ✅ |
| `/profil` | Profil detail (future) | ✅ |
| `/pelatihan` | Form pelatihan (future) | ✅ |

---

## LOG FILE

File log plugin: `wp-content/hcisysq.log`

Contoh log:
```
[HCIS.YSQ 2025-10-04 10:15:23] Login attempt: NIP=123456
[HCIS.YSQ 2025-10-04 10:15:24] Login success: NIP=123456, redirect=/dashboard/
```

---

## ROLLBACK (Jika Ada Masalah)

1. Non-aktifkan plugin via WP Admin
2. Hapus folder `wp-content/plugins/hcis.ysq/`
3. Upload backup plugin lama (jika ada)
4. Aktifkan plugin lama

**Database tidak perlu di-restore** (tidak ada perubahan tabel).

---

## NEXT STEPS

Setelah setup berhasil:
1. Import data pegawai via **Settings → HCIS.YSQ → Section 1-3**
2. Test login dengan beberapa NIP pegawai
3. Monitor log file untuk error
4. Setup Google Sheets integration (lihat `docs/SETUP-GOOGLE-SHEETS.md`)

---

**Version**: 1.2.0
**Author**: samijaya
**Support**: Lihat `IMPLEMENTATION-REPORT-v1.2.0.md` untuk detail teknis
