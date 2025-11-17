<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class SessionMaintenance {
  const OPTION_LAST_CLEANUP = 'hcisysq_last_session_cleanup';

  public static function init() {
    add_action('admin_menu', [self::class, 'register_admin_page']);
    add_action('admin_post_hcisysq_prune_sessions', [self::class, 'handle_admin_prune']);

    if (defined('WP_CLI') && WP_CLI && class_exists('\WP_CLI')) {
      \WP_CLI::add_command('hcisysq-sessions', __NAMESPACE__ . '\\SessionMaintenanceCommand');
    }
  }

  public static function register_admin_page() {
    add_submenu_page(
      'tools.php',
      __('HCIS Sessions', 'hcis-ysq'),
      __('HCIS Sessions', 'hcis-ysq'),
      'manage_options',
      'hcisysq-sessions',
      [self::class, 'render_admin_page']
    );
  }

  public static function render_admin_page() {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to access this page.', 'hcis-ysq'));
    }

    $last_cleanup = get_option(self::OPTION_LAST_CLEANUP);
    $cleanup_url = wp_nonce_url(admin_url('admin-post.php?action=hcisysq_prune_sessions'), 'hcisysq_prune_sessions');
    $pruned = isset($_GET['hcisysq_pruned']) ? intval($_GET['hcisysq_pruned']) : null;
    ?>
    <div class="wrap">
      <h1><?php esc_html_e('HCIS Session Maintenance', 'hcis-ysq'); ?></h1>
      <?php if ($pruned !== null) : ?>
        <div class="notice notice-success is-dismissible">
          <p><?php printf(esc_html__('%d expired sessions were pruned.', 'hcis-ysq'), $pruned); ?></p>
        </div>
      <?php endif; ?>
      <p><?php esc_html_e('Use this tool to manually prune expired sessions from the database and transient stores.', 'hcis-ysq'); ?></p>
      <p>
        <strong><?php esc_html_e('Last manual cleanup:', 'hcis-ysq'); ?></strong>
        <?php echo $last_cleanup ? esc_html($last_cleanup) : esc_html__('Never', 'hcis-ysq'); ?>
      </p>
      <p>
        <a href="<?php echo esc_url($cleanup_url); ?>" class="button button-primary">
          <?php esc_html_e('Prune Expired Sessions Now', 'hcis-ysq'); ?>
        </a>
      </p>
    </div>
    <?php
  }

  public static function handle_admin_prune() {
    if (!current_user_can('manage_options')) {
      wp_die(__('You do not have permission to perform this action.', 'hcis-ysq'));
    }

    check_admin_referer('hcisysq_prune_sessions');

    $deleted = SessionHandler::cleanup();
    update_option(self::OPTION_LAST_CLEANUP, current_time('mysql'));

    $redirect = add_query_arg(
      [
        'page' => 'hcisysq-sessions',
        'hcisysq_pruned' => $deleted,
      ],
      admin_url('tools.php')
    );

    wp_safe_redirect($redirect);
    exit;
  }
}

if (defined('WP_CLI') && WP_CLI && class_exists('\WP_CLI_Command')) {
  class SessionMaintenanceCommand extends \WP_CLI_Command {
    /**
     * Prune expired sessions from all stores.
     *
     * ## EXAMPLES
     *
     *     wp hcisysq-sessions prune
     *
     * @when after_wp_load
     */
    public function prune($args, $assoc_args) {
      $deleted = SessionHandler::cleanup();
      \WP_CLI::success(sprintf(__('%d expired sessions removed.', 'hcis-ysq'), $deleted));
    }
  }
}
