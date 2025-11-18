<?php
namespace HCISYSQ\Repositories;

if (!defined('ABSPATH')) exit;

class PelatihanRepository extends AbstractSheetRepository {

  protected $tab = 'pelatihan';
  protected $columns = [
    'nip' => 'NIP',
    'nama_pelatihan' => 'Nama Pelatihan',
    'penyelenggara' => 'Penyelenggara',
    'tanggal_mulai' => 'Tanggal Mulai',
    'tanggal_selesai' => 'Tanggal Selesai',
    'status' => 'Status',
    'sertifikat' => 'Link Sertifikat',
  ];

  public function syncToWordPress(array $rows): array {
    global $wpdb;
    $table = $wpdb->prefix . 'ysq_training_history';
    $this->ensureColumns($table);
    $grouped = [];

    foreach ($rows as $row) {
      $nip = $row['nip'] ?? '';
      if ($nip === '') {
        continue;
      }
      $grouped[$nip][] = $row;
    }

    $synced = 0;
    $failed = 0;
    foreach ($grouped as $nip => $entries) {
      $employeeId = $this->findEmployeeIdByNip($nip);
      if (!$employeeId) {
        continue;
      }
      $wpdb->delete($table, ['employee_id' => $employeeId]);
      foreach ($entries as $entry) {
        $data = [
          'employee_id' => $employeeId,
          'course_name' => $entry['nama_pelatihan'] ?? '',
          'organizer' => $entry['penyelenggara'] ?? '',
          'training_date' => $entry['tanggal_mulai'] ?? '',
          'end_date' => $entry['tanggal_selesai'] ?? '',
          'venue' => '',
          'cost' => '',
          'funding_source' => '',
          'payment_method' => '',
          'payment_proof_file' => '',
          'status' => $entry['status'] ?? 'Diajukan',
          'certificate_file' => $entry['sertifikat'] ?? '',
        ];
        $formats = ['%d','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s'];
        if ($wpdb->insert($table, $data, $formats) !== false) {
          $synced++;
        } else {
          $failed++;
        }
      }
    }

    return [
      'synced' => $synced,
      'failed' => $failed,
    ];
  }

  protected function ensureColumns(string $table): void {
    global $wpdb;
    $columns = $wpdb->get_col("SHOW COLUMNS FROM $table");
    if (!in_array('end_date', $columns, true)) {
      $wpdb->query("ALTER TABLE $table ADD COLUMN end_date VARCHAR(50) DEFAULT '' AFTER training_date");
    }
  }
}
