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
    $this->assertFalse(get_option('hcis_gs_tab_col_order_users'));
    $this->assertFalse(get_option(GoogleSheetSettings::OPT_SETUP_KEYS));
  }
}
