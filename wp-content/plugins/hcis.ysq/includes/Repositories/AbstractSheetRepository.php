<?php
namespace HCISYSQ\Repositories;

if (!defined('ABSPATH')) exit;

use HCISYSQ\GoogleSheetSettings;
use HCISYSQ\GoogleSheetsAPI;
use HCISYSQ\SheetCache;

abstract class AbstractSheetRepository {

  protected $api;
  protected $cache;
  protected $sheet_id;
  protected $tab = '';
  protected $columns = [];
  protected $primaryKey = 'nip';
  protected $column_index_map = [];
  protected $sheet_headers = [];
  protected $configured_columns = null;

  public function __construct(?SheetCache $cache = null) {
    $this->api = GoogleSheetsAPI::getInstance();
    $this->cache = $cache ?? new SheetCache();
    $this->sheet_id = GoogleSheetSettings::get_sheet_id();
    $this->api->setSpreadsheetId($this->sheet_id);
  }

  public function all(): array {
    $cacheKey = $this->cacheKey('all');
    return $this->cache->remember($cacheKey, function () {
      $range = GoogleSheetSettings::get_tab_range($this->tab);
      $all_rows = $this->api->getRows($range);

      if (empty($all_rows)) {
        return [];
      }

      // Assume the first row is the header
      $this->sheet_headers = array_map('trim', array_shift($all_rows));

      // Build the column index map
      $this->buildColumnIndexMap();

      $result = [];
      foreach ($all_rows as $index => $row) {
        $normalized = $this->mapRow($row, $index + 1); // +1 because we removed the header row
        if (!empty(array_filter($normalized, static function ($value) { return $value !== ''; }))) {
          $result[] = $normalized;
        }
      }
      return $result;
    });
  }

  public function find(string $value): array {
    $cacheKey = $this->cacheKey('find_' . md5($value));
    $cached = $this->cache->get($cacheKey);
    if ($cached !== null) {
      return $cached;
    }

    foreach ($this->all() as $row) {
      if (($row[$this->primaryKey] ?? '') === $value) {
        $this->cache->put($cacheKey, $row);
        return $row;
      }
    }

    return [];
  }

  public function append(array $data): bool {
    $row = $this->buildRow($data);
    $range = GoogleSheetSettings::get_tab_range($this->tab);
    $success = $this->api->appendRows($range, [$row]);
    if ($success) {
      $this->flushCache();
    }
    return $success;
  }

  public function updateByPrimary(array $data): bool {
    $key = $data[$this->primaryKey] ?? '';
    if ($key === '') {
      return false;
    }
    $range = $this->findRangeByPrimary($key);
    if (!$range) {
      return false;
    }
    $row = $this->buildRow($data);
    $success = $this->api->updateRows($range, [$row]);
    if ($success) {
      $this->flushCache();
    }
    return $success;
  }

  public function deleteByPrimary(string $value): bool {
    $rows = $this->all();
    foreach ($rows as $row) {
      if (($row[$this->primaryKey] ?? '') === $value) {
        $rowIndex = $row['row_index'] ?? null;
        if ($rowIndex === null) {
          continue;
        }
        $gid = GoogleSheetSettings::resolve_tab_gid($this->tab);
        if ($gid === '') {
          hcisysq_log(sprintf('Unable to resolve GID for tab %s when deleting %s', $this->tab, $value), 'error');
          return false;
        }
        $startIndex = max(0, (int) $rowIndex);
        $endIndex = $startIndex + 1;
        $success = $this->api->deleteRows($gid, $startIndex, $endIndex);
        if ($success) {
          $this->flushCache();
        }
        return (bool) $success;
      }
    }
    return false;
  }

  public function syncToWordPress(array $rows): array {
    // default no-op
    return [
      'synced' => 0,
      'failed' => 0,
    ];
  }

  protected function mapRow(array $row, int $index): array {
    $mapped = ['row_index' => $index];
    foreach ($this->columns as $internal_key => $label) {
        $sheet_column_index = $this->column_index_map[$internal_key] ?? null;
        if ($sheet_column_index !== null && isset($row[$sheet_column_index])) {
            $mapped[$internal_key] = trim((string) $row[$sheet_column_index]);
        } else {
            $mapped[$internal_key] = ''; // Default empty if not found or mapped
        }
    }
    return $mapped;
  }

  protected function buildRow(array $data): array {
    $row = [];
    $configured_columns = $this->getConfiguredColumns();

    foreach ($configured_columns as $column) {
      $internalKey = $column['key'] ?? null;
      $setupKey = $column['setup_key'] ?? null;
      $valueKey = null;
      if ($internalKey !== null && $internalKey !== '') {
        $valueKey = $internalKey;
      } elseif ($setupKey !== null && $setupKey !== '') {
        $valueKey = $setupKey;
      }

      if ($valueKey !== null && array_key_exists($valueKey, $data)) {
        $row[] = $data[$valueKey];
      } else {
        $row[] = '';
      }
    }
    return $row;
  }

  public function toSheetRow(array $data): array {
    return $this->buildRow($data);
  }

  public function getTabRange(): string {
    return GoogleSheetSettings::get_tab_range($this->tab);
  }

  public function getTabSlug(): string {
    return $this->tab;
  }

  protected function findRangeByPrimary(string $value): ?string {
    $range = GoogleSheetSettings::get_tab_range($this->tab);
    $rows = $this->api->getRows($range);
    if (empty($rows)) {
      return null;
    }
    foreach ($rows as $index => $row) {
      $columnIndex = array_search($this->primaryKey, array_keys($this->columns), true);
      if ($columnIndex === false) {
        continue;
      }
      $cellValue = isset($row[$columnIndex]) ? trim((string) $row[$columnIndex]) : '';
      if ($cellValue === $value) {
        $endColumn = $this->columnLetter(count($this->columns));
        $rowNumber = $index + 1;
        return sprintf('%s!A%d:%s%d', GoogleSheetSettings::get_tab_name($this->tab), $rowNumber, $endColumn, $rowNumber);
      }
    }
    return null;
  }

  protected function buildColumnIndexMap(): void {
    $this->column_index_map = [];
    $configured_columns = $this->getConfiguredColumns();

    foreach ($configured_columns as $column) {
      $label = $column['label'] ?? '';
      if ($label === '') {
        continue;
      }

      $sheet_column_index = array_search($label, $this->sheet_headers, true);
      if ($sheet_column_index === false) {
        continue;
      }

      $internal_key = $column['key'] ?? null;
      if ($internal_key === null || $internal_key === '') {
        $setupKey = $column['setup_key'] ?? null;
        if ($setupKey !== null && array_key_exists($setupKey, $this->columns)) {
          $internal_key = $setupKey;
        } else {
          $matching = array_search($label, $this->columns, true);
          if ($matching !== false) {
            $internal_key = $matching;
          }
        }
      }

      if ($internal_key === null || $internal_key === '') {
        continue;
      }

      $this->column_index_map[$internal_key] = $sheet_column_index;
    }
  }

  protected function columnLetter(int $count): string {
    $letter = '';
    while ($count > 0) {
      $remainder = ($count - 1) % 26;
      $letter = chr(65 + $remainder) . $letter;
      $count = (int) (($count - $remainder - 1) / 26);
    }
    return $letter === '' ? 'A' : $letter;
  }

  protected function cacheKey(string $suffix): string {
    return implode('_', ['sheet', $this->tab, $suffix]);
  }

  protected function flushCache(): void {
    $this->cache->forget($this->cacheKey('all'));
  }

  protected function getConfiguredColumns(): array {
    if ($this->configured_columns !== null) {
      return $this->configured_columns;
    }

    $isLegacyMap = null;
    $configured_map = GoogleSheetSettings::get_tab_column_map($this->tab, $isLegacyMap);
    $configured = [];
    $present_keys = [];

    foreach ($configured_map as $entry) {
      $label = isset($entry['label']) ? trim((string) $entry['label']) : '';
      $setupKey = isset($entry['setup_key']) ? trim((string) $entry['setup_key']) : '';
      $internalKey = $setupKey !== '' && array_key_exists($setupKey, $this->columns) ? $setupKey : null;

      if ($internalKey === null && $label !== '') {
        $matching = array_search($label, $this->columns, true);
        if ($matching !== false) {
          $internalKey = $matching;
        }
      }

      $configured[] = [
        'key' => $internalKey,
        'setup_key' => $setupKey,
        'label' => $label !== '' ? $label : ($internalKey !== null ? $this->columns[$internalKey] : ''),
        'optional' => false,
        'position' => $entry['position'] ?? count($configured),
      ];

      if ($internalKey !== null) {
        $present_keys[] = $internalKey;
      }
    }

    foreach ($this->columns as $internal => $label) {
      if (in_array($internal, $present_keys, true)) {
        continue;
      }
      $configured[] = [
        'key' => $internal,
        'setup_key' => $internal,
        'label' => $label,
        'optional' => true,
        'position' => count($configured),
      ];
    }

    if (empty($configured)) {
      foreach ($this->columns as $internal => $label) {
        $configured[] = [
          'key' => $internal,
          'setup_key' => $internal,
          'label' => $label,
          'optional' => true,
          'position' => count($configured),
        ];
      }
    }

    usort($configured, static function ($a, $b) {
      return ($a['position'] ?? 0) <=> ($b['position'] ?? 0);
    });

    return $this->configured_columns = $configured;
  }

  protected function findEmployeeIdByNip(string $nip): ?int {
    static $cache = [];
    if (isset($cache[$nip])) {
      return $cache[$nip];
    }
    global $wpdb;
    $table = $wpdb->prefix . 'ysq_employees';
    $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE employee_id_number = %s", $nip));
    $cache[$nip] = $id ? (int) $id : null;
    return $cache[$nip];
  }
}
