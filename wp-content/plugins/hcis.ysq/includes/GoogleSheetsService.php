<?php
namespace HCISYSQ;

use Google_Client;
use Google_Service_Sheets;
use Exception;

/**
 * Handles all interactions with the Google Sheets API.
 */
class GoogleSheetsService {

    /**
     * Google Sheets Service instance.
     *
     * @var Google_Service_Sheets
     */
    private $service;

    /**
     * The Google Spreadsheet ID.
     *
     * @var string
     */
    private $spreadsheet_id;

    /**
     * Constructor.
     * Initializes the Google Sheets service.
     */
    public function __construct() {
        $this->spreadsheet_id = GoogleSheetSettings::get_sheet_id();
        $this->service = $this->create_service();
    }

    /**
     * Creates and configures the Google Sheets service.
     *
     * @return Google_Service_Sheets|null Returns the service on success, or null on failure.
     */
    private function create_service() {
        $credentials_json = GoogleSheetSettings::get_credentials_json();

        if (empty($credentials_json) || empty($this->spreadsheet_id)) {
            return null;
        }

        try {
            $credentials = json_decode($credentials_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to decode Google credentials JSON.');
            }

            $client = new Google_Client();
            $client->setAuthConfig($credentials);
            $client->setScopes([Google_Service_Sheets::SPREADSHEETS]);
            $client->setAccessType('offline');

            return new Google_Service_Sheets($client);
        } catch (Exception $e) {
            // Log the error or handle it appropriately
            error_log('HCIS GoogleSheetsService Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Checks if the service is properly configured.
     *
     * @return boolean
     */
    public function is_configured() {
        return $this->service !== null && !empty($this->spreadsheet_id);
    }

    /**
     * Gets data from a sheet.
     *
     * @param string $range The range in A1 notation. E.g., 'Sheet1!A1:D5'.
     * @return array|null An array of values, or null on failure.
     */
    public function get_sheet_data($range) {
        if (!$this->is_configured()) {
            return null;
        }

        try {
            $response = $this->service->spreadsheets_values->get($this->spreadsheet_id, $range);
            return $response->getValues();
        } catch (Exception $e) {
            error_log('HCIS GoogleSheetsService get_sheet_data Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Updates data in a sheet.
     *
     * @param string $range The range in A1 notation.
     * @param array  $values A 2D array of values.
     * @return mixed The response from the API, or null on failure.
     */
    public function update_sheet_data($range, $values) {
        if (!$this->is_configured()) {
            return null;
        }

        try {
            $body = new \Google_Service_Sheets_ValueRange([
                'values' => $values
            ]);
            $params = [
                'valueInputOption' => 'USER_ENTERED'
            ];
            $result = $this->service->spreadsheets_values->update($this->spreadsheet_id, $range, $body, $params);
            return $result;
        } catch (Exception $e) {
            error_log('HCIS GoogleSheetsService update_sheet_data Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Tests the connection to the configured spreadsheet.
     *
     * @return string The title of the spreadsheet on success.
     * @throws Exception If the connection fails.
     */
    public function test_connection() {
        if (!$this->is_configured()) {
            throw new Exception('Plugin is not configured. Please check settings.');
        }

        try {
            $spreadsheet = $this->service->spreadsheets->get($this->spreadsheet_id);
            return $spreadsheet->getProperties()->getTitle();
        } catch (Exception $e) {
            // Re-throw the exception to be caught by the AJAX handler
            throw new Exception('Connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Creates a new sheet/tab inside the configured spreadsheet.
     *
     * @param string $title
     * @return bool True on success, false on failure.
     */
    public function create_sheet(string $title): bool {
        if (!$this->is_configured()) {
            return false;
        }

        try {
            $request = new \Google_Service_Sheets_Request([
                'addSheet' => [
                    'properties' => [
                        'title' => $title,
                    ],
                ],
            ]);

            $batch = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                'requests' => [$request],
            ]);

            $this->service->spreadsheets->batchUpdate($this->spreadsheet_id, $batch);
            return true;
        } catch (Exception $e) {
            error_log('HCIS GoogleSheetsService create_sheet Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Writes header labels to the first row of a sheet.
     *
     * @param string $sheet_title
     * @param array $headers
     * @return bool
     */
    public function set_headers(string $sheet_title, array $headers): bool {
        if (!$this->is_configured() || empty($headers)) {
            return false;
        }

        try {
            $end_column = $this->column_letter(count($headers));
            $range = sprintf('%s!A1:%s1', $sheet_title, $end_column);

            $body = new \Google_Service_Sheets_ValueRange([
                'values' => [array_values($headers)],
            ]);

            $params = [
                'valueInputOption' => 'RAW',
            ];

            $this->service->spreadsheets_values->update($this->spreadsheet_id, $range, $body, $params);
            return true;
        } catch (Exception $e) {
            error_log('HCIS GoogleSheetsService set_headers Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Convert column count to Google Sheets column letter (e.g., 1 -> A, 27 -> AA).
     */
    private function column_letter(int $count): string {
        $letter = '';
        while ($count > 0) {
            $remainder = ($count - 1) % 26;
            $letter = chr(65 + $remainder) . $letter;
            $count = (int) (($count - 1) / 26);
        }
        return $letter;
    }
}
