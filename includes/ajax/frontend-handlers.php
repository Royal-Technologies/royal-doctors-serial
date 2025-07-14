<?php
/**
 * AJAX handlers for the public-facing booking form.
 *
 * @package RoyalDoctorsSerial
 */

// Security check:
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Handler to get schedule info (allowed days, off days, advance days) for a chamber
add_action( 'wp_ajax_rds_get_chamber_schedule', 'rds_get_chamber_schedule_callback' );
add_action( 'wp_ajax_nopriv_rds_get_chamber_schedule', 'rds_get_chamber_schedule_callback' );
function rds_get_chamber_schedule_callback() {
    global $wpdb;
    // Nonce check for security
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    if ( ! wp_verify_nonce( $nonce, 'rds_frontend_nonce' ) ) {
        wp_send_json_error(['message' => 'Nonce verification failed.']);
        return;
    }

    if ( !isset($_POST['chamber_id']) || empty($_POST['chamber_id']) ) {
        wp_send_json_error(['message' => 'Invalid Chamber.']);
    }
    $chamber_id = intval($_POST['chamber_id']);

    // Get allowed days of the week (e.g., Saturday, Sunday)
    $time_table = $wpdb->prefix . 'rds_chambers_time';
    $allowed_days_names = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT visit_day FROM $time_table WHERE chamber_id = %d", $chamber_id));
    
    // Convert day names to numeric format for JavaScript Datepicker (Sunday=0, Monday=1...)
    $day_map = ["Sunday" => 0, "Monday" => 1, "Tuesday" => 2, "Wednesday" => 3, "Thursday" => 4, "Friday" => 5, "Saturday" => 6];
    $allowed_days_numeric = [];
    foreach ($allowed_days_names as $day_name) {
        if (isset($day_map[$day_name])) {
            $allowed_days_numeric[] = $day_map[$day_name];
        }
    }

    // Get all specific off-dates for this chamber
    $offday_table = $wpdb->prefix . 'rds_offday';
    $blocked_dates = $wpdb->get_col($wpdb->prepare("SELECT off_date FROM $offday_table WHERE chamber_id = %d", $chamber_id));

    // Get advance_booking_days for this chamber
    $chamber_table = $wpdb->prefix . 'rds_chambers';
    $advance_days = $wpdb->get_var($wpdb->prepare("SELECT advance_booking_days FROM $chamber_table WHERE id = %d", $chamber_id));

    wp_send_json_success([
        'allowedDays'        => $allowed_days_numeric,
        'blockedDates'       => $blocked_dates,
        'advanceBookingDays' => $advance_days ? intval($advance_days) : 7 // Default to 7 days if not set
    ]);
}

// Handler to get available time slots for a specific chamber and date
add_action( 'wp_ajax_rds_get_times_for_date', 'rds_get_times_for_date_callback' );
add_action( 'wp_ajax_nopriv_rds_get_times_for_date', 'rds_get_times_for_date_callback' );
function rds_get_times_for_date_callback() {
    global $wpdb;
    // Nonce check for security
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    if ( ! wp_verify_nonce( $nonce, 'rds_frontend_nonce' ) ) {
        wp_send_json_error(['message' => 'Nonce verification failed.']);
        return;
    }

    if ( !isset($_POST['chamber_id'], $_POST['date']) || empty($_POST['chamber_id']) || empty($_POST['date']) ) {
        wp_send_json_error(['message' => 'Chamber and Date are required.']);
    }

    $chamber_id = intval($_POST['chamber_id']);
    $selected_date = sanitize_text_field($_POST['date']);
    $day_of_week = date('l', strtotime($selected_date));

    $time_table = $wpdb->prefix . 'rds_chambers_time';
    $available_times = $wpdb->get_results($wpdb->prepare(
        "SELECT id as schedule_id, visit_time FROM {$time_table} WHERE chamber_id = %d AND visit_day = %s ORDER BY visit_time ASC",
        $chamber_id, $day_of_week
    ));

    wp_send_json_success($available_times);
}