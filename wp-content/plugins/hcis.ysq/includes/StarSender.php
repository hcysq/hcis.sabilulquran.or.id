<?php
namespace Hcis\Ysq\Includes;

if (!defined('ABSPATH')) exit;

class StarSender {

    /**
     * Sends a text message using the StarSender API.
     *
     * @param string $to The recipient's phone number.
     * @param string $text The message content.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function send($to, $text) {
        $url = defined('HCISYSQ_SS_URL') ? HCISYSQ_SS_URL : 'https://starsender.online/api/sendText';
        $api_key = get_option('hcisysq_wa_token'); // Correct option name from Admin.php

        if (empty($api_key)) {
            hcisysq_log('StarSender Error: API Key is not set.', 'error');
            return new \WP_Error('starsender_error', 'StarSender API Key is not configured.');
        }

        // Normalize phone number
        $to = self::normalize_phone_number($to);

        $body = [
            'tujuan' => $to,
            'message' => $text,
        ];

        $args = [
            'method'  => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'apikey' => $api_key,
            ],
            'body'    => json_encode($body),
            'timeout' => 15,
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            hcisysq_log('StarSender WP_Error: ' . $response->get_error_message(), 'error');
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code >= 300) {
            hcisysq_log("StarSender HTTP Error: Code $response_code, Body: $response_body", 'error');
            return new \WP_Error('starsender_http_error', "StarSender request failed with status code $response_code.");
        }
        
        $data = json_decode($response_body, true);
        if (!isset($data['status']) || $data['status'] !== true) {
            $error_message = isset($data['message']) ? $data['message'] : 'Unknown error from StarSender.';
            hcisysq_log("StarSender API Error: $error_message", 'error');
            return new \WP_Error('starsender_api_error', $error_message);
        }

        hcisysq_log("StarSender Success: Message sent to $to");
        return true;
    }

    /**
     * Sends a notification to the administrator.
     *
     * @param string $text The message content.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public static function sendToAdmin($text) {
        $admin_phone = get_option('hcisysq_admin_wa'); // Correct option name from Admin.php

        if (empty($admin_phone)) {
            hcisysq_log('StarSender Admin Error: Admin phone number is not set.', 'warning');
            return false; // Don't treat as a critical error
        }

        return self::send($admin_phone, "[HCIS Notification]\n" . $text);
    }

    /**
     * Normalizes a phone number to the required format.
     *
     * @param string $phone
     * @return string
     */
    private static function normalize_phone_number($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }
        return $phone;
    }
}
