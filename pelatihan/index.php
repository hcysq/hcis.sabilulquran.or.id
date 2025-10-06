<?php
// === CONFIG (sudah diisi sesuai instruksi) ===
$GAS_EXEC_URL     = 'https://script.google.com/macros/s/AKfycbxReKFiKsW1BtDZufNNi4sCuazw5jjzUQ9iHDPylmm9ARuAudqsB6CmSI_2vNpng3uP/exec';
$HCIS_GAS_API_KEY = 'sq-hris-2025-key';

// === Bootstrap WordPress ===
require_once dirname(__DIR__) . '/wp-load.php';

// Gunakan sesi login HCIS custom
$identity = \HCISYSQ\Auth::current_identity();
if (!$identity || ($identity['type'] ?? '') !== 'user') {
    $slug = defined('HCISYSQ_LOGIN_SLUG') ? trim((string)HCISYSQ_LOGIN_SLUG, '/') : 'masuk';
    wp_safe_redirect(home_url('/' . ($slug !== '' ? $slug . '/' : 'masuk/')));
    exit;
}

$me = $identity['user'];
$nip = '';
if (isset($me->nip)) {
    $nip = trim((string) $me->nip);
}
if ($nip === '' && isset($identity['username'])) {
    $nip = trim((string) $identity['username']);
}
if ($nip === '') {
    wp_die('NIP tidak ditemukan pada sesi pengguna. Silakan hubungi tim HCM.');
}

// Buat token acak (opaque), TTL 5 menit, single-use
try {
    $token = bin2hex(random_bytes(16)); // 32 hex
} catch (Exception $e) {
    $token = wp_generate_password(32, false, false);
}

// Simpan mapping token→NIP (transient WP)
$data = [
    'nip'  => $nip,
    'iat'  => time(),
    'used' => false,
    'key'  => $HCIS_GAS_API_KEY,
];
set_transient('gas_token_' . $token, $data, 5 * MINUTE_IN_SECONDS);

$targetUrl = $GAS_EXEC_URL . '?token=' . rawurlencode($token);

nocache_headers();
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pelatihan Pegawai</title>
    <?php wp_head(); ?>
    <style>
        body.pelatihan-embed {
            margin: 0;
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }
        .pelatihan-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 24px;
            gap: 18px;
        }
        .pelatihan-card {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 16px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.12);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .pelatihan-status {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #155e75;
        }
        .pelatihan-frame {
            flex: 1;
            min-height: 60vh;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 24px 46px rgba(15, 23, 42, 0.16);
            background: #fff;
        }
        .pelatihan-frame iframe {
            border: 0;
            width: 100%;
            height: 100%;
            display: block;
        }
        .pelatihan-fallback {
            font-size: 14px;
            color: #475569;
        }
        .pelatihan-fallback a {
            color: #155e75;
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px solid rgba(21, 94, 117, 0.35);
            padding-bottom: 2px;
        }
        .pelatihan-fallback a:hover {
            opacity: 0.8;
        }
        @media (max-width: 640px) {
            .pelatihan-shell {
                padding: 16px;
            }
            .pelatihan-card {
                padding: 18px;
            }
        }
    </style>
</head>
<body <?php body_class('pelatihan-embed'); ?>>
<?php if (function_exists('wp_body_open')) { wp_body_open(); } ?>
    <div class="pelatihan-shell">
        <div class="pelatihan-card">
            <span class="pelatihan-status" id="pelatihan-status">Menyiapkan koneksi ke aplikasi pelatihan…</span>
            <div class="pelatihan-frame">
                <iframe id="pelatihan-frame" src="<?= esc_url($targetUrl) ?>" title="Pelatihan Pegawai" loading="lazy" allowfullscreen></iframe>
            </div>
            <p class="pelatihan-fallback">
                Jika aplikasi tidak muncul, <a href="<?= esc_url($targetUrl) ?>" target="_blank" rel="noopener">buka tautan pelatihan di tab baru</a>.
            </p>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var frame = document.getElementById('pelatihan-frame');
            var status = document.getElementById('pelatihan-status');
            if (!frame || !status) return;
            frame.addEventListener('load', function () {
                status.textContent = 'Aplikasi pelatihan siap digunakan.';
            });
        });
    </script>
    <?php wp_footer(); ?>
</body>
</html>
