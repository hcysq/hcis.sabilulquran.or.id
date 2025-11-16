<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

/**
 * Admin Logs Viewer
 * 
 * Displays structured logs in WordPress admin dashboard
 * Allows filtering by level, user, and date range
 * 
 * @package HCISYSQ
 */
class AdminLogsViewer {

  /**
   * Initialize logs viewer
   */
  public static function init() {
    add_action('admin_menu', [__CLASS__, 'add_menu']);
    add_action('admin_post_hcisysq_clear_logs', [__CLASS__, 'handle_clear_logs']);
    add_action('admin_post_hcisysq_export_logs', [__CLASS__, 'handle_export_logs']);
  }

  /**
   * Add menu item for logs viewer
   */
  public static function add_menu() {
    add_submenu_page(
      'hcis-portal',
      __('Error Logs', 'hcis-ysq'),
      __('Error Logs', 'hcis-ysq'),
      'manage_options',
      'hcis-error-logs',
      [__CLASS__, 'render_page']
    );
  }

  /**
   * Render the logs viewer page
   */
  public static function render_page() {
    if (!current_user_can('manage_options')) {
      wp_die(__('Unauthorized access', 'hcis-ysq'));
    }

    // Get filter parameters
    $level = isset($_GET['level']) ? sanitize_text_field($_GET['level']) : '';
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : '';
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 25;
    $offset = ($paged - 1) * $per_page;

    // Fetch logs
    $logs = self::get_logs([
      'level' => $level,
      'user_id' => $user_id,
      'search' => $search,
      'limit' => $per_page,
      'offset' => $offset
    ]);

    $total = self::count_logs([
      'level' => $level,
      'user_id' => $user_id,
      'search' => $search
    ]);

    $total_pages = ceil($total / $per_page);

    ?>
    <div class="wrap hcis-logs-viewer">
      <h1><?php esc_html_e('HCIS Error Logs', 'hcis-ysq'); ?></h1>

      <div class="hcis-logs-toolbar">
        <form method="get" class="hcis-logs-filters">
          <input type="hidden" name="page" value="hcis-error-logs" />

          <!-- Level Filter -->
          <select name="level" class="hcis-filter-select">
            <option value=""><?php esc_html_e('All Levels', 'hcis-ysq'); ?></option>
            <option value="DEBUG" <?php selected($level, 'DEBUG'); ?>>Debug</option>
            <option value="INFO" <?php selected($level, 'INFO'); ?>>Info</option>
            <option value="WARNING" <?php selected($level, 'WARNING'); ?>>Warning</option>
            <option value="ERROR" <?php selected($level, 'ERROR'); ?>>Error</option>
            <option value="CRITICAL" <?php selected($level, 'CRITICAL'); ?>>Critical</option>
          </select>

          <!-- User Filter -->
          <select name="user_id" class="hcis-filter-select">
            <option value=""><?php esc_html_e('All Users', 'hcis-ysq'); ?></option>
            <option value="0" <?php selected($user_id, '0'); ?>><?php esc_html_e('System', 'hcis-ysq'); ?></option>
            <?php self::render_user_options($user_id); ?>
          </select>

          <!-- Search -->
          <input type="text" name="search" class="hcis-filter-input" 
                 placeholder="<?php esc_attr_e('Search message...', 'hcis-ysq'); ?>" 
                 value="<?php echo esc_attr($search); ?>" />

          <button type="submit" class="button button-primary">
            <?php esc_html_e('Filter', 'hcis-ysq'); ?>
          </button>
          <a href="<?php echo admin_url('admin.php?page=hcis-error-logs'); ?>" class="button">
            <?php esc_html_e('Reset', 'hcis-ysq'); ?>
          </a>
        </form>

        <div class="hcis-logs-actions">
          <form method="post" style="display:inline;">
            <?php wp_nonce_field('hcisysq_export_logs', 'export_nonce'); ?>
            <button type="submit" name="action" value="hcisysq_export_logs" class="button">
              <?php esc_html_e('Export CSV', 'hcis-ysq'); ?>
            </button>
          </form>

          <form method="post" onsubmit="return confirm('<?php esc_js(__('Clear all logs? This action cannot be undone.', 'hcis-ysq')); ?>')">
            <?php wp_nonce_field('hcisysq_clear_logs', 'clear_nonce'); ?>
            <button type="submit" name="action" value="hcisysq_clear_logs" class="button button-danger">
              <?php esc_html_e('Clear All Logs', 'hcis-ysq'); ?>
            </button>
          </form>
        </div>
      </div>

      <!-- Logs Table -->
      <table class="widefat hcis-logs-table">
        <thead>
          <tr>
            <th><?php esc_html_e('Time', 'hcis-ysq'); ?></th>
            <th><?php esc_html_e('Level', 'hcis-ysq'); ?></th>
            <th><?php esc_html_e('Message', 'hcis-ysq'); ?></th>
            <th><?php esc_html_e('User', 'hcis-ysq'); ?></th>
            <th><?php esc_html_e('IP Address', 'hcis-ysq'); ?></th>
            <th><?php esc_html_e('Details', 'hcis-ysq'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($logs)): ?>
            <?php foreach ($logs as $log): ?>
              <tr class="hcis-log-row hcis-log-<?php echo esc_attr(strtolower($log['level'])); ?>">
                <td class="hcis-log-time">
                  <?php echo esc_html(wp_date('Y-m-d H:i:s', strtotime($log['created_at']))); ?>
                </td>
                <td class="hcis-log-level">
                  <span class="hcis-badge hcis-badge-<?php echo esc_attr(strtolower($log['level'])); ?>">
                    <?php echo esc_html(ucfirst(strtolower($log['level']))); ?>
                  </span>
                </td>
                <td class="hcis-log-message">
                  <?php echo esc_html(substr($log['message'], 0, 100)); ?>
                  <?php if (strlen($log['message']) > 100): ?>
                    <span class="hcis-log-truncated">...</span>
                  <?php endif; ?>
                </td>
                <td class="hcis-log-user">
                  <?php
                    if (!empty($log['user_id'])) {
                      $user = get_user_by('id', $log['user_id']);
                      echo $user ? esc_html($user->user_login) : esc_html($log['user_id']);
                    } else {
                      echo esc_html_e('System', 'hcis-ysq');
                    }
                  ?>
                </td>
                <td class="hcis-log-ip">
                  <?php echo esc_html($log['ip_address'] ?? 'N/A'); ?>
                </td>
                <td class="hcis-log-details">
                  <button class="button button-small hcis-log-expand" data-log-id="<?php echo esc_attr($log['id']); ?>">
                    <?php esc_html_e('View', 'hcis-ysq'); ?>
                  </button>
                </td>
              </tr>
              
              <!-- Expandable details row -->
              <tr class="hcis-log-details-row" id="log-details-<?php echo esc_attr($log['id']); ?>" style="display:none;">
                <td colspan="6" class="hcis-log-details-content">
                  <div class="hcis-log-full-message">
                    <strong><?php esc_html_e('Full Message:', 'hcis-ysq'); ?></strong>
                    <pre><?php echo esc_html($log['message']); ?></pre>
                  </div>
                  
                  <?php if (!empty($log['context'])): ?>
                    <div class="hcis-log-context">
                      <strong><?php esc_html_e('Context:', 'hcis-ysq'); ?></strong>
                      <pre><?php echo esc_html($log['context']); ?></pre>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="hcis-no-logs">
                <?php esc_html_e('No logs found', 'hcis-ysq'); ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
          <div class="tablenav-pages">
            <?php
              $pagination = paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => __('&laquo; Previous'),
                'next_text' => __('Next &raquo;'),
                'total' => $total_pages,
                'current' => $paged,
                'type' => 'array'
              ]);

              if ($pagination) {
                echo implode(' ', $pagination);
              }
            ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Stats -->
      <div class="hcis-logs-stats">
        <div class="stat-box">
          <strong><?php esc_html_e('Total Logs:', 'hcis-ysq'); ?></strong>
          <?php echo intval($total); ?>
        </div>
        <div class="stat-box">
          <strong><?php esc_html_e('Page:', 'hcis-ysq'); ?></strong>
          <?php echo intval($paged) . ' / ' . intval($total_pages); ?>
        </div>
      </div>
    </div>

    <script>
    jQuery(function($) {
      // Expand/collapse log details
      $(document).on('click', '.hcis-log-expand', function(e) {
        e.preventDefault();
        var logId = $(this).data('log-id');
        var detailsRow = $('#log-details-' + logId);
        
        if (detailsRow.is(':visible')) {
          detailsRow.fadeOut();
          $(this).text('<?php esc_js(__('View', 'hcis-ysq')); ?>');
        } else {
          detailsRow.fadeIn();
          $(this).text('<?php esc_js(__('Hide', 'hcis-ysq')); ?>');
        }
      });
    });
    </script>

    <style>
    .hcis-logs-viewer {
      margin: 20px 0;
    }

    .hcis-logs-toolbar {
      background: #f5f5f5;
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 4px;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
    }

    .hcis-logs-filters {
      display: flex;
      gap: 10px;
      flex: 1;
      align-items: center;
    }

    .hcis-filter-select,
    .hcis-filter-input {
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 3px;
    }

    .hcis-logs-actions {
      display: flex;
      gap: 10px;
    }

    .hcis-logs-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    .hcis-logs-table th {
      background: #f1f1f1;
      font-weight: 600;
      padding: 10px;
      text-align: left;
      border-bottom: 2px solid #ddd;
    }

    .hcis-logs-table td {
      padding: 10px;
      border-bottom: 1px solid #ddd;
    }

    .hcis-log-row:hover {
      background: #f9f9f9;
    }

    .hcis-log-time {
      font-size: 12px;
      color: #666;
      white-space: nowrap;
    }

    .hcis-badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 3px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .hcis-badge-debug {
      background: #d3d3d3;
      color: #333;
    }

    .hcis-badge-info {
      background: #d1ecf1;
      color: #0c5460;
    }

    .hcis-badge-warning {
      background: #fff3cd;
      color: #856404;
    }

    .hcis-badge-error {
      background: #f8d7da;
      color: #721c24;
    }

    .hcis-badge-critical {
      background: #721c24;
      color: #fff;
    }

    .hcis-log-message {
      max-width: 400px;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .hcis-log-details-content {
      background: #f9f9f9;
      padding: 15px;
    }

    .hcis-log-full-message,
    .hcis-log-context {
      margin-bottom: 15px;
    }

    .hcis-log-full-message pre,
    .hcis-log-context pre {
      background: #fff;
      border: 1px solid #ddd;
      padding: 10px;
      border-radius: 3px;
      overflow-x: auto;
      font-size: 12px;
    }

    .hcis-no-logs {
      text-align: center;
      padding: 20px;
      color: #999;
      font-style: italic;
    }

    .hcis-logs-stats {
      margin-top: 20px;
      display: flex;
      gap: 20px;
    }

    .stat-box {
      background: #f5f5f5;
      padding: 10px 15px;
      border-radius: 3px;
      border-left: 4px solid #0073aa;
    }

    .button-danger {
      background-color: #dc3545;
      border-color: #dc3545;
      color: white;
    }

    .button-danger:hover {
      background-color: #c82333;
      border-color: #c82333;
    }
    </style>
    <?php
  }

  /**
   * Get logs from database
   */
  private static function get_logs($args = []) {
    global $wpdb;

    $defaults = [
      'level' => '',
      'user_id' => '',
      'search' => '',
      'limit' => 25,
      'offset' => 0
    ];

    $args = wp_parse_args($args, $defaults);

    $query = "SELECT * FROM {$wpdb->prefix}hcisysq_logs WHERE 1=1";
    $params = [];

    if (!empty($args['level'])) {
      $query .= " AND level = %s";
      $params[] = $args['level'];
    }

    if (!empty($args['user_id'])) {
      $query .= " AND user_id = %d";
      $params[] = intval($args['user_id']);
    }

    if (!empty($args['search'])) {
      $query .= " AND (message LIKE %s OR context LIKE %s)";
      $search = '%' . $wpdb->esc_like($args['search']) . '%';
      $params[] = $search;
      $params[] = $search;
    }

    $query .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $params[] = intval($args['limit']);
    $params[] = intval($args['offset']);

    if (!empty($params)) {
      return $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
    }

    return $wpdb->get_results($query, ARRAY_A);
  }

  /**
   * Count logs with filters
   */
  private static function count_logs($args = []) {
    global $wpdb;

    $defaults = [
      'level' => '',
      'user_id' => '',
      'search' => ''
    ];

    $args = wp_parse_args($args, $defaults);

    $query = "SELECT COUNT(*) FROM {$wpdb->prefix}hcisysq_logs WHERE 1=1";
    $params = [];

    if (!empty($args['level'])) {
      $query .= " AND level = %s";
      $params[] = $args['level'];
    }

    if (!empty($args['user_id'])) {
      $query .= " AND user_id = %d";
      $params[] = intval($args['user_id']);
    }

    if (!empty($args['search'])) {
      $query .= " AND (message LIKE %s OR context LIKE %s)";
      $search = '%' . $wpdb->esc_like($args['search']) . '%';
      $params[] = $search;
      $params[] = $search;
    }

    if (!empty($params)) {
      return intval($wpdb->get_var($wpdb->prepare($query, $params)));
    }

    return intval($wpdb->get_var($query));
  }

  /**
   * Render user options for dropdown
   */
  private static function render_user_options($selected = '') {
    $users = get_users([
      'number' => 100,
      'orderby' => 'display_name'
    ]);

    foreach ($users as $user) {
      echo sprintf(
        '<option value="%d" %s>%s</option>',
        intval($user->ID),
        selected($selected, $user->ID),
        esc_html($user->display_name . ' (' . $user->user_login . ')')
      );
    }
  }

  /**
   * Handle clear logs action
   */
  public static function handle_clear_logs() {
    check_admin_referer('hcisysq_clear_logs', 'clear_nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('Unauthorized access', 'hcis-ysq'));
    }

    global $wpdb;
    $deleted = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}hcisysq_logs");

    wp_redirect(admin_url('admin.php?page=hcis-error-logs&message=logs_cleared'));
    exit;
  }

  /**
   * Handle export logs as CSV
   */
  public static function handle_export_logs() {
    check_admin_referer('hcisysq_export_logs', 'export_nonce');

    if (!current_user_can('manage_options')) {
      wp_die(__('Unauthorized access', 'hcis-ysq'));
    }

    global $wpdb;

    // Get all logs
    $logs = $wpdb->get_results(
      "SELECT * FROM {$wpdb->prefix}hcisysq_logs ORDER BY created_at DESC",
      ARRAY_A
    );

    // Generate CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="hcis-logs-' . date('Y-m-d-His') . '.csv"');

    $output = fopen('php://output', 'w');

    // Header
    fputcsv($output, ['ID', 'Level', 'Message', 'Context', 'Created At', 'User ID', 'IP Address']);

    // Data
    foreach ($logs as $log) {
      fputcsv($output, [
        $log['id'],
        $log['level'],
        $log['message'],
        $log['context'],
        $log['created_at'],
        $log['user_id'],
        $log['ip_address']
      ]);
    }

    fclose($output);
    exit;
  }
}
