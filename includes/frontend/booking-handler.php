<?php
// Security check
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'wp_ajax_rds_book_serial', 'rds_book_serial_callback' );
add_action( 'wp_ajax_nopriv_rds_book_serial', 'rds_book_serial_callback' );

function rds_book_serial_callback() {
    global $wpdb;
    check_ajax_referer( 'rds_frontend_nonce', 'nonce' );

    // 1. Validate required fields from the form submission.
    $required_fields = ['chamber_id', 'serial_date', 'patient_name', 'patient_mobile', 'captcha_answer', 'captcha_id'];
    foreach ($required_fields as $field) {
        if ( ! isset( $_POST[$field] ) || empty( $_POST[$field] ) ) {
            wp_send_json_error( ['message' => 'Please fill all required fields.'] );
        }
    }

    // 2. Verify the Captcha answer.
    $captcha_id = intval( $_POST['captcha_id'] );
    $user_answer = sanitize_text_field( $_POST['captcha_answer'] );
    $correct_answer = $wpdb->get_var( $wpdb->prepare( "SELECT answer FROM {$wpdb->prefix}rds_captcha WHERE id = %d", $captcha_id ) );

    if ( strtolower( trim( $user_answer ) ) !== strtolower( trim( $correct_answer ) ) ) {
        wp_send_json_error( ['message' => 'Captcha answer is incorrect!'] );
        return;
    }

    // 3. Sanitize all received data.
    $chamber_id     = intval($_POST['chamber_id']);
    $serial_date    = sanitize_text_field( $_POST['serial_date'] );
    $patient_name   = sanitize_text_field( $_POST['patient_name'] );
    $patient_mobile = sanitize_text_field( $_POST['patient_mobile'] );
    $patient_email  = isset($_POST['patient_email']) ? sanitize_email( $_POST['patient_email'] ) : '';

    // 4. Find the correct schedule and its serial limit.
    $day_of_week = date('l', strtotime($serial_date));
    $schedule_info = $wpdb->get_row($wpdb->prepare(
        // The query is updated to select 'serial_limit' as well.
        "SELECT id as schedule_id, visit_time, serial_limit FROM {$wpdb->prefix}rds_chambers_time WHERE chamber_id = %d AND visit_day = %s ORDER BY visit_time ASC LIMIT 1",
        $chamber_id, $day_of_week
    ));

    if (!$schedule_info) {
        wp_send_json_error(['message' => 'No schedule found for the selected chamber and date.']);
        return;
    }
    
    // Assign all values from the schedule info.
    $schedule_id  = $schedule_info->schedule_id;
    $visit_time   = $schedule_info->visit_time;
    $serial_limit = $schedule_info->serial_limit; // This line was missing.

    // 5. Check if the serial limit has been reached.
    $serials_table = $wpdb->prefix . 'rds_serials';
    $existing_serials = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $serials_table WHERE schedule_id = %d AND serial_date = %s",
        $schedule_id, $serial_date
    ));

    if ($existing_serials >= $serial_limit) {
        wp_send_json_error(['message' => 'The serial limit for this schedule has been reached. Please try another day.']);
        return;
    }

    // 6. Calculate new serial number and estimated time.
    $new_serial_number = $existing_serials + 1;
    $time_per_patient = get_option( 'rds_time_per_patient', 10 );
    $minutes_to_add = $existing_serials * $time_per_patient;
    $estimated_timestamp = strtotime("{$serial_date} {$visit_time}") + ($minutes_to_add * 60);
    $estimated_time = date('h:i A', $estimated_timestamp);

    // 7. Insert the new serial into the database.
    $result = $wpdb->insert($serials_table, [
        'chamber_id'     => $chamber_id,
        'schedule_id'    => $schedule_id,
        'serial_date'    => $serial_date,
        'patient_name'   => $patient_name,
        'patient_mobile' => $patient_mobile,
        'patient_email'  => $patient_email,
        'serial_number'  => $new_serial_number,
    ]);
    
    if ($result) {
        wp_send_json_success([
            'serial_number'  => $new_serial_number,
            'estimated_time' => $estimated_time,
        ]);
    } else {
        wp_send_json_error(['message' => 'Could not save your booking. Please try again.']);
    }
}