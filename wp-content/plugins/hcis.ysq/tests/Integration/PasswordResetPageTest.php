<?php
namespace HCISYSQ\Tests\Integration;

use HCISYSQ\PasswordReset;
use HCISYSQ\PasswordResetManager;
use HCISYSQ\Shortcodes;

class PasswordResetPageTest extends \WP_UnitTestCase {
    protected function setUp(): void {
        parent::setUp();
        PasswordResetManager::create_table();
        global $wpdb;
        $table = $wpdb->prefix . 'hcisysq_password_resets';
        $wpdb->query("TRUNCATE TABLE $table");
    }

    public function test_needs_reset_user_sees_reset_page_instead_of_404() {
        PasswordReset::create_pages();
        Shortcodes::init();

        $token = bin2hex(random_bytes(8));
        global $wpdb;
        $table = $wpdb->prefix . 'hcisysq_password_resets';
        $wpdb->insert($table, [
            'nip' => 'TESTRESET',
            'token_hash' => hash('sha256', $token),
            'expires_at' => gmdate('Y-m-d H:i:s', time() + 1800),
        ]);

        $_GET['token'] = $token;
        $resetPath = '/' . trim(HCISYSQ_RESET_SLUG, '/') . '/';
        $this->go_to(home_url($resetPath . '?token=' . $token));

        global $wp_query;
        $this->assertFalse($wp_query->is_404(), 'Reset page should not return 404');

        $page = get_page_by_path(HCISYSQ_RESET_SLUG);
        $this->assertNotNull($page, 'Reset page must be created.');

        $content = do_shortcode($page->post_content);
        $this->assertStringContainsString('hcisysq-reset-password-form', $content);
    }
}
