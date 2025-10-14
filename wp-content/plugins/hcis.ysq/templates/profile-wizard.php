<?php
/** @var array $employee_data */
/** @var array $family */
/** @var array $education */
/** @var array $work_history */
/** @var array $training_history */
/** @var array $employment_history */
/** @var array $quran */
/** @var array $islamic */

if (!defined('ABSPATH')) exit;

$family_items = !empty($family) ? $family : [['name' => '', 'relationship' => '', 'birth_date' => '']];
$education_items = !empty($education) ? $education : [['level' => '', 'institution_name' => '', 'major' => '', 'end_year' => '']];
$work_items = !empty($work_history) ? $work_history : [['company_name' => '', 'position' => '', 'start_date' => '', 'end_date' => '', 'reference_contact' => '']];
$training_items = !empty($training_history) ? $training_history : [['course_name' => '', 'organizer' => '', 'training_date' => '', 'venue' => '', 'cost' => '', 'funding_source' => '', 'payment_method' => '', 'payment_proof_file' => '', 'status' => '', 'certificate_file' => '']];
$employment_items = !empty($employment_history) ? $employment_history : [['unit_name' => '', 'position_name' => '', 'employment_status' => '', 'start_date' => '', 'end_date' => '']];
$islamic_items = !empty($islamic) ? $islamic : [['study_topic_or_book' => '', 'teacher_name' => '', 'organizer' => '', 'study_type' => '', 'start_date' => '', 'end_date' => '']];
$quran_entry = !empty($quran) ? $quran : ['juz_memorized' => '', 'last_tested_date' => '', 'examiner_name' => '', 'notes' => ''];
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class('ysq-profile-wizard-page'); ?>>
  <main class="ysq-profile-wizard">
    <h1><?php esc_html_e('Lengkapi Profil Pegawai', 'hcis-ysq'); ?></h1>
    <p><?php esc_html_e('Mohon lengkapi seluruh data berikut agar profil Anda valid.', 'hcis-ysq'); ?></p>
    <?php if (!empty($force_password_change)) : ?>
      <div class="notice notice-warning">
        <p><?php esc_html_e('Untuk keamanan, silakan segera mengganti password Anda di profil WordPress setelah melengkapi data berikut.', 'hcis-ysq'); ?></p>
      </div>
    <?php endif; ?>
    <form method="post" class="ysq-profile-form">
      <?php wp_nonce_field('ysq_profile_wizard'); ?>
      <section class="ysq-card">
        <h2><?php esc_html_e('Data Pribadi', 'hcis-ysq'); ?></h2>
        <div class="ysq-grid">
          <label>
            <span><?php esc_html_e('Gelar Depan', 'hcis-ysq'); ?></span>
            <input type="text" name="employee[title_prefix]" value="<?php echo esc_attr($employee_data['title_prefix'] ?? ''); ?>">
          </label>
          <label>
            <span><?php esc_html_e('Nama Lengkap', 'hcis-ysq'); ?></span>
            <input type="text" name="employee[full_name]" value="<?php echo esc_attr($employee_data['full_name'] ?? ''); ?>" required>
          </label>
          <label>
            <span><?php esc_html_e('Gelar Belakang', 'hcis-ysq'); ?></span>
            <input type="text" name="employee[title_suffix]" value="<?php echo esc_attr($employee_data['title_suffix'] ?? ''); ?>">
          </label>
          <label>
            <span><?php esc_html_e('NIP', 'hcis-ysq'); ?></span>
            <input type="text" name="employee[employee_id_number]" value="<?php echo esc_attr($employee_data['employee_id_number'] ?? ''); ?>" required>
          </label>
          <label>
            <span><?php esc_html_e('No. KTP', 'hcis-ysq'); ?></span>
            <input type="text" name="employee[ktp_number]" value="<?php echo esc_attr($employee_data['ktp_number'] ?? ''); ?>">
          </label>
          <label>
            <span><?php esc_html_e('Email', 'hcis-ysq'); ?></span>
            <input type="email" name="employee[email]" value="<?php echo esc_attr($employee_data['email'] ?? ''); ?>" required>
          </label>
          <label>
            <span><?php esc_html_e('No. HP', 'hcis-ysq'); ?></span>
            <input type="text" name="employee[phone_number]" value="<?php echo esc_attr($employee_data['phone_number'] ?? ''); ?>" required>
          </label>
          <label>
            <span><?php esc_html_e('Tempat Lahir', 'hcis-ysq'); ?></span>
            <input type="text" name="employee[birth_place]" value="<?php echo esc_attr($employee_data['birth_place'] ?? ''); ?>">
          </label>
          <label>
            <span><?php esc_html_e('Tanggal Lahir', 'hcis-ysq'); ?></span>
            <input type="date" name="employee[birth_date]" value="<?php echo esc_attr($employee_data['birth_date'] ?? ''); ?>">
          </label>
          <label>
            <span><?php esc_html_e('Jenis Kelamin', 'hcis-ysq'); ?></span>
            <select name="employee[gender]">
              <option value=""><?php esc_html_e('Pilih', 'hcis-ysq'); ?></option>
              <option value="Laki-laki" <?php selected($employee_data['gender'] ?? '', 'Laki-laki'); ?>><?php esc_html_e('Laki-laki', 'hcis-ysq'); ?></option>
              <option value="Perempuan" <?php selected($employee_data['gender'] ?? '', 'Perempuan'); ?>><?php esc_html_e('Perempuan', 'hcis-ysq'); ?></option>
            </select>
          </label>
          <label>
            <span><?php esc_html_e('Status Pernikahan', 'hcis-ysq'); ?></span>
            <input type="text" name="employee[marital_status]" value="<?php echo esc_attr($employee_data['marital_status'] ?? ''); ?>">
          </label>
          <label class="ysq-grid-full">
            <span><?php esc_html_e('Alamat', 'hcis-ysq'); ?></span>
            <textarea name="employee[address]" rows="3"><?php echo esc_textarea($employee_data['address'] ?? ''); ?></textarea>
          </label>
        </div>
      </section>

      <section class="ysq-card">
        <h2><?php esc_html_e('Informasi Kepegawaian', 'hcis-ysq'); ?></h2>
        <div class="ysq-grid">
          <label>
            <span><?php esc_html_e('Tanggal Bergabung', 'hcis-ysq'); ?></span>
            <input type="date" name="employee[join_date]" value="<?php echo esc_attr($employee_data['join_date'] ?? ''); ?>" required>
          </label>
          <label>
            <span><?php esc_html_e('Status Kepegawaian', 'hcis-ysq'); ?></span>
            <select name="employee[status]">
              <option value="Aktif" <?php selected($employee_data['status'] ?? 'Aktif', 'Aktif'); ?>><?php esc_html_e('Aktif', 'hcis-ysq'); ?></option>
              <option value="Tidak Aktif" <?php selected($employee_data['status'] ?? '', 'Tidak Aktif'); ?>><?php esc_html_e('Tidak Aktif', 'hcis-ysq'); ?></option>
            </select>
          </label>
          <label>
            <span><?php esc_html_e('Kontak Darurat', 'hcis-ysq'); ?></span>
            <input type="text" name="employee[emergency_contact_name]" value="<?php echo esc_attr($employee_data['emergency_contact_name'] ?? ''); ?>">
          </label>
          <label>
            <span><?php esc_html_e('No. Kontak Darurat', 'hcis-ysq'); ?></span>
            <input type="text" name="employee[emergency_contact_phone]" value="<?php echo esc_attr($employee_data['emergency_contact_phone'] ?? ''); ?>">
          </label>
          <label>
            <span><?php esc_html_e('URL Foto Profil', 'hcis-ysq'); ?></span>
            <input type="url" name="employee[profile_picture_url]" value="<?php echo esc_attr($employee_data['profile_picture_url'] ?? ''); ?>">
          </label>
        </div>
      </section>

      <section class="ysq-card">
        <h2><?php esc_html_e('Informasi Keuangan', 'hcis-ysq'); ?></h2>
        <div class="ysq-grid">
          <label>
            <span><?php esc_html_e('Nama Bank', 'hcis-ysq'); ?></span>
            <input type="text" name="employee[bank_name]" value="<?php echo esc_attr($employee_data['bank_name'] ?? ''); ?>">
          </label>
          <label>
            <span><?php esc_html_e('No. Rekening', 'hcis-ysq'); ?></span>
            <input type="text" name="employee[bank_account_number]" value="<?php echo esc_attr($employee_data['bank_account_number'] ?? ''); ?>">
          </label>
          <label>
            <span><?php esc_html_e('NPWP', 'hcis-ysq'); ?></span>
            <input type="text" name="employee[npwp_number]" value="<?php echo esc_attr($employee_data['npwp_number'] ?? ''); ?>">
          </label>
        </div>
      </section>

      <section class="ysq-card">
        <h2><?php esc_html_e('Riwayat Unit & Jabatan', 'hcis-ysq'); ?></h2>
        <div class="ysq-repeater" data-repeater="employment_history">
          <div class="ysq-repeater-items">
            <?php foreach ($employment_items as $index => $item): ?>
              <div class="ysq-repeater-item">
                <div class="ysq-grid">
                  <label>
                    <span><?php esc_html_e('Unit Kerja', 'hcis-ysq'); ?></span>
                    <input type="text" name="employment_history[<?php echo esc_attr($index); ?>][unit_name]" value="<?php echo esc_attr($item['unit_name'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Jabatan', 'hcis-ysq'); ?></span>
                    <input type="text" name="employment_history[<?php echo esc_attr($index); ?>][position_name]" value="<?php echo esc_attr($item['position_name'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Status', 'hcis-ysq'); ?></span>
                    <input type="text" name="employment_history[<?php echo esc_attr($index); ?>][employment_status]" value="<?php echo esc_attr($item['employment_status'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Mulai', 'hcis-ysq'); ?></span>
                    <input type="date" name="employment_history[<?php echo esc_attr($index); ?>][start_date]" value="<?php echo esc_attr($item['start_date'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Selesai', 'hcis-ysq'); ?></span>
                    <input type="date" name="employment_history[<?php echo esc_attr($index); ?>][end_date]" value="<?php echo esc_attr($item['end_date'] ?? ''); ?>">
                  </label>
                </div>
                <button type="button" class="button-link ysq-repeater-remove"><?php esc_html_e('Hapus', 'hcis-ysq'); ?></button>
              </div>
            <?php endforeach; ?>
          </div>
          <template>
            <div class="ysq-repeater-item">
              <div class="ysq-grid">
                <label>
                  <span><?php esc_html_e('Unit Kerja', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="unit_name">
                </label>
                <label>
                  <span><?php esc_html_e('Jabatan', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="position_name">
                </label>
                <label>
                  <span><?php esc_html_e('Status', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="employment_status">
                </label>
                <label>
                  <span><?php esc_html_e('Mulai', 'hcis-ysq'); ?></span>
                  <input type="date" data-name="start_date">
                </label>
                <label>
                  <span><?php esc_html_e('Selesai', 'hcis-ysq'); ?></span>
                  <input type="date" data-name="end_date">
                </label>
              </div>
              <button type="button" class="button-link ysq-repeater-remove"><?php esc_html_e('Hapus', 'hcis-ysq'); ?></button>
            </div>
          </template>
          <button type="button" class="button ysq-repeater-add"><?php esc_html_e('Tambah Riwayat', 'hcis-ysq'); ?></button>
        </div>
      </section>

      <section class="ysq-card">
        <h2><?php esc_html_e('Keluarga', 'hcis-ysq'); ?></h2>
        <div class="ysq-repeater" data-repeater="family">
          <div class="ysq-repeater-items">
            <?php foreach ($family_items as $index => $item): ?>
              <div class="ysq-repeater-item">
                <div class="ysq-grid">
                  <label>
                    <span><?php esc_html_e('Nama', 'hcis-ysq'); ?></span>
                    <input type="text" name="family[<?php echo esc_attr($index); ?>][name]" value="<?php echo esc_attr($item['name'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Hubungan', 'hcis-ysq'); ?></span>
                    <input type="text" name="family[<?php echo esc_attr($index); ?>][relationship]" value="<?php echo esc_attr($item['relationship'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Tanggal Lahir', 'hcis-ysq'); ?></span>
                    <input type="date" name="family[<?php echo esc_attr($index); ?>][birth_date]" value="<?php echo esc_attr($item['birth_date'] ?? ''); ?>">
                  </label>
                </div>
                <button type="button" class="button-link ysq-repeater-remove"><?php esc_html_e('Hapus', 'hcis-ysq'); ?></button>
              </div>
            <?php endforeach; ?>
          </div>
          <template>
            <div class="ysq-repeater-item">
              <div class="ysq-grid">
                <label>
                  <span><?php esc_html_e('Nama', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="name">
                </label>
                <label>
                  <span><?php esc_html_e('Hubungan', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="relationship">
                </label>
                <label>
                  <span><?php esc_html_e('Tanggal Lahir', 'hcis-ysq'); ?></span>
                  <input type="date" data-name="birth_date">
                </label>
              </div>
              <button type="button" class="button-link ysq-repeater-remove"><?php esc_html_e('Hapus', 'hcis-ysq'); ?></button>
            </div>
          </template>
          <button type="button" class="button ysq-repeater-add"><?php esc_html_e('Tambah Anggota Keluarga', 'hcis-ysq'); ?></button>
        </div>
      </section>

      <section class="ysq-card">
        <h2><?php esc_html_e('Pendidikan Formal', 'hcis-ysq'); ?></h2>
        <div class="ysq-repeater" data-repeater="education">
          <div class="ysq-repeater-items">
            <?php foreach ($education_items as $index => $item): ?>
              <div class="ysq-repeater-item">
                <div class="ysq-grid">
                  <label>
                    <span><?php esc_html_e('Jenjang', 'hcis-ysq'); ?></span>
                    <input type="text" name="education[<?php echo esc_attr($index); ?>][level]" value="<?php echo esc_attr($item['level'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Institusi', 'hcis-ysq'); ?></span>
                    <input type="text" name="education[<?php echo esc_attr($index); ?>][institution_name]" value="<?php echo esc_attr($item['institution_name'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Jurusan', 'hcis-ysq'); ?></span>
                    <input type="text" name="education[<?php echo esc_attr($index); ?>][major]" value="<?php echo esc_attr($item['major'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Tahun Lulus', 'hcis-ysq'); ?></span>
                    <input type="text" name="education[<?php echo esc_attr($index); ?>][end_year]" value="<?php echo esc_attr($item['end_year'] ?? ''); ?>">
                  </label>
                </div>
                <button type="button" class="button-link ysq-repeater-remove"><?php esc_html_e('Hapus', 'hcis-ysq'); ?></button>
              </div>
            <?php endforeach; ?>
          </div>
          <template>
            <div class="ysq-repeater-item">
              <div class="ysq-grid">
                <label>
                  <span><?php esc_html_e('Jenjang', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="level">
                </label>
                <label>
                  <span><?php esc_html_e('Institusi', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="institution_name">
                </label>
                <label>
                  <span><?php esc_html_e('Jurusan', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="major">
                </label>
                <label>
                  <span><?php esc_html_e('Tahun Lulus', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="end_year">
                </label>
              </div>
              <button type="button" class="button-link ysq-repeater-remove"><?php esc_html_e('Hapus', 'hcis-ysq'); ?></button>
            </div>
          </template>
          <button type="button" class="button ysq-repeater-add"><?php esc_html_e('Tambah Pendidikan', 'hcis-ysq'); ?></button>
        </div>
      </section>

      <section class="ysq-card">
        <h2><?php esc_html_e('Pengalaman Kerja', 'hcis-ysq'); ?></h2>
        <div class="ysq-repeater" data-repeater="work_history">
          <div class="ysq-repeater-items">
            <?php foreach ($work_items as $index => $item): ?>
              <div class="ysq-repeater-item">
                <div class="ysq-grid">
                  <label>
                    <span><?php esc_html_e('Perusahaan', 'hcis-ysq'); ?></span>
                    <input type="text" name="work_history[<?php echo esc_attr($index); ?>][company_name]" value="<?php echo esc_attr($item['company_name'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Posisi', 'hcis-ysq'); ?></span>
                    <input type="text" name="work_history[<?php echo esc_attr($index); ?>][position]" value="<?php echo esc_attr($item['position'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Mulai', 'hcis-ysq'); ?></span>
                    <input type="date" name="work_history[<?php echo esc_attr($index); ?>][start_date]" value="<?php echo esc_attr($item['start_date'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Selesai', 'hcis-ysq'); ?></span>
                    <input type="date" name="work_history[<?php echo esc_attr($index); ?>][end_date]" value="<?php echo esc_attr($item['end_date'] ?? ''); ?>">
                  </label>
                  <label class="ysq-grid-full">
                    <span><?php esc_html_e('Kontak Referensi', 'hcis-ysq'); ?></span>
                    <input type="text" name="work_history[<?php echo esc_attr($index); ?>][reference_contact]" value="<?php echo esc_attr($item['reference_contact'] ?? ''); ?>">
                  </label>
                </div>
                <button type="button" class="button-link ysq-repeater-remove"><?php esc_html_e('Hapus', 'hcis-ysq'); ?></button>
              </div>
            <?php endforeach; ?>
          </div>
          <template>
            <div class="ysq-repeater-item">
              <div class="ysq-grid">
                <label>
                  <span><?php esc_html_e('Perusahaan', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="company_name">
                </label>
                <label>
                  <span><?php esc_html_e('Posisi', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="position">
                </label>
                <label>
                  <span><?php esc_html_e('Mulai', 'hcis-ysq'); ?></span>
                  <input type="date" data-name="start_date">
                </label>
                <label>
                  <span><?php esc_html_e('Selesai', 'hcis-ysq'); ?></span>
                  <input type="date" data-name="end_date">
                </label>
                <label class="ysq-grid-full">
                  <span><?php esc_html_e('Kontak Referensi', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="reference_contact">
                </label>
              </div>
              <button type="button" class="button-link ysq-repeater-remove"><?php esc_html_e('Hapus', 'hcis-ysq'); ?></button>
            </div>
          </template>
          <button type="button" class="button ysq-repeater-add"><?php esc_html_e('Tambah Pengalaman Kerja', 'hcis-ysq'); ?></button>
        </div>
      </section>

      <section class="ysq-card">
        <h2><?php esc_html_e('Pelatihan & Sertifikasi', 'hcis-ysq'); ?></h2>
        <div class="ysq-repeater" data-repeater="training_history">
          <div class="ysq-repeater-items">
            <?php foreach ($training_items as $index => $item): ?>
              <div class="ysq-repeater-item">
                <div class="ysq-grid">
                  <label>
                    <span><?php esc_html_e('Nama Pelatihan', 'hcis-ysq'); ?></span>
                    <input type="text" name="training_history[<?php echo esc_attr($index); ?>][course_name]" value="<?php echo esc_attr($item['course_name'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Penyelenggara', 'hcis-ysq'); ?></span>
                    <input type="text" name="training_history[<?php echo esc_attr($index); ?>][organizer]" value="<?php echo esc_attr($item['organizer'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Tanggal Pelatihan', 'hcis-ysq'); ?></span>
                    <input type="date" name="training_history[<?php echo esc_attr($index); ?>][training_date]" value="<?php echo esc_attr($item['training_date'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Lokasi', 'hcis-ysq'); ?></span>
                    <input type="text" name="training_history[<?php echo esc_attr($index); ?>][venue]" value="<?php echo esc_attr($item['venue'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Biaya', 'hcis-ysq'); ?></span>
                    <input type="number" step="0.01" name="training_history[<?php echo esc_attr($index); ?>][cost]" value="<?php echo esc_attr($item['cost'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Sumber Dana', 'hcis-ysq'); ?></span>
                    <input type="text" name="training_history[<?php echo esc_attr($index); ?>][funding_source]" value="<?php echo esc_attr($item['funding_source'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Metode Pembayaran', 'hcis-ysq'); ?></span>
                    <input type="text" name="training_history[<?php echo esc_attr($index); ?>][payment_method]" value="<?php echo esc_attr($item['payment_method'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('URL Bukti Pembayaran', 'hcis-ysq'); ?></span>
                    <input type="url" name="training_history[<?php echo esc_attr($index); ?>][payment_proof_file]" value="<?php echo esc_attr($item['payment_proof_file'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Status', 'hcis-ysq'); ?></span>
                    <select name="training_history[<?php echo esc_attr($index); ?>][status]">
                      <?php
                        $statuses = ['Diajukan','Disetujui','Ditolak','Selesai','Dibatalkan'];
                        $current_status = $item['status'] ?? '';
                        foreach ($statuses as $status) {
                          printf('<option value="%1$s" %2$s>%1$s</option>', esc_attr($status), selected($current_status, $status, false));
                        }
                      ?>
                    </select>
                  </label>
                  <label>
                    <span><?php esc_html_e('URL Sertifikat', 'hcis-ysq'); ?></span>
                    <input type="url" name="training_history[<?php echo esc_attr($index); ?>][certificate_file]" value="<?php echo esc_attr($item['certificate_file'] ?? ''); ?>">
                  </label>
                </div>
                <button type="button" class="button-link ysq-repeater-remove"><?php esc_html_e('Hapus', 'hcis-ysq'); ?></button>
              </div>
            <?php endforeach; ?>
          </div>
          <template>
            <div class="ysq-repeater-item">
              <div class="ysq-grid">
                <label>
                  <span><?php esc_html_e('Nama Pelatihan', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="course_name">
                </label>
                <label>
                  <span><?php esc_html_e('Penyelenggara', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="organizer">
                </label>
                <label>
                  <span><?php esc_html_e('Tanggal Pelatihan', 'hcis-ysq'); ?></span>
                  <input type="date" data-name="training_date">
                </label>
                <label>
                  <span><?php esc_html_e('Lokasi', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="venue">
                </label>
                <label>
                  <span><?php esc_html_e('Biaya', 'hcis-ysq'); ?></span>
                  <input type="number" step="0.01" data-name="cost">
                </label>
                <label>
                  <span><?php esc_html_e('Sumber Dana', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="funding_source">
                </label>
                <label>
                  <span><?php esc_html_e('Metode Pembayaran', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="payment_method">
                </label>
                <label>
                  <span><?php esc_html_e('URL Bukti Pembayaran', 'hcis-ysq'); ?></span>
                  <input type="url" data-name="payment_proof_file">
                </label>
                <label>
                  <span><?php esc_html_e('Status', 'hcis-ysq'); ?></span>
                  <select data-name="status">
                    <?php foreach (['Diajukan','Disetujui','Ditolak','Selesai','Dibatalkan'] as $status): ?>
                      <option value="<?php echo esc_attr($status); ?>"><?php echo esc_html($status); ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
                <label>
                  <span><?php esc_html_e('URL Sertifikat', 'hcis-ysq'); ?></span>
                  <input type="url" data-name="certificate_file">
                </label>
              </div>
              <button type="button" class="button-link ysq-repeater-remove"><?php esc_html_e('Hapus', 'hcis-ysq'); ?></button>
            </div>
          </template>
          <button type="button" class="button ysq-repeater-add"><?php esc_html_e('Tambah Pelatihan', 'hcis-ysq'); ?></button>
        </div>
      </section>

      <section class="ysq-card">
        <h2><?php esc_html_e('Tahfidz & Catatan Quran', 'hcis-ysq'); ?></h2>
        <div class="ysq-grid">
          <label>
            <span><?php esc_html_e('Juz Hafalan', 'hcis-ysq'); ?></span>
            <input type="number" step="0.01" name="quran_memorization[juz_memorized]" value="<?php echo esc_attr($quran_entry['juz_memorized'] ?? ''); ?>">
          </label>
          <label>
            <span><?php esc_html_e('Tanggal Tasmi Terakhir', 'hcis-ysq'); ?></span>
            <input type="date" name="quran_memorization[last_tested_date]" value="<?php echo esc_attr($quran_entry['last_tested_date'] ?? ''); ?>">
          </label>
          <label>
            <span><?php esc_html_e('Penguji', 'hcis-ysq'); ?></span>
            <input type="text" name="quran_memorization[examiner_name]" value="<?php echo esc_attr($quran_entry['examiner_name'] ?? ''); ?>">
          </label>
          <label class="ysq-grid-full">
            <span><?php esc_html_e('Catatan', 'hcis-ysq'); ?></span>
            <textarea name="quran_memorization[notes]" rows="3"><?php echo esc_textarea($quran_entry['notes'] ?? ''); ?></textarea>
          </label>
        </div>
      </section>

      <section class="ysq-card">
        <h2><?php esc_html_e('Riwayat Kajian Islam', 'hcis-ysq'); ?></h2>
        <div class="ysq-repeater" data-repeater="islamic_studies">
          <div class="ysq-repeater-items">
            <?php foreach ($islamic_items as $index => $item): ?>
              <div class="ysq-repeater-item">
                <div class="ysq-grid">
                  <label>
                    <span><?php esc_html_e('Topik / Kitab', 'hcis-ysq'); ?></span>
                    <input type="text" name="islamic_studies[<?php echo esc_attr($index); ?>][study_topic_or_book]" value="<?php echo esc_attr($item['study_topic_or_book'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Pengajar', 'hcis-ysq'); ?></span>
                    <input type="text" name="islamic_studies[<?php echo esc_attr($index); ?>][teacher_name]" value="<?php echo esc_attr($item['teacher_name'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Penyelenggara', 'hcis-ysq'); ?></span>
                    <input type="text" name="islamic_studies[<?php echo esc_attr($index); ?>][organizer]" value="<?php echo esc_attr($item['organizer'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Jenis Kajian', 'hcis-ysq'); ?></span>
                    <input type="text" name="islamic_studies[<?php echo esc_attr($index); ?>][study_type]" value="<?php echo esc_attr($item['study_type'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Mulai', 'hcis-ysq'); ?></span>
                    <input type="date" name="islamic_studies[<?php echo esc_attr($index); ?>][start_date]" value="<?php echo esc_attr($item['start_date'] ?? ''); ?>">
                  </label>
                  <label>
                    <span><?php esc_html_e('Selesai', 'hcis-ysq'); ?></span>
                    <input type="date" name="islamic_studies[<?php echo esc_attr($index); ?>][end_date]" value="<?php echo esc_attr($item['end_date'] ?? ''); ?>">
                  </label>
                </div>
                <button type="button" class="button-link ysq-repeater-remove"><?php esc_html_e('Hapus', 'hcis-ysq'); ?></button>
              </div>
            <?php endforeach; ?>
          </div>
          <template>
            <div class="ysq-repeater-item">
              <div class="ysq-grid">
                <label>
                  <span><?php esc_html_e('Topik / Kitab', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="study_topic_or_book">
                </label>
                <label>
                  <span><?php esc_html_e('Pengajar', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="teacher_name">
                </label>
                <label>
                  <span><?php esc_html_e('Penyelenggara', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="organizer">
                </label>
                <label>
                  <span><?php esc_html_e('Jenis Kajian', 'hcis-ysq'); ?></span>
                  <input type="text" data-name="study_type">
                </label>
                <label>
                  <span><?php esc_html_e('Mulai', 'hcis-ysq'); ?></span>
                  <input type="date" data-name="start_date">
                </label>
                <label>
                  <span><?php esc_html_e('Selesai', 'hcis-ysq'); ?></span>
                  <input type="date" data-name="end_date">
                </label>
              </div>
              <button type="button" class="button-link ysq-repeater-remove"><?php esc_html_e('Hapus', 'hcis-ysq'); ?></button>
            </div>
          </template>
          <button type="button" class="button ysq-repeater-add"><?php esc_html_e('Tambah Riwayat Kajian', 'hcis-ysq'); ?></button>
        </div>
      </section>

      <p class="ysq-submit">
        <button type="submit" class="button button-primary button-large"><?php esc_html_e('Simpan Profil', 'hcis-ysq'); ?></button>
      </p>
    </form>
  </main>
  <?php wp_footer(); ?>
</body>
</html>
