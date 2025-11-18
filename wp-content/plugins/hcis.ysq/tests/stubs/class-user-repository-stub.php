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
