<?php
namespace HCISYSQ\Repositories;

if (!defined('ABSPATH')) exit;

class DokumenRepository extends AbstractSheetRepository {

  protected $tab = 'dokumen';
  protected $primaryKey = 'nomor';
  protected $columns = [
    'nip' => 'NIP',
    'kategori' => 'Kategori',
    'judul' => 'Judul Dokumen',
    'nomor' => 'Nomor Dokumen',
    'tanggal_terbit' => 'Tanggal Terbit',
    'tanggal_kadaluarsa' => 'Tanggal Kedaluwarsa',
    'tautan' => 'Tautan',
  ];

  public function syncToWordPress(array $rows): array {
    global $wpdb;
    $table = $wpdb->prefix . 'hcisysq_documents';
    $this->maybeCreateTable($table);
    $wpdb->query("TRUNCATE TABLE $table");

    $synced = 0;
    $failed = 0;
    foreach ($rows as $row) {
      $nip = $row['nip'] ?? '';
      $title = $row['judul'] ?? '';
      if ($nip === '' && $title === '') {
        continue;
      }
      $data = [
        'nip' => $nip,
        'kategori' => $row['kategori'] ?? '',
        'judul' => $title,
        'nomor' => $row['nomor'] ?? '',
        'tanggal_terbit' => $row['tanggal_terbit'] ?? '',
        'tanggal_kadaluarsa' => $row['tanggal_kadaluarsa'] ?? '',
        'tautan' => $row['tautan'] ?? '',
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
      nip VARCHAR(50) DEFAULT '',
      kategori VARCHAR(100) DEFAULT '',
      judul VARCHAR(255) DEFAULT '',
      nomor VARCHAR(100) DEFAULT '',
      tanggal_terbit VARCHAR(50) DEFAULT '',
      tanggal_kadaluarsa VARCHAR(50) DEFAULT '',
      tautan TEXT,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY idx_nip (nip)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
  }
}
