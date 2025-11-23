<?php
namespace HCISYSQ;

if (!defined("ABSPATH")) exit;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Exception as GoogleServiceException;

/**
 * Google Sheets API Wrapper (Singleton)
 *
 * Handles authentication, CRUD operations, and error handling
 * for real-time synchronization with Google Sheets.
 *
 * @package HCISYSQ
 */
class GoogleSheetsAPI {

    private static $instance = null;
    private $client;
    private $service;
    private $spreadsheet_id;
    private $authenticated = false;
    private $sheetIdCache = [];

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct() {
        // The constructor is private to enforce the singleton pattern.
    }

    /**
     * Get the singleton instance of the API client.
     *
     * @return GoogleSheetsAPI
     * @throws \Exception If authentication fails.
     */
    public static function getInstance(): GoogleSheetsAPI {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->authenticate();
        }
        return self::$instance;
    }

    /**
     * Authenticate with Google Service Account credentials from settings.
     *
     * @throws \Exception If credentials are not configured or are invalid.
     */
    private function authenticate() {
        if ($this->authenticated) {
            return;
        }

        $credentials = GoogleSheetSettings::get_credentials();
        if (empty($credentials)) {
            throw new \Exception('Google API credentials are not configured.');
        }

        try {
            $this->client = new Client();
            $this->client->setAuthConfig($credentials);
            $this->client->addScope(Sheets::SPREADSHEETS);

            $this->service = new Sheets($this->client);
            $this->authenticated = true;
            
            $this->setSpreadsheetId(GoogleSheetSettings::get_sheet_id());

        } catch (\Exception $e) {
            $this->authenticated = false;
            throw new \Exception("Google Sheets authentication failed: " . $e->getMessage());
        }
    }

    public function setSpreadsheetId($spreadsheet_id) {
        $this->spreadsheet_id = $spreadsheet_id;
        $this->sheetIdCache = [];
    }

    public function getService() {
        return $this->service;
    }

    /**
     * @return \Google\Service\Sheets\Spreadsheet
     * @throws GoogleServiceException
     * @throws \Exception
     */
    public function getSpreadsheet() {
        if (!$this->authenticated || !$this->spreadsheet_id) {
            throw new \Exception("API not authenticated or Spreadsheet ID not set.");
        }

        try {
            return $this->service->spreadsheets->get($this->spreadsheet_id);
        } catch (GoogleServiceException $e) {
            hcisysq_log("Get spreadsheet failed: " . $e->getMessage(), "ERROR", ['code' => $e->getCode()]);
            throw $e; // Re-throw for the caller to handle
        }
    }

    public function getSheetIdByTitle(string $title): ?int {
        if (!$this->authenticated) {
            return null;
        }

        if (isset($this->sheetIdCache[$title])) {
            return $this->sheetIdCache[$title];
        }

        try {
            $spreadsheet = $this->getSpreadsheet();
            $sheets = $spreadsheet->getSheets();
            if (!is_array($sheets)) {
                return null;
            }

            foreach ($sheets as $sheet) {
                $properties = $sheet->getProperties();
                if (!$properties) {
                    continue;
                }

                $sheetTitle = $properties->getTitle();
                $sheetId = $properties->getSheetId();

                if ($sheetTitle === null || $sheetId === null) {
                    continue;
                }

                $this->sheetIdCache[$sheetTitle] = $sheetId;
            }
        } catch (GoogleServiceException $e) {
            hcisysq_log("Get sheet metadata failed: " . $e->getMessage(), "ERROR", ['code' => $e->getCode()]);
            return null;
        }

        return $this->sheetIdCache[$title] ?? null;
    }

    public function getRows($range): array {
        if (!$this->authenticated) {
            return [];
        }

        try {
            $response = $this->service->spreadsheets_values->get($this->spreadsheet_id, $range);
            return $response->getValues() ?? [];
        } catch (GoogleServiceException $e) {
            hcisysq_log("Get rows failed for range {$range}: " . $e->getMessage(), "ERROR", ['code' => $e->getCode()]);
            return []; // Return empty on error to prevent site crashes
        }
    }

    public function updateRows($range, $values): bool {
        if (!$this->authenticated || empty($values)) {
            return false;
        }

        try {
            $body = new Sheets\ValueRange(["values" => $values]);
            $params = ["valueInputOption" => "RAW"];
            $this->service->spreadsheets_values->update($this->spreadsheet_id, $range, $body, $params);
            return true;
        } catch (GoogleServiceException $e) {
            hcisysq_log("Update rows failed for range {$range}: " . $e->getMessage(), "ERROR", ['code' => $e->getCode()]);
            return false;
        }
    }
    
    public function appendRows($range, $values): bool {
        if (!$this->authenticated || empty($values)) {
            return false;
        }

        try {
            $body = new Sheets\ValueRange(["values" => $values]);
            $params = ["valueInputOption" => "RAW", "insertDataOption" => "INSERT_ROWS"];
            $this->service->spreadsheets_values->append($this->spreadsheet_id, $range, $body, $params);
            return true;
        } catch (GoogleServiceException $e) {
            hcisysq_log("Append rows failed for range {$range}: " . $e->getMessage(), "ERROR", ['code' => $e->getCode()]);
            return false;
        }
    }

    public function deleteRows(string $sheetTitle, $start_row, $end_row): bool {
        if (!$this->authenticated) {
            return false;
        }

        $gid = $this->getSheetIdByTitle($sheetTitle);
        if ($gid === null) {
            hcisysq_log("Delete rows failed: sheet not found", "ERROR", ['title' => $sheetTitle]);
            return false;
        }

        try {
            $request = new Sheets\Request([
                "deleteDimension" => new Sheets\DeleteDimensionRequest([
                    'range' => [
                        'sheetId' => $gid,
                        'dimension' => 'ROWS',
                        'startIndex' => $start_row,
                        'endIndex' => $end_row
                    ]
                ])
            ]);

            $batch = new Sheets\BatchUpdateSpreadsheetRequest(["requests" => [$request]]);
            $this->service->spreadsheets->batchUpdate($this->spreadsheet_id, $batch);
            return true;
        } catch (GoogleServiceException $e) {
            hcisysq_log("Delete rows failed for sheet {$sheetTitle}: " . $e->getMessage(), "ERROR", ['code' => $e->getCode()]);
            return false;
        }
    }

    public function isAuthenticated(): bool {
        return $this->authenticated;
    }

    public function createSheet(string $title): bool {
        if (!$this->authenticated) {
            return false;
        }

        try {
            $request = new Sheets\Request([
                'addSheet' => new Sheets\AddSheetRequest([
                    'properties' => ['title' => $title],
                ]),
            ]);

            $batch = new Sheets\BatchUpdateSpreadsheetRequest([
                'requests' => [$request],
            ]);

            $this->service->spreadsheets->batchUpdate($this->spreadsheet_id, $batch);
            return true;
        } catch (GoogleServiceException $e) {
            hcisysq_log("Create sheet failed for {$title}: " . $e->getMessage(), "ERROR", ['code' => $e->getCode()]);
            return false;
        }
    }

    public function setHeaders(string $sheetTitle, array $headers): bool {
        if (!$this->authenticated || empty($headers)) {
            return false;
        }

        try {
            $range = sprintf('%s!A1:%s1', $sheetTitle, $this->columnLetter(count($headers)));
            $body = new Sheets\ValueRange(['values' => [array_values($headers)]]);
            $params = ["valueInputOption" => "RAW"];
            $this->service->spreadsheets_values->update($this->spreadsheet_id, $range, $body, $params);
            return true;
        } catch (GoogleServiceException $e) {
            hcisysq_log("Set headers failed for {$sheetTitle}: " . $e->getMessage(), "ERROR", ['code' => $e->getCode()]);
            return false;
        }
    }

    public function getSheetTitles(): array {
        try {
            $spreadsheet = $this->getSpreadsheet();
            $sheets = $spreadsheet->getSheets();
            if (!is_array($sheets)) {
                return [];
            }

            return array_values(array_filter(array_map(static function ($sheet) {
                return $sheet->getProperties()->getTitle();
            }, $sheets)));
        } catch (\Exception $e) {
            hcisysq_log("Get sheet titles failed: " . $e->getMessage(), "ERROR");
            return [];
        }
    }

    private function columnLetter(int $count): string {
        $letter = '';
        while ($count > 0) {
            $remainder = ($count - 1) % 26;
            $letter = chr(65 + $remainder) . $letter;
            $count = (int) (($count - 1) / 26);
        }
        return $letter;
    }
}

