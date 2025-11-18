<?php

namespace HCISYSQ;

use WP_Error;

class PasswordResetManager {

    private static $table_name = 'wp_hcisysq_password_resets';

    public static function create_reset_request($nip, $nik) {
        global $wpdb;

        if (empty($nip) || empty($nik)) {
            return new WP_Error('validation_failed', 'NIP dan NIK wajib diisi.');
        }

        $employees_table = $wpdb->prefix . 'ysq_employees';
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT wp_user_id, phone_number FROM $employees_table WHERE employee_id_number = %s AND ktp_number = %s",
            $nip,
            $nik
        ));

        if (!$employee || empty($employee->wp_user_id)) {
            return new WP_Error('validation_failed', 'Kombinasi NIP dan NIK tidak ditemukan.');
        }

        // Generate a secure random token
        $token = bin2hex(random_bytes(32));

        // Hash the token for database storage
        $token_hash = hash('sha256', $token);

        // Set an expiration time (e.g., 30 minutes from now)
        $expires_at = date('Y-m-d H:i:s', time() + 30 * 60);

        // Store the token hash in the database
        $wpdb->insert(
            self::$table_name,
            [
                'nip'        => $nip,
                'token_hash' => $token_hash,
                'expires_at' => $expires_at,
            ],
            [
                '%s',
                '%s',
                '%s',
            ]
        );

        // Send the token to the user via WhatsApp
        $reset_link = site_url('/reset-password/?token=' . $token);
        $phone_number = $employee->phone_number;
        
        $user_message = "Anda telah meminta reset password. Silakan klik link di bawah ini untuk melanjutkan. Link ini hanya berlaku selama 30 menit.\n\n" . $reset_link;
        
        $send_result = StarSender::send($phone_number, $user_message);

        if (is_wp_error($send_result)) {
            // If sending fails, we should probably log it but not necessarily block the user.
            // The user-facing message is generic.
            hcisysq_log('Password reset link failed to send to ' . $phone_number . ': ' . $send_result->get_error_message(), 'error');
        }

        // Notify admin
        StarSender::sendToAdmin("User dengan NIP $nip telah meminta reset password.");

        return ['success' => true, 'message' => 'Jika data valid, link reset password akan dikirimkan ke nomor WhatsApp Anda yang terdaftar.'];
    }

    public static function validate_token($token) {
        global $wpdb;

        if (empty($token)) {
            return new WP_Error('invalid_token', 'Token tidak boleh kosong.');
        }

        $token_hash = hash('sha256', $token);

        $reset_request = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$table_name . " WHERE token_hash = %s",
            $token_hash
        ));

        if (!$reset_request) {
            return new WP_Error('invalid_token', 'Token tidak valid.');
        }

        if ($reset_request->used_at !== null) {
            return new WP_Error('token_used', 'Token ini sudah digunakan.');
        }

        $expires = strtotime($reset_request->expires_at);
        if (time() > $expires) {
            return new WP_Error('token_expired', 'Token sudah kedaluwarsa.');
        }

        return ['success' => true, 'nip' => $reset_request->nip];
    }

    public static function complete_reset($token, $new_password) {
        global $wpdb;

        $validation = self::validate_token($token);
        if (is_wp_error($validation)) {
            return $validation;
        }

        if (empty($new_password)) {
            return new WP_Error('password_empty', 'Password tidak boleh kosong.');
        }

        $nip = $validation['nip'];

        // Get the WordPress user ID from the ysq_employees table
        $employees_table = $wpdb->prefix . 'ysq_employees';
        $employee = $wpdb->get_row($wpdb->prepare("SELECT wp_user_id FROM $employees_table WHERE employee_id_number = %s", $nip));

        if (!$employee || empty($employee->wp_user_id)) {
            return new WP_Error('user_not_found', 'User tidak ditemukan untuk NIP ini.');
        }

        $user_id = $employee->wp_user_id;

        // Update the user's password
        wp_set_password($new_password, $user_id);

        // Mark the token as used
        $token_hash = hash('sha256', $token);
        $wpdb->update(
            self::$table_name,
            ['used_at' => current_time('mysql')],
            ['token_hash' => $token_hash],
            ['%s'],
            ['%s']
        );

        // Clear any flags that force a password reset
        if (get_user_meta($user_id, 'needs_password_reset', true)) {
            delete_user_meta($user_id, 'needs_password_reset');
        }
        if (get_user_meta($user_id, 'ysq_force_password_change', true)) {
            delete_user_meta($user_id, 'ysq_force_password_change');
        }

        return ['success' => true];
    }

    public static function create_table() {
        global $wpdb;
        $table_name = self::$table_name;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nip varchar(255) NOT NULL,
            token_hash varchar(255) NOT NULL,
            expires_at datetime NOT NULL,
            used_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY ix_token_hash (token_hash)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
