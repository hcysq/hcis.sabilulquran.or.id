<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

use HCISYSQ\Repositories\UserRepository;
use HCISYSQ\Repositories\AbstractSheetRepository;
use HCISYSQ\SheetCache;

class GoogleSheetsSync {

  private static $batch_queue = [];
  private static $prototype_repos = [];
  const BATCH_SIZE = 50;

  public static function init() {
    add_action('user_register', [__CLASS__, 'on_user_created']);
    add_action('profile_update', [__CLASS__, 'on_user_updated'], 10, 2);
    add_action('profile_update', [__CLASS__, 'on_profile_data_changed'], 20, 2);
    add_action('delete_user', [__CLASS__, 'on_user_deleted']);
    add_action('save_post_' . Publikasi::POST_TYPE, [__CLASS__, 'on_publikasi_saved'], 10, 3);
    add_action('hcisysq/training/submitted', [__CLASS__, 'on_training_submitted'], 10, 2);

    add_action('hcis_gs_batch_sync', [__CLASS__, 'processBatch']);
    add_action('hcisysq_google_sheets_sync_tab', [__CLASS__, 'run_cron_sync'], 10, 1);
  }

  public static function on_user_created($user_id) {
    self::sync_single_user($user_id, 'create');
  }

  public static function on_user_updated($user_id, $old_userdata = null) {
    self::sync_single_user($user_id, 'update');
  }

  public static function on_user_deleted($user_id) {
    self::sync_single_user($user_id, 'delete');
  }

  public static function on_profile_data_changed($user_id) {
    if (!GoogleSheetSettings::is_configured()) {
      return;
    }
    $payload = self::buildProfilePayload($user_id);
    if (empty($payload)) {
      return;
    }
    
    $repo = self::makeRepository('profiles');
    if (!$repo) {
      return;
    }
    $existing = $repo->find($payload['nip']);
    if (empty($existing)) {
      $repo->append($payload);
    } else {
      $repo->updateByPrimary($payload);
    }
  }

  public static function on_publikasi_saved($post_id, $post, $updated) {
    if (wp_is_post_revision($post_id)) {
      return;
    }
    if ($post->post_type !== Publikasi::POST_TYPE) {
      return;
    }
    $row = [
      'nip' => '',
      'kategori' => implode(', ', wp_get_post_terms($post_id, Publikasi::TAXONOMY, ['fields' => 'names'])),
      'judul' => get_the_title($post_id),
      'nomor' => 'PUB-' . $post_id,
      'tanggal_terbit' => get_post_time('Y-m-d H:i:s', true, $post, true),
      'tanggal_kadaluarsa' => get_post_meta($post_id, Publikasi::META_ARCHIVED_AT, true),
      'tautan' => get_post_meta($post_id, Publikasi::META_LINK_URL, true) ?: get_permalink($post_id),
    ];
    self::queueSheetAppend('dokumen', $row);
  }

  public static function on_training_submitted($payload, $user = null) {
    $row = [
      'nip' => $payload['nip'] ?? ($user->nip ?? ''),
      'nama_pelatihan' => $payload['nama_pelatihan'] ?? ($payload['entry']['nama_pelatihan'] ?? ''),
      'penyelenggara' => $payload['kategori'] ?? ($payload['entry']['kategori'] ?? ''),
      'tanggal_mulai' => $payload['tahun_penyelenggaraan'] ?? ($payload['entry']['tahun_penyelenggaraan'] ?? ''),
      'tanggal_selesai' => '',
      'status' => 'Diajukan',
      'sertifikat' => $payload['link_sertifikat'] ?? '',
    ];
    self::queueSheetAppend('pelatihan', $row);
  }

  public static function processBatch() {
    if (empty(self::$batch_queue) || !GoogleSheetSettings::is_configured()) {
      return;
    }

    try {
      $api = self::makeApi();
      if (!$api) {
        return;
      }

      $grouped = [];
      foreach (self::$batch_queue as $op) {
        if ($op['type'] === 'append') {
          $grouped[$op['tab']][] = $op['row'];
        }
      }

      foreach ($grouped as $tab => $rows) {
        foreach (array_chunk($rows, self::BATCH_SIZE) as $chunk) {
          $range = GoogleSheetSettings::get_tab_range($tab);
          $api->appendRows($range, $chunk);
        }
      }

      hcisysq_log('GoogleSheetsSync::processBatch - Processed ' . count(self::$batch_queue) . ' operations');
      self::$batch_queue = [];
    } catch (\Exception $e) {
      hcisysq_log('GoogleSheetsSync::processBatch - Exception: ' . $e->getMessage(), 'ERROR');
    }
  }

  public static function run_cron_sync($tab = null) {
    if (!GoogleSheetSettings::is_configured()) {
      hcisysq_log('GoogleSheetsSync::run_cron_sync - Settings not configured', 'WARNING');
      return;
    }

    try {
      $api = self::makeApi();
      if (!$api) {
        return;
      }
      $tabSlugs = array_keys(GoogleSheetSettings::get_tabs());
      if (empty($tabSlugs)) {
        return;
      }

      if ($tab === null) {
        $repositories = self::buildRepositories($api);
        foreach ($repositories as $slug => $repo) {
          self::syncRepository($slug, $repo);
        }
        update_option('hcis_gs_last_sync', current_time('mysql'));
        GoogleSheetMetrics::recordSuccess();
        return;
      }

      if (!in_array($tab, $tabSlugs, true)) {
        hcisysq_log('GoogleSheetsSync::run_cron_sync - Unknown tab: ' . $tab, 'WARNING');
        return;
      }

      $repo = self::makeRepository($tab, $api);
      if (!$repo) {
        return;
      }

      self::syncRepository($tab, $repo);

      $nextTab = self::nextTabSlug($tabSlugs, $tab);
      if ($nextTab) {
        wp_schedule_single_event(time(), 'hcisysq_google_sheets_sync_tab', [$nextTab]);
      } else {
        update_option('hcis_gs_last_sync', current_time('mysql'));
      }
      GoogleSheetMetrics::recordSuccess();
    } catch (\Exception $e) {
      GoogleSheetMetrics::recordFailure();
      update_option('hcis_gs_last_error', $e->getMessage());
      hcisysq_log('GoogleSheetsSync::run_cron_sync - Exception: ' . $e->getMessage(), 'ERROR');
    }
  }

  public static function getStatus() {
    return [
      'configured' => GoogleSheetSettings::is_configured(),
      'queue_count' => count(self::$batch_queue),
      'last_sync' => get_option('hcis_gs_last_sync', 'Never'),
      'last_error' => get_option('hcis_gs_last_error', 'None')
    ];
  }

  protected static function sync_single_user($user_id, $action) {
    if (!GoogleSheetSettings::is_configured()) {
      hcisysq_log('GoogleSheetsSync::sync_single_user - Settings not configured', 'WARNING');
      return;
    }

    try {
      $repo = new UserRepository(new SheetCache());
      $result = false;

      switch ($action) {
        case 'create':
          $result = $repo->create($user_id);
          break;
        case 'update':
          $result = $repo->update($user_id);
          break;
        case 'delete':
          $result = $repo->delete($user_id);
          break;
      }

      if ($result) {
        hcisysq_log('GoogleSheetsSync::sync_single_user - Synced user ' . $action . ': ' . $user_id);
      } else {
        hcisysq_log('GoogleSheetsSync::sync_single_user - Sync failed for action ' . $action . ' user: ' . $user_id, 'WARNING');
      }
    } catch (\Exception $e) {
      hcisysq_log('GoogleSheetsSync::sync_single_user - Exception: ' . $e->getMessage(), 'ERROR');
    }
  }

  protected static function buildProfilePayload($user_id) {
    $user = get_userdata($user_id);
    if (!$user) {
      return [];
    }
    $nip = get_user_meta($user_id, 'nip', true);
    if (!$nip) {
      return [];
    }
    return [
      'nip' => $nip,
      'nama' => $user->display_name,
      'unit' => get_user_meta($user_id, 'unit', true) ?: '',
      'jabatan' => get_user_meta($user_id, 'jabatan', true) ?: '',
      'tempat_lahir' => get_user_meta($user_id, 'tempat_lahir', true) ?: '',
      'tanggal_lahir' => get_user_meta($user_id, 'tanggal_lahir', true) ?: '',
      'alamat_ktp' => get_user_meta($user_id, 'alamat_ktp', true) ?: '',
      'desa' => get_user_meta($user_id, 'desa', true) ?: '',
      'kecamatan' => get_user_meta($user_id, 'kecamatan', true) ?: '',
      'kota' => get_user_meta($user_id, 'kota', true) ?: '',
      'kode_pos' => get_user_meta($user_id, 'kode_pos', true) ?: '',
      'email' => $user->user_email,
      'hp' => get_user_meta($user_id, 'phone', true) ?: '',
      'tmt' => get_user_meta($user_id, 'tmt', true) ?: '',
    ];
  }

  protected static function queueSheetAppend(string $tab, array $data): void {
    if (!GoogleSheetSettings::is_configured()) {
      return;
    }
    $prototype = self::getPrototypeRepository($tab);
    if (!$prototype) {
      return;
    }
    self::$batch_queue[] = [
      'tab' => $tab,
      'type' => 'append',
      'row' => $prototype->toSheetRow($data),
    ];
    self::ensureBatchScheduled();
  }

  protected static function ensureBatchScheduled(): void {
    if (!wp_next_scheduled('hcis_gs_batch_sync')) {
      wp_schedule_single_event(time() + MINUTE_IN_SECONDS, 'hcis_gs_batch_sync');
    }
  }

  protected static function getPrototypeRepository(string $tab): ?AbstractSheetRepository {
    if (isset(self::$prototype_repos[$tab])) {
      return self::$prototype_repos[$tab];
    }
    $class = GoogleSheetSettings::repository_class_for($tab);
    if (!$class || !class_exists($class)) {
      return null;
    }
    self::$prototype_repos[$tab] = new $class(new SheetCache());
    return self::$prototype_repos[$tab];
  }

  protected static function makeApi(): ?GoogleSheetsAPI {
    try {
        return GoogleSheetsAPI::getInstance();
    } catch (\Exception $e) {
        hcisysq_log('GoogleSheetsSync::makeApi - Failed to get API instance: ' . $e->getMessage(), 'ERROR');
        return null;
    }
  }

  protected static function makeRepository(string $tab): ?AbstractSheetRepository {
    $class = GoogleSheetSettings::repository_class_for($tab);
    if (!$class || !class_exists($class)) {
      return null;
    }
    return new $class(new SheetCache());
  }

  protected static function buildRepositories(GoogleSheetsAPI $api): array {
    $repos = [];
    foreach (array_keys(GoogleSheetSettings::get_tabs()) as $tab) {
      $repo = self::makeRepository($tab, $api);
      if ($repo) {
        $repos[$tab] = $repo;
      }
    }
    return $repos;
  }

  protected static function syncRepository(string $slug, AbstractSheetRepository $repo): void {
    $rows = $repo->all();
    $hash = md5(wp_json_encode($rows));
    if ($hash === GoogleSheetSettings::get_tab_hash($slug)) {
      return;
    }
    $applied = $repo->syncToWordPress($rows);
    $applied_stats = is_array($applied) ? $applied : [
      'synced' => (int) $applied,
      'failed' => 0,
    ];
    GoogleSheetSettings::set_tab_hash($slug, $hash);
    GoogleSheetSettings::record_tab_metrics($slug, [
      'rows' => count($rows),
      'applied' => $applied_stats['synced'] ?? 0,
      'failed' => $applied_stats['failed'] ?? 0,
      'hash' => $hash,
    ]);
  }

  protected static function nextTabSlug(array $tabs, string $current): ?string {
    $index = array_search($current, $tabs, true);
    if ($index === false) {
      return null;
    }
    return $tabs[$index + 1] ?? null;
  }
}
