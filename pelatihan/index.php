<?php
// === CONFIG (sudah diisi sesuai instruksi) ===
$GAS_EXEC_URL     = 'https://script.google.com/macros/s/AKfycbxReKFiKsW1BtDZufNNi4sCuazw5jjzUQ9iHDPylmm9ARuAudqsB6CmSI_2vNpng3uP/exec';
$HCIS_GAS_API_KEY = 'sq-hris-2025-key';

// === Bootstrap WordPress ===
// Sesuaikan path jika WordPress kamu tidak di folder satu tingkat di atas
require_once dirname(__DIR__) . '/wp-load.php';

// Cek login
if ( ! is_user_logged_in() ) {
  header('Location: https://hcis.sabilulquran.or.id/masuk');
  exit;
}

// Ambil NIP dari session atau user_meta
if (session_status() === PHP_SESSION_NONE) session_start();
$nip = '';
if (!empty($_SESSION['nip'])) {
  $nip = $_SESSION['nip'];
} else {
  $uid = get_current_user_id();
  $nip = get_user_meta($uid, 'nip', true);
}
$nip = trim((string)$nip);

if ($nip === '') {
  wp_die('NIP tidak ditemukan di sesi pengguna. Silakan hubungi HCM.');
}

// Buat token acak (opaque), TTL 5 menit, single-use
try {
  $token = bin2hex(random_bytes(16)); // 32 hex
} catch (Exception $e) {
  $token = wp_generate_password(32, false, false);
}

// Simpan mapping token→NIP (transient WP)
$data = array(
  'nip'  => $nip,
  'iat'  => time(),
  'used' => false,
  'key'  => $HCIS_GAS_API_KEY // opsional
);
set_transient('gas_token_' . $token, $data, 5 * MINUTE_IN_SECONDS);

// Redirect ke GAS dengan ?token=...
$target = $GAS_EXEC_URL . '?token=' . rawurlencode($token);
header('Location: ' . $target);
exit;
