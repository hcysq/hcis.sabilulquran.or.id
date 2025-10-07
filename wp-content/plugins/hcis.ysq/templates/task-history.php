<?php
/**
 * Task history template.
 */

declare(strict_types=1);

wp_enqueue_style('hcisysq-dashboard');
wp_enqueue_style('hcisysq-admin');
wp_enqueue_script('hcisysq-dashboard');
wp_enqueue_script('hcisysq-admin');

get_header();

$context = get_query_var('hcisysq_task_context');
if (!is_array($context)) {
  $context = [];
}

$task = isset($context['task']) && is_array($context['task']) ? $context['task'] : [];
$assignments = isset($context['assignments']) && is_array($context['assignments']) ? $context['assignments'] : [];
$unitLabels = isset($context['unit_labels']) && is_array($context['unit_labels']) ? $context['unit_labels'] : [];
$historyUrl = isset($context['history_url']) ? esc_url($context['history_url']) : '';
$deadlineDisplay = isset($context['deadline_display']) ? (string) $context['deadline_display'] : '';
$totalAssignments = isset($context['total_assignments']) ? (int) $context['total_assignments'] : count($assignments);
$completedAssignments = isset($context['completed_assignments']) ? (int) $context['completed_assignments'] : 0;

$taskId = isset($task['id']) ? (string) $task['id'] : '';
$title = isset($task['title']) ? (string) $task['title'] : '';
$description = isset($task['description']) ? (string) $task['description'] : '';
$deadline = isset($task['deadline']) ? (string) $task['deadline'] : '';
$linkLabel = isset($task['link_label']) ? (string) $task['link_label'] : '';
$linkUrl = isset($task['link_url']) ? (string) $task['link_url'] : '';
$status = isset($task['status']) ? (string) $task['status'] : 'published';
$createdAt = isset($task['created_at']) ? (string) $task['created_at'] : '';
$updatedAt = isset($task['updated_at']) ? (string) $task['updated_at'] : '';

$createdAtDisplay = $createdAt ? mysql2date('d M Y H:i', $createdAt) : '';
$updatedAtDisplay = $updatedAt ? mysql2date('d M Y H:i', $updatedAt) : '';

$statusLabel = __('Dipublikasikan', 'hcisysq');
$statusClass = 'is-published';
if ($status === 'archived') {
  $statusLabel = __('Diarsipkan', 'hcisysq');
  $statusClass = 'is-archived';
} elseif ($status === 'completed') {
  $statusLabel = __('Selesai', 'hcisysq');
  $statusClass = 'is-done';
}

$messageText = '';
$isError = false;
if (isset($_GET['task_msg'])) {
  $messageText = sanitize_text_field(wp_unslash((string) $_GET['task_msg']));
  $isError = isset($_GET['task_error']) && (int) $_GET['task_error'] === 1;
}
?>
<div class="hcisysq-task-history">
  <div class="hcisysq-task-history__inner">
    <header class="hcisysq-task-history__header">
      <div>
        <a class="btn-link" href="<?php echo esc_url(home_url('/' . trim(HCISYSQ_DASHBOARD_SLUG, '/') . '/?view=tugas')); ?>">&larr; <?php esc_html_e('Kembali ke dashboard', 'hcisysq'); ?></a>
      </div>
      <h1 class="hcisysq-task-history__title"><?php echo esc_html($title !== '' ? $title : __('Detail Tugas', 'hcisysq')); ?></h1>
      <div class="hcisysq-task-history__meta">
        <span><span class="hcisysq-status-chip <?php echo esc_attr($statusClass); ?>"><?php echo esc_html($statusLabel); ?></span></span>
        <?php if ($deadlineDisplay !== '') : ?>
          <span><?php printf(esc_html__('Batas waktu: %s', 'hcisysq'), esc_html($deadlineDisplay)); ?></span>
        <?php endif; ?>
        <?php if ($totalAssignments > 0) : ?>
          <span><?php printf(esc_html__('Ketuntasan: %1$d/%2$d pegawai', 'hcisysq'), $completedAssignments, $totalAssignments); ?></span>
        <?php endif; ?>
      </div>
    </header>

    <?php if ($messageText !== '') : ?>
      <div class="msg <?php echo $isError ? 'error' : 'ok'; ?> hcisysq-history-message"><?php echo esc_html($messageText); ?></div>
    <?php endif; ?>

    <section class="hcisysq-history-card">
      <h2><?php esc_html_e('Detail tugas', 'hcisysq'); ?></h2>
      <form method="post" class="hcisysq-history-form">
        <?php wp_nonce_field('hcisysq_task_action_' . $taskId); ?>
        <table class="hcisysq-history-table">
          <tbody>
            <tr>
              <th scope="row"><?php esc_html_e('Judul', 'hcisysq'); ?></th>
              <td><input type="text" name="title" class="hcisysq-input" value="<?php echo esc_attr($title); ?>" required></td>
            </tr>
            <tr>
              <th scope="row"><?php esc_html_e('Keterangan', 'hcisysq'); ?></th>
              <td><textarea name="description" class="hcisysq-textarea" rows="6"><?php echo esc_textarea($description); ?></textarea></td>
            </tr>
            <tr>
              <th scope="row"><?php esc_html_e('Batas waktu', 'hcisysq'); ?></th>
              <td><input type="date" name="deadline" class="hcisysq-input" value="<?php echo esc_attr($deadline); ?>"></td>
            </tr>
            <tr>
              <th scope="row"><?php esc_html_e('Teks tautan', 'hcisysq'); ?></th>
              <td><input type="text" name="link_label" class="hcisysq-input" value="<?php echo esc_attr($linkLabel); ?>" placeholder="<?php esc_attr_e('Contoh: Buka Formulir', 'hcisysq'); ?>"></td>
            </tr>
            <tr>
              <th scope="row"><?php esc_html_e('URL tautan', 'hcisysq'); ?></th>
              <td><input type="url" name="link_url" class="hcisysq-input" value="<?php echo esc_attr($linkUrl); ?>" placeholder="https://"></td>
            </tr>
            <?php if (!empty($unitLabels)) : ?>
              <tr>
                <th scope="row"><?php esc_html_e('Unit tujuan', 'hcisysq'); ?></th>
                <td>
                  <div class="hcisysq-history-units">
                    <?php foreach ($unitLabels as $label) : ?>
                      <span><?php echo esc_html($label); ?></span>
                    <?php endforeach; ?>
                  </div>
                </td>
              </tr>
            <?php endif; ?>
            <tr>
              <th scope="row"><?php esc_html_e('Tanggal dibuat', 'hcisysq'); ?></th>
              <td><?php echo $createdAtDisplay ? esc_html($createdAtDisplay) : '&mdash;'; ?></td>
            </tr>
            <tr>
              <th scope="row"><?php esc_html_e('Terakhir diperbarui', 'hcisysq'); ?></th>
              <td><?php echo $updatedAtDisplay ? esc_html($updatedAtDisplay) : '&mdash;'; ?></td>
            </tr>
            <?php if ($historyUrl) : ?>
              <tr>
                <th scope="row"><?php esc_html_e('URL histori', 'hcisysq'); ?></th>
                <td><a class="btn-link" href="<?php echo $historyUrl; ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Buka di tab baru', 'hcisysq'); ?></a></td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
        <div class="form-actions">
          <button type="submit" class="btn-primary" name="task_action" value="update"><?php esc_html_e('Simpan perubahan', 'hcisysq'); ?></button>
        </div>
      </form>
      <div class="hcisysq-history-quick-actions">
        <form method="post">
          <?php wp_nonce_field('hcisysq_task_action_' . $taskId); ?>
          <?php if ($status === 'archived') : ?>
            <button type="submit" class="btn-light" name="task_action" value="publish"><?php esc_html_e('Publikasikan', 'hcisysq'); ?></button>
          <?php else : ?>
            <button type="submit" class="btn-light" name="task_action" value="archive"><?php esc_html_e('Arsipkan', 'hcisysq'); ?></button>
          <?php endif; ?>
        </form>
        <form method="post">
          <?php wp_nonce_field('hcisysq_task_action_' . $taskId); ?>
          <?php if ($status === 'completed') : ?>
            <button type="submit" class="btn-light" name="task_action" value="publish"><?php esc_html_e('Aktifkan', 'hcisysq'); ?></button>
          <?php else : ?>
            <button type="submit" class="btn-light" name="task_action" value="complete"><?php esc_html_e('Tandai selesai', 'hcisysq'); ?></button>
          <?php endif; ?>
        </form>
        <form method="post" onsubmit="return confirm('<?php echo esc_js(__('Hapus tugas ini secara permanen?', 'hcisysq')); ?>');">
          <?php wp_nonce_field('hcisysq_task_action_' . $taskId); ?>
          <button type="submit" class="btn-light btn-danger" name="task_action" value="delete"><?php esc_html_e('Hapus tugas', 'hcisysq'); ?></button>
        </form>
      </div>
    </section>

    <section class="hcisysq-history-card">
      <h2><?php esc_html_e('Rekapitulasi penyelesaian', 'hcisysq'); ?></h2>
      <?php if (!empty($assignments)) : ?>
        <div class="hcisysq-task-list">
          <table class="hcisysq-history-assignment-table">
            <thead>
              <tr>
                <th scope="col"><?php esc_html_e('No.', 'hcisysq'); ?></th>
                <th scope="col"><?php esc_html_e('Nama Pegawai', 'hcisysq'); ?></th>
                <th scope="col"><?php esc_html_e('Unit', 'hcisysq'); ?></th>
                <th scope="col"><?php esc_html_e('Status', 'hcisysq'); ?></th>
                <th scope="col"><?php esc_html_e('Aksi', 'hcisysq'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($assignments as $index => $row) :
                $rowStatus = isset($row['status']) ? (string) $row['status'] : 'pending';
                $assignmentClass = $rowStatus === 'completed' ? 'is-done' : 'is-pending';
                $assignmentLabel = $rowStatus === 'completed' ? __('Selesai', 'hcisysq') : __('Belum selesai', 'hcisysq');
                $completedAt = isset($row['completed_at']) ? (string) $row['completed_at'] : '';
                $completedDisplay = $completedAt ? mysql2date('d M Y H:i', $completedAt) : '';
              ?>
                <tr>
                  <td><?php echo esc_html((string) ($index + 1)); ?></td>
                  <td>
                    <div class="hcisysq-task-title"><?php echo esc_html($row['nama'] ?? ''); ?></div>
                    <div class="hcisysq-task-meta"><?php esc_html_e('NIP', 'hcisysq'); ?>: <?php echo esc_html($row['nip'] ?? ''); ?></div>
                  </td>
                  <td><?php echo esc_html($row['unit'] ?? ''); ?></td>
                  <td>
                    <span class="hcisysq-status-chip <?php echo esc_attr($assignmentClass); ?>"><?php echo esc_html($assignmentLabel); ?></span>
                    <?php if ($completedDisplay) : ?>
                      <div class="hcisysq-task-meta"><?php printf(esc_html__('Selesai: %s', 'hcisysq'), esc_html($completedDisplay)); ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <form method="post" class="hcisysq-history-inline-form">
                      <?php wp_nonce_field('hcisysq_task_action_' . $taskId); ?>
                      <input type="hidden" name="assignment_nip" value="<?php echo esc_attr($row['nip'] ?? ''); ?>">
                      <div class="hcisysq-history-assignment-actions">
                        <?php if ($rowStatus !== 'completed') : ?>
                          <button type="submit" class="btn-link" name="task_action" value="assignment_complete"><?php esc_html_e('Tandai selesai', 'hcisysq'); ?></button>
                        <?php else : ?>
                          <button type="submit" class="btn-link" name="task_action" value="assignment_pending"><?php esc_html_e('Batalkan selesai', 'hcisysq'); ?></button>
                        <?php endif; ?>
                      </div>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else : ?>
        <p class="hcisysq-history-empty"><?php esc_html_e('Belum ada pegawai yang ditugaskan.', 'hcisysq'); ?></p>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php get_footer(); ?>
