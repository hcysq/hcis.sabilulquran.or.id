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
    $tab_labels = GoogleSheetSettings::get_tab_labels();
    $setup_key_rows = GoogleSheetSettings::get_effective_setup_keys();

    $wa_token_value = get_option('hcisysq_wa_token', '');
    $wa_token_override = Config::describe_override('wa_token');
    $adminContacts = [];

    $setup_key_config = GoogleSheetSettings::get_setup_key_config();
    $gids_value = GoogleSheetSettings::get_gid_map();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      check_admin_referer('hcis_portal_settings');

      $credentials_json = isset($_POST['hcis_portal_credentials_json'])
        ? trim(wp_unslash($_POST['hcis_portal_credentials_json']))
        : '';
      $sheet_id = sanitize_text_field($_POST['hcis_portal_sheet_id'] ?? '');
      $wa_token_input = sanitize_text_field(wp_unslash($_POST['hcis_portal_wa_token'] ?? ''));

      $gids = [];
      foreach ($tab_labels as $key => $label) {
        if ($key === 'admins') {
          continue;
        }
        $gids[$key] = sanitize_text_field($_POST["hcis_portal_gid_{$key}"] ?? '');
      }

      $setup_keys_input = isset($_POST['hcis_portal_setup_keys']) ? wp_unslash($_POST['hcis_portal_setup_keys']) : [];
      $setup_keys = [];
      if (is_array($setup_keys_input)) {
        foreach ($setup_keys_input as $setup_key => $row) {
          if (!is_array($row)) {
            continue;
          }
          $setup_keys[$setup_key] = [
            'tab' => sanitize_key($row['tab'] ?? ''),
            'header' => sanitize_text_field($row['header'] ?? ''),
            'order' => isset($row['order']) ? absint($row['order']) : 0,
          ];
        }
      }

      $status_data = GoogleSheetSettings::save_settings($credentials_json, $sheet_id, $gids, $setup_keys);

      update_option('hcisysq_wa_token', $wa_token_input);
      $wa_token_value = $wa_token_input;
      $gids_value = GoogleSheetSettings::get_gid_map();

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
    $setup_key_rows = GoogleSheetSettings::get_effective_setup_keys();

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
          <tr>
            <th scope="row" colspan="2"><h2 style="margin: 0;"><?php esc_html_e('Data Sources', 'hcis-ysq'); ?></h2></th>
          </tr>
          <?php foreach ($tab_labels as $key => $label): ?>
            <?php if ($key === 'admins') continue; ?>
            <tr>
              <th scope="row"><label for="hcis_portal_gid_<?php echo esc_attr($key); ?>"><?php echo esc_html(sprintf(__('GID %s', 'hcis-ysq'), $label)); ?></label></th>
              <td>
                <input type="text" id="hcis_portal_gid_<?php echo esc_attr($key); ?>" name="hcis_portal_gid_<?php echo esc_attr($key); ?>" class="regular-text" style="width: 220px" value="<?php echo esc_attr($gids_value[$key] ?? ''); ?>" placeholder="0">
                <p class="description"><?php echo esc_html(sprintf(__('GID untuk tab %s', 'hcis-ysq'), $label)); ?></p>
              </td>
            </tr>
          <?php endforeach; ?>

          <tr>
            <th scope="row"><?php esc_html_e('Setup Key Mapping', 'hcis-ysq'); ?></th>
            <td>
              <p class="description"><?php esc_html_e('Tentukan tab Google Sheet, header, dan urutan untuk setiap key yang dibutuhkan plugin. Contoh: employee_id_number muncul di Dashboard → Profil sehingga harus menunjuk kolom NIP yang benar.', 'hcis-ysq'); ?></p>
              <table class="widefat striped" style="max-width: 1000px; margin-top: 10px;">
                <thead>
                  <tr>
                    <th><?php esc_html_e('Kolom', 'hcis-ysq'); ?></th>
                    <th><?php esc_html_e('Plugin', 'hcis-ysq'); ?></th>
                    <th><?php esc_html_e('Deskripsi', 'hcis-ysq'); ?></th>
                    <th><?php esc_html_e('Tab / Sheet', 'hcis-ysq'); ?></th>
                    <th><?php esc_html_e('Header di Sheet', 'hcis-ysq'); ?></th>
                    <th><?php esc_html_e('Urutan', 'hcis-ysq'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($setup_key_rows as $key => $row): ?>
                    <tr>
                      <td><code><?php echo esc_html($row['label']); ?></code></td>
                      <td><?php echo esc_html($row['plugin']); ?></td>
                      <td><?php echo esc_html($row['description']); ?></td>
                      <td>
                        <select name="hcis_portal_setup_keys[<?php echo esc_attr($key); ?>][tab]">
                          <?php foreach ($tab_labels as $tab_key => $tab_label): ?>
                            <option value="<?php echo esc_attr($tab_key); ?>" <?php selected($row['tab'], $tab_key); ?>><?php echo esc_html($tab_label); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </td>
                      <td>
                        <input type="text" name="hcis_portal_setup_keys[<?php echo esc_attr($key); ?>][header]" value="<?php echo esc_attr($row['header']); ?>" class="regular-text" />
                      </td>
                      <td>
                        <input type="number" min="1" step="1" name="hcis_portal_setup_keys[<?php echo esc_attr($key); ?>][order]" value="<?php echo esc_attr($row['order']); ?>" style="width: 80px;" />
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </td>
          </tr>
        </table>
        <p class="submit">
          <input type="text" id="hcis-test-nip" name="hcis_test_nip" class="regular-text" placeholder="<?php esc_attr_e('NIP to Test (optional)', 'hcis-ysq'); ?>" style="width: 200px; margin-right: 10px;">
          <button type="submit" class="button button-primary"><?php esc_html_e('Simpan', 'hcis-ysq'); ?></button>
          <button type="button" id="hcis-test-connection" class="button"><?php esc_html_e('Test Connection', 'hcis-ysq'); ?></button>
          <button type="button" id="hcis-clear-cache" class="button"><?php esc_html_e('Clear Cache', 'hcis-ysq'); ?></button>
          <button type="button" id="hcis-test-wa-connection" class="button"><?php esc_html_e('Test WA Connection', 'hcis-ysq'); ?></button>
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

            if ($user_data && is_array($user_data)) {
                $allowed_keys = ['nip', 'nama', 'nik', 'phone', 'email'];
                $sanitized_user_data = [];

                foreach ($allowed_keys as $key) {
                    if (!array_key_exists($key, $user_data)) {
                        continue;
                    }

                    $value = $user_data[$key];
                    if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                        $sanitized_user_data[$key] = sanitize_text_field((string) $value);
                    }
                }

                if (!empty($sanitized_user_data)) {
                    $response_data['user_data_for_nip'] = $sanitized_user_data;
                } else {
                    $response_data['user_data_for_nip'] = 'No public user data available for NIP: ' . $nip;
                }
            } else {
                $response_data['user_data_for_nip'] = 'No user found for NIP: ' . $nip;
            }
        }
        wp_send_json_success($response_data);
    } catch (\Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()], 500);
    }
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