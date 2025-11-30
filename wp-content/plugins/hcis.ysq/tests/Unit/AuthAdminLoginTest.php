<?php
namespace HCISYSQ\Tests\Unit;

use HCISYSQ\Auth;
use HCISYSQ\Repositories\AdminRepository;

class AuthAdminLoginTest extends \WP_UnitTestCase {
    protected function tearDown(): void {
        parent::tearDown();
        AdminRepository::set_test_admins([]);
    }

    public function test_unknown_username_does_not_fall_back_to_primary_account(): void {
        AdminRepository::set_test_admins([
            [
                'username' => 'primary-admin',
                'display_name' => 'Primary Admin',
                'password' => 'primary-password',
            ],
        ]);

        $result = Auth::login_admin('missing-user', 'primary-password');

        $this->assertFalse($result['ok']);
        $this->assertSame(__('Akun administrator tidak ditemukan.', 'hcis-ysq'), $result['msg']);
    }
}
