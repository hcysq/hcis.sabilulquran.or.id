<?php
namespace HCISYSQ\Tests\Integration;

use HCISYSQ\Installer;

class InstallerActivationTest extends \WP_UnitTestCase {
  protected function setUp(): void {
    parent::setUp();
    global $wpdb;
    $table = $wpdb->prefix . 'hcisysq_logs';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) {
      $wpdb->query("DELETE FROM $table");
    }
  }

  public function test_activation_logs_schema_upgrade() {
    delete_option('hcisysq_schema_version');
    Installer::activate();

    global $wpdb;
    $table = $wpdb->prefix . 'hcisysq_logs';
    $like = '%' . $wpdb->esc_like('Installer::activate() - schema upgraded to version ' . Installer::SCHEMA_VERSION) . '%';
    $log = $wpdb->get_row($wpdb->prepare("SELECT message FROM $table WHERE message LIKE %s ORDER BY id DESC LIMIT 1", $like));

    $this->assertNotNull($log, 'Activation should log schema upgrade message.');
    $this->assertStringContainsString((string)Installer::SCHEMA_VERSION, $log->message);
  }
}
