<?php
namespace HCISYSQ\Tests\Integration;

use HCISYSQ\PasswordResetManager;
use WP_Error;

class PasswordResetManagerIntegrationTest extends \WP_UnitTestCase {
    protected function setUp(): void {
        parent::setUp();
        PasswordResetManager::create_table();
    }

    public function test_complete_reset_surfaces_error_for_invalid_password() {
        global $wpdb;

        $token = bin2hex(random_bytes(16));
        $token_hash = hash('sha256', $token);
        $table = $wpdb->prefix . 'hcisysq_password_resets';

        $wpdb->insert(
            $table,
            [
                'nip' => '9876543210',
                'token_hash' => $token_hash,
                'expires_at' => gmdate('Y-m-d H:i:s', time() + HOUR_IN_SECONDS),
                'used_at' => null,
            ]
        );

        $result = PasswordResetManager::complete_reset($token, 'Password!');
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('password_missing_digit', $result->get_error_code());

        $wpdb->delete($table, ['token_hash' => $token_hash]);
    }
}
