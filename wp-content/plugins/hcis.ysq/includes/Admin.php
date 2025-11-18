<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Admin {

  public static function init() {
    add_action('admin_notices', [__CLASS__, 'check_required_settings']);
    add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    add_action('wp_ajax_hcis_test_connection', [__CLASS__, 'ajax_test_connection']);
    add_action('wp_ajax_hcis_clear_cache', [__CLASS__, 'ajax_clear_cache']);
  }

  public static function enqueue_assets($hook) {
    // Only load on our specific admin pages.
    if ($hook !== 'toplevel_page_hcis-admin-portal' && $hook !== 'hcis-admin-portal_page_hcis-admin-portal-settings') {
        return;
    }

    wp_enqueue_script(
        'hcis-admin-settings',
        HCISYSQ_URL . 'assets/js/admin-settings.js',
        ['jquery'],
        HCISYSQ_VER,
        true
    );

    wp_localize_script(
        'hcis-admin-settings',
        'hcis_admin',
        [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('hcis-admin-ajax-nonce'),
        ]
    );
  }

  public static function check_required_settings() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $api_key = get_option('hcisysq_wa_token');
    $admin_phone = get_option('hcisysq_admin_wa');

    if (empty($api_key) || empty($admin_phone)) {
        $settings_url = admin_url('options-general.php?page=hcisysq-settings');
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong>Peringatan HCIS.YSQ:</strong> Pengaturan WhatsApp (API Key dan Nomor Admin) belum lengkap. Fitur "Lupa Password" tidak akan berfungsi dengan benar.
                <a href="<?= esc_url($settings_url); ?>">Lengkapi pengaturan sekarang</a>.
            </p>
        </div>
        <?php
    }
  }


  public static function menu() {
    // 1. Keep the old settings page, but hook it to 'add_options_page' for consistency.
    add_options_page(
      'HCIS.YSQ Settings',
      'HCIS.YSQ Settings',
      'manage_options',
      'hcisysq-settings',
      [__CLASS__, 'render_portal_settings_page'] // Redirect old to new
    );

    // 2. Add the new main admin portal page.
    add_menu_page(
      'Portal HCIS',
      'Portal HCIS',
      'manage_hcis_portal',
      'hcis-admin-portal',
      [__CLASS__, 'render_admin_portal_page'],
      'dashicons-groups',
      25
    );

    add_submenu_page(
      'hcis-admin-portal',
      __('Portal HCIS Settings', 'hcis-ysq'),
      __('Settings', 'hcis-ysq'),
      'manage_hcis_portal',
      'hcis-admin-portal-settings',
      [__CLASS__, 'render_portal_settings_page']
    );
  }

  /**
   * Render the view for the new "Portal HCIS" admin page.
   */
  public static function render_admin_portal_page() {
    if (!current_user_can('manage_hcis_portal') && !current_user_can('manage_options')) return;

    self::render_portal_settings_page();
  }

  /**
   * Render the Portal HCIS settings page.
   */
  public static function render_portal_settings_page() {
    if (!current_user_can('manage_hcis_portal') && !current_user_can('manage_options')) return;

    $notice = '';
    $default_sheet_id = GoogleSheetSettings::DEFAULT_SHEET_ID;
    $gid_keys = GoogleSheetSettings::get_tab_labels();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      check_admin_referer('hcis_portal_settings');

      $credentials_json = isset($_POST['hcis_portal_credentials_json'])
        ? trim(wp_unslash($_POST['hcis_portal_credentials_json']))
        : '';
      $sheet_id = sanitize_text_field($_POST['hcis_portal_sheet_id'] ?? '');

      $gids = [];
      foreach ($gid_keys as $key => $label) {
        $gids[$key] = sanitize_text_field($_POST["hcis_portal_gid_{$key}"] ?? '');
      }

      // Retrieve column orders from POST
      $column_orders = [];
      foreach ($gid_keys as $key => $label) {
        $column_orders[$key] = sanitize_text_field($_POST["hcis_portal_column_order_{$key}"] ?? '');
      }

      $status_data = GoogleSheetSettings::save_settings($credentials_json, $sheet_id, $gids, $column_orders);

      if (!empty($status_data['valid'])) {
        $notice = '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'hcis-ysq') . '</p></div>';
      } else {
        $message = esc_html($status_data['message'] ?? __('Credential JSON tidak valid.', 'hcis-ysq'));
        $notice = '<div class="notice notice-error"><p>' . esc_html__('Settings saved but credential is invalid:', 'hcis-ysq') . ' ' . $message . '</p></div>';
      }
    }

    $credentials_value = GoogleSheetSettings::get_credentials_json();
    $sheet_id_value = GoogleSheetSettings::get_sheet_id() ?: $default_sheet_id;
    $gids_value = GoogleSheetSettings::get_gid_map();
    $status_block = GoogleSheetSettings::get_status();

    ?>
    <div class="wrap">
      <h1><?= esc_html__('Portal HCIS Settings', 'hcis-ysq'); ?></h1>
      <?php if ($notice): ?>
        <?= wp_kses_post($notice); ?>
      <?php endif; ?>

      <?php if (!empty($status_block['message'])): ?>
        <?php
          $status_class = !empty($status_block['valid']) ? 'notice-success' : 'notice-warning';
          $checked_at = !empty($status_block['last_checked'])
            ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), $status_block['last_checked'])
            : '';
        ?>
        <div class="notice <?= esc_attr($status_class); ?>">
          <p><?= esc_html($status_block['message']); ?></p>
          <?php if ($checked_at): ?>
            <p><em><?= esc_html(sprintf(__('Terakhir dicek: %s', 'hcis-ysq'), $checked_at)); ?></em></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <form method="post">
        <?php wp_nonce_field('hcis_portal_settings'); ?>
        <table class="form-table">
          <tr>
            <th scope="row"><label for="hcis_portal_credentials_json"><?php esc_html_e('Kredensial JSON', 'hcis-ysq'); ?></label></th>
            <td>
              <textarea id="hcis_portal_credentials_json" name="hcis_portal_credentials_json" class="large-text code" rows="12" placeholder="{\n  &quot;type&quot;: &quot;service_account&quot;,\n  ...\n}"><?php echo esc_textarea($credentials_value); ?></textarea>
              <p class="description"><?php esc_html_e('Tempel seluruh isi file kredensial .json di sini.', 'hcis-ysq'); ?></p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="hcis_portal_sheet_id"><?php esc_html_e('Sheet ID', 'hcis-ysq'); ?></label></th>
            <td>
              <input type="text" id="hcis_portal_sheet_id" name="hcis_portal_sheet_id" class="regular-text" style="width: 480px" value="<?php echo esc_attr($sheet_id_value ?: $default_sheet_id); ?>" placeholder="<?php echo esc_attr($default_sheet_id); ?>">
              <p class="description"><?php esc_html_e('ID Google Sheet utama.', 'hcis-ysq'); ?></p>
            </td>
          </tr>
          <?php foreach ($gid_keys as $key => $label): ?>
            <tr>
              <th scope="row"><label for="hcis_portal_gid_<?php echo esc_attr($key); ?>"><?php echo esc_html(sprintf(__('GID %s', 'hcis-ysq'), $label)); ?></label></th>
              <td>
                <input type="text" id="hcis_portal_gid_<?php echo esc_attr($key); ?>" name="hcis_portal_gid_<?php echo esc_attr($key); ?>" class="regular-text" style="width: 220px" value="<?php echo esc_attr($gids_value[$key] ?? ''); ?>" placeholder="0">
                <p class="description"><?php esc_html_e('Masukkan GID tab terkait di Google Sheet.', 'hcis-ysq'); ?></p>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php // New section for Column Order ?>
          <?php
            $column_orders_value = [];
            foreach ($gid_keys as $key => $label) {
                $column_orders_value[$key] = GoogleSheetSettings::get_tab_column_order($key);
                // Convert array back to comma-separated string for display in textarea
                $column_orders_value[$key] = implode(', ', $column_orders_value[$key]);
            }
          ?>
          <?php foreach ($gid_keys as $key => $label): ?>
            <tr>
              <th scope="row"><label for="hcis_portal_column_order_<?php echo esc_attr($key); ?>"><?php echo esc_html(sprintf(__('Column Order for %s', 'hcis-ysq'), $label)); ?></label></th>
              <td>
                <textarea id="hcis_portal_column_order_<?php echo esc_attr($key); ?>" name="hcis_portal_column_order_<?php echo esc_attr($key); ?>" class="large-text code" rows="3" placeholder="NIP, Nama, Password Hash, No HP, Email, NIK"><?php echo esc_textarea($column_orders_value[$key] ?? ''); ?></textarea>
                <p class="description"><?php esc_html_e('Masukkan urutan header kolom yang dipisahkan koma untuk tab ini (misalnya: NIP, Nama, Password Hash).', 'hcis-ysq'); ?></p>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
        <p class="submit">
          <input type="text" id="hcis-test-nip" name="hcis_test_nip" class="regular-text" placeholder="<?php esc_attr_e('NIP to Test (optional)', 'hcis-ysq'); ?>" style="width: 200px; margin-right: 10px;">
          <button type="submit" class="button button-primary"><?php esc_html_e('Simpan', 'hcis-ysq'); ?></button>
          <button type="button" id="hcis-test-connection" class="button"><?php esc_html_e('Test Connection', 'hcis-ysq'); ?></button>
          <button type="button" id="hcis-clear-cache" class="button"><?php esc_html_e('Clear Cache', 'hcis-ysq'); ?></button>
        </p>
      </form>
      <div id="hcis-admin-notice" class="notice" style="display: none; margin-top: 1rem;"></div>
    </div>
    <?php
  }

  public static function ajax_test_connection() {
    check_ajax_referer('hcis-admin-ajax-nonce');

    if (!current_user_can('manage_hcis_portal')) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    $nip = sanitize_text_field($_POST['nip'] ?? '');
    $response_data = [];

    try {
        // Use the modern, standardized service
        $service = new GoogleSheetsService();
        $title = $service->test_connection();
        $response_data['connection_status'] = 'Successfully connected to spreadsheet: ' . $title;

        if (!empty($nip)) {
            $repo = new \HCISYSQ\Repositories\UserRepository();
            $user_data = $repo->find($nip);
            if ($user_data) {
                $response_data['user_data_for_nip'] = $user_data;
            } else {
                $response_data['user_data_for_nip'] = 'No user found for NIP: ' . $nip;
            }
        }
        wp_send_json_success($response_data);
    } catch (\Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()], 500);
    }
  }

  public static function ajax_clear_cache() {
    check_ajax_referer('hcis-admin-ajax-nonce');

    if (!current_user_can('manage_hcis_portal')) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    try {
        SheetCache::flush();
        wp_send_json_success(['message' => 'Cache cleared successfully.']);
    } catch (\Exception $e) {
        wp_send_json_error(['message' => 'An error occurred while clearing the cache: ' . $e->getMessage()], 500);
    }
  }
}