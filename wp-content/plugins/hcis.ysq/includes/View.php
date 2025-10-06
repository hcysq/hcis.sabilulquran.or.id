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
    $homeSettings   = [
      'marquee_text' => is_string($homeMarquee) ? RichText::sanitize($homeMarquee) : '',
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
            <article class="hcisysq-card">
              <h3 class="hcisysq-card-title">Pengaturan Beranda HCIS</h3>
              <form id="hcisysq-home-settings-form" class="hcisysq-form-grid">
                <div class="form-group">
                  <label for="hcisysq-home-marquee-editor">Running Text</label>
                  <div class="hcisysq-editor" data-editor-wrapper data-editor-name="marquee_text">
                    <div class="hcisysq-editor__toolbar" role="toolbar" aria-label="Format teks running text">
                      <button type="button" class="hcisysq-editor__button" data-command="bold" title="Tebal"><span aria-hidden="true">B</span><span class="screen-reader-text">Tebal</span></button>
                      <button type="button" class="hcisysq-editor__button" data-command="italic" title="Miring"><span aria-hidden="true">I</span><span class="screen-reader-text">Miring</span></button>
                      <button type="button" class="hcisysq-editor__button" data-command="unorderedList" title="Bullet"><span aria-hidden="true">•</span><span class="screen-reader-text">Bullet</span></button>
                      <button type="button" class="hcisysq-editor__button" data-command="orderedList" title="Penomoran"><span aria-hidden="true">1.</span><span class="screen-reader-text">Penomoran</span></button>
                      <div class="hcisysq-editor__divider" aria-hidden="true"></div>
                      <label class="screen-reader-text" for="hcisysq-marquee-font">Pilih font</label>
                      <select id="hcisysq-marquee-font" class="hcisysq-editor__select" data-command="fontFamily">
                        <option value="">Font default</option>
                        <option value="arial">Arial</option>
                        <option value="helvetica">Helvetica</option>
                        <option value="times new roman">Times New Roman</option>
                      </select>
                      <label class="screen-reader-text" for="hcisysq-marquee-size">Ukuran teks</label>
                      <select id="hcisysq-marquee-size" class="hcisysq-editor__select" data-command="fontSize">
                        <option value="">Ukuran</option>
                        <option value="12">12 px</option>
                        <option value="14">14 px</option>
                        <option value="16">16 px</option>
                        <option value="18">18 px</option>
                        <option value="20">20 px</option>
                        <option value="24">24 px</option>
                      </select>
                      <div class="hcisysq-editor__divider" aria-hidden="true"></div>
                      <button type="button" class="hcisysq-editor__button" data-command="clear" title="Bersihkan format"><span aria-hidden="true">⟲</span><span class="screen-reader-text">Bersihkan format</span></button>
                    </div>
                    <div id="hcisysq-home-marquee-editor" class="hcisysq-editor__content" data-editor-content contenteditable="true" role="textbox" aria-multiline="true" aria-describedby="hcisysq-home-marquee-help">
                      <?= $homeSettings['marquee_text'] !== '' ? $homeSettings['marquee_text'] : '<p></p>' ?>
                    </div>
                    <textarea id="hcisysq-home-marquee" name="marquee_text" data-editor-input hidden><?= esc_textarea($homeSettings['marquee_text']) ?></textarea>
                  </div>
                  <p class="form-helper" id="hcisysq-home-marquee-help">Teks akan bergerak di halaman beranda. Gunakan enter untuk item baru. Biarkan kosong jika tidak ingin ditampilkan.</p>
                </div>
                <div class="form-actions">
                  <button type="submit" class="btn-primary">Simpan Pengaturan</button>
                  <div class="msg" data-role="home-message"></div>
                </div>
              </form>
            </article>

            <article class="hcisysq-card">
              <h3 class="hcisysq-card-title">Buat Pengumuman Baru</h3>
              <form id="hcisysq-announcement-form" class="hcisysq-form-grid">
                <input type="hidden" name="announcement_id" value="">
                <div class="form-group">
                  <label for="hcisysq-ann-title">Judul <span class="req">*</span></label>
                  <input type="text" id="hcisysq-ann-title" name="title" class="hcisysq-input" placeholder="Contoh: Pembaruan Data Pegawai" required>
                </div>
                <div class="form-group">
                  <label for="hcisysq-ann-body-editor">Isi Pengumuman <span class="req">*</span></label>
                  <div class="hcisysq-editor" data-editor-wrapper data-editor-name="body">
                    <div class="hcisysq-editor__toolbar" role="toolbar" aria-label="Format teks pengumuman">
                      <button type="button" class="hcisysq-editor__button" data-command="bold" title="Tebal"><span aria-hidden="true">B</span><span class="screen-reader-text">Tebal</span></button>
                      <button type="button" class="hcisysq-editor__button" data-command="italic" title="Miring"><span aria-hidden="true">I</span><span class="screen-reader-text">Miring</span></button>
                      <button type="button" class="hcisysq-editor__button" data-command="unorderedList" title="Bullet"><span aria-hidden="true">•</span><span class="screen-reader-text">Bullet</span></button>
                      <button type="button" class="hcisysq-editor__button" data-command="orderedList" title="Penomoran"><span aria-hidden="true">1.</span><span class="screen-reader-text">Penomoran</span></button>
                      <div class="hcisysq-editor__divider" aria-hidden="true"></div>
                      <label class="screen-reader-text" for="hcisysq-ann-font">Pilih font</label>
                      <select id="hcisysq-ann-font" class="hcisysq-editor__select" data-command="fontFamily">
                        <option value="">Font default</option>
                        <option value="arial">Arial</option>
                        <option value="helvetica">Helvetica</option>
                        <option value="times new roman">Times New Roman</option>
                      </select>
                      <label class="screen-reader-text" for="hcisysq-ann-size">Ukuran teks</label>
                      <select id="hcisysq-ann-size" class="hcisysq-editor__select" data-command="fontSize">
                        <option value="">Ukuran</option>
                        <option value="12">12 px</option>
                        <option value="14">14 px</option>
                        <option value="16">16 px</option>
                        <option value="18">18 px</option>
                        <option value="20">20 px</option>
                        <option value="24">24 px</option>
                        <option value="28">28 px</option>
                      </select>
                      <div class="hcisysq-editor__divider" aria-hidden="true"></div>
                      <button type="button" class="hcisysq-editor__button" data-command="clear" title="Bersihkan format"><span aria-hidden="true">⟲</span><span class="screen-reader-text">Bersihkan format</span></button>
                    </div>
                    <div id="hcisysq-ann-body-editor" class="hcisysq-editor__content" data-editor-content contenteditable="true" role="textbox" aria-multiline="true" aria-describedby="hcisysq-ann-body-help"></div>
                    <textarea id="hcisysq-ann-body" name="body" data-editor-input hidden></textarea>
                  </div>
                  <p class="form-helper" id="hcisysq-ann-body-help">Tambahkan poin, penomoran, dan format dasar agar pengumuman lebih mudah dibaca.</p>
                </div>
                <div class="form-group">
                  <label for="hcisysq-ann-link-type">Tautan Tambahan</label>
                  <div class="hcisysq-field-group">
                    <select id="hcisysq-ann-link-type" name="link_type" class="hcisysq-input">
                      <option value="">Tidak ada tautan</option>
                      <option value="external">Gunakan URL khusus</option>
                      <option value="training">Gunakan tautan Form Pelatihan</option>
                    </select>
                    <input type="url" id="hcisysq-ann-link-url" name="link_url" class="hcisysq-input" placeholder="https://contoh.id" autocomplete="url" disabled>
                  </div>
                </div>
                <div class="form-group">
                  <label for="hcisysq-ann-link-label">Label Tautan</label>
                  <input type="text" id="hcisysq-ann-link-label" name="link_label" class="hcisysq-input" placeholder="Contoh: Baca selengkapnya">
                </div>
                <div class="form-actions">
                  <button type="submit" class="btn-primary" data-role="announcement-submit">Publikasikan</button>
                  <button type="button" class="btn-light" data-role="announcement-cancel" hidden>Batal</button>
                  <div class="msg" data-role="announcement-message"></div>
                </div>
              </form>
            </article>

            <article class="hcisysq-card">
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
          <a class="is-active" href="<?= esc_url(home_url('/' . HCISYSQ_DASHBOARD_SLUG . '/')) ?>">Dashboard</a>
          <a href="#">Profil</a>
          <a href="#">Slip Gaji</a>
          <a href="#">Rekap Absensi</a>
          <a href="#">Riwayat Kepegawaian</a>
          <a href="#">Cuti &amp; Izin</a>
          <a href="#">Penilaian Kinerja</a>
          <a href="#">Tugas &amp; Komunikasi</a>
          <a href="#">Administrasi Lain</a>
          <hr>
          <a href="#">Panduan</a>
          <a href="#">Support</a>
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

          <section class="hcisysq-card-grid hcisysq-card-grid--1">
            <article class="hcisysq-card hcisysq-card--empty">
              <h3 class="hcisysq-card-title">Fitur Mendatang</h3>
              <div class="hcisysq-coming-soon">
                <span class="hcisysq-coming-soon__tag">Coming Soon</span>
                <div class="hcisysq-progress" aria-hidden="true">
                  <span class="hcisysq-progress__bar"></span>
                </div>
                <p class="hcisysq-coming-soon__desc">Work in Progress. Modul slip gaji, absensi, dan fitur kepegawaian lainnya sedang disiapkan untuk Anda.</p>
              </div>
            </article>
          </section>
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
