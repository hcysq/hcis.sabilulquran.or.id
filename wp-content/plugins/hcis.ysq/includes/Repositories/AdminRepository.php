<?php
namespace HCISYSQ\Repositories;

if (!defined('ABSPATH')) exit;

use function hcisysq_log;

class AdminRepository extends AbstractSheetRepository {

  protected $tab = 'admins';
  protected $primaryKey = 'username';
  protected $columns = [
    'username' => 'Username',
    'display_name' => 'Display Name',
    'password' => 'Password',
    'whatsapp' => 'WhatsApp',
  ];

  /**
   * Admin rows must include plaintext password values (no password_hash column support).
   */
  private $requiredColumns = ['username', 'password'];

  public function all(bool $bypassCache = false): array {
    $rows = parent::all($bypassCache);

    $missingColumns = $this->missingRequiredColumns();
    if (!empty($missingColumns)) {
      hcisysq_log(
        sprintf(
          'AdminRepository::all - Missing required columns in sheet header: %s',
          implode(', ', $missingColumns)
        ),
        'warning'
      );
      return [];
    }

    $filtered = [];
    foreach ($rows as $row) {
      if ($this->rowHasRequiredValues($row)) {
        $filtered[] = $row;
      } else {
        $rowIndex = $row['row_index'] ?? 'unknown';
        hcisysq_log(
          sprintf('AdminRepository::all - Skipping row %s due to incomplete required fields', $rowIndex),
          'warning'
        );
      }
    }

    return $filtered;
  }

  public function getByUsername(string $username, bool $bypassCache = false): array {
    $username = sanitize_user($username, true);
    if ($username === '') {
      return [];
    }

    foreach ($this->all($bypassCache) as $row) {
      if (strcasecmp($row['username'] ?? '', $username) === 0) {
        return $row;
      }
    }

    return [];
  }

  public function getPrimaryAdmin(): ?array {
    $all = $this->all();
    return $all[0] ?? null;
  }

  private function hasRequiredColumns(): bool {
    return empty($this->missingRequiredColumns());
  }

  private function missingRequiredColumns(): array {
    $missing = [];

    foreach ($this->requiredColumns as $column) {
      if (!array_key_exists($column, $this->column_index_map)) {
        $missing[] = $column;
      }
    }

    return $missing;
  }

  private function rowHasRequiredValues(array $row): bool {
    foreach ($this->requiredColumns as $column) {
      if (empty($row[$column])) {
        return false;
      }
    }
    return true;
  }
}
