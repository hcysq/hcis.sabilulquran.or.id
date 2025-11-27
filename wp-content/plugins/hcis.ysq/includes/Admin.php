<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Admin {

  public static function init() {
    add_action('admin_notices', [__CLASS__, 'check_required_settings']);
    add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    add_action('wp_ajax_hcis_test_connection', [__CLASS__, 'ajax_test_connection']);
    add_action('wp_ajax_hcis_test_wa_connection', [__CLASS__, 'ajax_test_wa_connection']);
    add_action('wp_ajax_hcis_clear_cache', [__CLASS__, 'ajax_clear_cache']);
    add_action('wp_ajax_hcis_setup_sheets', [__CLASS__, 'ajax_setup_sheets']);
  }

  public static function enqueue_assets($hook) {
    // Only load on our specific admin pages.
    $allowed_hooks = [
        'toplevel_page_hcis-admin-portal',
        'hcis-admin-portal_page_hcis-admin-portal',
        'hcis-admin-portal_page_hcis-admin-portal-settings',
        'settings_page_hcisysq-settings',
    ];

    if (!in_array($hook, $allowed_hooks, true)) {
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
            'wa_test'  => [
                'target_number' => '',
                'success_text'  => __('Pesan tes WhatsApp berhasil dikirim ke %s.', 'hcis-ysq'),
                'error_text'    => __('Gagal mengirim pesan tes WhatsApp. Silakan coba lagi.', 'hcis-ysq'),
            ],
        ]
    );
  }

  public static function check_required_settings() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $api_key = get_option('hcisysq_wa_token');

    if (empty($api_key)) {
        $settings_url = admin_url('options-general.php?page=hcisysq-settings');
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong>Peringatan HCIS.YSQ:</strong> Pengaturan WhatsApp (API Key) belum lengkap. Fitur "Lupa Password" tidak akan berfungsi dengan benar.
                <a href="<?= esc_url($settings_url); ?>">Isi token</a> dan pastikan tab <em>Admin</em> pada Google Sheet sudah berisi kontak admin.
            </p>
        </div>
        <?php
    }
  }

  private static function should_force_fresh_users_lookup(string $context): bool {
    $env = getenv('HCISYSQ_FORCE_FRESH_USERS');
    $default = $env === false ? true : $env !== '0';

    return (bool) apply_filters('hcisysq_force_fresh_users_lookup', $default, $context);
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

    $wa_token_value = get_option('hcisysq_wa_token', '');
    $wa_token_override = Config::describe_override('wa_token');
    $adminContacts = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      check_admin_referer('hcis_portal_settings');

      $credentials_json = isset($_POST['hcis_portal_credentials_json'])
        ? trim(wp_unslash($_POST['hcis_portal_credentials_json']))
        : '';
      $sheet_id = sanitize_text_field($_POST['hcis_portal_sheet_id'] ?? '');
      $wa_token_input = sanitize_text_field(wp_unslash($_POST['hcis_portal_wa_token'] ?? ''));

      $status_data = GoogleSheetSettings::save_settings($credentials_json, $sheet_id);

      update_option('hcisysq_wa_token', $wa_token_input);
      $wa_token_value = $wa_token_input;

      if (!empty($status_data['valid'])) {
        $notice = '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'hcis-ysq') . '</p></div>';
      } else {
        $message = esc_html($status_data['message'] ?? __('Credential JSON tidak valid.', 'hcis-ysq'));
        $notice = '<div class="notice notice-error"><p>' . esc_html__('Settings saved but credential is invalid:', 'hcis-ysq') . ' ' . $message . '</p></div>';
      }
    }

    $credentials_value = GoogleSheetSettings::get_credentials_json();
    $sheet_id_value = GoogleSheetSettings::get_sheet_id() ?: $default_sheet_id;
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
          <tr>
            <th scope="row"><label for="hcis_portal_wa_token"><?php esc_html_e('StarSender API Key', 'hcis-ysq'); ?></label></th>
            <td>
              <input type="text" id="hcis_portal_wa_token" name="hcis_portal_wa_token" class="regular-text" style="width: 480px" value="<?php echo esc_attr($wa_token_value); ?>" placeholder="STARSENDER_API_KEY">
              <p class="description"><?php esc_html_e('Digunakan untuk mengirim pesan WhatsApp melalui StarSender.', 'hcis-ysq'); ?></p>
              <?php if ($wa_token_override): ?>
                <p class="description"><em><?php echo wp_kses_post($wa_token_override); ?></em></p>
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <th scope="row"><?php esc_html_e('Status Kredensial Admin', 'hcis-ysq'); ?></th>
            <td>
              <p><?php esc_html_e('Kredensial admin kini diambil dari tab “Admin” pada Google Sheet. Perubahan user/nomor WhatsApp dilakukan langsung di sheet.', 'hcis-ysq'); ?></p>
              <p class="description"><?php esc_html_e('Pastikan tab Admin memiliki baris admin yang lengkap (username, password hash, nomor WhatsApp) agar login & notifikasi berjalan.', 'hcis-ysq'); ?></p>
            </td>
          </tr>
          </table>
        <p class="submit">
          <input type="text" id="hcis-test-nip" name="hcis_test_nip" class="regular-text" placeholder="<?php esc_attr_e('NIP to Test (optional)', 'hcis-ysq'); ?>" style="width: 200px; margin-right: 10px;">
          <button type="submit" class="button button-primary"><?php esc_html_e('Simpan', 'hcis-ysq'); ?></button>
          <button type="button" id="hcis-test-connection" class="button"><?php esc_html_e('Test Connection', 'hcis-ysq'); ?></button>
          <button type="button" id="hcis-clear-cache" class="button"><?php esc_html_e('Clear Cache', 'hcis-ysq'); ?></button>
          <button type="button" id="hcis-setup-sheets" class="button button-secondary"><?php esc_html_e('Setup Database', 'hcis-ysq'); ?></button>
          <button type="button" id="hcis-test-wa-connection" class="button"><?php esc_html_e('Test WA Connection', 'hcis-ysq'); ?></button>
          <label for="hcis-rewrite-headers" style="margin-left: 12px; vertical-align: middle;">
            <input type="checkbox" id="hcis-rewrite-headers">
            <?php esc_html_e('Tulis ulang header tab yang sudah ada', 'hcis-ysq'); ?>
          </label>
        </p>
      </form>
      <div id="hcis-admin-notice" class="notice" style="display: none; margin-top: 1rem;"></div>
    </div>
    <?php
  }

  public static function ajax_test_connection() {
    check_ajax_referer('hcis-admin-ajax-nonce');

    // Accept either the custom portal capability or the default manage_options so the
    // legacy settings page (options-general.php?page=hcisysq-settings) keeps working
    // for administrators who never received the new role.
    if (!current_user_can('manage_hcis_portal') && !current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    global $wpdb;

    $nip = sanitize_text_field($_POST['nip'] ?? '');
    $bypassCacheRequested = filter_var($_POST['bypass_cache'] ?? false, FILTER_VALIDATE_BOOLEAN);

    $bypassCache = self::should_force_fresh_users_lookup('admin_test_connection') || $bypassCacheRequested;
    $bypassCache = (bool) apply_filters('hcisysq_admin_test_connection_bypass_cache', $bypassCache, $nip);

    if ($bypassCache) {
        SheetCache::flush();
    }

    $google_status = [
        'success' => false,
        'message' => '',
    ];

    $user_header_check = [];

    try {
        $service = new GoogleSheetsService();
        $title = $service->test_connection();
        $google_status['success'] = true;
        $google_status['message'] = 'Successfully connected to spreadsheet: ' . $title;

        $user_header_check = [
            'tab' => GoogleSheetSettings::get_tab_name('users'),
            'range' => '',
            'expected' => [],
            'actual' => [],
            'missing' => [],
            'mismatched' => [],
        ];

        try {
            $userRepo = new \HCISYSQ\Repositories\UserRepository();
            $user_header_check['expected'] = $userRepo->getExpectedHeaders();

            $headerRange = sprintf('%s!1:1', $user_header_check['tab']);
            $user_header_check['range'] = $headerRange;

            $headerRows = $service->get_sheet_data($headerRange) ?? [];
            $actualHeaders = isset($headerRows[0]) ? array_map('trim', $headerRows[0]) : [];
            $user_header_check['actual'] = $actualHeaders;

            foreach ($user_header_check['expected'] as $index => $expectedHeader) {
                $actualHeader = $actualHeaders[$index] ?? '';

                if ($actualHeader === '') {
                    $user_header_check['missing'][] = [
                        'expected' => $expectedHeader,
                        'position' => $index + 1,
                    ];
                    continue;
                }

                if (strcasecmp($expectedHeader, $actualHeader) !== 0) {
                    $user_header_check['mismatched'][] = [
                        'expected' => $expectedHeader,
                        'actual' => $actualHeader,
                        'position' => $index + 1,
                    ];
                }
            }
        } catch (\Exception $e) {
            $user_header_check['error'] = $e->getMessage();
        }
    } catch (\Exception $e) {
        $google_status['message'] = $e->getMessage();
    }

    $table = $wpdb->prefix . 'hcisysq_users';
    $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));

    $database_status = [
        'success' => false,
        'message' => '',
    ];

    if ($table_exists !== $table) {
        $database_status['message'] = sprintf(__('Tabel pengguna tidak ditemukan: %s', 'hcis-ysq'), $table);
    } else {
        $sample_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} LIMIT %d", 1), ARRAY_A);

        if (!empty($wpdb->last_error)) {
            $database_status['message'] = sprintf(__('Gagal membaca tabel pengguna: %s', 'hcis-ysq'), $wpdb->last_error);
        } else {
            $database_status['success'] = true;
            $database_status['message'] = sprintf(__('Tabel pengguna tersedia: %s', 'hcis-ysq'), $table);

            if (!empty($sample_row)) {
                $allowed_user_keys = ['nip', 'nama', 'nik', 'no_hp', 'email'];
                $sample_user = [];

                foreach ($allowed_user_keys as $key) {
                    if (!array_key_exists($key, $sample_row)) {
                        continue;
                    }

                    $value = $sample_row[$key];
                    if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                        $sample_user[$key] = sanitize_text_field((string) $value);
                    }
                }

                if (!empty($sample_user)) {
                    $database_status['sample_user'] = $sample_user;
                }
            } else {
                $database_status['message'] .= ' - ' . __('Tidak ada baris contoh ditemukan.', 'hcis-ysq');
            }
        }
    }

    $response_data = [
        'connection_status' => $google_status['message'],
        'google_sheets' => $google_status,
        'database' => $database_status,
    ];

    if (!empty($user_header_check)) {
        $response_data['user_sheet_headers'] = $user_header_check;
    }

    if (!empty($nip)) {
        $repo = new \HCISYSQ\Repositories\UserRepository();
        $user_data = $repo->find($nip, $bypassCache);

        if (!empty($user_data)) {
            $allowed_keys = ['nip', 'nama', 'nik', 'no_hp', 'jabatan', 'unit'];
            $sanitized_user_data = [];

            foreach ($allowed_keys as $key) {
                $value = null;

                if (is_array($user_data) && array_key_exists($key, $user_data)) {
                    $value = $user_data[$key];
                } elseif (is_object($user_data) && property_exists($user_data, $key)) {
                    $value = $user_data->$key;
                } elseif ($key === 'no_hp') {
                    $alternate = is_array($user_data)
                        ? ($user_data['phone'] ?? null)
                        : (is_object($user_data) ? ($user_data->phone ?? null) : null);
                    $value = $alternate;
                }

                if ($value === null) {
                    continue;
                }

                if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                    $sanitized_user_data[$key] = sanitize_text_field((string) $value);
                }
            }

            if (!empty($sanitized_user_data)) {
                $source_key = '';
                if (is_array($user_data)) {
                    $source_key = $user_data['_source'] ?? $user_data['source'] ?? '';
                } elseif (is_object($user_data)) {
                    $source_key = $user_data->_source ?? ($user_data->source ?? '');
                }

                $source_label = $source_key === 'database'
                    ? __('Database lokal', 'hcis-ysq')
                    : __('Google Sheet', 'hcis-ysq');

                $response_data['user_data_for_nip'] = $sanitized_user_data;
                $response_data['user_data_source'] = $source_label;
            } else {
                $response_data['user_data_for_nip'] = 'No public user data available for NIP: ' . $nip;
            }
        } else {
            $response_data['user_data_for_nip'] = 'No user found for NIP: ' . $nip;
        }
    }

    $overall_success = $google_status['success'] && $database_status['success'];

    if ($overall_success) {
        wp_send_json_success($response_data);
    }

    $error_messages = [];
    if (!$google_status['success'] && !empty($google_status['message'])) {
        $error_messages[] = sprintf(__('Google Sheets error: %s', 'hcis-ysq'), $google_status['message']);
    }
    if (!$database_status['success'] && !empty($database_status['message'])) {
        $error_messages[] = sprintf(__('Database error: %s', 'hcis-ysq'), $database_status['message']);
    }

    if (!empty($error_messages)) {
        $response_data['message'] = implode(' | ', $error_messages);
    }

    wp_send_json_error($response_data, 500);
  }

  public static function render_admin_credentials_page() {
    if (!current_user_can('manage_options')) {
      wp_die(__('Anda tidak memiliki izin untuk mengakses halaman ini.', 'hcis-ysq'));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      check_admin_referer('hcis_admin_credentials');
      $action = sanitize_key($_POST['hcis_admin_action'] ?? '');
      $message = '';
      $type = 'success';

      switch ($action) {
        case 'add':
          $username = sanitize_user($_POST['username'] ?? '', true);
          $display = sanitize_text_field($_POST['display_name'] ?? $username);
          $whatsapp = sanitize_text_field($_POST['whatsapp'] ?? '');
          $password = trim((string)($_POST['password'] ?? ''));
          if ($password === '') {
            $message = __('Password wajib diisi untuk akun baru.', 'hcis-ysq');
            $type = 'error';
            break;
          }
          $result = AdminCredentials::add_account([
            'username'     => $username,
            'display_name' => $display,
            'whatsapp'     => $whatsapp,
          ], $password);
          if (is_wp_error($result)) {
            $message = $result->get_error_message();
            $type = 'error';
          } else {
            $message = __('Akun administrator berhasil ditambahkan.', 'hcis-ysq');
          }
          break;
        case 'update':
          $accountId = sanitize_text_field($_POST['account_id'] ?? '');
          $username = sanitize_user($_POST['username'] ?? '', true);
          $display = sanitize_text_field($_POST['display_name'] ?? '');
          $whatsapp = sanitize_text_field($_POST['whatsapp'] ?? '');
          $password = trim((string)($_POST['password'] ?? ''));
          $result = AdminCredentials::update_account($accountId, [
            'username'     => $username,
            'display_name' => $display,
            'whatsapp'     => $whatsapp,
          ], $password !== '' ? $password : null);
          if (is_wp_error($result)) {
            $message = $result->get_error_message();
            $type = 'error';
          } else {
            $message = __('Akun administrator diperbarui.', 'hcis-ysq');
          }
          break;
        case 'delete':
          $accountId = sanitize_text_field($_POST['account_id'] ?? '');
          $result = AdminCredentials::delete_account($accountId);
          if (is_wp_error($result)) {
            $message = $result->get_error_message();
            $type = 'error';
          } else {
            $message = __('Akun administrator dihapus.', 'hcis-ysq');
          }
          break;
        default:
          $message = __('Aksi tidak dikenali.', 'hcis-ysq');
          $type = 'error';
      }

      $redirect = add_query_arg([
        'page' => 'hcis-admin-credentials',
        'hcis_admin_notice' => rawurlencode($message),
        'hcis_admin_notice_type' => $type,
      ], admin_url('tools.php'));
      wp_safe_redirect($redirect);
      exit;
    }

    $noticeRaw = isset($_GET['hcis_admin_notice']) ? wp_unslash($_GET['hcis_admin_notice']) : '';
    $notice = $noticeRaw !== '' ? sanitize_text_field(rawurldecode($noticeRaw)) : '';
    $noticeType = isset($_GET['hcis_admin_notice_type']) ? sanitize_key($_GET['hcis_admin_notice_type']) : 'success';
    $accounts = AdminCredentials::get_accounts();
    $canAdd = count($accounts) < AdminCredentials::MAX_ACCOUNTS;

    ?>
    <div class="wrap">
      <h1><?php esc_html_e('Kredensial Admin', 'hcis-ysq'); ?></h1>
      <p><?php esc_html_e('Tambahkan hingga tiga akun administrator. Setiap perubahan akan otomatis disinkronkan dengan pengguna WordPress berperan HCIS Admin.', 'hcis-ysq'); ?></p>

      <?php if ($notice): ?>
        <div class="notice notice-<?php echo $noticeType === 'error' ? 'error' : 'success'; ?> is-dismissible">
          <p><?php echo esc_html($notice); ?></p>
        </div>
      <?php endif; ?>

      <?php if ($canAdd): ?>
        <h2><?php esc_html_e('Tambah Akun Baru', 'hcis-ysq'); ?></h2>
        <form method="post" class="card">
          <?php wp_nonce_field('hcis_admin_credentials'); ?>
          <table class="form-table" role="presentation">
            <tr>
              <th scope="row"><label for="hcis-admin-new-username"><?php esc_html_e('Username', 'hcis-ysq'); ?></label></th>
              <td><input type="text" id="hcis-admin-new-username" name="username" class="regular-text" required></td>
            </tr>
            <tr>
              <th scope="row"><label for="hcis-admin-new-display"><?php esc_html_e('Nama Tampilan', 'hcis-ysq'); ?></label></th>
              <td><input type="text" id="hcis-admin-new-display" name="display_name" class="regular-text"></td>
            </tr>
            <tr>
              <th scope="row"><label for="hcis-admin-new-wa"><?php esc_html_e('Nomor WhatsApp', 'hcis-ysq'); ?></label></th>
              <td><input type="text" id="hcis-admin-new-wa" name="whatsapp" class="regular-text" placeholder="628xxxxxxxxxx"></td>
            </tr>
            <tr>
              <th scope="row"><label for="hcis-admin-new-pass"><?php esc_html_e('Password', 'hcis-ysq'); ?></label></th>
              <td><input type="password" id="hcis-admin-new-pass" name="password" class="regular-text" required></td>
            </tr>
          </table>
          <p class="submit">
            <button type="submit" name="hcis_admin_action" value="add" class="button button-primary"><?php esc_html_e('Tambah Akun', 'hcis-ysq'); ?></button>
          </p>
        </form>
      <?php else: ?>
        <p><em><?php printf(esc_html__('Maksimal %d akun sudah terpakai.', 'hcis-ysq'), AdminCredentials::MAX_ACCOUNTS); ?></em></p>
      <?php endif; ?>

      <h2><?php esc_html_e('Daftar Akun', 'hcis-ysq'); ?></h2>
      <?php if (empty($accounts)): ?>
        <p><?php esc_html_e('Belum ada akun administrator.', 'hcis-ysq'); ?></p>
      <?php else: ?>
        <?php foreach ($accounts as $account): ?>
          <form method="post" class="card">
            <?php wp_nonce_field('hcis_admin_credentials'); ?>
            <input type="hidden" name="account_id" value="<?php echo esc_attr($account['id']); ?>">
            <table class="form-table" role="presentation">
              <tr>
                <th scope="row"><label><?php esc_html_e('Username', 'hcis-ysq'); ?></label></th>
                <td><input type="text" name="username" class="regular-text" value="<?php echo esc_attr($account['username']); ?>" required></td>
              </tr>
              <tr>
                <th scope="row"><label><?php esc_html_e('Nama Tampilan', 'hcis-ysq'); ?></label></th>
                <td><input type="text" name="display_name" class="regular-text" value="<?php echo esc_attr($account['display_name']); ?>"></td>
              </tr>
              <tr>
                <th scope="row"><label><?php esc_html_e('Nomor WhatsApp', 'hcis-ysq'); ?></label></th>
                <td><input type="text" name="whatsapp" class="regular-text" value="<?php echo esc_attr($account['whatsapp']); ?>"></td>
              </tr>
              <tr>
                <th scope="row"><label><?php esc_html_e('Password Baru', 'hcis-ysq'); ?></label></th>
                <td><input type="password" name="password" class="regular-text" placeholder="<?php esc_attr_e('Biarkan kosong jika tidak diubah', 'hcis-ysq'); ?>"></td>
              </tr>
            </table>
            <p class="submit">
              <button type="submit" name="hcis_admin_action" value="update" class="button button-primary"><?php esc_html_e('Simpan Perubahan', 'hcis-ysq'); ?></button>
              <button type="submit" name="hcis_admin_action" value="delete" class="button button-link-delete" onclick="return confirm('<?php esc_attr_e('Hapus akun ini?', 'hcis-ysq'); ?>');"><?php esc_html_e('Hapus', 'hcis-ysq'); ?></button>
            </p>
          </form>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php
  }

  public static function ajax_clear_cache() {
    check_ajax_referer('hcis-admin-ajax-nonce');

    // Accept either capability so existing admins without the custom role can still
    // use the legacy settings page's AJAX actions.
    if (!current_user_can('manage_hcis_portal') && !current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    try {
        SheetCache::flush();
        wp_send_json_success(['message' => 'Cache cleared successfully.']);
    } catch (\Exception $e) {
        wp_send_json_error(['message' => 'An error occurred while clearing the cache: ' . $e->getMessage()], 500);
    }
  }

  public static function ajax_setup_sheets() {
    check_ajax_referer('hcis-admin-ajax-nonce');

    if (!current_user_can('manage_hcis_portal') && !current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    try {
        $service = new GoogleSheetsService();
        if (!$service->is_configured()) {
            wp_send_json_error([
                'message' => __('Google Sheet belum dikonfigurasi.', 'hcis-ysq'),
            ], 400);
        }
    } catch (\Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()], 400);
    }

    try {
        $existing_titles = $service->get_sheet_titles();
    } catch (\Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()], 400);
    }

    $tabs = \HCISYSQ\GoogleSheetSettings::get_tabs();

    $created = [];
    $skipped = [];
    $headers_written = [];
    $rewrite_headers = !empty($_POST['rewrite_headers']) && filter_var($_POST['rewrite_headers'], FILTER_VALIDATE_BOOLEAN);

    foreach ($tabs as $slug => $config) {
        $title = $config['title'];
        $headers = \HCISYSQ\GoogleSheetSettings::get_tab_column_map($slug);

        if (in_array($title, $existing_titles, true)) {
            if ($rewrite_headers && !empty($headers)) {
                $service->set_headers($title, $headers);
                $headers_written[] = $title;
            } else {
                $skipped[] = $title;
            }
            continue;
        }

        $created_successfully = $service->create_sheet($title);
        if (!$created_successfully) {
            wp_send_json_error([
                'message' => sprintf(__('Gagal membuat tab %s.', 'hcis-ysq'), $title),
            ]);
        }

        if (!empty($headers)) {
            $service->set_headers($title, $headers);
            $headers_written[] = $title;
        }

        $created[] = $title;
    }

    $message = __('Tab dasar berhasil dibuat.', 'hcis-ysq');
    if (empty($created)) {
        if (!empty($headers_written)) {
            $message = __('Header tab berhasil ditulis ulang.', 'hcis-ysq');
        } else {
            $message = __('database sudah ada', 'hcis-ysq');
        }
    }

    wp_send_json_success([
        'message' => $message,
        'created' => $created,
        'skipped' => $skipped,
        'headers_written' => $headers_written,
    ]);
  }

  public static function ajax_test_wa_connection() {
    check_ajax_referer('hcis-admin-ajax-nonce');

    // Accept either capability so legacy admins using the settings page aren't blocked.
    if (!current_user_can('manage_hcis_portal') && !current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    wp_send_json_error([
        'message' => __('Tes WA dinonaktifkan karena kredensial admin kini dibaca langsung dari tab Admin di Google Sheet. Pastikan nomor admin sudah ada di sheet.', 'hcis-ysq'),
    ], 400);
  }
}
