<?php

namespace HCISYSQ;

class PasswordReset {
    public static function init() {
        // Override the default WordPress lost password URL
        add_filter('lostpassword_url', [__CLASS__, 'override_lost_password_url'], 10, 2);
        add_filter('retrieve_password_message', [__CLASS__, 'disable_password_reset_email'], 10, 4);

        // Create the custom password reset pages
        add_action('init', [__CLASS__, 'create_pages']);

        // Provide backwards compatibility for the legacy slug
        add_action('template_redirect', [__CLASS__, 'handle_legacy_reset_redirect'], 0);

        // Handle the form submission for requesting a reset
        add_action('template_redirect', [__CLASS__, 'handle_reset_request']);
        
        // Handle the actual password reset form
        add_action('template_redirect', [__CLASS__, 'handle_password_reset']);
    }

    public static function override_lost_password_url($lostpassword_url, $redirect) {
        return site_url('/lupa-password');
    }

    public static function disable_password_reset_email($message, $key, $user_login, $user_data) {
        // Return an empty string or a custom message to prevent the email from being sent.
        // This effectively disables the default password reset flow.
        return '';
    }

    public static function create_pages() {
        if (!get_page_by_path('lupa-password')) {
            wp_insert_post([
                'post_title'    => 'Lupa Password',
                'post_name'     => 'lupa-password',
                'post_content'  => '[hcis_lupa_password_form]',
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_type'     => 'page',
            ]);
        }
        $resetSlug = trim(defined('HCISYSQ_RESET_SLUG') ? HCISYSQ_RESET_SLUG : 'reset-password', '/');
        if ($resetSlug === '') {
            return;
        }

        if (!get_page_by_path($resetSlug)) {
            wp_insert_post([
                'post_title'    => 'Ganti Password',
                'post_name'     => $resetSlug,
                'post_content'  => '[hcis_reset_password_form]',
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_type'     => 'page',
            ]);
        }
    }

    public static function handle_reset_request() {
        if (is_page('lupa-password') && isset($_POST['submit_reset_request'])) {
            if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'hcis_request_reset')) {
                $nip = sanitize_text_field($_POST['nip']);
                $nik = sanitize_text_field($_POST['nik']);

                $result = PasswordResetManager::create_reset_request($nip, $nik);

                if (is_wp_error($result)) {
                    set_transient('hcis_reset_message', ['error' => $result->get_error_message()], 60);
                } else {
                    set_transient('hcis_reset_message', ['success' => $result['message']], 60);
                }
                // Redirect to the same page to show the message and prevent form resubmission
                wp_redirect(site_url('/lupa-password'));
                exit;
            }
        }
    }

    public static function handle_password_reset() {
        $resetSlug = trim(defined('HCISYSQ_RESET_SLUG') ? HCISYSQ_RESET_SLUG : 'reset-password', '/');
        if ($resetSlug === '') {
            return;
        }

        if (!is_page($resetSlug)) {
            return;
        }

        $identity   = \HCISYSQ\Auth::current_identity();
        $isUser     = $identity && ($identity['type'] ?? '') === 'user';
        $needsReset = $isUser && !empty($identity['needs_password_reset']);
        $dashboardUrl = home_url('/' . trim(HCISYSQ_DASHBOARD_SLUG, '/') . '/');
        $nonceValid = isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'hcis_reset_password');

        if (isset($_POST['skip_password_reset'])) {
            if ($nonceValid && $needsReset) {
                \HCISYSQ\Auth::update_current_session([
                    'reset_skip_granted'    => true,
                    'needs_password_reset'  => false,
                ]);
                wp_redirect($dashboardUrl);
            } else {
                wp_redirect(home_url('/' . $resetSlug . '/'));
            }
            exit;
        }

        if (!isset($_POST['submit_new_password'])) {
            return;
        }

        if (!$nonceValid) {
            wp_redirect(home_url('/' . $resetSlug . '/'));
            exit;
        }

        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            set_transient('hcis_reset_message', ['error' => 'Password baru tidak cocok.'], 60);
            $redirect = $token !== ''
                ? home_url('/' . $resetSlug . '/?token=' . rawurlencode($token))
                : home_url('/' . $resetSlug . '/');
            wp_redirect($redirect);
            exit;
        }

        if ($token !== '') {
            $result = PasswordResetManager::complete_reset($token, $new_password);

            if (is_wp_error($result)) {
                set_transient('hcis_reset_message', ['error' => $result->get_error_message()], 60);
                wp_redirect(home_url('/' . $resetSlug . '/?token=' . rawurlencode($token)));
                exit;
            }

            wp_redirect(wp_login_url() . '?password_reset=success');
            exit;
        }

        if (!$needsReset || !$isUser || empty($identity['user'])) {
            set_transient('hcis_reset_message', ['error' => 'Sesi Anda tidak memiliki akses untuk mengganti password tanpa token.'], 60);
            wp_redirect(home_url('/' . $resetSlug . '/'));
            exit;
        }

        $nip = $identity['user']->nip ?? '';
        if ($nip === '') {
            set_transient('hcis_reset_message', ['error' => 'Identitas pengguna tidak valid.'], 60);
            wp_redirect(home_url('/' . $resetSlug . '/'));
            exit;
        }

        $result = PasswordResetManager::update_password_for_nip($nip, $new_password);
        if (is_wp_error($result)) {
            set_transient('hcis_reset_message', ['error' => $result->get_error_message()], 60);
            wp_redirect(home_url('/' . $resetSlug . '/'));
            exit;
        }

        \HCISYSQ\Auth::update_current_session([
            'needs_password_reset' => false,
            'reset_skip_granted'   => false,
        ]);

        wp_redirect(add_query_arg('password_reset', 'success', $dashboardUrl));
        exit;
    }

    public static function handle_legacy_reset_redirect() {
        if (defined('HCISYSQ_RESET_SLUG') && HCISYSQ_RESET_SLUG === 'reset-password') {
            return;
        }

        $legacySlug = 'reset-password';
        $requested = '';

        global $wp;
        if (isset($wp->request)) {
            $requested = trim($wp->request, '/');
        } elseif (!empty($_SERVER['REQUEST_URI'])) {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $requested = trim($path ?? '', '/');
        }

        if (strcasecmp($requested, $legacySlug) !== 0) {
            return;
        }

        $targetSlug = trim(defined('HCISYSQ_RESET_SLUG') ? HCISYSQ_RESET_SLUG : $legacySlug, '/');
        if ($targetSlug === '') {
            return;
        }

        wp_safe_redirect(home_url('/' . $targetSlug . '/'), 301);
        exit;
    }
}
