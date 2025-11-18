<?php
namespace HCISYSQ\Repositories;

if (!defined('ABSPATH')) exit;

class PayrollRepository extends AbstractSheetRepository {

  protected $tab = 'payroll';
  protected $columns = [
    'nip' => 'NIP',
    'periode' => 'Periode',
    'gaji_pokok' => 'Gaji Pokok',
    'tunjangan' => 'Tunjangan',
    'potongan' => 'Potongan',
    'take_home_pay' => 'Take Home Pay',
    'status' => 'Status',
  ];

  public function syncToWordPress(array $rows): array {
    global $wpdb;
    $table = $wpdb->prefix . 'hcisysq_payroll';
    $this->maybeCreateTable($table);
    $synced = 0;
    $failed = 0;

    $wpdb->query("TRUNCATE TABLE $table");

    foreach ($rows as $row) {
      $nip = $row['nip'] ?? '';
      $periode = $row['periode'] ?? '';
      if ($nip === '' || $periode === '') {
        continue;
      }
      $data = [
        'nip' => $nip,
        'periode' => $periode,
        'gaji_pokok' => $row['gaji_pokok'] ?? '',
        'tunjangan' => $row['tunjangan'] ?? '',
        'potongan' => $row['potongan'] ?? '',
        'take_home_pay' => $row['take_home_pay'] ?? '',
        'status' => $row['status'] ?? '',
        'updated_at' => current_time('mysql'),
      ];
      $formats = array_fill(0, count($data), '%s');
      $inserted = $wpdb->insert($table, $data, $formats);
      if ($inserted !== false) {
        $synced++;
      } else {
        $failed++;
      }
    }

    return [
      'synced' => $synced,
      'failed' => $failed,
    ];
  }

  protected function maybeCreateTable(string $table): void {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      nip VARCHAR(50) NOT NULL,
      periode VARCHAR(50) NOT NULL,
      gaji_pokok VARCHAR(100) DEFAULT '',
      tunjangan VARCHAR(100) DEFAULT '',
      potongan VARCHAR(100) DEFAULT '',
      take_home_pay VARCHAR(100) DEFAULT '',
      status VARCHAR(50) DEFAULT '',
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_nip_periode (nip, periode)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
  }
}
