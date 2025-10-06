<?php
/**
 * Pelatihan redirect handler.
 */
define('GAS_EXEC_URL', 'https://script.google.com/macros/s/AKfycbxReKFiKsW1BtDZufNNi4sCuazw5jjzUQ9iHDPylmm9ARuAudqsB6CmSI_2vNpng3uP/exec');

require_once dirname(__DIR__) . '/wp-load.php';

use HCISYSQ\Auth;
use HCISYSQ\Hcis_Gas_Token;

if (!defined('ABSPATH')) {
  header('Location: /');
  exit;
}

$login_url = home_url('/' . HCISYSQ_LOGIN_SLUG . '/');

$identity = class_exists('HCISYSQ\\Auth') ? Auth::current_identity() : null;
$identity_type = $identity['type'] ?? null;
$is_user_identity = $identity_type === 'user';

if (!is_user_logged_in() && !$is_user_identity) {
  wp_safe_redirect($login_url);
  exit;
}

$token = Hcis_Gas_Token::create_token_for_current_user();
if (is_wp_error($token)) {
  hcisysq_log('GAS redirect error: ' . $token->get_error_message());
  wp_die('Tidak dapat membuat token akses. Hubungi administrator.');
}

$redirect_url = GAS_EXEC_URL . '?token=' . rawurlencode($token);
wp_redirect($redirect_url);
exit;
