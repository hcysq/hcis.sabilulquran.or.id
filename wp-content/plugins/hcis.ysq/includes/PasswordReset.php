<?php

namespace Hcis\Ysq\Includes;

class PasswordReset {
    public static function init() {
        // Override the default WordPress lost password URL
        add_filter('lostpassword_url', [__CLASS__, 'override_lost_password_url'], 10, 2);
        add_filter('retrieve_password_message', [__CLASS__, 'disable_password_reset_email'], 10, 4);

        // Create the custom password reset pages
        add_action('init', [__CLASS__, 'create_pages']);

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
        if (!get_page_by_path('reset-password')) {
            wp_insert_post([
                'post_title'    => 'Reset Password',
                'post_name'     => 'reset-password',
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
        if (is_page('reset-password') && isset($_POST['submit_new_password'])) {
            if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'hcis_reset_password')) {
                $token = sanitize_text_field($_POST['token']);
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];

                if ($new_password !== $confirm_password) {
                    set_transient('hcis_reset_message', ['error' => 'Password baru tidak cocok.'], 60);
                    wp_redirect(site_url('/reset-password/?token=' . $token));
                    exit;
                }

                $result = PasswordResetManager::complete_reset($token, $new_password);

                if (is_wp_error($result)) {
                    set_transient('hcis_reset_message', ['error' => $result->get_error_message()], 60);
                    wp_redirect(site_url('/reset-password/?token=' . $token));
                    exit;
                } else {
                    // Redirect to login page with a success message
                    wp_redirect(wp_login_url() . '?password_reset=success');
                    exit;
                }
            }
        }
    }
}
