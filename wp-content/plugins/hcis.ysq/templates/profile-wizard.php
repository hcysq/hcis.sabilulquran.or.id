<?php
/**
 * Template untuk Halaman Ganti Password Paksa (Profile Wizard).
 *
 * File ini dipanggil oleh \Includes\ProfileWizard.php
 * Menerima variabel $args['error'] jika ada kesalahan validasi.
 */

// Exit jika diakses langsung
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

// Ambil pesan error dari controller jika ada
$error_message = $args['error'] ?? '';

// Memuat header tema WordPress
get_header();
?>

<div class="hcis-wizard-container">
<div class="hcis-wizard-box">

<div class="hcis-wizard-header">
<?php
// Definisikan HCIS_YSQ_PLUGIN_FILE jika belum ada (untuk keamanan)
if ( ! defined( 'HCIS_YSQ_PLUGIN_FILE' ) ) {
// Asumsikan path relatif dari lokasi template ini
define( 'HCIS_YSQ_PLUGIN_FILE', dirname( __DIR__ ) . '/hcis.ysq.php' );
}
$logo_url = plugins_url( 'assets/logo.png', HCIS_YSQ_PLUGIN_FILE );
?>
<img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo Yayasan Sabilul Qur'an" class="hcis-wizard-logo">
<h1>Ganti Password Anda</h1>
<p>Untuk meningkatkan keamanan, Anda wajib mengganti password lama Anda (yang menggunakan No. HP) dengan password baru.</p>
</div>

<?php // Tampilkan pesan error jika ada ?>
<?php if ( ! empty( $error_message ) ) : ?>
<div class="hcis-wizard-error">
<p><?php echo esc_html( $error_message ); ?></p>
</div>
<?php endif; ?>

<?php // Form Ganti Password ?>
<form method="POST" class="hcis-wizard-form">

<?php
// Nonce field untuk keamanan.
// Ini akan divalidasi oleh ProfileWizard.php dengan nama 'hcis_change_password_nonce'
wp_nonce_field( 'hcis_change_password', 'hcis_change_password_nonce' );
?>

<div class="hcis-form-group">
<label for="hcis_new_password">Password Baru</label>
<input type="password" id="hcis_new_password" name="hcis_new_password" class="input" value="" required>
<p class="description">Sangat disarankan: minimal 8 karakter, gunakan huruf besar, huruf kecil, dan angka.</p>
</div>

<div class="hcis-form-group">
<label for="hcis_confirm_password">Konfirmasi Password Baru</label>
<input type="password" id="hcis_confirm_password" name="hcis_confirm_password" class="input" value="" required>
</div>

<div class="hcis-form-submit">
<button type="submit" class="button button-primary button-large">
Simpan dan Lanjutkan
</button>
</div>
</form>

</div>
</div>

<?php
// Memuat footer tema WordPress
get_footer();
?>
