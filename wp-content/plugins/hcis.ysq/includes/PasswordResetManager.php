<?php

namespace HCISYSQ;

use WP_Error;

class PasswordResetManager {

    private static $user_repository = null;
    private static $star_sender_class = StarSender::class;

    public static function set_user_repository($repository): void {
        self::$user_repository = $repository;
    }

    public static function set_star_sender_class(?string $class): void {
        self::$star_sender_class = $class ?: StarSender::class;
    }

    public static function reset_testing_overrides(): void {
        self::$user_repository = null;
        self::$star_sender_class = StarSender::class;
    }

    private static function get_user_repository() {
        if (self::$user_repository) {
            return self::$user_repository;
        }

        return new \HCISYSQ\Repositories\UserRepository();
    }

    private static function get_table_name(): string {
        global $wpdb;

        return $wpdb->prefix . 'hcisysq_password_resets';
    }

    private static function call_star_sender(string $method, ...$args) {
        $class = self::get_star_sender_class();

        return forward_static_call([$class, $method], ...$args);
    }

    private static function get_star_sender_class(): string {
        return self::$star_sender_class ?: StarSender::class;
    }

    public static function create_reset_request($nip, $nik) {
        global $wpdb;

        if (empty($nip) || empty($nik)) {
            return new WP_Error('validation_failed', 'NIP dan NIK wajib diisi.');
        }

        // 1. Fetch user from Google Sheets' "User" tab to validate NIK and get phone number
        $user_repo = self::get_user_repository();
        $user = $user_repo->find($nip);

        // 2. Validate user exists and the NIK matches
        if (!$user || empty($user['nik']) || strcasecmp(trim($user['nik']), trim($nik)) !== 0) {
            return new WP_Error('validation_failed', 'Kombinasi NIP dan NIK tidak ditemukan.');
        }
        
        // 3. Check for phone number
        if (empty($user['phone'])) {
            return new WP_Error('phone_not_found', 'Nomor HP untuk pengguna ini tidak ditemukan di sistem.');
        }
        $phone_number = $user['phone'];

        // Generate a secure random token
        $token = bin2hex(random_bytes(32));

        // Hash the token for database storage
        $token_hash = hash('sha256', $token);

        // Set an expiration time (e.g., 30 minutes from now)
        $expires_at = date('Y-m-d H:i:s', time() + 30 * 60);

        // Store the token hash in the database
        $wpdb->insert(
            self::get_table_name(),
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
        $resetSlug = trim(defined('HCISYSQ_RESET_SLUG') ? HCISYSQ_RESET_SLUG : 'reset-password', '/');
        $reset_link = home_url('/' . $resetSlug . '/?token=' . rawurlencode($token));
        
        $user_message = "Anda telah meminta reset password. Silakan klik link di bawah ini untuk melanjutkan. Link ini hanya berlaku selama 30 menit.\n\n" . $reset_link;
        
        $send_result = self::call_star_sender('send', $phone_number, $user_message);

        if (is_wp_error($send_result)) {
            // If sending fails, we should probably log it but not necessarily block the user.
            // The user-facing message is generic.
            hcisysq_log('Password reset link failed to send to ' . $phone_number . ': ' . $send_result->get_error_message(), 'error');
        }

        // Notify admin
        self::call_star_sender('sendToAdmin', "User dengan NIP $nip telah meminta reset password.");

        return ['success' => true, 'message' => 'Jika data valid, link reset password akan dikirimkan ke nomor WhatsApp Anda yang terdaftar.'];
    }

    public static function validate_token($token) {
        global $wpdb;

        if (empty($token)) {
            return new WP_Error('invalid_token', 'Token tidak boleh kosong.');
        }

        $token_hash = hash('sha256', $token);

        $reset_request = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table_name() . " WHERE token_hash = %s",
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

    public static function validate_new_password($password) {
        if (strlen($password) < 8) {
            return new WP_Error('password_too_short', 'Password minimal harus terdiri dari 8 karakter.');
        }

        if (!preg_match('/[A-Za-z]/', $password)) {
            return new WP_Error('password_missing_letter', 'Password harus mengandung huruf.');
        }

        if (!preg_match('/\d/', $password)) {
            return new WP_Error('password_missing_digit', 'Password harus mengandung angka.');
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return new WP_Error('password_missing_symbol', 'Password harus mengandung simbol (mis. !@#$%).');
        }

        return ['success' => true];
    }

    public static function complete_reset($token, $new_password) {
        global $wpdb;

        $validation = self::validate_token($token);
        if (is_wp_error($validation)) {
            return $validation;
        }

        $update = self::update_password_for_nip($validation['nip'], $new_password);
        if (is_wp_error($update)) {
            return $update;
        }

        $token_hash = hash('sha256', $token);
        $wpdb->update(
            self::get_table_name(),
            ['used_at' => current_time('mysql')],
            ['token_hash' => $token_hash],
            ['%s'],
            ['%s']
        );

        return ['success' => true];
    }

    public static function update_password_for_nip($nip, $new_password) {
        if (empty($nip)) {
            return new WP_Error('invalid_user', 'Pengguna tidak ditemukan.');
        }

        if (empty($new_password)) {
            return new WP_Error('password_empty', 'Password tidak boleh kosong.');
        }

        $password_requirements = self::validate_new_password($new_password);
        if (is_wp_error($password_requirements)) {
            return $password_requirements;
        }

        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        if (!$new_password_hash) {
            return new WP_Error('hash_failed', 'Gagal memproses password baru.');
        }

        $user_repo = self::get_user_repository();
        $success = $user_repo->updateByPrimary([
            'nip' => $nip,
            'password_hash' => $new_password_hash,
        ]);

        if (!$success) {
            return new WP_Error('update_failed', 'Gagal memperbarui password di sistem. Silakan coba lagi.');
        }

        return ['success' => true];
    }

    public static function create_table() {
        global $wpdb;
        $table_name = self::get_table_name();
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
