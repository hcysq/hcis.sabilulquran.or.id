<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class SheetCache {

  const CACHE_TTL = 300;
  const PREFIX = 'hcis_gs_cache_';

  private static $metrics = [
    'hits' => 0,
    'misses' => 0,
    'deletes' => 0
  ];

  public static function remember($key, $callback, $ttl = self::CACHE_TTL) {
    $cache_key = self::PREFIX . md5($key);
    $cached = get_transient($cache_key);

    if ($cached !== false) {
      self::$metrics['hits']++;
      self::recordMetrics();
      return $cached;
    }

    self::$metrics['misses']++;

    try {
      $data = call_user_func($callback);
      
      if ($data !== null && $data !== false) {
        set_transient($cache_key, $data, $ttl);
      }

      self::recordMetrics();
      return $data;
    } catch (\Exception $e) {
      hcisysq_log('Cache remember failed: ' . $e->getMessage(), 'ERROR');
      self::recordMetrics();
      return null;
    }
  }

  public static function put($key, $value, $ttl = self::CACHE_TTL) {
    $cache_key = self::PREFIX . md5($key);

    if (set_transient($cache_key, $value, $ttl)) {
      hcisysq_log('Cached: ' . $key);
      return true;
    }

    return false;
  }

  public static function get($key, $default = null) {
    $cache_key = self::PREFIX . md5($key);
    $cached = get_transient($cache_key);

    if ($cached !== false) {
      self::$metrics['hits']++;
      return $cached;
    }

    self::$metrics['misses']++;
    return $default;
  }

  public static function has($key) {
    $cache_key = self::PREFIX . md5($key);
    return get_transient($cache_key) !== false;
  }

  public static function forget($key) {
    $cache_key = self::PREFIX . md5($key);
    self::$metrics['deletes']++;
    self::recordMetrics();
    return delete_transient($cache_key);
  }

  public static function flush() {
    global $wpdb;

    $result = $wpdb->query(
      "DELETE FROM {$wpdb->options}
       WHERE option_name LIKE 'transient_" . self::PREFIX . "%'"
    );

    self::$metrics['deletes'] += max($result, 0);
    self::recordMetrics();
    hcisysq_log('Flushed all Google Sheets cache');

    return $result !== false;
  }

  public static function getMetrics() {
    $total = self::$metrics['hits'] + self::$metrics['misses'];
    $hit_rate = $total > 0 ? round((self::$metrics['hits'] / $total) * 100, 2) : 0;

    return [
      'hits' => self::$metrics['hits'],
      'misses' => self::$metrics['misses'],
      'deletes' => self::$metrics['deletes'],
      'total' => $total,
      'hit_rate_percent' => $hit_rate
    ];
  }

  private static function recordMetrics() {
    update_option('hcis_gs_cache_metrics', [
      'hits' => self::$metrics['hits'],
      'misses' => self::$metrics['misses'],
      'deletes' => self::$metrics['deletes'],
      'timestamp' => current_time('mysql')
    ]);
  }

  public static function resetMetrics() {
    self::$metrics = [
      'hits' => 0,
      'misses' => 0,
      'deletes' => 0
    ];
    delete_option('hcis_gs_cache_metrics');
  }
}