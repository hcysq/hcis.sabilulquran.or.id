<?php
namespace HCISYSQ\Repositories;

if (!defined('ABSPATH')) exit;

class ProfileRepository extends AbstractSheetRepository {

  protected $tab = 'profiles';
  protected $columns = [
    'nip' => 'NIP',
    'nama' => 'Nama',
    'unit' => 'Unit',
    'jabatan' => 'Jabatan',
    'tempat_lahir' => 'Tempat Lahir',
    'tanggal_lahir' => 'Tanggal Lahir',
    'alamat_ktp' => 'Alamat KTP',
    'desa' => 'Desa/Kelurahan',
    'kecamatan' => 'Kecamatan',
    'kota' => 'Kota/Kabupaten',
    'kode_pos' => 'Kode Pos',
    'email' => 'Email',
    'hp' => 'No HP',
    'tmt' => 'TMT',
  ];

  public function syncToWordPress(array $rows): int {
    global $wpdb;
    $table = $wpdb->prefix . 'hcisysq_profiles';
    $synced = 0;

    foreach ($rows as $row) {
      $nip = $row['nip'] ?? '';
      $nama = $row['nama'] ?? '';
      if ($nip === '' || $nama === '') {
        continue;
      }
      $data = [
        'nip' => $nip,
        'nama' => $nama,
        'unit' => $row['unit'] ?? '',
        'jabatan' => $row['jabatan'] ?? '',
        'tempat_lahir' => $row['tempat_lahir'] ?? '',
        'tanggal_lahir' => $row['tanggal_lahir'] ?? '',
        'alamat_ktp' => $row['alamat_ktp'] ?? '',
        'desa' => $row['desa'] ?? '',
        'kecamatan' => $row['kecamatan'] ?? '',
        'kota' => $row['kota'] ?? '',
        'kode_pos' => $row['kode_pos'] ?? '',
        'email' => $row['email'] ?? '',
        'hp' => $row['hp'] ?? '',
        'tmt' => $row['tmt'] ?? '',
        'updated_at' => current_time('mysql'),
      ];
      $formats = array_fill(0, count($data), '%s');
      $result = $wpdb->replace($table, $data, $formats);
      if ($result !== false) {
        $synced++;
      }
    }

    return $synced;
  }
}
