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

  public function __construct(?SheetCache $cache = null) {
    $this->api = GoogleSheetsAPI::getInstance();
    $this->cache = $cache ?? new SheetCache();
    $this->sheet_id = GoogleSheetSettings::get_sheet_id();
    $this->api->setSpreadsheetId($this->sheet_id);
  }

  public function all(bool $bypassCache = false): array {
    $cacheKey = $this->cacheKey('all');

    if ($bypassCache) {
      $this->cache->forget($cacheKey);
      return $this->resolveAllRows();
    }

    return $this->cache->remember($cacheKey, function () {
      return $this->resolveAllRows();
    });
  }

  public function find(string $value, bool $bypassCache = false): array {
    $cacheKey = $this->cacheKey('find_' . md5($value));

    if (!$bypassCache) {
      $cached = $this->cache->get($cacheKey);
      if ($cached !== null) {
        return $cached;
      }
    }

    foreach ($this->all($bypassCache) as $row) {
      if (($row[$this->primaryKey] ?? '') === $value) {
        if (!$bypassCache) {
          $this->cache->put($cacheKey, $row);
        }
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
        $success = $this->api->deleteRows(GoogleSheetSettings::get_tab_name($this->tab), $rowIndex, $rowIndex);
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
    $effective_column_labels = $this->getEffectiveColumnLabels();

    foreach ($effective_column_labels as $label) {
        // Find the internal key corresponding to this label
        $internal_key = array_search($label, $this->columns, true);
        if ($internal_key !== false) {
            $row[] = $data[$internal_key] ?? '';
        } else {
            // If a label in effective_column_labels doesn't have a corresponding internal_key,
            // it means it's a column the user expects but the system doesn't explicitly handle.
            // We can either skip it or add an empty string. Adding empty string for now.
            $row[] = '';
        }
    }
    return $row;
  }

  public function toSheetRow(array $data): array {
    return $this->buildRow($data);
  }

  protected function getEffectiveColumnLabels(): array {
    $default_columns_labels = array_values($this->columns);
    $configured_order = GoogleSheetSettings::get_tab_column_map($this->tab);

    if (empty($configured_order)) {
      return $default_columns_labels;
    }

    $filtered_order = array_values(array_filter($configured_order, function ($label) use ($default_columns_labels) {
      return in_array($label, $default_columns_labels, true);
    }));

    foreach ($default_columns_labels as $label) {
      if (!in_array($label, $filtered_order, true)) {
        $filtered_order[] = $label;
      }
    }

    return $filtered_order;
  }

  protected function resolveAllRows(): array {
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
  }

  public function getExpectedHeaders(): array {
    return $this->getEffectiveColumnLabels();
  }

  public function hasMappedColumn(string $key): bool {
    if (empty($this->column_index_map)) {
      // Ensure headers are loaded and column map is built.
      $this->all();
    }

    return array_key_exists($key, $this->column_index_map);
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
    $effective_column_labels = $this->getEffectiveColumnLabels();

    $setup_definitions = GoogleSheetSettings::get_setup_key_config();
    $tab_definitions = array_filter($setup_definitions, function ($definition) {
      return ($definition['tab'] ?? '') === $this->tab;
    });

    $direct_header_map = [];
    $sanitized_header_map = [];

    foreach ($this->sheet_headers as $index => $header) {
      $directKey = $this->normalizeDirectHeaderMatch($header);
      if ($directKey !== '') {
        $direct_header_map[$directKey] = $index;
      }

      $normalizedHeader = $this->normalizeHeader($header);
      if ($normalizedHeader !== '') {
        $sanitized_header_map[$normalizedHeader] = $index;
      }
    }

    foreach ($effective_column_labels as $internal_key_label) {
      $internal_key = array_search($internal_key_label, $this->columns, true);
      if ($internal_key === false) {
        continue;
      }

      $candidates = $this->buildColumnLabelCandidates($internal_key, $internal_key_label, $tab_definitions);
      $matched_index = $this->matchSheetHeaderIndex($candidates, $direct_header_map, $sanitized_header_map);

      if ($matched_index !== null) {
        $this->column_index_map[$internal_key] = $matched_index;
      }
    }
  }

  protected function buildColumnLabelCandidates(string $internal_key, string $effective_label, array $tab_definitions): array {
    $candidates = [$effective_label];
    $base_label = $this->columns[$internal_key] ?? '';

    if ($base_label !== '' && !in_array($base_label, $candidates, true)) {
      $candidates[] = $base_label;
    }

    foreach ($tab_definitions as $definition) {
      $definition_header = trim((string) ($definition['header'] ?? ''));
      $definition_label = trim((string) ($definition['label'] ?? ''));
      $definition_aliases = isset($definition['aliases']) && is_array($definition['aliases']) ? $definition['aliases'] : [];

      $matches_column = ($definition_header !== '' && $this->headersMatch($definition_header, $effective_label))
        || ($definition_label !== '' && $this->headersMatch($definition_label, $effective_label))
        || ($base_label !== '' && ($this->headersMatch($definition_header, $base_label) || $this->headersMatch($definition_label, $base_label)));

      if ($matches_column) {
        foreach (array_merge([$definition_header, $definition_label], $definition_aliases) as $alias) {
          $alias = trim((string) $alias);
          if ($alias !== '' && !in_array($alias, $candidates, true)) {
            $candidates[] = $alias;
          }
        }
      }
    }

    return $candidates;
  }

  protected function headersMatch(string $expected, string $actual): bool {
    return $this->normalizeHeader($expected) === $this->normalizeHeader($actual);
  }

  protected function matchSheetHeaderIndex(array $candidates, array $direct_header_map, array $sanitized_header_map): ?int {
    foreach ($candidates as $candidate) {
      $directKey = $this->normalizeDirectHeaderMatch($candidate);
      if ($directKey !== '' && array_key_exists($directKey, $direct_header_map)) {
        return $direct_header_map[$directKey];
      }
    }

    foreach ($candidates as $candidate) {
      $normalized = $this->normalizeHeader($candidate);
      if ($normalized !== '' && array_key_exists($normalized, $sanitized_header_map)) {
        return $sanitized_header_map[$normalized];
      }
    }

    return null;
  }

  protected function normalizeDirectHeaderMatch(string $header): string {
    $normalized = preg_replace('/\s+/u', ' ', trim($header));
    return strtolower((string) $normalized);
  }

  protected function normalizeHeader(string $header): string {
    $normalized = strtolower(trim((string) $header));
    $normalized = preg_replace('/[\r\n]+/u', ' ', $normalized); // Replace newlines with spaces
    $normalized = preg_replace('/\([^)]*\)/u', ' ', $normalized); // Remove text inside parentheses
    $normalized = preg_replace('/\[[^\]]*\]/u', ' ', $normalized); // Remove text inside brackets
    $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $normalized); // Remove punctuation/symbols
    $normalized = preg_replace('/\s+/u', ' ', $normalized);
    return trim((string) $normalized);
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
    return implode('_', ['sheet', $this->sheet_id, $this->tab, $suffix]);
  }

  protected function flushCache(): void {
    $this->cache->forget($this->cacheKey('all'));
    $this->bumpCacheBuster();
  }

  protected function getCacheBuster(): string {
    $optionKey = $this->getCacheBusterOptionKey();
    $buster = get_option($optionKey);

    if (empty($buster)) {
      $buster = uniqid('', true);
      update_option($optionKey, $buster);
    }

    return (string) $buster;
  }

  protected function bumpCacheBuster(): void {
    update_option($this->getCacheBusterOptionKey(), uniqid('', true));
  }

  protected function getCacheBusterOptionKey(): string {
    return implode('_', ['hcis', 'gs', 'cache', 'buster', $this->tab]);
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
