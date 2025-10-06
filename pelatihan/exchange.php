<?php
// === CONFIG (sudah diisi sesuai instruksi) ===
$HCIS_GAS_API_KEY = 'sq-hris-2025-key';

// Bootstrap WP
require_once dirname(__DIR__) . '/wp-load.php';

// Respon JSON
header('Content-Type: application/json; charset=utf-8');

// Validasi API key dari GAS
$hdr_key = '';
if (!empty($_SERVER['HTTP_X_HCIS_GAS_KEY'])) {
  $hdr_key = trim($_SERVER['HTTP_X_HCIS_GAS_KEY']);
}
if ($hdr_key === '' || !hash_equals($HCIS_GAS_API_KEY, $hdr_key)) {
  http_response_code(401);
  echo json_encode(array('ok' => false, 'error' => 'unauthorized'));
  exit;
}

// Ambil token
$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
if ($token === '') {
  http_response_code(400);
  echo json_encode(array('ok' => false, 'error' => 'missing_token'));
  exit;
}

// Ambil transient + invalidasi (single-use)
$key  = 'gas_token_' . $token;
$data = get_transient($key);
if ($data === false) {
  http_response_code(400);
  echo json_encode(array('ok' => false, 'error' => 'invalid_or_expired'));
  exit;
}
delete_transient($key); // single-use

// Ambil NIP
$nip = isset($data['nip']) ? trim((string)$data['nip']) : '';
if ($nip === '') {
  http_response_code(400);
  echo json_encode(array('ok' => false, 'error' => 'nip_not_found'));
  exit;
}

// Sukses
echo json_encode(array('ok' => true, 'nip' => $nip));
exit;
