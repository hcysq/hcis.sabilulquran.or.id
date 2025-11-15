<?php
/**
 * Template untuk Profile Wizard (Ganti Password Paksa).
 * Dipanggil oleh \HCISYSQ\ProfileWizard
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

global $hcisysq_wizard_data; // Data dikirim dari \HCISYSQ\ProfileWizard
$identity = $hcisysq_wizard_data['identity'] ?? null;
$error_message = $hcisysq_wizard_data['error'] ?? '';
$success_message = $hcisysq_wizard_data['success'] ?? '';

if (!$identity || $identity['type'] !== 'user') {
  // Seharusnya tidak pernah terjadi, tapi sebagai pengaman
  echo "<p>Sesi tidak valid. Silakan logout dan login kembali.</p>";
  return;
}

$user = $identity['user'];
?>

<div class="hcisysq-profile-wizard-container">
  <div class="hcisysq-wizard-box">

    <div class="hcisysq-wizard-header">
      <h2 class="hcisysq-wizard-title">Perbarui Password Anda</h2>
      <p class="hcisysq-wizard-subtitle">
        Demi keamanan, Anda wajib memperbarui password Anda. Password lama Anda (berdasarkan No. HP) tidak lagi diizinkan.
      </p>
    </div>

    <?php if ($error_message): ?>
      <div class="hcisysq-alert hcisysq-alert-error">
        <?php echo esc_html($error_message); ?>
      </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
      <div class="hcisysq-alert hcisysq-alert-success">
        <?php echo esc_html($success_message); ?>
      </div>
      <p>Anda akan diarahkan ke Dashboard...</p>
      <script>
        setTimeout(function() {
          window.location.href = '<?php echo esc_url(home_url('/dashboard/')); ?>';
        }, 3000);
      </script>
    <?php else: ?>
      <form method="POST" action="" class="hcisysq-wizard-form">

        <?php // Security Nonce ?>
        <?php wp_nonce_field('hcisysq_reset_password', 'hcisysq_reset_nonce'); ?>

        <div class="hcisysq-form-group">
          <label for="hcisysq_new_password">Password Baru</label>
          <input type="password" id="hcisysq_new_password" name="hcisysq_new_password" required>
        </div>

        <div class="hcisysq-form-group">
          <label for="hcisysq_confirm_password">Konfirmasi Password Baru</label>
          <input type="password" id="hcisysq_confirm_password" name="hcisysq_confirm_password" required>
        </div>

        <div class="hcisysq-form-group">
          <button type="submit" name="hcisysq_submit_reset" class="hcisysq-button-primary">
            Simpan Password Baru
          </button>
        </div>
      </form>
    <?php endif; ?>

  </div>
</div>
