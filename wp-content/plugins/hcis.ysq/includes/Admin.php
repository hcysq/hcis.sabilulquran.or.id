<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class Admin {
  const OPTION_PORTAL_CREDENTIALS = 'hcis_portal_credentials_json';
  const OPTION_PORTAL_SHEET_ID = 'hcis_portal_sheet_id';
  const OPTION_PORTAL_GIDS = 'hcis_portal_gids';

  public static function init() {
    add_action('admin_notices', [__CLASS__, 'check_required_settings']);
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


  public static function add_settings_page() {
    $google_sheet_settings_label = __('Google Sheet Settings', 'hcis-ysq');

    add_submenu_page(
      'hcis-admin-portal',
      $google_sheet_settings_label,
      $google_sheet_settings_label,
      'manage_hcis_portal',
      'hcis-google-settings',
      [__CLASS__, 'render_settings_page']
    );
  }

  public static function menu() {
    // 1. Keep the old settings page, but hook it to 'add_options_page' for consistency.
    add_options_page(
      'HCIS.YSQ Settings',
      'HCIS.YSQ Settings',
      'manage_options',
      'hcisysq-settings',
      [__CLASS__, 'render']
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

    add_management_page(
      __('HCIS Data Migration', 'hcis-ysq'),
      __('HCIS Data Migration', 'hcis-ysq'),
      'manage_options',
      Migration::PAGE_SLUG,
      ['HCISYSQ\\Migration', 'render_admin_page']
    );
  }

  /**
   * Render the view for the new "Portal HCIS" admin page.
   */
  public static function render_admin_portal_page() {
    if (!current_user_can('manage_hcis_portal') && !current_user_can('manage_options')) return;

    self::render_settings_interface();
  }

  /**
   * Render the Portal HCIS settings page.
   */
  public static function render_portal_settings_page() {
    if (!current_user_can('manage_hcis_portal') && !current_user_can('manage_options')) return;

    $notice = '';
    $default_sheet_id = '110MjkBJbBzFayIUZcA3ZhKuno8y5OcWEnn04TDVHW-Y';
    $gid_keys = [
      'users' => __('Users', 'hcis-ysq'),
      'profiles' => __('Profiles', 'hcis-ysq'),
      'payroll' => __('Payroll', 'hcis-ysq'),
      'keluarga' => __('Keluarga', 'hcis-ysq'),
      'dokumen' => __('Dokumen', 'hcis-ysq'),
      'pendidikan' => __('Pendidikan', 'hcis-ysq'),
      'pelatihan' => __('Pelatihan', 'hcis-ysq'),
    ];

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

      update_option(self::OPTION_PORTAL_CREDENTIALS, $credentials_json);
      update_option(self::OPTION_PORTAL_SHEET_ID, $sheet_id);
      update_option(self::OPTION_PORTAL_GIDS, $gids);

      $notice = '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'hcis-ysq') . '</p></div>';
    }

    $credentials_value = get_option(self::OPTION_PORTAL_CREDENTIALS, '');
    $sheet_id_value = get_option(self::OPTION_PORTAL_SHEET_ID, $default_sheet_id);
    $gids_value = get_option(self::OPTION_PORTAL_GIDS, []);
    if (!is_array($gids_value)) {
      $gids_value = [];
    }

    ?>
    <div class="wrap">
      <h1><?= esc_html__('Portal HCIS Settings', 'hcis-ysq'); ?></h1>
      <?php if ($notice): ?>
        <?= wp_kses_post($notice); ?>
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
        </table>
        <p class="submit">
          <button type="submit" class="button button-primary"><?php esc_html_e('Simpan', 'hcis-ysq'); ?></button>
        </p>
      </form>
    </div>
    <?php
  }

  public static function render_settings_page() {
    if (!current_user_can('manage_hcis_portal') && !current_user_can('manage_options')) return;

    $creds_value = get_option('hcis_google_json_creds', '');
    $sheet_id_value = get_option('hcis_google_sheet_id', '');
    $gid_users = get_option('hcis_gid_users', '');
    $gid_profiles = get_option('hcis_gid_profiles', '');
    $gid_payroll = get_option('hcis_gid_payroll', '');
    $gid_keluarga = get_option('hcis_gid_keluarga', '');
    $gid_dokumen = get_option('hcis_gid_dokumen', '');
    $gid_pendidikan = get_option('hcis_gid_pendidikan', '');
    $gid_pelatihan = get_option('hcis_gid_pelatihan', '');

    ?>
    <div class="wrap">
      <h1><?php esc_html_e('Google Sheet Settings', 'hcis-ysq'); ?></h1>
      <form method="post" action="options.php">
        <?php settings_fields('hcis_google_settings_group'); ?>
        <table class="form-table">
          <tr>
            <th scope="row"><label for="hcis_google_json_creds"><?php esc_html_e('JSON Credentials', 'hcis-ysq'); ?></label></th>
            <td>
              <textarea id="hcis_google_json_creds" name="hcis_google_json_creds" class="large-text code" rows="10"><?php echo esc_textarea($creds_value); ?></textarea>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="hcis_google_sheet_id"><?php esc_html_e('Sheet ID', 'hcis-ysq'); ?></label></th>
            <td>
              <input type="text" id="hcis_google_sheet_id" name="hcis_google_sheet_id" class="regular-text" value="<?php echo esc_attr($sheet_id_value); ?>">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="hcis_gid_users"><?php esc_html_e('GID Users', 'hcis-ysq'); ?></label></th>
            <td>
              <input type="text" id="hcis_gid_users" name="hcis_gid_users" class="regular-text" value="<?php echo esc_attr($gid_users); ?>">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="hcis_gid_profiles"><?php esc_html_e('GID Profiles', 'hcis-ysq'); ?></label></th>
            <td>
              <input type="text" id="hcis_gid_profiles" name="hcis_gid_profiles" class="regular-text" value="<?php echo esc_attr($gid_profiles); ?>">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="hcis_gid_payroll"><?php esc_html_e('GID Payroll', 'hcis-ysq'); ?></label></th>
            <td>
              <input type="text" id="hcis_gid_payroll" name="hcis_gid_payroll" class="regular-text" value="<?php echo esc_attr($gid_payroll); ?>">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="hcis_gid_keluarga"><?php esc_html_e('GID Keluarga', 'hcis-ysq'); ?></label></th>
            <td>
              <input type="text" id="hcis_gid_keluarga" name="hcis_gid_keluarga" class="regular-text" value="<?php echo esc_attr($gid_keluarga); ?>">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="hcis_gid_dokumen"><?php esc_html_e('GID Dokumen', 'hcis-ysq'); ?></label></th>
            <td>
              <input type="text" id="hcis_gid_dokumen" name="hcis_gid_dokumen" class="regular-text" value="<?php echo esc_attr($gid_dokumen); ?>">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="hcis_gid_pendidikan"><?php esc_html_e('GID Pendidikan', 'hcis-ysq'); ?></label></th>
            <td>
              <input type="text" id="hcis_gid_pendidikan" name="hcis_gid_pendidikan" class="regular-text" value="<?php echo esc_attr($gid_pendidikan); ?>">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="hcis_gid_pelatihan"><?php esc_html_e('GID Pelatihan', 'hcis-ysq'); ?></label></th>
            <td>
              <input type="text" id="hcis_gid_pelatihan" name="hcis_gid_pelatihan" class="regular-text" value="<?php echo esc_attr($gid_pelatihan); ?>">
            </td>
          </tr>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>
    <?php
  }

  public static function register_settings() {
    register_setting('hcis_google_settings_group', 'hcis_google_json_creds');
    register_setting('hcis_google_settings_group', 'hcis_google_sheet_id');
    register_setting('hcis_google_settings_group', 'hcis_gid_users');
    register_setting('hcis_google_settings_group', 'hcis_gid_profiles');
    register_setting('hcis_google_settings_group', 'hcis_gid_payroll');
    register_setting('hcis_google_settings_group', 'hcis_gid_keluarga');
    register_setting('hcis_google_settings_group', 'hcis_gid_dokumen');
    register_setting('hcis_google_settings_group', 'hcis_gid_pendidikan');
    register_setting('hcis_google_settings_group', 'hcis_gid_pelatihan');
  }

  public static function render(){
    if (!current_user_can('manage_options')) return;

    self::render_settings_interface();
  }

  private static function render_settings_interface() {
    // handle POST
    $msg = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      check_admin_referer('hcisysq_settings');

      // Profil Pegawai CSV
      if (isset($_POST['save_profiles']) || isset($_POST['import_profiles'])) {
        $url = esc_url_raw($_POST['profiles_csv_url'] ?? '');
        Profiles::set_csv_url($url);

        if (isset($_POST['import_profiles']) && $url) {
          $res = Profiles::import_from_csv($url);
          $msg .= $res['ok']
            ? "<strong>Import Profil:</strong> inserted {$res['inserted']}, updated {$res['updated']}.<br>"
            : "<strong>Import Profil GAGAL:</strong> " . esc_html($res['msg']) . "<br>";
        }
      }

      // Users Google Sheet
      if (isset($_POST['save_users']) || isset($_POST['import_users'])) {
        $sheet_id = sanitize_text_field($_POST['users_sheet_id'] ?? '');
        $tab_name = sanitize_text_field($_POST['users_tab_name'] ?? 'User');
        Users::set_sheet_config($sheet_id, $tab_name);

        if (isset($_POST['import_users']) && $sheet_id) {
          $url = Users::build_csv_url($sheet_id, $tab_name);
          $res = Users::import_from_csv($url);
          $msg .= $res['ok']
            ? "<strong>Import Users:</strong> inserted {$res['inserted']}, updated {$res['updated']}.<br>"
            : "<strong>Import Users GAGAL:</strong> " . esc_html($res['msg']) . "<br>";
        }
      }

      // Training Sheet Config
      if (isset($_POST['save_training'])) {
        $sheet_id = sanitize_text_field($_POST['training_sheet_id'] ?? '');
        $tab_name = sanitize_text_field($_POST['training_tab_name'] ?? 'Data');
        $webapp_url = esc_url_raw($_POST['training_webapp_url'] ?? '');
        $drive_folder = sanitize_text_field($_POST['training_drive_folder_id'] ?? '');

        Trainings::set_sheet_config($sheet_id, $tab_name);
        Trainings::set_webapp_url($webapp_url);
        if ($drive_folder) {
          Trainings::set_drive_folder_id($drive_folder);
        } else {
          delete_option(Trainings::OPT_TRAINING_DRIVE_FOLDER_ID);
        }

        $msg .= "<strong>Training config saved.</strong><br>";
      }

      // WhatsApp & SSO Settings
      if (isset($_POST['save_wa_settings'])) {
        $admin_wa = sanitize_text_field($_POST['hcisysq_admin_wa'] ?? '');
        $wa_token = sanitize_text_field($_POST['hcisysq_wa_token'] ?? '');
        $gas_api_key = sanitize_text_field($_POST['hcis_gas_api_key'] ?? '');

        update_option('hcisysq_admin_wa', $admin_wa);
        update_option('hcisysq_wa_token', $wa_token);
        update_option(Hcis_Gas_Token::OPTION_API_KEY, $gas_api_key);

        $msg .= "<strong>WhatsApp & GAS settings saved.</strong><br>";
      }

      if (isset($_POST['save_security_settings'])) {
        $rate_limit = [
          'window'   => max(10, absint($_POST['security_rate_window'] ?? 300)),
          'per_ip'   => max(1, absint($_POST['security_rate_ip'] ?? 30)),
          'per_user' => max(0, absint($_POST['security_rate_user'] ?? 60)),
        ];

        $enabled_forms = [
          'login'        => !empty($_POST['security_captcha_form_login']),
          'registration' => !empty($_POST['security_captcha_form_registration']),
          'training'     => !empty($_POST['security_captcha_form_training']),
        ];

        $captcha = [
          'provider'      => sanitize_text_field($_POST['security_captcha_provider'] ?? ''),
          'site_key'      => sanitize_text_field($_POST['security_captcha_site_key'] ?? ''),
          'secret_key'    => sanitize_text_field($_POST['security_captcha_secret_key'] ?? ''),
          'threshold'     => (float) ($_POST['security_captcha_threshold'] ?? 0.5),
          'enabled_forms' => $enabled_forms,
        ];

        Security::save_settings([
          'rate_limit' => $rate_limit,
          'captcha'    => $captcha,
        ]);

        $msg .= "<strong>Security & CAPTCHA settings saved.</strong><br>";
      }
    }

    $profiles_csv = esc_url(Profiles::get_csv_url());
    $users_sheet_id = esc_attr(Users::get_sheet_id());
    $users_tab_name = esc_attr(Users::get_tab_name());
    $training_sheet_id = esc_attr(Trainings::get_sheet_id());
    $training_tab_name = esc_attr(Trainings::get_tab_name());
    $training_drive_folder = esc_attr(Trainings::get_drive_folder_id());
    $training_webapp_url = esc_url(Trainings::get_webapp_url());
    $gas_api_key = esc_attr(get_option(Hcis_Gas_Token::OPTION_API_KEY, ''));
    $admin_wa_value = esc_attr(Config::get('admin_wa', 'option'));
    $wa_token_value = esc_attr(Config::get('wa_token', 'option'));
    $admin_wa_notice = Config::describe_override('admin_wa');
    $wa_token_notice = Config::describe_override('wa_token');
    $security_settings = Security::get_settings();
    $rate_settings = $security_settings['rate_limit'];
    $captcha_settings = $security_settings['captcha'];
    $rate_window = esc_attr($rate_settings['window']);
    $rate_per_ip = esc_attr($rate_settings['per_ip']);
    $rate_per_user = esc_attr($rate_settings['per_user']);
    $captcha_provider = esc_attr($captcha_settings['provider']);
    $captcha_site_key = esc_attr($captcha_settings['site_key']);
    $captcha_secret_key = esc_attr($captcha_settings['secret_key']);
    $captcha_threshold = esc_attr($captcha_settings['threshold']);
    $captcha_forms = is_array($captcha_settings['enabled_forms']) ? $captcha_settings['enabled_forms'] : [];
    ?>
    <div class="wrap">
      <h1>HCIS.YSQ • Settings & Import</h1>
      <?php if ($msg): ?>
        <div class="notice notice-info"><p><?= $msg ?></p></div>
      <?php endif; ?>

      <!-- PROFIL PEGAWAI (CSV) -->
      <h2>1. Profil Pegawai (CSV)</h2>
      <form method="post">
        <?php wp_nonce_field('hcisysq_settings'); ?>
        <table class="form-table">
          <tr>
            <th scope="row"><label for="profiles_csv_url">CSV URL</label></th>
            <td>
              <input type="url" id="profiles_csv_url" name="profiles_csv_url" class="regular-text code" style="width: 600px"
                     value="<?= $profiles_csv ?>" placeholder="https://docs.google.com/spreadsheets/d/e/…/pub?gid=…&single=true&output=csv">
              <p class="description">URL: <code>https://docs.google.com/spreadsheets/d/e/2PACX-1vTlR2VUOcQfXRjZN4fNC-o4CvPTgd-ZlReqj_pfEfYGr5A87Wh6K2zU16iexLnfIh5djkrXzmVlk1w-/pub?gid=0&single=true&output=csv</code></p>
            </td>
          </tr>
        </table>
        <p class="submit">
          <button type="submit" name="save_profiles" class="button button-primary">Simpan</button>
          <button type="submit" name="import_profiles" class="button">Import Sekarang</button>
        </p>
      </form>
      <hr>

      <!-- USERS (Google Sheet) -->
      <h2>2. Users (Google Sheet)</h2>
      <form method="post">
        <?php wp_nonce_field('hcisysq_settings'); ?>
        <table class="form-table">
          <tr>
            <th scope="row"><label for="users_sheet_id">Sheet ID</label></th>
            <td>
              <input type="text" id="users_sheet_id" name="users_sheet_id" class="regular-text" style="width: 600px"
                     value="<?= $users_sheet_id ?>" placeholder="14Uf7pjsFVURLmL5NWXlWhYvoILrwdiW11y3sVOLrLt4">
              <p class="description">Sheet ID: <code>14Uf7pjsFVURLmL5NWXlWhYvoILrwdiW11y3sVOLrLt4</code></p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="users_tab_name">Tab Name</label></th>
            <td>
              <input type="text" id="users_tab_name" name="users_tab_name" class="regular-text"
                     value="<?= $users_tab_name ?>" placeholder="User">
            </td>
          </tr>
        </table>
        <p class="submit">
          <button type="submit" name="save_users" class="button button-primary">Simpan</button>
          <button type="submit" name="import_users" class="button">Import Sekarang</button>
        </p>
      </form>
      <hr>

      <!-- TRAINING (Google Sheet) -->
      <h2>3. Training Form → Google Sheet</h2>
      <form method="post">
        <?php wp_nonce_field('hcisysq_settings'); ?>
        <table class="form-table">
          <tr>
            <th scope="row"><label for="training_sheet_id">Sheet ID</label></th>
            <td>
              <input type="text" id="training_sheet_id" name="training_sheet_id" class="regular-text" style="width: 600px"
                     value="<?= $training_sheet_id ?>" placeholder="1Ex3WqFgW-pkEg07-IopgIMyzcsZdirIcSEz4GRQ3UFQ">
              <p class="description">Sheet ID: <code>1Ex3WqFgW-pkEg07-IopgIMyzcsZdirIcSEz4GRQ3UFQ</code></p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="training_tab_name">Tab Name</label></th>
            <td>
              <input type="text" id="training_tab_name" name="training_tab_name" class="regular-text"
                     value="<?= $training_tab_name ?>" placeholder="Data">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="training_drive_folder_id">Drive Folder ID</label></th>
            <td>
              <input type="text" id="training_drive_folder_id" name="training_drive_folder_id" class="regular-text" style="width: 600px"
                     value="<?= $training_drive_folder ?>" placeholder="1Wpf6k5G21Zb4kAILYDL7jfCjyKZd55zp">
              <p class="description">File sertifikat akan disimpan di folder Google Drive ini (sub-folder otomatis: <code>&lt;NIP&gt;-&lt;Nama&gt;</code>).</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="training_webapp_url">Web App URL</label></th>
            <td>
              <input type="url" id="training_webapp_url" name="training_webapp_url" class="regular-text code" style="width: 600px"
                     value="<?= $training_webapp_url ?>" placeholder="https://script.google.com/macros/s/…/exec">
              <p class="description">Deploy Google Apps Script sebagai Web App, lalu paste URL-nya di sini.</p>
            </td>
          </tr>
        </table>
        <p class="submit">
          <button type="submit" name="save_training" class="button button-primary">Simpan</button>
        </p>
      </form>

      <hr>

      <!-- WHATSAPP & SSO SETTINGS -->
      <h2>4. WhatsApp & SSO Settings</h2>
      <form method="post">
        <?php wp_nonce_field('hcisysq_settings'); ?>
        <table class="form-table">
          <tr>
            <th scope="row"><label for="hcisysq_admin_wa">Admin WhatsApp (E.164)</label></th>
            <td>
              <input type="text" id="hcisysq_admin_wa" name="hcisysq_admin_wa" class="regular-text"
                     value="<?= $admin_wa_value ?>" placeholder="62xxxxxxxxxxx">
              <p class="description">
                Nomor WhatsApp admin HCM untuk notifikasi "Lupa Password" (format: 62xxx).
                <?php if ($admin_wa_notice): ?>
                  <br><strong>Perhatian:</strong> <?= \wp_kses_post($admin_wa_notice); ?>
                <?php endif; ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="hcisysq_wa_token">WhatsApp API Token</label></th>
            <td>
              <input type="text" id="hcisysq_wa_token" name="hcisysq_wa_token" class="regular-text code" style="width: 600px"
                     value="<?= $wa_token_value ?>" placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
              <p class="description">
                API Key dari Starsender untuk mengirim pesan WhatsApp.
                <?php if ($wa_token_notice): ?>
                  <br><strong>Perhatian:</strong> <?= \wp_kses_post($wa_token_notice); ?>
                <?php endif; ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="hcis_gas_api_key">HCIS GAS API Key</label></th>
            <td>
              <input type="password" id="hcis_gas_api_key" name="hcis_gas_api_key" class="regular-text" style="width: 400px"
                     value="<?= $gas_api_key ?>" autocomplete="off" placeholder="Masukkan shared key">
              <p class="description">Masukkan shared key yang sama seperti di Google Apps Script (header <code>x-hcis-gas-key</code> untuk endpoint exchange).</p>
            </td>
          </tr>
        </table>
        <p class="submit">
          <button type="submit" name="save_wa_settings" class="button button-primary">Simpan</button>
        </p>
      </form>

      <hr>

      <!-- SECURITY SETTINGS -->
      <h2>5. Keamanan & CAPTCHA</h2>
      <form method="post">
        <?php wp_nonce_field('hcisysq_settings'); ?>
        <table class="form-table">
          <tr>
            <th scope="row"><label for="security_rate_window">Jendela Rate Limit (detik)</label></th>
            <td>
              <input type="number" min="10" id="security_rate_window" name="security_rate_window" class="small-text" value="<?= $rate_window ?>">
              <p class="description">Durasi perhitungan kuota permintaan. Semua batas dihitung ulang setiap jendela waktu.</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="security_rate_ip">Maksimal per IP</label></th>
            <td>
              <input type="number" min="1" id="security_rate_ip" name="security_rate_ip" class="small-text" value="<?= $rate_per_ip ?>">
              <p class="description">Jumlah permintaan yang diizinkan dari satu alamat IP di setiap jendela.</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="security_rate_user">Maksimal per Pengguna</label></th>
            <td>
              <input type="number" min="0" id="security_rate_user" name="security_rate_user" class="small-text" value="<?= $rate_per_user ?>">
              <p class="description">Set ke 0 untuk menonaktifkan batas khusus pengguna.</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="security_captcha_provider">Penyedia CAPTCHA</label></th>
            <td>
              <select id="security_captcha_provider" name="security_captcha_provider">
                <option value=""<?= $captcha_provider === '' ? ' selected' : '' ?>>Nonaktif</option>
                <option value="recaptcha"<?= $captcha_provider === 'recaptcha' ? ' selected' : '' ?>>Google reCAPTCHA</option>
                <option value="hcaptcha"<?= $captcha_provider === 'hcaptcha' ? ' selected' : '' ?>>hCaptcha</option>
              </select>
              <p class="description">Masukkan Site Key & Secret Key sesuai penyedia.</p>
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="security_captcha_site_key">Site Key</label></th>
            <td>
              <input type="text" id="security_captcha_site_key" name="security_captcha_site_key" class="regular-text" value="<?= $captcha_site_key ?>" autocomplete="off">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="security_captcha_secret_key">Secret Key</label></th>
            <td>
              <input type="password" id="security_captcha_secret_key" name="security_captcha_secret_key" class="regular-text" value="<?= $captcha_secret_key ?>" autocomplete="off">
            </td>
          </tr>
          <tr>
            <th scope="row"><label for="security_captcha_threshold">Threshold Skor</label></th>
            <td>
              <input type="number" id="security_captcha_threshold" name="security_captcha_threshold" min="0" max="1" step="0.1" value="<?= $captcha_threshold ?>" class="small-text">
              <p class="description">Digunakan untuk provider berbasis skor seperti reCAPTCHA v3.</p>
            </td>
          </tr>
          <tr>
            <th scope="row">Aktifkan untuk Form</th>
            <td>
              <label>
                <input type="checkbox" name="security_captcha_form_login" value="1" <?php checked(!empty($captcha_forms['login'])); ?>> Login
              </label><br>
              <label>
                <input type="checkbox" name="security_captcha_form_registration" value="1" <?php checked(!empty($captcha_forms['registration'])); ?>> Reset/Registrasi Pengguna
              </label><br>
              <label>
                <input type="checkbox" name="security_captcha_form_training" value="1" <?php checked(!empty($captcha_forms['training'])); ?>> Form Pelatihan / high-risk
              </label>
            </td>
          </tr>
        </table>
        <p class="submit">
          <button type="submit" name="save_security_settings" class="button button-primary">Simpan</button>
        </p>
      </form>

      <hr>
      <p><strong>Tips:</strong> Import otomatis dijalankan harian via WP-Cron (Profil & Users).</p>
    </div>
    <?php
  }
}