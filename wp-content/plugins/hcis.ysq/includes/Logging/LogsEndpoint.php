<?php
namespace HCISYSQ\Logging;

if (!defined('ABSPATH')) exit;

use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 * REST endpoint for exporting logs to external systems.
 */
class LogsEndpoint {

  public static function init(): void {
    add_action('rest_api_init', [__CLASS__, 'register_routes']);
  }

  public static function register_routes(): void {
    register_rest_route('hcisysq/v1', '/logs', [
      'methods' => WP_REST_Server::READABLE,
      'callback' => [__CLASS__, 'handle_get_logs'],
      'permission_callback' => [__CLASS__, 'permissions_check'],
      'args' => [
        'level' => ['sanitize_callback' => 'sanitize_text_field'],
        'component' => ['sanitize_callback' => 'sanitize_text_field'],
        'request_id' => ['sanitize_callback' => 'sanitize_text_field'],
        'search' => ['sanitize_callback' => 'sanitize_text_field'],
        'since' => ['sanitize_callback' => 'sanitize_text_field'],
        'until' => ['sanitize_callback' => 'sanitize_text_field'],
        'page' => ['sanitize_callback' => 'absint', 'default' => 1],
        'per_page' => ['sanitize_callback' => 'absint', 'default' => 50],
      ],
    ]);
  }

  public static function permissions_check(WP_REST_Request $request) {
    $token = defined('HCISYSQ_LOG_EXPORT_TOKEN') ? HCISYSQ_LOG_EXPORT_TOKEN : '';
    $provided = $request->get_header('x-hcisysq-token') ?: $request->get_param('token');

    if ($token && $provided && function_exists('hash_equals') && hash_equals($token, (string) $provided)) {
      return true;
    }

    if (function_exists('current_user_can') && current_user_can('manage_options')) {
      return true;
    }

    return new WP_Error('forbidden', __('Invalid log export token.', 'hcis-ysq'), ['status' => 403]);
  }

  public static function handle_get_logs(WP_REST_Request $request) {
    global $wpdb;

    if (!isset($wpdb)) {
      return rest_ensure_response(['data' => [], 'meta' => ['total' => 0, 'page' => 1]]);
    }

    $page = max(1, (int) $request->get_param('page') ?: 1);
    $per_page = min(100, max(1, (int) $request->get_param('per_page') ?: 50));

    $args = [
      'level' => $request->get_param('level'),
      'component' => $request->get_param('component'),
      'request_id' => $request->get_param('request_id'),
      'search' => $request->get_param('search'),
      'since' => $request->get_param('since'),
      'until' => $request->get_param('until'),
      'limit' => $per_page,
      'offset' => ($page - 1) * $per_page,
    ];

    $logs = self::query_logs($args);
    $count = self::count_logs($args);

    $data = array_map([__CLASS__, 'format_row'], $logs);

    return rest_ensure_response([
      'data' => $data,
      'meta' => [
        'total' => $count,
        'page' => $page,
        'pages' => $per_page ? ceil($count / $per_page) : 1,
      ],
    ]);
  }

  private static function query_logs(array $args): array {
    global $wpdb;

    $defaults = [
      'level' => '',
      'component' => '',
      'request_id' => '',
      'search' => '',
      'since' => '',
      'until' => '',
      'limit' => 50,
      'offset' => 0,
    ];
    $args = wp_parse_args($args, $defaults);

    $query = "SELECT * FROM {$wpdb->prefix}hcisysq_logs WHERE 1=1";
    $params = [];

    list($query, $params) = self::apply_filters($query, $params, $args);

    $query .= ' ORDER BY created_at DESC LIMIT %d OFFSET %d';
    $params[] = intval($args['limit']);
    $params[] = intval($args['offset']);

    return $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
  }

  private static function count_logs(array $args): int {
    global $wpdb;

    $defaults = [
      'level' => '',
      'component' => '',
      'request_id' => '',
      'search' => '',
      'since' => '',
      'until' => '',
    ];
    $args = wp_parse_args($args, $defaults);

    $query = "SELECT COUNT(*) FROM {$wpdb->prefix}hcisysq_logs WHERE 1=1";
    $params = [];

    list($query, $params) = self::apply_filters($query, $params, $args);

    return (int) $wpdb->get_var($wpdb->prepare($query, $params));
  }

  private static function apply_filters(string $query, array $params, array $args): array {
    global $wpdb;

    if (!empty($args['level'])) {
      $query .= ' AND level = %s';
      $params[] = $args['level'];
    }

    if (!empty($args['component'])) {
      $query .= ' AND component = %s';
      $params[] = $args['component'];
    }

    if (!empty($args['request_id'])) {
      $query .= ' AND request_id = %s';
      $params[] = $args['request_id'];
    }

    if (!empty($args['search'])) {
      $like = '%' . $wpdb->esc_like($args['search']) . '%';
      $query .= ' AND (message LIKE %s OR context LIKE %s OR extra LIKE %s)';
      $params[] = $like;
      $params[] = $like;
      $params[] = $like;
    }

    if (!empty($args['since'])) {
      $normalized = self::normalize_date($args['since']);
      if ($normalized) {
        $query .= ' AND created_at >= %s';
        $params[] = $normalized;
      }
    }

    if (!empty($args['until'])) {
      $normalized = self::normalize_date($args['until']);
      if ($normalized) {
        $query .= ' AND created_at <= %s';
        $params[] = $normalized;
      }
    }

    return [$query, $params];
  }

  private static function format_row(array $row): array {
    $row['context'] = self::decode_json($row['context'] ?? '');
    $row['extra'] = self::decode_json($row['extra'] ?? '');
    return $row;
  }

  private static function decode_json($value) {
    if (empty($value) || !is_string($value)) {
      return $value;
    }

    $decoded = json_decode($value, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
  }

  private static function normalize_date($value): string {
    $timestamp = strtotime($value);
    if (!$timestamp) {
      return '';
    }

    return date('Y-m-d H:i:s', $timestamp);
  }
}
