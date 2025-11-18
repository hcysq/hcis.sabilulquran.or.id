<?php
namespace HCISYSQ\Tests\Unit;

use HCISYSQ\PasswordResetManager;
use WP_Error;

class PasswordResetManagerTest extends \WP_UnitTestCase {
    public function test_validate_new_password_accepts_strong_password() {
        $result = PasswordResetManager::validate_new_password('Abcdef1!');
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }

    /**
     * @dataProvider invalid_password_provider
     */
    public function test_validate_new_password_rejects_invalid_passwords($password, $expected_code) {
        $result = PasswordResetManager::validate_new_password($password);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame($expected_code, $result->get_error_code());
    }

    public function invalid_password_provider() {
        return [
            'too short' => ['Ab1!', 'password_too_short'],
            'missing letter' => ['12345678!', 'password_missing_letter'],
            'missing digit' => ['Password!', 'password_missing_digit'],
            'missing symbol' => ['Password1', 'password_missing_symbol'],
        ];
    }
}
