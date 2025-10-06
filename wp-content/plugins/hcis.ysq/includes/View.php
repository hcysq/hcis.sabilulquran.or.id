<?php
namespace HCISYSQ;

if (!defined('ABSPATH')) exit;

class View {

  /* ========== LOGIN PAGE ========== */
  public static function login(){
    wp_enqueue_style('hcisysq');
    wp_enqueue_script('hcisysq');
    ob_start(); ?>
    <div class="hcisysq-auth-wrap">
      <div class="auth-card">
        <div class="auth-header">
          <h2>Masuk ke Akun Guru/Pegawai</h2>
        </div>

        <form id="hcisysq-login-form" class="auth-form" method="post" autocomplete="off">
          <label for="hcisysq-nip">Akun <span class="req">*</span></label>
          <input id="hcisysq-nip" type="text" name="nip" placeholder="Masukkan NIP" autocomplete="username" required>

          <label for="hcisysq-pw">Pasword <span class="req">*</span></label>
          <div class="pw-row">
            <input id="hcisysq-pw" type="password" name="pw" placeholder="Gunakan No HP" autocomplete="current-password" required>
            <button type="button" id="hcisysq-eye" class="eye">lihat</button>
          </div>

          <button type="submit" class="btn-primary">Masuk</button>
          <button type="button" id="hcisysq-forgot" class="link-forgot">Lupa pasword?</button>
          <div class="msg" aria-live="polite"></div>
        </form>
      </div>
    <!-- Modal Lupa Password -->
    <div id="hcisysq-modal" class="modal-backdrop" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="hcisysq-forgot-title">
      <div class="modal">
        <button type="button" class="modal-close" id="hcisysq-close-modal" aria-label="Tutup">×</button>
        <h3 id="hcisysq-forgot-title">Lupa Pasword</h3>
        <p>Masukkan Akun (NIP) Anda. Kami akan mengirim permintaan ke Admin HCM.</p>
        <label>Akun (NIP)</label>
        <input id="hcisysq-nip-forgot" type="text" placeholder="Masukkan NIP">
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

    wp_enqueue_style('hcisysq');
    wp_enqueue_script('hcisysq');

    $inline = [
      'announcements' => $announcements,
      'settings'      => $publicSettings,
      'home'          => $homeSettings,
    ];
    wp_add_inline_script('hcisysq', 'window.hcisysqAdmin = ' . wp_json_encode($inline) . ';', 'before');

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

    wp_enqueue_style('hcisysq');
    wp_enqueue_script('hcisysq');

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
      'tugas-komunikasi' => [
        'title' => 'Tugas & Komunikasi',
        'description' => 'Work in Progress. Kolaborasi tugas & komunikasi internal segera hadir.'
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

    $marqueeData = self::get_dashboard_marquee_data();
    $marqueeItemsJson = '';
    if (!empty($marqueeData['items'])) {
      $encoded = wp_json_encode($marqueeData['items'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
      if (false !== $encoded) {
        $marqueeItemsJson = $encoded;
      }
    }

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
          <a href="#tugas-komunikasi" data-section="tugas-komunikasi">Tugas &amp; Komunikasi</a>
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
            <?php if ($marqueeItemsJson) : ?>
              <div class="hcisysq-running" data-role="running-text" role="region" aria-label="<?= esc_attr__('Informasi berjalan', 'hcisysq') ?>" aria-live="polite"
                   data-items="<?= esc_attr($marqueeItemsJson) ?>"
                   data-speed="<?= esc_attr($marqueeData['options']['speed']) ?>"
                   data-gap="<?= esc_attr($marqueeData['options']['gap']) ?>"
                   data-letter="<?= esc_attr($marqueeData['options']['letter_spacing']) ?>"
                   data-bg="<?= esc_attr($marqueeData['options']['background']) ?>">
                <div class="hcisysq-running__track" data-role="running-track"></div>
              </div>
            <?php endif; ?>
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

            <section class="hcisysq-card-grid hcisysq-card-grid--1">
              <article class="hcisysq-card">
                <h3 class="hcisysq-card-title">Pengumuman</h3>
                <?php if (!empty($announcements)): ?>
                  <ul class="hcisysq-bullet-list">
                    <?php foreach ($announcements as $item): ?>
                      <li>
                        <strong><?= esc_html($item['title']) ?></strong>
                        <?php if (!empty($item['body'])): ?>
                          <div class="hcisysq-bullet-body"><?= wp_kses_post($item['body']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($item['link_url'])): ?>
                          <div class="hcisysq-bullet-link">
                            <a href="<?= esc_url($item['link_url']) ?>" target="_blank" rel="noopener">
                              <?= esc_html($item['link_label'] ?: 'Buka tautan') ?>
                            </a>
                          </div>
                        <?php elseif (!empty($item['link_label'])): ?>
                          <div class="hcisysq-bullet-link"><?= esc_html($item['link_label']) ?></div>
                        <?php endif; ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <p>Tidak ada pengumuman terbaru.</p>
                <?php endif; ?>
              </article>
            </section>
          </section>

          <?php foreach ($comingSoonModules as $slug => $module): ?>
            <section id="<?= esc_attr($slug) ?>" class="hcisysq-dashboard-section" data-section="<?= esc_attr($slug) ?>" tabindex="-1">
              <section class="hcisysq-card-grid hcisysq-card-grid--1">
                <article class="hcisysq-card hcisysq-card--empty">
                  <h3 class="hcisysq-card-title"><?= esc_html($module['title']) ?></h3>
                  <div class="hcisysq-coming-soon">
                    <span class="hcisysq-coming-soon__tag">Coming Soon</span>
                    <div class="hcisysq-progress" aria-hidden="true">
                      <span class="hcisysq-progress__bar"></span>
                    </div>
                    <p class="hcisysq-coming-soon__desc"><?= esc_html($module['description']) ?></p>
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

  private static function get_dashboard_marquee_data(){
    $rawText = get_option('hcisysq_home_marquee_text', '');
    if (!is_string($rawText)) {
      $rawText = '';
    } else {
      $rawText = trim($rawText);
    }

    if ($rawText !== '') {
      $rawText = RichText::sanitize($rawText);
    }

    $items = [];

    if ($rawText !== '') {
      $allowed = [
        'p' => [],
        'br' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'span' => ['style' => true],
        'strong' => [],
        'em' => [],
      ];

      $html = wp_kses($rawText, $allowed);

      if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $html, $matches)) {
        foreach ($matches[1] as $segment) {
          $text = trim(wp_strip_all_tags($segment));
          if ($text !== '') {
            $items[] = html_entity_decode($text, ENT_QUOTES, get_bloginfo('charset'));
          }
        }
      }

      if (empty($items)) {
        $plain = trim(wp_strip_all_tags($html));
        if ($plain !== '') {
          $segments = preg_split("/\r\n|\r|\n/", $plain);
          if (is_array($segments)) {
            foreach ($segments as $segment) {
              $segment = trim($segment);
              if ($segment !== '') {
                $items[] = html_entity_decode($segment, ENT_QUOTES, get_bloginfo('charset'));
              }
            }
          }

          if (empty($items)) {
            $items[] = html_entity_decode($plain, ENT_QUOTES, get_bloginfo('charset'));
          }
        }
      }
    }

    $items = array_map('trim', $items);
    $items = array_filter($items, static function ($value) {
      return $value !== '';
    });
    $items = array_values(array_unique($items));
    if (count($items) > 20) {
      $items = array_slice($items, 0, 20);
    }

    $optionsRaw = get_option('hcisysq_home_marquee_options', []);
    if (!is_array($optionsRaw)) {
      $optionsRaw = [];
    }

    $speed = isset($optionsRaw['speed']) ? (float) $optionsRaw['speed'] : 1.0;
    if ($speed < 0.5) {
      $speed = 0.5;
    } elseif ($speed > 3.0) {
      $speed = 3.0;
    }

    $gap = isset($optionsRaw['gap']) ? (int) $optionsRaw['gap'] : 32;
    if ($gap < 8) {
      $gap = 8;
    } elseif ($gap > 160) {
      $gap = 160;
    }

    $letterSpacing = isset($optionsRaw['letter_spacing']) ? (float) $optionsRaw['letter_spacing'] : 0.0;
    if ($letterSpacing < 0) {
      $letterSpacing = 0.0;
    } elseif ($letterSpacing > 10) {
      $letterSpacing = 10.0;
    }

    $background = isset($optionsRaw['background']) ? sanitize_hex_color($optionsRaw['background']) : '';
    if (!$background) {
      $background = '#ffffff';
    }

    return [
      'items' => $items,
      'options' => [
        'speed' => $speed,
        'gap' => $gap,
        'letter_spacing' => $letterSpacing,
        'background' => $background,
      ],
    ];
  }

  /* ========== FORM PELATIHAN ========== */
  public static function form(){
    $me = Auth::current_user();
    if (!$me) { wp_safe_redirect(home_url('/' . HCISYSQ_LOGIN_SLUG . '/')); exit; }

    wp_enqueue_style('hcisysq');
    wp_enqueue_script('hcisysq');

    ob_start(); ?>
    <div id="hcisysq-app" class="hcisysq-app">
      <div class="hcisysq-form-wrap">
        <h2>Form Riwayat Pelatihan</h2>
        <p>Lengkapi data pelatihan yang telah Anda ikuti.</p>

        <form id="hcisysq-training-form" enctype="multipart/form-data" class="training-form">
          <div class="form-group">
            <label>Nama Pelatihan <span class="req">*</span></label>
            <input type="text" name="nama_pelatihan" placeholder="Contoh: Workshop Laravel" required>
          </div>

          <div class="form-group">
            <label>Tahun <span class="req">*</span></label>
            <input type="number" name="tahun" placeholder="2024" min="1990" max="2099" required>
          </div>

          <div class="form-group">
            <label>Pembiayaan <span class="req">*</span></label>
            <select name="pembiayaan" required>
              <option value="">Pilih Pembiayaan</option>
              <option value="mandiri">Mandiri</option>
              <option value="yayasan">Yayasan</option>
            </select>
          </div>

          <div class="form-group">
            <label>Kategori <span class="req">*</span></label>
            <select name="kategori" required>
              <option value="">Pilih Kategori</option>
              <option value="hard">Hard Skill</option>
              <option value="soft">Soft Skill</option>
            </select>
          </div>

          <div class="form-group">
            <label>Upload Sertifikat (opsional)</label>
            <input type="file" name="sertifikat" accept=".pdf,.jpg,.jpeg,.png">
            <small>Format: PDF, JPG, PNG (max 5MB)</small>
          </div>

          <button type="submit" class="btn-primary">Simpan</button>
          <a href="<?= esc_url(home_url('/' . HCISYSQ_DASHBOARD_SLUG . '/')) ?>" class="btn-light">Batal</a>
        </form>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }
}
