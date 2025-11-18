<?php
namespace HCISYSQ\Repositories;

if (!defined('ABSPATH')) exit;

class PendidikanRepository extends AbstractSheetRepository {

  protected $tab = 'pendidikan';
  protected $columns = [
    'nip' => 'NIP',
    'jenjang' => 'Jenjang',
    'institusi' => 'Institusi',
    'jurusan' => 'Jurusan',
    'tahun_masuk' => 'Tahun Masuk',
    'tahun_lulus' => 'Tahun Lulus',
    'nilai' => 'Nilai/IPK',
  ];

  public function syncToWordPress(array $rows): array {
    global $wpdb;
    $table = $wpdb->prefix . 'ysq_education_history';
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
          'level' => $entry['jenjang'] ?? '',
          'institution_name' => $entry['institusi'] ?? '',
          'major' => $entry['jurusan'] ?? '',
          'end_year' => $entry['tahun_lulus'] ?? '',
        ];
        $formats = ['%d', '%s', '%s', '%s', '%s'];
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
}
