<?php
namespace HCISYSQ;

if (!defined("ABSPATH")) exit;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\Spreadsheet;

/**
 * Google Sheets API Wrapper
 *
 * Handles authentication, CRUD operations, and quota tracking
 * for real-time synchronization with Google Sheets
 *
 * @package HCISYSQ
 */
class GoogleSheetsAPI {

  private $client;
  private $service;
  private $spreadsheet_id;
  private $authenticated = false;

  const QUOTA_LIMIT = 500;
  const BATCH_SIZE = 50;

  /**
   * Authenticate with Google Service Account
   */
  public function authenticate($credentials) {
    try {
      $this->client = new Client();
      $this->client->setAuthConfig($credentials);
      $this->client->addScope(Sheets::SPREADSHEETS);

      $this->service = new Sheets($this->client);
      $this->authenticated = true;

      hcisysq_log("Google Sheets API authenticated successfully");
      return true;
    } catch (\Exception $e) {
      hcisysq_log("Google Sheets authentication failed: " . $e->getMessage(), "ERROR");
      return false;
    }
  }

  public function setSpreadsheetId($spreadsheet_id) {
    $this->spreadsheet_id = $spreadsheet_id;
  }

  public function getService() {
    return $this->service;
  }

  public function getSpreadsheet() {
    if (!$this->authenticated || !$this->spreadsheet_id) {
      return null;
    }

    try {
      $quotaRecord = $this->recordQuotaUsage("getSpreadsheet");
      if (!$quotaRecord["allowed"]) {
        throw new \Exception("Quota limit exceeded");
      }

      return $this->service->spreadsheets->get($this->spreadsheet_id);
    } catch (\Exception $e) {
      hcisysq_log("Get spreadsheet failed: " . $e->getMessage(), "ERROR");
      return null;
    }
  }

  public function getRows($range) {
    if (!$this->authenticated) {
      return [];
    }

    try {
      $quotaRecord = $this->recordQuotaUsage("getRows");
      if (!$quotaRecord["allowed"]) {
        throw new \Exception("Quota limit exceeded");
      }

      $response = $this->service->spreadsheets_values->get(
        $this->spreadsheet_id,
        $range
      );

      $values = $response->getValues();
      return $values ?? [];
    } catch (\Exception $e) {
      hcisysq_log("Get rows failed: " . $e->getMessage(), "ERROR");
      return [];
    }
  }

  public function appendRows($range, $values) {
    if (!$this->authenticated || empty($values)) {
      return false;
    }

    try {
      $quotaRecord = $this->recordQuotaUsage("appendRows");
      if (!$quotaRecord["allowed"]) {
        throw new \Exception("Quota limit exceeded");
      }

      $body = new Sheets\ValueRange([
        "values" => $values
      ]);

      $params = [
        "valueInputOption" => "RAW",
        "insertDataOption" => "INSERT_ROWS"
      ];

      $result = $this->service->spreadsheets_values->append(
        $this->spreadsheet_id,
        $range,
        $body,
        $params
      );

      hcisysq_log("Appended " . count($values) . " rows to " . $range);
      return true;
    } catch (\Exception $e) {
      hcisysq_log("Append rows failed: " . $e->getMessage(), "ERROR");
      return false;
    }
  }

  public function updateRows($range, $values) {
    if (!$this->authenticated || empty($values)) {
      return false;
    }

    try {
      $quotaRecord = $this->recordQuotaUsage("updateRows");
      if (!$quotaRecord["allowed"]) {
        throw new \Exception("Quota limit exceeded");
      }

      $body = new Sheets\ValueRange([
        "values" => $values
      ]);

      $params = [
        "valueInputOption" => "RAW"
      ];

      $result = $this->service->spreadsheets_values->update(
        $this->spreadsheet_id,
        $range,
        $body,
        $params
      );

      hcisysq_log("Updated " . count($values) . " rows in " . $range);
      return true;
    } catch (\Exception $e) {
      hcisysq_log("Update rows failed: " . $e->getMessage(), "ERROR");
      return false;
    }
  }

  public function deleteRows($sheet_name, $gid, $start_row, $end_row) {
    if (!$this->authenticated) {
      return false;
    }

    try {
      $quotaRecord = $this->recordQuotaUsage("deleteRows");
      if (!$quotaRecord["allowed"]) {
        throw new \Exception("Quota limit exceeded");
      }

      $request = new Sheets\Request([
        "deleteRange" => new Sheets\DeleteRangeRequest([
          "range" => new Sheets\GridRange([
            "sheetId" => $gid,
            "startRowIndex" => $start_row,
            "endRowIndex" => $end_row + 1,
          ]),
          "shiftDimension" => "ROWS"
        ])
      ]);

      $batch = new Sheets\BatchUpdateSpreadsheetRequest([
        "requests" => [$request]
      ]);

      $this->service->spreadsheets->batchUpdate(
        $this->spreadsheet_id,
        $batch
      );

      hcisysq_log("Deleted rows " . $start_row . "-" . $end_row . " from " . $sheet_name);
      return true;
    } catch (\Exception $e) {
      hcisysq_log("Delete rows failed: " . $e->getMessage(), "ERROR");
      return false;
    }
  }

  public function clearRange($range) {
    if (!$this->authenticated) {
      return false;
    }

    try {
      $quotaRecord = $this->recordQuotaUsage("clearRange");
      if (!$quotaRecord["allowed"]) {
        throw new \Exception("Quota limit exceeded");
      }

      $this->service->spreadsheets_values->clear(
        $this->spreadsheet_id,
        $range,
        new Sheets\ClearValuesRequest()
      );

      hcisysq_log("Cleared range: " . $range);
      return true;
    } catch (\Exception $e) {
      hcisysq_log("Clear range failed: " . $e->getMessage(), "ERROR");
      return false;
    }
  }

  public function batchUpdate($data) {
    if (!$this->authenticated || empty($data)) {
      return false;
    }

    try {
      $quotaRecord = $this->recordQuotaUsage("batchUpdate");
      if (!$quotaRecord["allowed"]) {
        throw new \Exception("Quota limit exceeded");
      }

      $requests = [];
      foreach ($data as $range => $values) {
        $requests[] = new Sheets\ValueRange([
          "range" => $range,
          "values" => $values
        ]);
      }

      $body = new Sheets\BatchUpdateValuesRequest([
        "data" => $requests,
        "valueInputOption" => "RAW"
      ]);

      $result = $this->service->spreadsheets_values->batchUpdate(
        $this->spreadsheet_id,
        $body
      );

      hcisysq_log("Batch updated " . count($requests) . " ranges");
      return true;
    } catch (\Exception $e) {
      hcisysq_log("Batch update failed: " . $e->getMessage(), "ERROR");
      return false;
    }
  }

  private function recordQuotaUsage($operation) {
    global $wpdb;

    $current_timestamp = time();
    $window_start = $current_timestamp - 100;

    $count = $wpdb->get_var($wpdb->prepare(
      "SELECT COUNT(*) FROM {$wpdb->options}
       WHERE option_name LIKE %s AND option_value > %d",
      "hcis_gs_quota_%",
      $window_start
    ));

    $allowed = $count < self::QUOTA_LIMIT;

    update_option(
      "hcis_gs_quota_" . date("YmdHis", $current_timestamp),
      $current_timestamp,
      "no"
    );

    $metrics = get_option("hcis_gs_quota_metrics", []);
    $metrics["operation"] = $operation;
    $metrics["timestamp"] = $current_timestamp;
    $metrics["usage_percent"] = round(($count / self::QUOTA_LIMIT) * 100, 2);
    update_option("hcis_gs_quota_metrics", $metrics);

    if (!$allowed) {
      hcisysq_log("Quota limit approaching: " . $count . "/" . self::QUOTA_LIMIT, "WARNING");
    }

    return [
      "allowed" => $allowed,
      "usage" => $count,
      "limit" => self::QUOTA_LIMIT
    ];
  }

  public function getQuotaMetrics() {
    return get_option("hcis_gs_quota_metrics", [
      "operation" => "none",
      "timestamp" => 0,
      "usage_percent" => 0
    ]);
  }

  public function isAuthenticated() {
    return $this->authenticated;
  }
}
