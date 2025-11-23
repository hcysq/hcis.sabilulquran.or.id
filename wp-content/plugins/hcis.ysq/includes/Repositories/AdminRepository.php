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
    'password_hash' => 'Password Hash',
    'whatsapp' => 'WhatsApp',
  ];

  private $requiredColumns = ['username', 'display_name', 'password_hash', 'whatsapp'];

  public function all(): array {
    $rows = parent::all();

    if (!$this->hasRequiredColumns()) {
      hcisysq_log('AdminRepository::all - Missing required columns in sheet header', 'warning');
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

  public function getByUsername(string $username): array {
    $username = sanitize_user($username, true);
    if ($username === '') {
      return [];
    }

    foreach ($this->all() as $row) {
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
    foreach ($this->requiredColumns as $column) {
      if (!array_key_exists($column, $this->column_index_map)) {
        return false;
      }
    }
    return true;
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
