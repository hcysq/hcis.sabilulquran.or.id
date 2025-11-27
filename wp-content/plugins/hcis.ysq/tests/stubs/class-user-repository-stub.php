<?php
if (!defined('HCISYSQ_TEST_STUB_USER_REPOSITORY')) {
  return;
}

namespace HCISYSQ\Repositories;

class UserRepository {
  private static $records = [];

  public static function set_test_users(array $records): void {
    self::$records = $records;
  }

  public function __construct($cache = null) {}

  public function find($nip): array {
    $nip = (string)$nip;
    return self::$records[$nip] ?? [];
  }

  public function findMissingPasswordRows(int $limit = 10, bool $bypassCache = false): array {
    $missing = [];

    foreach (self::$records as $row) {
      $password = trim((string) ($row['password'] ?? ''));
      if ($password !== '') {
        continue;
      }

      $missing[] = [
        'nip' => $row['nip'] ?? '',
        'row' => isset($row['row_index']) ? ((int) $row['row_index']) + 1 : null,
      ];

      if (count($missing) >= $limit) {
        break;
      }
    }

    return $missing;
  }

  public function setPassword(string $nip, string $password): bool {
    if (!isset(self::$records[$nip])) {
      return false;
    }

    self::$records[$nip]['password'] = $password;
    return true;
  }

  public function setPasswordHash(string $nip, string $password): bool {
    return $this->setPassword($nip, $password);
  }

  public function generateAndPersistPassword(string $nip, ?string $password = null): array {
    $password = $password ?: 'temporary-password';
    return [
      'password' => $password,
      'updated' => $this->setPassword($nip, $password),
    ];
  }

  public function getByNIP($nip): array {
    return $this->find($nip);
  }

  public function all(): array {
    return array_values(self::$records);
  }

  public function create($user_id) { return false; }
  public function update($user_id) { return false; }
  public function delete($user_id) { return false; }
  public function syncFromWordPress() { return 0; }
  public function syncToWordPress(array $rows): array { return ['synced' => 0, 'failed' => 0]; }
}
