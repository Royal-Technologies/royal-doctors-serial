<?php
// সরাসরি ফাইল অ্যাক্সেস প্রতিরোধ করুন
if ( ! defined( 'WPINC' ) ) {
    die;
}



// ==============================
// বন্ধের দিন (Off Day) ম্যানেজমেন্ট AJAX হ্যান্ডলার
// ==============================

// অফ-ডে তালিকা পাওয়ার জন্য
add_action( 'wp_ajax_rds_get_offdays', 'rds_get_offdays_callback' );
function rds_get_offdays_callback() {
    global $wpdb;
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );
    $offday_table = $wpdb->prefix . 'rds_offday';
    $chamber_table = $wpdb->prefix . 'rds_chambers';

    $results = $wpdb->get_results(
        "SELECT o.id, o.chamber_id, c.name as chamber_name, o.off_date, o.note 
        FROM $offday_table o
        JOIN $chamber_table c ON o.chamber_id = c.id
        ORDER BY o.off_date DESC"
    );
    wp_send_json_success($results);
}

// নতুন অফ-ডে যোগ করার জন্য
add_action( 'wp_ajax_rds_add_offday', 'rds_add_offday_callback' );
function rds_add_offday_callback() {
    global $wpdb;
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );
    $table_name = $wpdb->prefix . 'rds_offday';

    if ( !isset($_POST['chamber_id'], $_POST['off_date']) || empty($_POST['chamber_id']) || empty($_POST['off_date']) ) {
        wp_send_json_error(['message' => 'Chamber and Date are required.']);
    }

    $chamber_id = intval($_POST['chamber_id']);
    $off_date = sanitize_text_field($_POST['off_date']);
    $note = isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '';

    $result = $wpdb->insert($table_name, ['chamber_id' => $chamber_id, 'off_date' => $off_date, 'note' => $note]);
    
    if ($result) {
        wp_send_json_success(['message' => 'Off day added successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to add off day.']);
    }
}

// অফ-ডে আপডেট করার জন্য
add_action( 'wp_ajax_rds_update_offday', 'rds_update_offday_callback' );
function rds_update_offday_callback() {
    global $wpdb;
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );
    $table_name = $wpdb->prefix . 'rds_offday';

    if ( !isset($_POST['id'], $_POST['chamber_id'], $_POST['off_date']) || empty($_POST['id']) ) {
        wp_send_json_error(['message' => 'ID, Chamber and Date are required.']);
    }

    $id = intval($_POST['id']);
    $chamber_id = intval($_POST['chamber_id']);
    $off_date = sanitize_text_field($_POST['off_date']);
    $note = isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '';

    $result = $wpdb->update($table_name, 
        ['chamber_id' => $chamber_id, 'off_date' => $off_date, 'note' => $note],
        ['id' => $id]
    );

    if ($result !== false) {
        wp_send_json_success(['message' => 'Off day updated successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to update off day.']);
    }
}

// অফ-ডে ডিলিট করার জন্য
add_action( 'wp_ajax_rds_delete_offday', 'rds_delete_offday_callback' );
function rds_delete_offday_callback() {
    global $wpdb;
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );
    $table_name = $wpdb->prefix . 'rds_offday';
    
    if ( !isset($_POST['id']) || empty($_POST['id']) ) {
        wp_send_json_error(['message' => 'Invalid ID.']);
    }

    $id = intval($_POST['id']);
    $result = $wpdb->delete($table_name, ['id' => $id]);

    if ($result) {
        wp_send_json_success(['message' => 'Off day deleted successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete off day.']);
    }
}