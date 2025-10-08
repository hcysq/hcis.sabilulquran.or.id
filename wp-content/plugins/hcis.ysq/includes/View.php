<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class View {

  /* ========== LOGIN PAGE ========== */
  public static function login(){
    wp_enqueue_style('hcisysq-login');
    wp_enqueue_script('hcisysq-login');
    ob_start(); ?>
    <div class="hcisysq-auth-wrap">
      <div class="auth-card">
        <div class="auth-header">
          <h2>Masuk ke Akun Guru/Pegawai</h2>
        </div>

        <form id="hcisysq-login-form" class="auth-form" method="post" autocomplete="off">
          <label for="hcisysq-nip">Akun (NIP atau No HP) <span class="req">*</span></label>
          <input id="hcisysq-nip" type="text" name="nip" placeholder="Masukkan NIP atau No HP" autocomplete="username" required>

          <label for="hcisysq-pw">Password <span class="req">*</span></label>
          <div class="pw-row">
            <input id="hcisysq-pw" type="password" name="pw" placeholder="Gunakan No HP" autocomplete="current-password" required>
            <button type="button" id="hcisysq-eye" class="eye">lihat</button>
          </div>

          <button type="submit" class="btn-primary">Masuk</button>
          <button type="button" id="hcisysq-forgot" class="link-forgot">Lupa password?</button>
          <div class="msg" aria-live="polite"></div>
        </form>
      </div>
    <!-- Modal Lupa Password -->
    <div id="hcisysq-modal" class="modal-backdrop" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="hcisysq-forgot-title">
      <div class="modal">
        <button type="button" class="modal-close" id="hcisysq-close-modal" aria-label="Tutup">×</button>
        <h3 id="hcisysq-forgot-title">Lupa Password</h3>
        <p>Masukkan Akun (NIP atau No HP) Anda. Kami akan mengirim tautan reset ke WhatsApp terdaftar.</p>
        <label>Akun (NIP atau No HP)</label>
        <input id="hcisysq-nip-forgot" type="text" placeholder="Masukkan NIP atau No HP">
        <div class="modal-actions">
          <button type="button" class="btn-light" id="hcisysq-cancel">Batal</button>
          <button type="button" class="btn-primary" id="hcisysq-send">Kirim</button>
        </div>
        <div id="hcisysq-forgot-msg" class="modal-msg" aria-live="polite"></div>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

  public static function reset_password(){
    wp_enqueue_style('hcisysq-login');
    wp_enqueue_script('hcisysq-reset');

    $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
    $payload = $token ? Forgot::get_token_payload($token) : null;
    $nip = '';
    $tokenValid = false;

    if (is_array($payload) && !empty($payload['nip'])) {
      $tokenValid = true;
      $nip = sanitize_text_field($payload['nip']);
    }

    ob_start(); ?>
    <div class="hcisysq-auth-wrap">
      <div class="auth-card">
        <div class="auth-header">
          <h2>Ganti Password HCIS</h2>
        </div>

        <?php if ($tokenValid): ?>
          <form id="hcisysq-reset-form" class="auth-form" method="post" autocomplete="off" data-token="<?= esc_attr($token) ?>">
            <?php if ($nip !== ''): ?>
              <label>Akun (NIP)</label>
              <input type="text" value="<?= esc_attr($nip) ?>" readonly>
              <input type="hidden" name="nip" value="<?= esc_attr($nip) ?>">
            <?php endif; ?>

            <label for="hcisysq-new-password">Password Baru <span class="req">*</span></label>
            <input id="hcisysq-new-password" type="password" name="password" placeholder="Minimal 6 karakter" autocomplete="new-password" required>

            <label for="hcisysq-confirm-password">Konfirmasi Password <span class="req">*</span></label>
            <input id="hcisysq-confirm-password" type="password" name="confirm" placeholder="Ulangi password baru" autocomplete="new-password" required>

            <button type="submit" class="btn-primary">Simpan Password Baru</button>
            <div class="msg" aria-live="polite"></div>
          </form>
        <?php else: ?>
          <p class="msg">Token reset tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru melalui halaman masuk.</p>
        <?php endif; ?>

        <div class="auth-footer">
          <a class="link-forgot" href="<?= esc_url(home_url('/' . trim(HCISYSQ_LOGIN_SLUG, '/') . '/')) ?>">Kembali ke halaman masuk</a>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

  private static function format_admin_announcements(array $items){
    return array_map(function($item){
      return [
        'id'          => $item['id'] ?? '',
        'title'       => $item['title'] ?? '',
        'body'        => $item['body'] ?? '',
        'link_label'  => $item['link_label'] ?? '',
        'link_url'    => $item['link_url'] ?? '',
        'status'      => $item['status'] ?? 'published',
        'created_at'  => $item['created_at'] ?? '',
        'updated_at'  => $item['updated_at'] ?? '',
        'archived_at' => $item['archived_at'] ?? '',
      ];
    }, $items);
  }

  private static function render_admin_dashboard(array $identity){
    $publicSettings = Auth::get_admin_public_settings();
    $announcements  = self::format_admin_announcements(Announcements::all());
    $tasksBootstrap = Tasks::get_admin_bootstrap();
    $homeMarquee    = get_option('hcisysq_home_marquee_text', '');
    $rawHomeOptions = get_option('hcisysq_home_marquee_options', []);
    $defaultOptions = [
      'speed'          => 1.0,
      'background'     => '#ffffff',
      'duplicates'     => 2,
      'letter_spacing' => 0.0,
      'gap'            => 32,
    ];
    $homeOptions = is_array($rawHomeOptions) ? array_merge($defaultOptions, array_intersect_key($rawHomeOptions, $defaultOptions)) : $defaultOptions;
    $homeOptions['speed'] = (float) $homeOptions['speed'];
    $homeOptions['duplicates'] = (int) $homeOptions['duplicates'];
    $homeOptions['letter_spacing'] = (float) $homeOptions['letter_spacing'];
    $homeOptions['gap'] = (int) $homeOptions['gap'];

    $homeSettings   = [
      'marquee_text' => is_string($homeMarquee) ? RichText::sanitize($homeMarquee) : '',
      'options'      => $homeOptions,
    ];

    wp_enqueue_style('hcisysq-admin');
    wp_enqueue_script('hcisysq-admin');

    $inline = [
      'announcements' => $announcements,
      'settings'      => $publicSettings,
      'home'          => $homeSettings,
      'tasks'         => $tasksBootstrap,
    ];
    wp_add_inline_script('hcisysq-admin', 'window.hcisysqAdmin = ' . wp_json_encode($inline) . ';', 'before');

    $statusBadge = function($status){
      $label = $status === 'archived' ? 'Diarsipkan' : 'Dipublikasikan';
      $class = $status === 'archived' ? 'is-archived' : 'is-published';
      return '<span class="hcisysq-status-badge ' . esc_attr($class) . '">' . esc_html($label) . '</span>';
    };

    ob_start(); ?>
    <div class="hcisysq-dashboard hcisysq-dashboard--admin" id="hcisysq-dashboard">
      <aside class="hcisysq-sidebar" id="hcisysq-sidebar" aria-label="Navigasi administrator">
        <div class="hcisysq-sidebar-header">
          <span class="hcisysq-sidebar-logo">SQ Admin</span>
          <button type="button" class="hcisysq-icon-button hcisysq-sidebar-close" id="hcisysq-sidebar-close" aria-label="Tutup menu administrator">
            <span aria-hidden="true">×</span>
          </button>
        </div>
        <nav class="hcisysq-sidebar-nav" data-admin-nav>
          <a href="#" class="is-active" data-view="home">Beranda HCIS</a>
          <a href="#" data-view="pegawai">Data Pegawai</a>
          <a href="#" data-view="pelatihan">Pelatihan</a>
          <a href="#" data-view="tugas">Tugas</a>
          <a href="#" data-view="pengguna">Kelola Pengguna</a>
          <a href="#" data-view="laporan">Laporan</a>
          <hr>
          <a href="#" data-view="settings">Pengaturan</a>
        </nav>
        <div class="hcisysq-sidebar-meta">
          <span>Versi <?= esc_html(HCISYSQ_VER) ?></span>
        </div>
      </aside>

      <div class="hcisysq-sidebar-overlay" id="hcisysq-sidebar-overlay" aria-hidden="true"></div>

      <main class="hcisysq-main">
        <header class="hcisysq-topbar">
          <div class="hcisysq-topbar-left">
            <button type="button" class="hcisysq-icon-button hcisysq-menu-toggle" id="hcisysq-sidebar-toggle" aria-label="Buka menu administrator" aria-expanded="true">
              <span></span>
              <span></span>
              <span></span>
            </button>
            <div>
              <h1 class="hcisysq-page-title">Dashboard Administrator</h1>
              <p class="hcisysq-page-subtitle">Kelola informasi &amp; keamanan sistem</p>
            </div>
          </div>
          <div class="hcisysq-user">
            <div class="hcisysq-user-meta">
              <span class="hcisysq-user-name"><?= esc_html($publicSettings['display_name']) ?></span>
              <span class="hcisysq-user-role"><?= esc_html($publicSettings['username']) ?></span>
            </div>
            <button type="button" class="btn-light" id="hcisysq-logout">Keluar</button>
          </div>
        </header>

        <div class="hcisysq-main-body">
          <section class="hcisysq-admin-view is-active" data-view="home">
            <div class="hcisysq-live-preview" data-role="marquee-preview-wrapper">
              <span class="hcisysq-live-preview__label">Preview Running Text</span>
              <div class="hcisysq-live-preview__track" data-role="marquee-preview"></div>
            </div>
            <article class="hcisysq-card hcisysq-card--elevated">
              <h3 class="hcisysq-card-title">Pengaturan Beranda HCIS</h3>
              <form id="hcisysq-home-settings-form" class="hcisysq-form-grid">
                <div class="form-group">
                  <label for="hcisysq-home-marquee">Running Text (HTML)</label>
                  <textarea id="hcisysq-home-marquee" name="marquee_text" class="hcisysq-textarea hcisysq-textarea--code" rows="5"><?= esc_textarea($homeSettings['marquee_text']) ?></textarea>
                  <p class="form-helper" id="hcisysq-home-marquee-help">Gunakan tag dasar seperti &lt;p&gt;, &lt;strong&gt;, &lt;em&gt;, atau &lt;ul&gt; untuk mengatur konten. Kosongkan jika tidak ingin menampilkan running text.</p>
                </div>
                <div class="form-group form-group--columns">
                  <div class="form-field">
                    <label for="hcisysq-marquee-speed">Kecepatan</label>
                    <select id="hcisysq-marquee-speed" name="marquee_speed" class="hcisysq-input">
                      <option value="0.5"<?= $homeSettings['options']['speed'] === 0.5 ? ' selected' : '' ?>>0,5x (lebih lambat)</option>
                      <option value="1"<?= $homeSettings['options']['speed'] === 1.0 ? ' selected' : '' ?>>1x (normal)</option>
                      <option value="2"<?= $homeSettings['options']['speed'] === 2.0 ? ' selected' : '' ?>>2x (lebih cepat)</option>
                      <option value="3"<?= $homeSettings['options']['speed'] === 3.0 ? ' selected' : '' ?>>3x (sangat cepat)</option>
                    </select>
                  </div>
                  <div class="form-field">
                    <label for="hcisysq-marquee-duplicates">Duplikasi Teks</label>
                    <select id="hcisysq-marquee-duplicates" name="marquee_duplicates" class="hcisysq-input">
                      <?php for ($i = 1; $i <= 6; $i++): ?>
                        <option value="<?= $i ?>"<?= $homeSettings['options']['duplicates'] === $i ? ' selected' : '' ?>><?= $i ?>x</option>
                      <?php endfor; ?>
                    </select>
                  </div>
                </div>
                <div class="form-group form-group--columns">
                  <div class="form-field">
                    <label for="hcisysq-marquee-background">Warna Latar</label>
                    <input type="color" id="hcisysq-marquee-background" name="marquee_background" value="<?= esc_attr($homeSettings['options']['background']) ?>">
                  </div>
                  <div class="form-field">
                    <label for="hcisysq-marquee-gap">Jarak Antar Duplikasi</label>
                    <div class="hcisysq-range-field">
                      <input type="range" id="hcisysq-marquee-gap" name="marquee_gap" min="8" max="160" step="4" value="<?= esc_attr($homeSettings['options']['gap']) ?>">
                      <span class="hcisysq-range-value" data-role="marquee-gap-value"><?= esc_html($homeSettings['options']['gap']) ?> px</span>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label for="hcisysq-marquee-letter">Jarak Antar Huruf</label>
                  <div class="hcisysq-range-field">
                    <input type="range" id="hcisysq-marquee-letter" name="marquee_letter_spacing" min="0" max="10" step="0.5" value="<?= esc_attr($homeSettings['options']['letter_spacing']) ?>">
                    <span class="hcisysq-range-value" data-role="marquee-letter-value"><?= esc_html(number_format($homeSettings['options']['letter_spacing'], 1, ',', '.')) ?> px</span>
                  </div>
                </div>
                <div class="form-actions">
                  <button type="submit" class="btn-primary">Simpan Pengaturan</button>
                  <div class="msg" data-role="home-message"></div>
                </div>
              </form>
            </article>

            <article class="hcisysq-card hcisysq-card--elevated">
              <h3 class="hcisysq-card-title">Buat Pengumuman Baru</h3>
              <form id="hcisysq-announcement-form" class="hcisysq-form-grid" enctype="multipart/form-data">
                <input type="hidden" name="announcement_id" value="">
                <input type="hidden" name="thumbnail_existing" value="0">
                <input type="hidden" name="thumbnail_action" value="keep">
                <input type="hidden" name="existing_attachments" value="[]">
                <div class="form-group form-group--columns">
                  <div class="form-field">
                    <label for="hcisysq-ann-category">Kategori <span class="req">*</span></label>
                    <select id="hcisysq-ann-category" name="category" class="hcisysq-input" required>
                      <?php foreach (Announcements::CATEGORY_TERMS as $slug => $label): ?>
                        <option value="<?= esc_attr($slug) ?>"><?= esc_html($label) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-field">
                    <label for="hcisysq-ann-title">Judul <span class="req">*</span></label>
                    <input type="text" id="hcisysq-ann-title" name="title" class="hcisysq-input" placeholder="Contoh: Pembaruan Data Pegawai" required>
                  </div>
                </div>
                <div class="form-group">
                  <label for="hcisysq-ann-body">Isi Pengumuman (HTML) <span class="req">*</span></label>
                  <textarea id="hcisysq-ann-body" name="body" class="hcisysq-textarea hcisysq-textarea--code" rows="10" aria-describedby="hcisysq-ann-body-help"></textarea>
                  <p class="form-helper" id="hcisysq-ann-body-help">Tulis dengan struktur HTML sederhana. Gunakan &lt;p&gt; untuk paragraf, &lt;ul&gt; dan &lt;li&gt; untuk daftar, atau &lt;strong&gt; untuk penekanan.</p>
                </div>
                <div class="form-group form-group--columns">
                  <div class="form-field">
                    <label for="hcisysq-ann-link-label">Tampilan Link</label>
                    <input type="text" id="hcisysq-ann-link-label" name="link_label" class="hcisysq-input" placeholder="Contoh: Baca selengkapnya">
                  </div>
                  <div class="form-field">
                    <label for="hcisysq-ann-link-type">Jenis Link</label>
                    <select id="hcisysq-ann-link-type" name="link_type" class="hcisysq-input">
                      <option value="">Tidak ada tautan</option>
                      <option value="external">Gunakan URL khusus</option>
                      <option value="training">Gunakan tautan Form Pelatihan</option>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label for="hcisysq-ann-link-url">Link (Opsional)</label>
                  <input type="url" id="hcisysq-ann-link-url" name="link_url" class="hcisysq-input" placeholder="https://contoh.id" autocomplete="url" disabled>
                </div>
                <div class="form-group">
                  <label for="hcisysq-ann-thumbnail">Thumbnail</label>
                  <div class="hcisysq-upload-group" data-role="thumbnail-wrapper">
                    <input type="file" id="hcisysq-ann-thumbnail" name="announcement_thumbnail" accept="image/*">
                    <div class="hcisysq-thumbnail-preview" data-role="thumbnail-preview" aria-live="polite"></div>
                    <button type="button" class="btn-link" data-action="remove-thumbnail" hidden>Hapus Thumbnail</button>
                  </div>
                  <p class="form-helper">Gunakan gambar rasio 16:9 berformat JPG atau PNG.</p>
                </div>
                <div class="form-group">
                  <label for="hcisysq-ann-attachments">Lampiran File (Opsional)</label>
                  <input type="file" id="hcisysq-ann-attachments" name="announcement_attachments[]" multiple>
                  <ul class="hcisysq-attachment-list" data-role="attachment-list" aria-live="polite"></ul>
                </div>
                <div class="form-actions">
                  <button type="submit" class="btn-primary" data-role="announcement-submit">Publikasikan</button>
                  <button type="button" class="btn-light" data-role="announcement-cancel" hidden>Batal</button>
                  <div class="msg" data-role="announcement-message"></div>
                </div>
              </form>
            </article>

            <article class="hcisysq-card hcisysq-card--elevated">
              <h3 class="hcisysq-card-title">Riwayat Pengumuman</h3>
              <div class="hcisysq-announcement-list" data-announcement-list>
                <?php if ($announcements): ?>
                  <?php foreach ($announcements as $item): ?>
                    <?php $updated = $item['updated_at'] ? wp_date('d M Y H:i', strtotime($item['updated_at'])) : ''; ?>
                    <?php $isTraining = ($item['link_url'] ?? '') === '__TRAINING_FORM__'; ?>
                    <div class="hcisysq-announcement-item" data-id="<?= esc_attr($item['id']) ?>">
                      <div class="hcisysq-announcement-header">
                        <div>
                          <h4><?= esc_html($item['title']) ?></h4>
                          <div class="hcisysq-announcement-meta">
                            <?= $statusBadge($item['status']) ?>
                            <?php if ($updated): ?><span>Diperbarui <?= esc_html($updated) ?></span><?php endif; ?>
                            <?php if (!empty($item['category']['label'])): ?>
                              <span class="hcisysq-announcement-category">Kategori: <?= esc_html($item['category']['label']) ?></span>
                            <?php endif; ?>
                          </div>
                        </div>
                        <div class="hcisysq-announcement-actions">
                          <button type="button" class="btn-link" data-action="edit" data-id="<?= esc_attr($item['id']) ?>">Edit</button>
                          <button type="button" class="btn-link" data-action="toggle" data-status="<?= esc_attr($item['status'] === 'archived' ? 'published' : 'archived') ?>">
                            <?= esc_html($item['status'] === 'archived' ? 'Publikasikan' : 'Arsipkan') ?>
                          </button>
                          <button type="button" class="btn-link btn-danger" data-action="delete">Hapus</button>
                        </div>
                      </div>
                      <div class="hcisysq-announcement-body"><?= wp_kses_post($item['body']) ?></div>
                      <?php if (!empty($item['attachments'])): ?>
                        <ul class="hcisysq-announcement-files">
                          <?php foreach ($item['attachments'] as $attachment): ?>
                            <li>
                              <a href="<?= esc_url($attachment['url']) ?>" target="_blank" rel="noopener">
                                <?= esc_html($attachment['title'] ?: $attachment['filename']) ?>
                              </a>
                            </li>
                          <?php endforeach; ?>
                        </ul>
                      <?php endif; ?>
                      <?php if (!empty($item['link_url'])): ?>
                        <?php
                          $href = $isTraining ? '#' : $item['link_url'];
                          $label = $item['link_label'] ?: ($isTraining ? 'Form Pelatihan Terbaru' : 'Buka tautan');
                        ?>
                        <p class="hcisysq-announcement-link">
                          <a href="<?= esc_url($href) ?>" target="_blank" rel="noopener">
                            <?= esc_html($label) ?>
                          </a>
                          <?php if ($isTraining): ?><span class="hcisysq-announcement-note">(tersedia dinamis di dashboard pegawai)</span><?php endif; ?>
                        </p>
                      <?php elseif (!empty($item['link_label'])): ?>
                        <p class="hcisysq-announcement-link"><?= esc_html($item['link_label']) ?></p>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p class="hcisysq-empty">Belum ada pengumuman.</p>
                <?php endif; ?>
              </div>
            </article>
          </section>

          <section class="hcisysq-admin-view" data-view="tugas">
            <article class="hcisysq-card hcisysq-card--elevated">
              <h3 class="hcisysq-card-title">Tambah Tugas</h3>
              <form id="hcisysq-task-form" class="hcisysq-task-form" autocomplete="off">
                <input type="hidden" name="task_id" value="">
                <input type="hidden" name="unit_ids" value="">
                <input type="hidden" name="employee_ids" value="">
                <div class="hcisysq-task-form__wrapper">
                  <div class="hcisysq-task-form__main">
                    <div class="hcisysq-form-row">
                      <label for="hcisysq-task-title" class="hcisysq-form-label">Nama Tugas <span class="req">*</span></label>
                      <div class="hcisysq-form-field">
                        <input type="text" id="hcisysq-task-title" name="title" class="hcisysq-form-control" placeholder="Masukkan nama tugas" required>
                      </div>
                    </div>
                    <div class="hcisysq-form-row">
                      <label for="hcisysq-task-description" class="hcisysq-form-label">Uraian</label>
                      <div class="hcisysq-form-field">
                        <textarea id="hcisysq-task-description" name="description" class="hcisysq-form-control hcisysq-form-control--textarea" placeholder="Tuliskan uraian tugas"></textarea>
                      </div>
                    </div>
                    <div class="hcisysq-form-row">
                      <label for="hcisysq-task-deadline" class="hcisysq-form-label">Batas Waktu</label>
                      <div class="hcisysq-form-field">
                        <input type="date" id="hcisysq-task-deadline" name="deadline" class="hcisysq-form-control">
                      </div>
                    </div>
                    <div class="hcisysq-form-row">
                      <label for="hcisysq-task-link-label" class="hcisysq-form-label">Nama Tautan</label>
                      <div class="hcisysq-form-field">
                        <input type="text" id="hcisysq-task-link-label" name="link_label" class="hcisysq-form-control" placeholder="Masukkan nama tautan (opsional)">
                      </div>
                    </div>
                    <div class="hcisysq-form-row">
                      <label for="hcisysq-task-link-url" class="hcisysq-form-label">Link Tautan</label>
                      <div class="hcisysq-form-field">
                        <input type="url" id="hcisysq-task-link-url" name="link_url" class="hcisysq-form-control" placeholder="Masukkan URL tautan (opsional)">
                      </div>
                    </div>
                  </div>

                  <section class="hcisysq-task-card" aria-labelledby="hcisysq-assignment-title">
                    <header class="hcisysq-task-card__header">
                      <h4 class="hcisysq-task-card__title" id="hcisysq-assignment-title">Tujuan Penugasan</h4>
                      <p class="hcisysq-task-card__helper">Pilih unit kerja dan pegawai yang akan menerima tugas.</p>
                    </header>
                    <div class="hcisysq-task-card__body">
                      <div class="hcisysq-form-row">
                        <label for="hcisysq-task-units" class="hcisysq-form-label">Unit <span class="req">*</span></label>
                        <div class="hcisysq-form-field">
                          <div class="hcisysq-multiselect" data-role="unit-dropdown">
                            <button type="button" class="hcisysq-multiselect__toggle" id="hcisysq-task-units" data-role="toggle" aria-haspopup="true" aria-expanded="false" aria-controls="hcisysq-task-units-panel">
                              <span class="hcisysq-multiselect__text" data-role="label">Pilih Unit</span>
                              <span class="hcisysq-multiselect__badge" data-role="badge" hidden>0</span>
                              <span class="hcisysq-multiselect__chevron" aria-hidden="true"></span>
                            </button>
                            <div class="hcisysq-multiselect__panel" id="hcisysq-task-units-panel" data-role="panel" role="group" tabindex="-1" hidden aria-labelledby="hcisysq-task-units">
                              <div class="hcisysq-multiselect__action">
                                <label class="hcisysq-multiselect__option hcisysq-multiselect__option--select-all">
                                  <input type="checkbox" data-role="select-all">
                                  <span>Pilih semua</span>
                                </label>
                              </div>
                              <div class="hcisysq-multiselect__status" data-role="status" aria-live="polite"></div>
                              <div class="hcisysq-multiselect__options" data-role="options"></div>
                            </div>
                          </div>
                          <p class="hcisysq-form-error" data-role="unit-error" hidden>Minimal pilih satu unit.</p>
                          <p class="form-helper">Pilih minimal satu unit kerja.</p>
                        </div>
                      </div>
                      <div class="hcisysq-form-row">
                        <label for="hcisysq-task-employees" class="hcisysq-form-label">Pegawai</label>
                        <div class="hcisysq-form-field">
                          <div class="hcisysq-multiselect" data-role="employee-dropdown">
                            <button type="button" class="hcisysq-multiselect__toggle" id="hcisysq-task-employees" data-role="toggle" aria-haspopup="true" aria-expanded="false" aria-controls="hcisysq-task-employees-panel">
                              <span class="hcisysq-multiselect__text" data-role="label">Pilih Pegawai</span>
                              <span class="hcisysq-multiselect__badge" data-role="badge" hidden>0</span>
                              <span class="hcisysq-multiselect__chevron" aria-hidden="true"></span>
                            </button>
                            <div class="hcisysq-multiselect__panel" id="hcisysq-task-employees-panel" data-role="panel" role="group" tabindex="-1" hidden aria-labelledby="hcisysq-task-employees">
                              <div class="hcisysq-multiselect__action">
                                <label class="hcisysq-multiselect__option hcisysq-multiselect__option--select-all">
                                  <input type="checkbox" data-role="select-all">
                                  <span>Pilih semua</span>
                                </label>
                              </div>
                              <div class="hcisysq-multiselect__status" data-role="status" aria-live="polite"></div>
                              <div class="hcisysq-multiselect__options" data-role="options"></div>
                            </div>
                          </div>
                          <p class="form-helper">Daftar pegawai mengikuti unit yang dipilih.</p>
                        </div>
                      </div>
                    </div>
                  </section>
                </div>
                <div class="form-actions">
                  <button type="submit" class="btn-primary" data-role="task-submit">Simpan Tugas</button>
                  <button type="button" class="btn-light" data-role="task-reset" hidden>Batalkan</button>
                  <div class="msg" data-role="task-message"></div>
                </div>
              </form>
            </article>

            <article class="hcisysq-card hcisysq-card--elevated">
              <h3 class="hcisysq-card-title">Daftar Tugas</h3>
              <div class="hcisysq-task-list" data-role="task-list"></div>
            </article>
          </section>

          <section class="hcisysq-admin-view" data-view="settings">
            <article class="hcisysq-card">
              <h3 class="hcisysq-card-title">Keamanan &amp; Akses Administrator</h3>
              <form id="hcisysq-admin-settings-form" class="hcisysq-form-grid">
                <div class="form-group">
                  <label for="hcisysq-admin-username">Username Administrator <span class="req">*</span></label>
                  <input type="text" id="hcisysq-admin-username" name="username" value="<?= esc_attr($publicSettings['username']) ?>" required>
                </div>
                <div class="form-group">
                  <label for="hcisysq-admin-display">Nama Tampilan</label>
                  <input type="text" id="hcisysq-admin-display" name="display_name" value="<?= esc_attr($publicSettings['display_name']) ?>">
                </div>
                <div class="form-group">
                  <label for="hcisysq-admin-password">Password Baru</label>
                  <input type="password" id="hcisysq-admin-password" name="password" placeholder="Kosongkan jika tidak diubah" autocomplete="new-password">
                </div>

                <div class="form-actions">
                  <button type="submit" class="btn-primary">Simpan Pengaturan</button>
                  <div class="msg" data-role="settings-message"></div>
                </div>
              </form>
            </article>
          </section>

          <section class="hcisysq-admin-view" data-view="pegawai">
            <article class="hcisysq-card hcisysq-card--empty">
              <h3 class="hcisysq-card-title">Data Pegawai</h3>
              <div class="hcisysq-coming-soon">
                <span class="hcisysq-coming-soon__tag">Coming Soon</span>
                <div class="hcisysq-progress" aria-hidden="true">
                  <span class="hcisysq-progress__bar"></span>
                </div>
                <p class="hcisysq-coming-soon__desc">Work in Progress. Modul Data Pegawai sedang disiapkan.</p>
              </div>
            </article>
          </section>

          <section class="hcisysq-admin-view" data-view="pelatihan">
            <article class="hcisysq-card hcisysq-card--empty">
              <h3 class="hcisysq-card-title">Pelatihan</h3>
              <div class="hcisysq-coming-soon">
                <span class="hcisysq-coming-soon__tag">Coming Soon</span>
                <div class="hcisysq-progress" aria-hidden="true">
                  <span class="hcisysq-progress__bar"></span>
                </div>
                <p class="hcisysq-coming-soon__desc">Work in Progress. Integrasi laporan pelatihan sedang dalam tahap pembangunan.</p>
              </div>
            </article>
          </section>

          <section class="hcisysq-admin-view" data-view="pengguna">
            <article class="hcisysq-card hcisysq-card--empty">
              <h3 class="hcisysq-card-title">Kelola Pengguna</h3>
              <div class="hcisysq-coming-soon">
                <span class="hcisysq-coming-soon__tag">Coming Soon</span>
                <div class="hcisysq-progress" aria-hidden="true">
                  <span class="hcisysq-progress__bar"></span>
                </div>
                <p class="hcisysq-coming-soon__desc">Work in Progress. Fitur manajemen pengguna lanjutan akan tersedia segera.</p>
              </div>
            </article>
          </section>

          <section class="hcisysq-admin-view" data-view="laporan">
            <article class="hcisysq-card hcisysq-card--empty">
              <h3 class="hcisysq-card-title">Laporan</h3>
              <div class="hcisysq-coming-soon">
                <span class="hcisysq-coming-soon__tag">Coming Soon</span>
                <div class="hcisysq-progress" aria-hidden="true">
                  <span class="hcisysq-progress__bar"></span>
                </div>
                <p class="hcisysq-coming-soon__desc">Work in Progress. Laporan analitik administrator sedang kami kembangkan.</p>
              </div>
            </article>
          </section>
        </div>
      </main>
      <div id="hrq-idle-backdrop" class="modal-backdrop" style="display:none;">
        <div class="modal">
          <h3>Sesi Akan Berakhir</h3>
          <p>Anda tidak aktif cukup lama. Otomatis keluar dalam
            <b><span id="hrq-idle-count">30</span> detik</b>.
          </p>
          <div class="modal-actions">
            <button id="hrq-idle-stay" class="btn-light">Batalkan</button>
            <button id="hrq-idle-exit" class="btn-primary">Keluar Sekarang</button>
          </div>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

  /* ========== DASHBOARD PAGE ========== */
  public static function dashboard(){
    $identity = Auth::current_identity();
    if (!$identity) { wp_safe_redirect(home_url('/' . HCISYSQ_LOGIN_SLUG . '/')); exit; }

    if ($identity['type'] === 'admin') {
      return self::render_admin_dashboard($identity);
    }

    $me = $identity['user'];
    $resolve = function(array $keys) use ($me){
      foreach ($keys as $key) {
        if (!isset($me->$key)) continue;
        $value = $me->$key;
        if (is_scalar($value)) {
          $value = trim((string)$value);
        } else {
          $value = '';
        }
        if ($value !== '') return $value;
      }
      return '';
    };
    $unit    = $resolve(['unit','unit_kerja','unitkerja','unitkerja_nama']);
    $jabatan = $resolve(['jabatan','posisi','position']);
    $hp      = $resolve(['no_hp','hp','telepon','phone']);
    $email   = $resolve(['email','mail']);
    $tempat  = $resolve(['tempat_lahir','tempatlahir','birth_place']);
    $tanggal = $resolve(['tanggal_lahir','tgl_lahir','birth_date']);
    $tmt     = $resolve(['tmt','tmt_mulai','tanggal_mulai']);
    $nik     = $resolve(['nik','no_ktp','ktp','nik_ktp','no_ktp_kk']);

    $tmtFormatted = '';
    if ($tmt) {
      try {
        $dt = new \DateTimeImmutable($tmt);
        $formatter = new \IntlDateFormatter(
          'id_ID',
          \IntlDateFormatter::LONG,
          \IntlDateFormatter::NONE,
          'Asia/Jakarta',
          \IntlDateFormatter::GREGORIAN,
          'd MMMM yyyy'
        );
        $tmtFormatted = $formatter->format($dt);
      } catch (\Exception $e) {
        $tmtFormatted = $tmt;
      }
    }

    $masaKerja = '';
    if ($tmt) {
      try {
        $d1 = new \DateTimeImmutable($tmt);
        $d2 = new \DateTimeImmutable('now');
        $diff = $d1->diff($d2);
        $y = (int)$diff->y;
        $m = (int)$diff->m;
        if ($y > 0 && $m > 0) {
          $masaKerja = $y.' tahun '.$m.' bulan';
        } elseif ($y > 0) {
          $masaKerja = $y.' tahun';
        } elseif ($m > 0) {
          $masaKerja = $m.' bulan';
        } else {
          $masaKerja = 'Kurang dari 1 bulan';
        }
      } catch (\Exception $e) {
        $masaKerja = '';
      }
    }

    $alamatUtama = $resolve(['alamat','alamat_ktp','alamat_domisili','alamatdomisili','alamat_rumah']);
    $alamatParts = array_filter([
      $alamatUtama,
      $resolve(['desa','kelurahan','desa_kelurahan']),
      $resolve(['kecamatan']),
      $resolve(['kota','kabupaten','kota_kabupaten']),
      $resolve(['kode_pos','kodepos'])
    ], function($val){ return $val !== ''; });
    $alamatFull = $alamatParts ? implode(', ', $alamatParts) : '';

    $profilRingkasRows = [
      ['label' => 'Nama', 'value' => isset($me->nama) ? trim((string)$me->nama) : ''],
      ['label' => 'NIK',  'value' => $nik],
      ['label' => 'Tempat & Tanggal Lahir', 'value' => trim($tempat . ($tempat && $tanggal ? ', ' : '') . $tanggal)],
      ['label' => 'Alamat', 'value' => $alamatFull],
      ['label' => 'HP', 'value' => $hp],
      ['label' => 'Email', 'value' => $email],
    ];

    $kepegawaianRows = [
      ['label' => 'NIP',        'value' => isset($me->nip) ? trim((string)$me->nip) : ''],
      ['label' => 'Jabatan',    'value' => $jabatan !== '' ? $jabatan : ($me->jabatan ?? '')],
      ['label' => 'Unit Kerja', 'value' => $unit   !== '' ? $unit   : ($me->unit   ?? '')],
      ['label' => 'TMT',        'value' => $tmtFormatted],
      ['label' => 'Masa Kerja', 'value' => $masaKerja],
    ];

    wp_enqueue_style('hcisysq-dashboard');
    wp_enqueue_script('hcisysq-dashboard');

    $formSlug = defined('HCISYSQ_FORM_SLUG') ? trim((string)HCISYSQ_FORM_SLUG, '/') : 'pelatihan';
    $trainingLink = add_query_arg(
      [
        'nip'  => (string)($me->nip ?? ''),
        'nama' => (string)($me->nama ?? ''),
      ],
      home_url('/' . ($formSlug !== '' ? $formSlug . '/' : ''))
    );

    $announcements = Announcements::published_for_user([
      'training_link' => $trainingLink,
    ]);

    $tasksData = Tasks::get_employee_tasks($me->nip ?? '');
    $employeeUpdates = $tasksData['items'];
    $pendingTaskCount = (int)($tasksData['pending'] ?? 0);

    if (empty($employeeUpdates)) {
      $legacyUpdates = self::get_legacy_employee_updates();
      if (!empty($legacyUpdates)) {
        $employeeUpdates = $legacyUpdates;
      } elseif (!empty($announcements)) {
        foreach ($announcements as $item) {
          if (($item['category'] ?? '') && $item['category'] !== 'pengumuman') {
            continue;
          }

          $title = sanitize_text_field($item['title'] ?? '');
          $body  = isset($item['body']) ? RichText::sanitize($item['body']) : '';
          $linkUrl   = isset($item['link_url']) ? esc_url_raw($item['link_url']) : '';
          $linkLabel = sanitize_text_field($item['link_label'] ?? '');

          if ($title === '' && $body === '' && $linkUrl === '') {
            continue;
          }

          $employeeUpdates[] = [
            'task'        => $title,
            'description' => $body,
            'deadline'    => '',
            'deadline_display' => '',
            'link_url'    => $linkUrl,
            'link_label'  => $linkLabel,
            'status'      => 'info',
          ];
        }
      }
    }

    $comingSoonModules = [
      'profil' => [
        'title' => 'Profil',
        'description' => 'Work in Progress. Modul profil pegawai sedang disiapkan.'
      ],
      'slip-gaji' => [
        'title' => 'Slip Gaji',
        'description' => 'Work in Progress. Slip gaji digital akan segera tersedia.'
      ],
      'rekap-absensi' => [
        'title' => 'Rekap Absensi',
        'description' => 'Work in Progress. Rekap absensi otomatis sedang kami kembangkan.'
      ],
      'riwayat-kepegawaian' => [
        'title' => 'Riwayat Kepegawaian',
        'description' => 'Work in Progress. Riwayat kepegawaian detail akan hadir segera.'
      ],
      'cuti-izin' => [
        'title' => 'Cuti & Izin',
        'description' => 'Work in Progress. Pengajuan cuti & izin online sedang dipersiapkan.'
      ],
      'penilaian-kinerja' => [
        'title' => 'Penilaian Kinerja',
        'description' => 'Work in Progress. Dashboard penilaian kinerja sedang kami desain.'
      ],
      'administrasi-lain' => [
        'title' => 'Administrasi Lain',
        'description' => 'Work in Progress. Dokumen administrasi lainnya sedang diintegrasikan.'
      ],
      'panduan' => [
        'title' => 'Panduan',
        'description' => 'Work in Progress. Pusat panduan pegawai sedang kami susun.'
      ],
      'support' => [
        'title' => 'Support',
        'description' => 'Work in Progress. Layanan bantuan digital akan segera tersedia.'
      ],
    ];

    $rawTickerOptions = get_option('hcisysq_home_marquee_options', []);
    $tickerDefaults = [
      'speed'          => 1.0,
      'background'     => '#2f7e20',
      'gap'            => 32,
      'letter_spacing' => 0.0,
    ];
    $tickerOptions = $tickerDefaults;
    if (is_array($rawTickerOptions)) {
      foreach ($tickerDefaults as $key => $default) {
        if (array_key_exists($key, $rawTickerOptions)) {
          $tickerOptions[$key] = $rawTickerOptions[$key];
        }
      }
    }

    $tickerSpeed = (float) $tickerOptions['speed'];
    if ($tickerSpeed <= 0) {
      $tickerSpeed = 1.0;
    }
    if ($tickerSpeed > 5) {
      $tickerSpeed = 5.0;
    }

    $tickerGap = absint($tickerOptions['gap']);
    if ($tickerGap < 12) {
      $tickerGap = 12;
    }
    if ($tickerGap > 160) {
      $tickerGap = 160;
    }

    $tickerLetter = (float) $tickerOptions['letter_spacing'];
    if ($tickerLetter < 0) {
      $tickerLetter = 0.0;
    }
    if ($tickerLetter > 10) {
      $tickerLetter = 10.0;
    }

    $tickerBackground = isset($tickerOptions['background']) ? sanitize_hex_color($tickerOptions['background']) : '';
    if (!$tickerBackground) {
      $tickerBackground = '#2f7e20';
    }

    $rawTicker = get_option('hcisysq_home_marquee_text', '');
    $tickerItems = [];
    if (is_string($rawTicker) && trim($rawTicker) !== '') {
      $cleanTicker = RichText::sanitize($rawTicker);
      if ($cleanTicker !== '') {
        $normalized = preg_replace(
          ['/\r\n|\r/', '/<\s*br\s*\/?\s*>/i', '/<\/p>/i', '/<\/li>/i', '/<\/ul>/i', '/<\/ol>/i'],
          ["\n", "\n", "\n", "\n", "\n", "\n"],
          $cleanTicker
        );
        $text = wp_strip_all_tags($normalized);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $lines = preg_split('/[\r\n]+/', $text);
        $processed = [];
        if (is_array($lines)) {
          foreach ($lines as $line) {
            $collapsed = trim(preg_replace('/\s+/', ' ', (string) $line));
            if ($collapsed !== '') {
              $processed[] = $collapsed;
            }
          }
        }
        if (empty($processed)) {
          $single = trim(preg_replace('/\s+/', ' ', $text));
          if ($single !== '') {
            $processed[] = $single;
          }
        }
        $tickerItems = $processed;
      }
    }

    $tickerItemsJson = wp_json_encode($tickerItems);
    if (!is_string($tickerItemsJson)) {
      $tickerItemsJson = '[]';
    }

    $format_number = static function ($value) {
      if (!is_numeric($value)) {
        return '0';
      }
      if (abs($value - round($value)) < 0.0001) {
        return (string) intval(round($value));
      }
      return rtrim(rtrim(sprintf('%.2f', $value), '0'), '.');
    };

    $tickerGapAttr = (string) $tickerGap;
    $tickerLetterAttr = $format_number($tickerLetter);
    $tickerSpeedAttr = $format_number($tickerSpeed);
    $tickerHiddenAttr = empty($tickerItems) ? ' hidden="hidden"' : '';

    ob_start(); ?>
    <div class="hcisysq-dashboard" id="hcisysq-dashboard">

      <!-- Sidebar -->
      <aside class="hcisysq-sidebar" id="hcisysq-sidebar" aria-label="Navigasi utama">
        <div class="hcisysq-sidebar-header">
          <span class="hcisysq-sidebar-logo">SQ Pegawai</span>
          <button type="button" class="hcisysq-icon-button hcisysq-sidebar-close" id="hcisysq-sidebar-close" aria-label="Tutup menu navigasi">
            <span aria-hidden="true">×</span>
          </button>
        </div>
        <nav class="hcisysq-sidebar-nav">
          <a class="is-active" href="#dashboard" data-section="dashboard">Dashboard</a>
          <a href="#profil" data-section="profil">Profil</a>
          <a href="#slip-gaji" data-section="slip-gaji">Slip Gaji</a>
          <a href="#rekap-absensi" data-section="rekap-absensi">Rekap Absensi</a>
          <a href="#riwayat-kepegawaian" data-section="riwayat-kepegawaian">Riwayat Kepegawaian</a>
          <a href="#cuti-izin" data-section="cuti-izin">Cuti &amp; Izin</a>
          <a href="#penilaian-kinerja" data-section="penilaian-kinerja">Penilaian Kinerja</a>
          <a href="#tugas-komunikasi" data-section="tugas-komunikasi">
            Tugas
            <?php if (!empty($pendingTaskCount)): ?>
              <span class="hcisysq-task-badge" data-role="task-pending-count"><?= esc_html($pendingTaskCount) ?></span>
            <?php endif; ?>
          </a>
          <a href="#administrasi-lain" data-section="administrasi-lain">Administrasi Lain</a>
          <hr>
          <a href="#panduan" data-section="panduan">Panduan</a>
          <a href="#support" data-section="support">Support</a>
        </nav>
        <div class="hcisysq-sidebar-meta">
          <span>Versi <?= esc_html(HCISYSQ_VER) ?></span>
        </div>
      </aside>

      <div class="hcisysq-sidebar-overlay" id="hcisysq-sidebar-overlay" aria-hidden="true"></div>

      <!-- Main -->
      <main class="hcisysq-main">
        <header class="hcisysq-topbar">
          <div class="hcisysq-topbar-left">
            <button type="button" class="hcisysq-icon-button hcisysq-menu-toggle" id="hcisysq-sidebar-toggle" aria-label="Buka menu navigasi" aria-expanded="true">
              <span></span>
              <span></span>
              <span></span>
            </button>
            <div>
              <h1 class="hcisysq-page-title">Dashboard Pegawai</h1>
              <p class="hcisysq-page-subtitle">Ringkasan informasi kepegawaian</p>
            </div>
          </div>
          <div class="hcisysq-user">
            <div class="hcisysq-user-meta">
              <span class="hcisysq-user-name"><?= esc_html($me->nama) ?></span>
              <span class="hcisysq-user-role">NIP: <?= esc_html($me->nip ?? '-') ?></span>
            </div>
            <button type="button" class="btn-light" id="hcisysq-logout">Keluar</button>
          </div>
        </header>

        <div class="hcisysq-main-body">
          <section id="dashboard" class="hcisysq-dashboard-section is-active" data-section="dashboard" tabindex="-1">
            <div
              class="ysq-running hcisysq-running"
              data-role="running-text"
              data-items="<?= esc_attr($tickerItemsJson) ?>"
              data-gap="<?= esc_attr($tickerGapAttr) ?>"
              data-letter="<?= esc_attr($tickerLetterAttr) ?>"
              data-bg="<?= esc_attr($tickerBackground) ?>"
              data-speed="<?= esc_attr($tickerSpeedAttr) ?>"
              aria-live="polite"
              <?= $tickerHiddenAttr ?>
            >
              <div class="ysq-track hcisysq-running__track" data-role="running-track"></div>
            </div>
            <section class="hcisysq-card-grid hcisysq-card-grid--2">
              <article class="hcisysq-card">
                <h3 class="hcisysq-card-title">Profil Ringkas</h3>
                <dl class="hcisysq-meta-list">
                  <?php foreach ($profilRingkasRows as $row): ?>
                    <div>
                      <dt><?= esc_html($row['label']) ?></dt>
                      <dd><?= esc_html($row['value'] !== '' ? $row['value'] : '-') ?></dd>
                    </div>
                  <?php endforeach; ?>
                </dl>
              </article>

              <article class="hcisysq-card">
                <h3 class="hcisysq-card-title">Data Kepegawaian</h3>
                <dl class="hcisysq-meta-list">
                  <?php foreach ($kepegawaianRows as $row): ?>
                    <div>
                      <dt><?= esc_html($row['label']) ?></dt>
                      <dd><?= esc_html($row['value'] !== '' ? $row['value'] : '-') ?></dd>
                    </div>
                  <?php endforeach; ?>
                </dl>
              </article>
            </section>

          </section>

          <section id="tugas-komunikasi" class="hcisysq-dashboard-section" data-section="tugas-komunikasi" tabindex="-1">
            <section class="hcisysq-card-grid hcisysq-card-grid--1">
              <article class="hcisysq-card">
                <h3 class="hcisysq-card-title">Daftar Tugas Pegawai</h3>
                <p>Pantau tugas yang perlu Anda selesaikan sesuai penugasan dari tim HCIS.</p>
                <?php if (!empty($employeeUpdates)): ?>
                  <div class="hcisysq-updates">
                    <table>
                      <thead>
                        <tr>
                          <th scope="col">No.</th>
                          <th scope="col">Nama Tugas</th>
                          <th scope="col">Uraian</th>
                          <th scope="col">Batas Waktu</th>
                          <th scope="col">Nama Tautan</th>
                          <th scope="col">Link Tautan</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($employeeUpdates as $index => $update): ?>
                          <?php
                          $deadlineDisplay = isset($update['deadline_display']) ? trim((string)$update['deadline_display']) : '';
                          $deadlineRaw = isset($update['deadline']) ? trim((string)$update['deadline']) : '';
                          if ($deadlineDisplay === '' && $deadlineRaw !== '') {
                            try {
                              $deadlineDate = new \DateTimeImmutable($deadlineRaw);
                              $deadlineFormatter = new \IntlDateFormatter(
                                'id_ID',
                                \IntlDateFormatter::LONG,
                                \IntlDateFormatter::NONE,
                                'Asia/Jakarta',
                                \IntlDateFormatter::GREGORIAN,
                                'd MMMM yyyy'
                              );
                              $deadlineDisplay = $deadlineFormatter->format($deadlineDate);
                            } catch (\Exception $e) {
                              $deadlineDisplay = $deadlineRaw;
                            }
                          }
                          if ($deadlineDisplay === '') {
                            $deadlineDisplay = '-';
                          }

                          $status = isset($update['status']) ? $update['status'] : 'pending';
                          $statusClass = 'is-pending';
                          $statusLabel = 'Belum Selesai';
                          if ($status === 'completed') {
                            $statusClass = 'is-done';
                            $statusLabel = 'Selesai';
                          } elseif ($status === 'info') {
                            $statusClass = 'is-info';
                            $statusLabel = 'Informasi';
                          }

                          $completedNote = '';
                          $completedRaw = isset($update['completed_at']) ? trim((string)$update['completed_at']) : '';
                          if ($status === 'completed' && $completedRaw !== '') {
                            try {
                              $completedDate = new \DateTimeImmutable($completedRaw);
                              $completedFormatter = new \IntlDateFormatter(
                                'id_ID',
                                \IntlDateFormatter::LONG,
                                \IntlDateFormatter::NONE,
                                'Asia/Jakarta',
                                \IntlDateFormatter::GREGORIAN,
                                'd MMMM yyyy'
                              );
                              $completedNote = $completedFormatter->format($completedDate);
                            } catch (\Exception $e) {
                              $completedNote = $completedRaw;
                            }
                          }

                          $description = isset($update['description']) ? $update['description'] : '';
                          $linkUrl = isset($update['link_url']) ? $update['link_url'] : '';
                          $linkLabel = isset($update['link_label']) ? $update['link_label'] : '';
                          ?>
                          <tr>
                            <td><?= esc_html($index + 1) ?></td>
                            <td>
                              <div class="hcisysq-task-name">
                                <span class="hcisysq-task-name__title"><?= esc_html($update['task'] ?? '') ?></span>
                                <span class="hcisysq-status-chip <?= esc_attr($statusClass) ?>"><?= esc_html($statusLabel) ?></span>
                                <?php if ($completedNote !== ''): ?>
                                  <div class="hcisysq-status-meta"><?= esc_html($completedNote) ?></div>
                                <?php endif; ?>
                              </div>
                            </td>
                            <td>
                              <?php if ($description !== ''): ?>
                                <?= wp_kses_post($description) ?>
                              <?php else: ?>
                                <span>-</span>
                              <?php endif; ?>
                            </td>
                            <td><?= esc_html($deadlineDisplay) ?></td>
                            <td>
                              <?php if ($linkLabel !== ''): ?>
                                <?= esc_html($linkLabel) ?>
                              <?php else: ?>
                                <span>-</span>
                              <?php endif; ?>
                            </td>
                            <td>
                              <?php if ($linkUrl !== ''): ?>
                                <a href="<?= esc_url($linkUrl) ?>" target="_blank" rel="noopener">
                                  <?= esc_html($linkUrl) ?>
                                </a>
                              <?php else: ?>
                                <span>-</span>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <p class="hcisysq-updates__empty">Belum ada tugas aktif saat ini.</p>
                <?php endif; ?>
              </article>
            </section>
          </section>

          <?php foreach ($comingSoonModules as $slug => $module): ?>
            <section id="<?= esc_attr($slug) ?>" class="hcisysq-dashboard-section" data-section="<?= esc_attr($slug) ?>" tabindex="-1">
              <section class="hcisysq-card-grid hcisysq-card-grid--1">
                <article class="hcisysq-card hcisysq-card--wip">
                  <div class="hcisysq-wip">
                    <div class="hcisysq-wip__illustration" aria-hidden="true">
                      <svg class="hcisysq-wip__art" viewBox="0 0 64 64" role="img" aria-hidden="true">
                        <g fill="currentColor" opacity="0.75">
                          <rect x="8" y="36" width="24" height="6" rx="3"></rect>
                          <rect x="12" y="24" width="40" height="6" rx="3" opacity="0.7"></rect>
                          <rect x="20" y="12" width="32" height="6" rx="3" opacity="0.5"></rect>
                          <circle cx="20" cy="46" r="4" opacity="0.45"></circle>
                          <circle cx="44" cy="30" r="4" opacity="0.6"></circle>
                          <circle cx="34" cy="18" r="4" opacity="0.4"></circle>
                        </g>
                      </svg>
                    </div>
                    <div class="hcisysq-wip__body">
                      <span class="hcisysq-coming-soon__tag">Work in Progress</span>
                      <h3 class="hcisysq-card-title"><?= esc_html($module['title']) ?></h3>
                      <p class="hcisysq-coming-soon__desc"><?= esc_html($module['description']) ?></p>
                      <div class="hcisysq-progress" aria-hidden="true">
                        <span class="hcisysq-progress__bar"></span>
                      </div>
                    </div>
                  </div>
                </article>
              </section>
            </section>
          <?php endforeach; ?>
        </div>
      </main>
    </div>

      <!-- Modal Auto-Logout (Idle) -->
      <div id="hrq-idle-backdrop" class="modal-backdrop" style="display:none;">
        <div class="modal">
          <h3>Sesi Akan Berakhir</h3>
          <p>Anda tidak aktif cukup lama. Otomatis keluar dalam
            <b><span id="hrq-idle-count">30</span> detik</b>.
          </p>
          <div class="modal-actions">
            <button id="hrq-idle-stay" class="btn-light">Batalkan</button>
            <button id="hrq-idle-exit" class="btn-primary">Keluar Sekarang</button>
          </div>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

  private static function get_legacy_employee_updates(){
    $raw = get_option('hcisysq_employee_updates', []);
    if (!is_array($raw)) {
      return [];
    }

    $items = [];

    foreach ($raw as $row) {
      if (!is_array($row)) {
        continue;
      }

      $task = sanitize_text_field($row['task'] ?? '');
      $deadline = sanitize_text_field($row['deadline'] ?? '');
      $linkUrl = '';
      if (!empty($row['link_url'])) {
        $linkUrl = esc_url_raw($row['link_url']);
      } elseif (!empty($row['link'])) {
        $linkUrl = esc_url_raw($row['link']);
      }
      $linkLabel = sanitize_text_field($row['link_label'] ?? '');

      $descriptionRaw = isset($row['description']) ? $row['description'] : '';
      $description = '';
      if (is_string($descriptionRaw) && trim($descriptionRaw) !== '') {
        $description = RichText::sanitize($descriptionRaw);
      }

      if ($task === '' && $description === '' && $deadline === '' && $linkUrl === '') {
        continue;
      }

      $items[] = [
        'task'        => $task,
        'description' => $description,
        'deadline'    => $deadline,
        'deadline_display' => $deadline,
        'link_url'    => $linkUrl,
        'link_label'  => $linkLabel,
        'status'      => 'info',
      ];
    }

    return $items;
  }
}
