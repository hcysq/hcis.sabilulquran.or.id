=== YSQ Theme ===
Contributors: Yayasan Sabilul Qur'an
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Tema ringan untuk subdomain login HRIS Yayasan Sabilul Qur'an (login.sabilulquran.or.id).

Tema ini meniru tampilan situs utama sabilulquran.or.id dengan header, background pola islami, dan footer yang konsisten.

== Fitur ==

* WordPress Customizer lengkap untuk edit visual
* Upload logo melalui Customizer (Site Identity)
* Ubah warna header, footer, dan brand color
* Edit teks header dan footer melalui Customizer
* Edit URL dan teks tombol navigasi
* Background custom melalui WordPress Background
* 3 Widget Area di footer (Info, Contact, Map)
* 3 Menu Location (Primary, Footer Info, Footer Contact)
* Template "Blank Page" tanpa header/footer untuk dashboard HRIS
* Responsif untuk semua ukuran layar
* Support Google Maps embed
* Font system-ui untuk performa optimal

== Instalasi ==

1. Upload folder 'ysq' ke direktori '/wp-content/themes/'
2. Aktifkan tema melalui menu 'Appearance' di WordPress
3. (Opsional) Ganti file logo.png di folder assets/ dengan logo asli Yayasan
4. (Opsional) Ganti file bg.jpg di folder assets/ dengan pola background yang sesuai

== Template Khusus ==

* Blank Page: Template tanpa header dan footer untuk halaman dashboard HRIS
  Cara menggunakan: Buat halaman baru, pilih template "Blank Page" di Page Attributes

== Kustomisasi ==

CARA MUDAH (Tanpa Edit File):

1. Logo: Appearance > Customize > Site Identity > Select Logo
2. Background: Appearance > Customize > Background Image
3. Warna: Appearance > Customize > Header Settings / Footer Settings
4. Teks: Appearance > Customize > Header Settings / Footer Settings
5. Footer Content: Appearance > Widgets > Footer Info/Contact/Map
6. Google Maps: Tambah widget Custom HTML dengan embed code
7. Mode Warna & Branding: Appearance > Customize > Mode Warna / Branding Card (atur primary color, font stack, dan toggle mode gelap)
8. Footer Columns: Appearance > Customize > Footer Settings (pilih jumlah kolom dan isinya tanpa edit kode)
9. Header Behavior: Appearance > Customize > Header Settings (sticky solid, sticky transparan, atau header statis)

CARA MANUAL (Edit File):

* Logo: Ganti /assets/logo.png dengan logo Yayasan (ukuran rekomendasi: 200x200px atau 48px tinggi)
* Background: Ganti /assets/bg.jpg dengan pola islami yang diinginkan (ukuran: 400x400px, seamless pattern)

== Mode Gelap & Branding ==

* Primary Color baru otomatis tersimpan sebagai CSS custom property sehingga tombol, link, dan aksen ikut berubah.
* Font Stack dapat diisi di Appearance > Customize > Typography untuk menentukan urutan font global (misal "Inter, system-ui").
* Toggle Mode Gelap dapat diaktifkan di Appearance > Customize > Mode Warna. Pengunjung dapat berganti antara Sistem > Terang > Gelap langsung dari header.
* Default mode dapat dipaksa Light/Dark melalui opsi yang sama sehingga brand khusus event bisa diterapkan tanpa coding.
* Pengaturan Header Behavior mengatur apakah header sticky solid, sticky blur, atau statis.
* Footer Columns dapat diatur 1-4 kolom dan grid otomatis menyesuaikan.

== Changelog ==

= 1.2 =
* Pembaruan metadata tema untuk rilis 1.2 (versi stylesheet, paket, dan tampilan footer)

= 1.0 =
* Rilis pertama tema YSQ
