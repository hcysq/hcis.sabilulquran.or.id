<?php
/**
 * Plugin Name: Fix Login Page
 * Description: Programmatically creates the login page and adds the [hcis_ysq_login] shortcode.
 * Version: 1.0
 * Author: Gemini
 */

if (!defined('ABSPATH')) exit;

function fix_login_page() {
    // Check if the page already exists
    $login_page = get_page_by_path('masuk');

    if (!$login_page) {
        // Create post object
        $login_page_data = array(
            'post_title'    => 'Masuk',
            'post_content'  => '[hcis_ysq_login]',
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_type'     => 'page',
            'post_name'     => 'masuk',
        );

        // Insert the post into the database
        wp_insert_post($login_page_data);
    }
}

register_activation_hook(__FILE__, 'fix_login_page');
