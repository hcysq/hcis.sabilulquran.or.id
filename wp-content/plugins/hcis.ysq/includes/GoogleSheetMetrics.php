<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

/**
 * Google Sheets Metrics & Monitoring
 *
 * Track API quota usage, sync status, cache performance
 * Display metrics in admin dashboard widget
 *
 * @package HCISYSQ
 */
class GoogleSheetMetrics {

  const OPTION_PREFIX = 'hcis_gs_metrics_';

  /**
   * Initialize metrics dashboard widget
   */
  public static function init() {
    add_action('wp_dashboard_setup', [__CLASS__, 'add_dashboard_widget']);
    add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_styles']);
  }

  /**
   * Add dashboard widget
   */
  public static function add_dashboard_widget() {
    if (!current_user_can('manage_options')) {
      return;
    }

    wp_add_dashboard_widget(
      'hcis_google_sheets_metrics',
      __('Google Sheets Sync Status', 'hcis-ysq'),
      [__CLASS__, 'render_widget']
    );
  }

  /**
   * Render dashboard widget
   */
  public static function render_widget() {
    $metrics = self::getMetrics();
    $is_configured = GoogleSheetSettings::is_configured();
    $config_status = GoogleSheetSettings::get_status();
    ?>
    <div id="hcis-gs-metrics">
      <style>
        .hcis-metric-card { background: #f5f5f5; padding: 15px; margin-bottom: 15px; border-radius: 4px; }
        .hcis-metric-label { font-weight: bold; color: #0073aa; font-size: 13px; }
        .hcis-metric-value { font-size: 24px; font-weight: bold; color: #333; }
        .hcis-metric-secondary { font-size: 12px; color: #666; margin-top: 5px; }
        .status-ok { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }
        .progress-bar { width: 100%; height: 20px; background: #ddd; border-radius: 3px; overflow: hidden; }
        .progress-fill { height: 100%; background: #0073aa; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-size: 11px; }
      </style>

      <?php if (!$is_configured): ?>
        <div class="notice notice-warning" style="margin: 0 0 15px 0; padding: 10px;">
          <p><strong><?php esc_html_e('Google Sheets not configured', 'hcis-ysq'); ?></strong></p>
          <p><?php echo sprintf(
            __('Go to <a href="%s">Google Sheets Settings</a> to configure', 'hcis-ysq'),
            admin_url('admin.php?page=hcis-google-settings')
          ); ?></p>
          <?php if (!empty($config_status['message'])): ?>
            <p><?php echo esc_html($config_status['message']); ?></p>
          <?php endif; ?>
        </div>
      <?php else: ?>

      <?php if (!empty($config_status['message'])): ?>
        <?php $status_class = !empty($config_status['valid']) ? 'status-ok' : 'status-warning'; ?>
        <div class="hcis-metric-card">
          <div class="hcis-metric-label"><?php esc_html_e('Configuration Status', 'hcis-ysq'); ?></div>
          <div class="hcis-metric-secondary <?= esc_attr($status_class); ?>">
            <?php echo esc_html($config_status['message']); ?>
          </div>
          <?php if (!empty($config_status['last_checked'])): ?>
            <div class="hcis-metric-secondary">
              <?php
                printf(
                  esc_html__('Last checked: %s', 'hcis-ysq'),
                  esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $config_status['last_checked']))
                );
              ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Sync Status -->
      <div class="hcis-metric-card">
        <div class="hcis-metric-label"><?php esc_html_e('Last Sync', 'hcis-ysq'); ?></div>
        <div class="hcis-metric-value">
          <?php if (!empty($metrics['last_sync'])): ?>
            <span class="status-ok"></span> <?php echo esc_html($metrics['last_sync']); ?>
          <?php else: ?>
            <span class="status-warning"></span> <?php esc_html_e('Never', 'hcis-ysq'); ?>
          <?php endif; ?>
        </div>
        <?php if (!empty($metrics['last_error'])): ?>
          <div class="hcis-metric-secondary status-error">
            <?php esc_html_e('Error:', 'hcis-ysq'); ?> <?php echo esc_html($metrics['last_error']); ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Quota Usage -->
      <div class="hcis-metric-card">
        <div class="hcis-metric-label"><?php esc_html_e('API Quota', 'hcis-ysq'); ?></div>
        <div class="hcis-metric-value"><?php echo esc_html($metrics['quota_usage'] ?? 0); ?>%</div>
        <div class="progress-bar">
          <div class="progress-fill" style="width: <?php echo esc_attr($metrics['quota_usage'] ?? 0); ?>%;">
            <?php echo esc_html($metrics['quota_usage'] ?? 0); ?>%
          </div>
        </div>
        <div class="hcis-metric-secondary">
          <?php if (($metrics['quota_usage'] ?? 0) > 80): ?>
            <span class="status-warning"><?php esc_html_e(' High quota usage', 'hcis-ysq'); ?></span>
          <?php else: ?>
            <span class="status-ok"><?php esc_html_e(' Normal', 'hcis-ysq'); ?></span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Cache Performance -->
      <div class="hcis-metric-card">
        <div class="hcis-metric-label"><?php esc_html_e('Cache Hit Rate', 'hcis-ysq'); ?></div>
        <div class="hcis-metric-value"><?php echo esc_html($metrics['cache_hit_rate'] ?? 0); ?>%</div>
        <div class="hcis-metric-secondary">
          <?php echo sprintf(
            esc_html__('%d hits / %d misses', 'hcis-ysq'),
            $metrics['cache_hits'] ?? 0,
            $metrics['cache_misses'] ?? 0
          ); ?>
        </div>
      </div>

      <!-- Sync Statistics -->
      <div class="hcis-metric-card">
        <div class="hcis-metric-label"><?php esc_html_e('Sync Operations', 'hcis-ysq'); ?></div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
          <div>
            <div style="font-size: 18px; font-weight: bold; color: #28a745;">
              <?php echo esc_html($metrics['sync_success'] ?? 0); ?>
            </div>
            <div style="font-size: 12px; color: #666;"><?php esc_html_e('Successful', 'hcis-ysq'); ?></div>
          </div>
          <div>
            <div style="font-size: 18px; font-weight: bold; color: #dc3545;">
              <?php echo esc_html($metrics['sync_failed'] ?? 0); ?>
            </div>
            <div style="font-size: 12px; color: #666;"><?php esc_html_e('Failed', 'hcis-ysq'); ?></div>
          </div>
        </div>
      </div>

      <?php endif; ?>

      <p style="margin-top: 15px; font-size: 12px; color: #666;">
        <?php esc_html_e('Updated:', 'hcis-ysq'); ?> <?php echo esc_html(current_time('H:i:s')); ?>
      </p>
    </div>

    <script>
    // Auto-refresh metrics every 60 seconds
    setInterval(function() {
      location.reload();
    }, 60000);
    </script>
    <?php
  }

  /**
   * Get all metrics
   *
   * @return array Metrics data
   */
  public static function getMetrics() {
    $cache_metrics = get_option('hcis_gs_cache_metrics', []);
    $quota_metrics = get_option('hcis_gs_quota_metrics', []);

    return [
      'last_sync' => get_option('hcis_gs_last_sync', ''),
      'last_error' => get_option('hcis_gs_last_error', ''),
      'quota_usage' => $quota_metrics['usage_percent'] ?? 0,
      'cache_hit_rate' => $cache_metrics['hit_rate_percent'] ?? 0,
      'cache_hits' => $cache_metrics['hits'] ?? 0,
      'cache_misses' => $cache_metrics['misses'] ?? 0,
      'sync_success' => get_option('hcis_gs_sync_success', 0),
      'sync_failed' => get_option('hcis_gs_sync_failed', 0),
    ];
  }

  /**
   * Record sync success
   */
  public static function recordSuccess() {
    $count = (int)get_option('hcis_gs_sync_success', 0);
    update_option('hcis_gs_sync_success', $count + 1);
  }

  /**
   * Record sync failure
   */
  public static function recordFailure() {
    $count = (int)get_option('hcis_gs_sync_failed', 0);
    update_option('hcis_gs_sync_failed', $count + 1);
  }

  /**
   * Enqueue styles
   */
  public static function enqueue_styles() {
    wp_enqueue_style('hcis-gs-metrics', HCISYSQ_URL . 'assets/css/metrics.css');
  }
}