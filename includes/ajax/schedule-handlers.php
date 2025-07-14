<?php
// Security check
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * AJAX handlers for Schedule Management (from the admin panel).
 */

// Handler to get the list of schedules for the admin popup.
add_action( 'wp_ajax_rds_get_schedules', function() {
    global $wpdb;
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );
    $time_table = $wpdb->prefix . 'rds_chambers_time';
    $chamber_table = $wpdb->prefix . 'rds_chambers';

    $schedules = $wpdb->get_results(
        "SELECT t.id, t.chamber_id, c.name as chamber_name, t.visit_day, t.visit_time, t.serial_limit 
        FROM $time_table t
        JOIN $chamber_table c ON t.chamber_id = c.id
        ORDER BY c.name, t.visit_day"
    );
    wp_send_json_success($schedules);
});

// Handler to add a new schedule from the admin popup.
add_action( 'wp_ajax_rds_add_schedule', function() {
    global $wpdb;
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );
    $table_name = $wpdb->prefix . 'rds_chambers_time';

    if ( !isset($_POST['chamber_id'], $_POST['day'], $_POST['time'], $_POST['limit']) ) {
        wp_send_json_error(['message' => 'All fields are required.']);
    }

    $chamber_id = intval($_POST['chamber_id']);
    $day = sanitize_text_field($_POST['day']);
    $time = sanitize_text_field($_POST['time']);
    $limit = intval($_POST['limit']);

    $result = $wpdb->insert($table_name, ['chamber_id' => $chamber_id, 'visit_day' => $day, 'visit_time' => $time, 'serial_limit' => $limit]);
    if ($result) wp_send_json_success(['message' => 'Schedule added successfully.']);
    else wp_send_json_error(['message' => 'Failed to add schedule.']);
});

// Handler to delete a schedule from the admin popup.
add_action( 'wp_ajax_rds_update_schedule', function() {
    global $wpdb;
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );
    $table_name = $wpdb->prefix . 'rds_chambers_time';

    if ( !isset($_POST['id'], $_POST['day'], $_POST['time'], $_POST['limit']) ) {
        wp_send_json_error(['message' => 'All fields are required.']);
    }

    $id = intval($_POST['id']);
    $day = sanitize_text_field($_POST['day']);
    $time = sanitize_text_field($_POST['time']);
    $limit = intval($_POST['limit']);

    $result = $wpdb->update($table_name, ['visit_day' => $day, 'visit_time' => $time, 'serial_limit' => $limit], ['id' => $id]);
    if ($result !== false) wp_send_json_success(['message' => 'Schedule updated successfully.']);
    else wp_send_json_error(['message' => 'Failed to update schedule.']);
});