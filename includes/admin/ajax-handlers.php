<?php
// security check
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * This file acts as a loader for all AJAX handler files.
 */

$handler_path = RDS_PLUGIN_DIR . 'includes/ajax/';

require_once $handler_path . 'chamber-handlers.php';
require_once $handler_path . 'schedule-handlers.php';
require_once $handler_path . 'offday-handlers.php';
require_once $handler_path . 'captcha-handlers.php';
require_once $handler_path . 'settings-handlers.php';
require_once $handler_path . 'datatables-handler.php';
require_once $handler_path . 'frontend-handlers.php'; 