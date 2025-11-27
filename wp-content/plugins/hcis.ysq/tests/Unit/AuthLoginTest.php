<?php
namespace HCISYSQ\Tests\Unit;

use HCISYSQ\Auth;
use HCISYSQ\Repositories\UserRepository;

class AuthLoginTest extends \WP_UnitTestCase {
    protected function tearDown(): void {
        parent::tearDown();
        UserRepository::set_test_users([]);
    }

    public function test_wordpress_hash_allows_login() {
        $password = 'secret123';
        $wpHash = wp_hash_password($password);

        UserRepository::set_test_users([
            'WP001' => [
                'row_index' => 0,
                'nip' => 'WP001',
                'nama' => 'WP User',
                'password_hash' => $wpHash,
                'phone' => '08123456789',
                'nik' => '1122334455',
            ],
        ]);

        $result = Auth::login('WP001', $password);

        $this->assertTrue($result['ok']);
        $this->assertSame('WP001', $result['user']['nip']);
        $this->assertArrayHasKey('force_password_reset', $result);
        $this->assertFalse($result['force_password_reset']);
    }

    public function test_login_falls_back_to_nik_only_when_password_missing() {
        $nik = '9876543210';

        UserRepository::set_test_users([
            'NIK001' => [
                'row_index' => 0,
                'nip' => 'NIK001',
                'nama' => 'Fallback User',
                'password_hash' => '',
                'phone' => '08111111111',
                'nik' => $nik,
            ],
        ]);

        $result = Auth::login('NIK001', $nik);

        $this->assertTrue($result['ok']);
        $this->assertSame('NIK001', $result['user']['nip']);
        $this->assertTrue($result['force_password_reset']);
    }

    public function test_login_rejects_nik_when_password_exists() {
        $nik = '9876543210';
        $passwordHash = wp_hash_password('different-password');

        UserRepository::set_test_users([
            'NIK002' => [
                'row_index' => 0,
                'nip' => 'NIK002',
                'nama' => 'Password Exists',
                'password_hash' => $passwordHash,
                'phone' => '08111111111',
                'nik' => $nik,
            ],
        ]);

        $result = Auth::login('NIK002', $nik);

        $this->assertFalse($result['ok']);
        $this->assertFalse($result['missing_password']);
        $this->assertTrue($result['nik_login_disabled']);
    }

    public function test_login_rejects_missing_password_hash(): void {
        UserRepository::set_test_users([
            'EMPTY1' => [
                'row_index' => 1,
                'nip' => 'EMPTY1',
                'nama' => 'No Password',
                'password_hash' => '',
                'nik' => '',
            ],
        ]);

        $result = Auth::login('EMPTY1', '');

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('missing_password', $result);
        $this->assertTrue($result['missing_password']);
    }
}
