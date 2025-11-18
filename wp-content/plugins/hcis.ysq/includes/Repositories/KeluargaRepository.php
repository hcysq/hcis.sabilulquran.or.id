<?php
namespace HCISYSQ\Repositories;

if (!defined('ABSPATH')) exit;

class KeluargaRepository extends AbstractSheetRepository {

  protected $tab = 'keluarga';
  protected $columns = [
    'nip' => 'NIP',
    'nama' => 'Nama Anggota',
    'hubungan' => 'Hubungan',
    'tanggal_lahir' => 'Tanggal Lahir',
    'pekerjaan' => 'Pekerjaan',
    'pendidikan' => 'Pendidikan',
  ];

  public function syncToWordPress(array $rows): int {
    global $wpdb;
    $table = $wpdb->prefix . 'ysq_family_members';
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
    foreach ($grouped as $nip => $members) {
      $employeeId = $this->findEmployeeIdByNip($nip);
      if (!$employeeId) {
        continue;
      }
      $wpdb->delete($table, ['employee_id' => $employeeId]);
      foreach ($members as $member) {
        $data = [
          'employee_id' => $employeeId,
          'name' => $member['nama'] ?? '',
          'relationship' => $member['hubungan'] ?? '',
          'birth_date' => $member['tanggal_lahir'] ?? '',
          'occupation' => $member['pekerjaan'] ?? '',
          'education' => $member['pendidikan'] ?? '',
        ];
        $formats = ['%d', '%s', '%s', '%s', '%s', '%s'];
        if ($wpdb->insert($table, $data, $formats) !== false) {
          $synced++;
        }
      }
    }

    return $synced;
  }

  protected function ensureColumns(string $table): void {
    global $wpdb;
    $columns = $wpdb->get_col("SHOW COLUMNS FROM $table");
    $maybeAdd = function ($name, $definition) use ($table, $columns, $wpdb) {
      if (!in_array($name, $columns, true)) {
        $wpdb->query("ALTER TABLE $table ADD COLUMN $name $definition");
      }
    };
    $maybeAdd('occupation', "VARCHAR(255) DEFAULT ''");
    $maybeAdd('education', "VARCHAR(255) DEFAULT ''");
  }
}
