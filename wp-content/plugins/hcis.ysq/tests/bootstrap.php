<?php
/**
 * Test Bootstrap
 *
 * Sets up the test environment for HCISYSQ plugin tests.
 *
 * @package HCISYSQ
 */

// Get the WordPress Test Library
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
  $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested
 */
function _manually_load_plugin() {
  require dirname(dirname(__FILE__)) . '/hcis.ysq.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Include test helpers
require_once dirname(__FILE__) . '/helpers/class-test-helper.php';
