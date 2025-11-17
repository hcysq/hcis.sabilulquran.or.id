<?php
namespace HCISYSQ\Tests\Unit;

use HCISYSQ\Security;
use WP_Error;

class SecurityRateLimiterTest extends \WP_UnitTestCase {
  protected $original_settings = [];

  protected function setUp(): void {
    parent::setUp();
    $this->purge_rate_limit_transients();
    $this->original_settings = Security::get_settings();
    Security::save_settings([
      'rate_limit' => [
        'window'   => 60,
        'per_ip'   => 3,
        'per_user' => 2,
      ],
      'captcha' => [
        'provider'      => '',
        'site_key'      => '',
        'secret_key'    => '',
        'enabled_forms' => [
          'login'        => false,
          'registration' => false,
          'training'     => false,
        ],
      ],
    ]);
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
  }

  protected function tearDown(): void {
    Security::save_settings($this->original_settings);
    $this->purge_rate_limit_transients();
    parent::tearDown();
  }

  private function purge_rate_limit_transients(): void {
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_hcisysq_rl_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_hcisysq_rl_%'");
  }

  public function test_rate_limit_blocks_after_threshold() {
    for ($i = 0; $i < 3; $i++) {
      $result = Security::enforce('ajax_login');
      $this->assertTrue($result);
    }

    $blocked = Security::enforce('ajax_login');
    $this->assertInstanceOf(WP_Error::class, $blocked);
    $this->assertSame('hcisysq_rate_limited', $blocked->get_error_code());
    $this->assertArrayHasKey('retry_after', (array) $blocked->get_error_data());
  }

  public function test_per_user_limit_isolated_from_ip_limit() {
    Security::save_settings([
      'rate_limit' => [
        'window'   => 60,
        'per_ip'   => 20,
        'per_user' => 1,
      ],
      'captcha' => [
        'provider'      => '',
        'site_key'      => '',
        'secret_key'    => '',
        'enabled_forms' => [
          'login'        => false,
          'registration' => false,
          'training'     => false,
        ],
      ],
    ]);

    $user_id = $this->factory->user->create();
    wp_set_current_user($user_id);

    $first = Security::enforce('profile_update');
    $this->assertTrue($first);

    $second = Security::enforce('profile_update');
    $this->assertInstanceOf(WP_Error::class, $second);
    $this->assertSame('hcisysq_rate_limited', $second->get_error_code());

    wp_set_current_user(0);
    $reset = Security::enforce('profile_update');
    $this->assertTrue($reset, 'Guest requests should still be allowed when per-user limit triggers.');
  }

  public function test_rate_limit_bucket_expires_after_window() {
    Security::save_settings([
      'rate_limit' => [
        'window'   => 1,
        'per_ip'   => 1,
        'per_user' => 0,
      ],
      'captcha' => [
        'provider'      => '',
        'site_key'      => '',
        'secret_key'    => '',
        'enabled_forms' => [
          'login'        => false,
          'registration' => false,
          'training'     => false,
        ],
      ],
    ]);

    $allowed = Security::enforce('rest_endpoint');
    $this->assertTrue($allowed);

    $blocked = Security::enforce('rest_endpoint');
    $this->assertInstanceOf(WP_Error::class, $blocked);

    sleep(2);

    $after_window = Security::enforce('rest_endpoint');
    $this->assertTrue($after_window);
  }
}
