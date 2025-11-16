<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

use HCISYSQ\Repositories\UserRepository;

/**
 * Google Sheets Real-Time Sync Hooks
 *
 * Immediate write to Google Sheet on WordPress CRUD actions
 * Batches 50 rows per request for performance
 *
 * @package HCISYSQ
 */
class GoogleSheetsSync {

  private static $batch_queue = [];
  const BATCH_SIZE = 50;

  /**
   * Initialize sync hooks
   */
  public static function init() {
    // User hooks (real-time immediate sync)
    add_action('user_register', [__CLASS__, 'on_user_created']);
    add_action('profile_update', [__CLASS__, 'on_user_updated']);
    add_action('delete_user', [__CLASS__, 'on_user_deleted']);

    // Batching hook for performance
    add_action('hcis_gs_batch_sync', [__CLASS__, 'processBatch']);
  }

  /**
   * Handle user creation - immediate sync to Google Sheet
   *
   * @param int $user_id User ID
   */
  public static function on_user_created($user_id) {
    try {
      if (!GoogleSheetSettings::is_configured()) {
        hcisysq_log('GoogleSheetsSync::on_user_created - Settings not configured', 'WARNING');
        return;
      }

      $api = new GoogleSheetsAPI();
      $creds = json_decode(get_option(GoogleSheetSettings::OPT_JSON_CREDS), true);
      
      if (!$api->authenticate($creds)) {
        hcisysq_log('GoogleSheetsSync::on_user_created - Authentication failed', 'ERROR');
        return;
      }

      $repo = new UserRepository($api, new SheetCache());
      
      if ($repo->create($user_id)) {
        hcisysq_log('GoogleSheetsSync::on_user_created - Synced user: ' . $user_id);
      } else {
        hcisysq_log('GoogleSheetsSync::on_user_created - Sync failed: ' . $user_id, 'WARNING');
      }
    } catch (\Exception $e) {
      hcisysq_log('GoogleSheetsSync::on_user_created - Exception: ' . $e->getMessage(), 'ERROR');
    }
  }

  /**
   * Handle user update - immediate sync to Google Sheet
   *
   * @param int $user_id User ID
   * @param object $old_userdata Old user data
   */
  public static function on_user_updated($user_id, $old_userdata = null) {
    try {
      if (!GoogleSheetSettings::is_configured()) {
        hcisysq_log('GoogleSheetsSync::on_user_updated - Settings not configured', 'WARNING');
        return;
      }

      $api = new GoogleSheetsAPI();
      $creds = json_decode(get_option(GoogleSheetSettings::OPT_JSON_CREDS), true);
      
      if (!$api->authenticate($creds)) {
        hcisysq_log('GoogleSheetsSync::on_user_updated - Authentication failed', 'ERROR');
        return;
      }

      $repo = new UserRepository($api, new SheetCache());
      
      if ($repo->update($user_id)) {
        hcisysq_log('GoogleSheetsSync::on_user_updated - Synced user: ' . $user_id);
      } else {
        hcisysq_log('GoogleSheetsSync::on_user_updated - Sync failed: ' . $user_id, 'WARNING');
      }
    } catch (\Exception $e) {
      hcisysq_log('GoogleSheetsSync::on_user_updated - Exception: ' . $e->getMessage(), 'ERROR');
    }
  }

  /**
   * Handle user deletion - immediate sync to Google Sheet
   *
   * @param int $user_id User ID
   */
  public static function on_user_deleted($user_id) {
    try {
      if (!GoogleSheetSettings::is_configured()) {
        hcisysq_log('GoogleSheetsSync::on_user_deleted - Settings not configured', 'WARNING');
        return;
      }

      $api = new GoogleSheetsAPI();
      $creds = json_decode(get_option(GoogleSheetSettings::OPT_JSON_CREDS), true);
      
      if (!$api->authenticate($creds)) {
        hcisysq_log('GoogleSheetsSync::on_user_deleted - Authentication failed', 'ERROR');
        return;
      }

      $repo = new UserRepository($api, new SheetCache());
      
      if ($repo->delete($user_id)) {
        hcisysq_log('GoogleSheetsSync::on_user_deleted - Synced user: ' . $user_id);
      } else {
        hcisysq_log('GoogleSheetsSync::on_user_deleted - Sync failed: ' . $user_id, 'WARNING');
      }
    } catch (\Exception $e) {
      hcisysq_log('GoogleSheetsSync::on_user_deleted - Exception: ' . $e->getMessage(), 'ERROR');
    }
  }

  /**
   * Process batch queue
   * Called by cron job to batch multiple operations
   */
  public static function processBatch() {
    if (empty(self::$batch_queue)) {
      return;
    }

    try {
      if (!GoogleSheetSettings::is_configured()) {
        return;
      }

      $api = new GoogleSheetsAPI();
      $creds = json_decode(get_option(GoogleSheetSettings::OPT_JSON_CREDS), true);
      
      if (!$api->authenticate($creds)) {
        hcisysq_log('GoogleSheetsSync::processBatch - Authentication failed', 'ERROR');
        return;
      }

      // Process in chunks
      $chunks = array_chunk(self::$batch_queue, self::BATCH_SIZE);
      
      foreach ($chunks as $chunk) {
        $api->batchUpdate([
          'Users!A:E' => $chunk
        ]);
      }

      hcisysq_log('GoogleSheetsSync::processBatch - Processed ' . count(self::$batch_queue) . ' operations');
      self::$batch_queue = [];

    } catch (\Exception $e) {
      hcisysq_log('GoogleSheetsSync::processBatch - Exception: ' . $e->getMessage(), 'ERROR');
    }
  }

  /**
   * Get sync status
   *
   * @return array Status info
   */
  public static function getStatus() {
    return [
      'configured' => GoogleSheetSettings::is_configured(),
      'queue_count' => count(self::$batch_queue),
      'last_sync' => get_option('hcis_gs_last_sync', 'Never'),
      'last_error' => get_option('hcis_gs_last_error', 'None')
    ];
  }
}