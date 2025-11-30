<?php
if (!defined('HCISYSQ_TEST_STUB_ADMIN_REPOSITORY')) {
  return;
}

namespace HCISYSQ\Repositories;

class AdminRepository {
  private static $records = [];

  public static function set_test_admins(array $records): void {
    self::$records = array_values($records);
  }

  public function __construct($cache = null) {}

  public function all(bool $bypassCache = false): array {
    return array_values(self::$records);
  }

  public function getByUsername(string $username, bool $bypassCache = false): array {
    foreach (self::$records as $record) {
      if (strcasecmp($record['username'] ?? '', $username) === 0) {
        return $record;
      }
    }

    return [];
  }

  public function getPrimaryAdmin(): ?array {
    $records = $this->all();
    return $records[0] ?? null;
  }
}
