<?php
namespace HCISYSQ\Tests\Integration;

use HCISYSQ\PasswordResetManager;

class PasswordResetManagerTest extends \WP_UnitTestCase {
  private $original_prefix;

  protected function setUp(): void {
    parent::setUp();
    global $wpdb;
    $this->original_prefix = $wpdb->prefix;
  }

  protected function tearDown(): void {
    global $wpdb;
    $table = $wpdb->prefix . 'hcisysq_password_resets';
    $wpdb->query("DROP TABLE IF EXISTS $table");
    $wpdb->prefix = $this->original_prefix;
    PasswordResetManager::reset_testing_overrides();
    FakeStarSender::reset();
    parent::tearDown();
  }

  public function test_reset_request_uses_custom_table_prefix(): void {
    global $wpdb;

    $wpdb->prefix = 'cstm_';
    $table = $wpdb->prefix . 'hcisysq_password_resets';
    $wpdb->query("DROP TABLE IF EXISTS $table");
    PasswordResetManager::create_table();

    $user_repo = new FakePasswordResetUserRepository([
      '98765' => [
        'nip' => '98765',
        'nik' => '321654',
        'phone' => '08123456789',
      ],
    ]);

    PasswordResetManager::set_user_repository($user_repo);
    PasswordResetManager::set_star_sender_class(FakeStarSender::class);

    $result = PasswordResetManager::create_reset_request('98765', '321654');

    $this->assertIsArray($result);
    $this->assertTrue($result['success']);

    $stored = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE nip = %s", '98765'));
    $this->assertNotNull($stored, 'Reset token should be stored in the custom prefixed table.');

    $this->assertSame(1, FakeStarSender::$send_calls, 'User notification should be sent.');
    $this->assertSame(1, FakeStarSender::$admin_calls, 'Admin notification should be sent.');
  }
}

class FakePasswordResetUserRepository {
  private $users;

  public function __construct(array $users = []) {
    $this->users = $users;
  }

  public function find($nip) {
    return $this->users[$nip] ?? [];
  }

  public function updateByPrimary(array $data) {
    $nip = $data['nip'] ?? null;
    if ($nip && isset($this->users[$nip])) {
      $this->users[$nip] = array_merge($this->users[$nip], $data);
    }
    return true;
  }
}

class FakeStarSender {
  public static $send_calls = 0;
  public static $admin_calls = 0;

  public static function send($to, $message) {
    self::$send_calls++;
    return true;
  }

  public static function sendToAdmin($message) {
    self::$admin_calls++;
    return true;
  }

  public static function reset(): void {
    self::$send_calls = 0;
    self::$admin_calls = 0;
  }
}
