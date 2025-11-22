<?php
/**
 * Unit Tests for GoogleSheetSettings GID persistence
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
      'hcis_gid_users',
      'hcis_gid_profiles',
      'hcis_gid_payroll',
      'hcis_gid_keluarga',
      'hcis_gid_dokumen',
      'hcis_gid_pendidikan',
      'hcis_gid_pelatihan',
    ];

    foreach ($options as $option) {
      delete_option($option);
    }
  }

  public function test_missing_tab_gid_in_payload_keeps_existing_option(): void {
    update_option('hcis_gid_payroll', '303', false);

    $status = GoogleSheetSettings::save_settings(
      $this->valid_credentials,
      'spreadsheet-123',
      [
        'profiles' => '101',
        'users' => '202',
      ],
      []
    );

    $this->assertTrue($status['valid']);
    $this->assertSame('101', get_option('hcis_gid_profiles'));
    $this->assertSame('202', get_option('hcis_gid_users'));
    $this->assertSame('303', get_option('hcis_gid_payroll'), 'Existing payroll GID should not be cleared when missing from payload');
  }

  public function test_gid_persists_across_multiple_submissions(): void {
    GoogleSheetSettings::save_settings(
      $this->valid_credentials,
      'spreadsheet-123',
      [
        'profiles' => '101',
        'users' => '202',
        'dokumen' => '404',
      ],
      []
    );

    $this->assertSame('404', get_option('hcis_gid_dokumen'));

    $status = GoogleSheetSettings::save_settings(
      $this->valid_credentials,
      'spreadsheet-123',
      [
        'profiles' => '101',
        'users' => '202',
      ],
      []
    );

    $this->assertTrue($status['valid']);
    $this->assertSame('404', get_option('hcis_gid_dokumen'), 'GID should persist across submissions even when omitted later');
  }
}
