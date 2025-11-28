<?php
/**
 * Unit Tests for GoogleSheetSettings validation and migrations
 *
 * @package HCISYSQ
 * @group GoogleSheetSettings
 */

namespace HCISYSQ\Tests\Unit;

use HCISYSQ\GoogleSheetSettings;

class GoogleSheetSettingsTest extends \WP_UnitTestCase {
  private $valid_credentials = '{"type":"service_account","client_email":"service@example.com"}';

  protected function setUp(): void {
    parent::setUp();
    $this->resetOptions();
  }

  protected function tearDown(): void {
    $this->resetOptions();
    parent::tearDown();
  }

  private function resetOptions(): void {
    $options = [
      GoogleSheetSettings::OPT_JSON_CREDS,
      GoogleSheetSettings::OPT_SHEET_ID,
      GoogleSheetSettings::OPT_STATUS,
      GoogleSheetSettings::OPT_SETUP_KEYS,
      GoogleSheetSettings::OPT_TAB_METRICS,
    ];

    foreach (GoogleSheetSettings::get_tabs() as $slug => $_config) {
      $options[] = GoogleSheetSettings::OPT_TAB_COLUMN_ORDER_PREFIX . $slug;
    }

    $options = array_merge($options, [
      'hcis_gid_users',
      'hcis_gid_admins',
      'hcis_gid_profiles',
      'hcis_gid_payroll',
      'hcis_gid_keluarga',
      'hcis_gid_dokumen',
      'hcis_gid_pendidikan',
      'hcis_gid_pelatihan',
    ]);

    foreach ($options as $option) {
      delete_option($option);
    }
  }

  public function test_save_settings_stores_sheet_id_and_credentials(): void {
    $status = GoogleSheetSettings::save_settings(
      $this->valid_credentials,
      'spreadsheet-123'
    );

    $this->assertTrue($status['valid']);
    $this->assertSame('spreadsheet-123', get_option(GoogleSheetSettings::OPT_SHEET_ID));
    $this->assertSame($this->valid_credentials, get_option(GoogleSheetSettings::OPT_JSON_CREDS));
  }

  public function test_save_settings_stores_setup_key_overrides(): void {
    $overrides = [
      'user_nip' => ['header' => 'Employee ID'],
    ];

    $status = GoogleSheetSettings::save_settings(
      $this->valid_credentials,
      'spreadsheet-123',
      [],
      $overrides
    );

    $this->assertTrue($status['valid']);
    $this->assertSame($overrides, get_option(GoogleSheetSettings::OPT_SETUP_KEYS));

    $config = GoogleSheetSettings::get_setup_key_config();
    $this->assertSame('Employee ID', $config['user_nip']['header']);
  }

  public function test_save_settings_requires_sheet_id(): void {
    $status = GoogleSheetSettings::save_settings(
      $this->valid_credentials,
      ''
    );

    $this->assertFalse($status['valid']);
    $this->assertSame('Sheet ID wajib diisi.', $status['message']);
  }

  public function test_legacy_gid_options_are_removed(): void {
    update_option('hcis_gid_users', '202', false);
    update_option('hcis_gs_tab_col_order_users', 'Email, Nama', false);
    update_option(GoogleSheetSettings::OPT_SETUP_KEYS, ['custom' => true], false);

    $status = GoogleSheetSettings::save_settings(
      $this->valid_credentials,
      'spreadsheet-123'
    );

    $this->assertTrue($status['valid']);
    $this->assertFalse(get_option('hcis_gid_users'));
    $this->assertSame('Email, Nama', get_option('hcis_gs_tab_col_order_users'));
    $this->assertSame(['custom' => true], get_option(GoogleSheetSettings::OPT_SETUP_KEYS));
  }

  public function test_password_hash_definitions_are_removed(): void {
    GoogleSheetSettings::save_settings(
      $this->valid_credentials,
      'spreadsheet-123'
    );

    $config = GoogleSheetSettings::get_setup_key_config();

    $this->assertArrayNotHasKey('user_password_hash', $config);
    $this->assertArrayNotHasKey('admin_password_hash', $config);
  }

  public function test_tab_column_order_overrides_are_respected(): void {
    update_option('hcis_gs_tab_col_order_users', ['Full Name', 'Employee ID'], false);

    $overrides = [
      'user_nip' => ['header' => 'Employee ID'],
      'user_name' => ['header' => 'Full Name'],
    ];

    GoogleSheetSettings::save_settings(
      $this->valid_credentials,
      'spreadsheet-123',
      [],
      $overrides
    );

    $config = GoogleSheetSettings::get_setup_key_config();
    $this->assertSame(2, $config['user_nip']['order']);
    $this->assertSame(1, $config['user_name']['order']);

    $headers = GoogleSheetSettings::get_tab_column_map('users');
    $this->assertSame(['Full Name', 'Employee ID'], array_slice($headers, 0, 2));
  }

  public function test_password_hash_headers_are_normalized_for_users_and_admins(): void {
    update_option('hcis_gs_tab_col_order_users', ['Password Hash', 'NIP'], false);
    update_option('hcis_gs_tab_col_order_admins', ['Username', 'Password Hash'], false);

    $userHeaders = GoogleSheetSettings::get_tab_column_map('users');
    $this->assertSame('Password', $userHeaders[0]);
    $this->assertNotContains('Password Hash', $userHeaders);

    $adminHeaders = GoogleSheetSettings::get_tab_column_map('admins');
    $this->assertSame(['Username', 'Password', 'Display Name', 'WhatsApp'], $adminHeaders);
    $this->assertNotContains('Password Hash', $adminHeaders);
  }

  public function test_save_settings_persists_gids_and_resolver_prefers_saved_option(): void {
    $status = GoogleSheetSettings::save_settings(
      $this->valid_credentials,
      'spreadsheet-123',
      ['users' => '1111']
    );

    $this->assertTrue($status['valid']);
    $this->assertSame('1111', get_option(GoogleSheetSettings::OPT_TAB_GID_PREFIX . 'users'));
    $this->assertSame('1111', GoogleSheetSettings::get_gid('users'));
  }
}
