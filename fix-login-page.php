<?php
/**
 * Plugin Name: Fix Login Page
 * Description: This file contains the code that fixes the login page bug.
 * Version: 1.0
 * Author: Your Name
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Deactivate the hcis.ysq plugin
add_action( 'admin_init', 'deactivate_hcis_ysq_plugin' );
function deactivate_hcis_ysq_plugin() {
    if ( is_plugin_active( 'hcis.ysq/hcis.ysq.php' ) ) {
        deactivate_plugins( 'hcis.ysq/hcis.ysq.php' );
    }
}

// Activate the hcis.ysq plugin
add_action( 'admin_init', 'activate_hcis_ysq_plugin' );
function activate_hcis_ysq_plugin() {
    if ( ! is_plugin_active( 'hcis.ysq/hcis.ysq.php' ) ) {
        activate_plugin( 'hcis.ysq/hcis.ysq.php' );
    }
}