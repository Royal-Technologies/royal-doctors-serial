<?php
// সরাসরি ফাইল অ্যাক্সেস প্রতিরোধ করুন
if ( ! defined( 'WPINC' ) ) {
    die;
}



// ==============================
// ক্যাপচা ম্যানেজমেন্ট AJAX হ্যান্ডলার
// ==============================

// ক্যাপচা তালিকা পাওয়ার জন্য
add_action( 'wp_ajax_rds_get_captchas', 'rds_get_captchas_callback' );
function rds_get_captchas_callback() {
    global $wpdb;
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );
    $table_name = $wpdb->prefix . 'rds_captcha';
    $captchas = $wpdb->get_results( "SELECT id, question, answer FROM $table_name" );
    wp_send_json_success( $captchas );
}

// নতুন ক্যাপচা যোগ করার জন্য
add_action( 'wp_ajax_rds_add_captcha', 'rds_add_captcha_callback' );
function rds_add_captcha_callback() {
    global $wpdb;
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );
    $table_name = $wpdb->prefix . 'rds_captcha';
    
    if ( ! isset( $_POST['question'] ) || empty( $_POST['question'] ) || ! isset( $_POST['answer'] ) || empty( $_POST['answer'] ) ) {
        wp_send_json_error( ['message' => 'Question and answer are required.'] );
    }

    $question = sanitize_text_field( $_POST['question'] );
    $answer = sanitize_text_field( $_POST['answer'] );

    $result = $wpdb->insert($table_name, ['question' => $question, 'answer' => $answer]);

    if ( $result ) {
        wp_send_json_success( ['message' => 'Captcha added successfully.'] );
    } else {
        wp_send_json_error( ['message' => 'Failed to add captcha.'] );
    }
}

// ক্যাপচা ডিলিট করার জন্য
add_action( 'wp_ajax_rds_delete_captcha', 'rds_delete_captcha_callback' );
function rds_delete_captcha_callback() {
    global $wpdb;
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );
    $table_name = $wpdb->prefix . 'rds_captcha';

    if ( ! isset( $_POST['captcha_id'] ) || empty( $_POST['captcha_id'] ) ) {
        wp_send_json_error( ['message' => 'Invalid Captcha ID.'] );
    }

    $id = intval( $_POST['captcha_id'] );
    $result = $wpdb->delete( $table_name, ['id' => $id] );

    if ( $result ) {
        wp_send_json_success( ['message' => 'Captcha deleted successfully.'] );
    } else {
        wp_send_json_error( ['message' => 'Failed to delete captcha.'] );
    }
}


// ক্যাপচা আপডেট করার জন্য
add_action( 'wp_ajax_rds_update_captcha', 'rds_update_captcha_callback' );
function rds_update_captcha_callback() {
    global $wpdb;
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );
    $table_name = $wpdb->prefix . 'rds_captcha';

    if ( ! isset( $_POST['id'] ) || empty( $_POST['id'] ) || ! isset( $_POST['question'] ) || empty( $_POST['question'] ) || ! isset( $_POST['answer'] ) || empty( $_POST['answer'] ) ) {
        wp_send_json_error( ['message' => 'All fields are required for update.'] );
    }

    $id = intval( $_POST['id'] );
    $question = sanitize_text_field( $_POST['question'] );
    $answer = sanitize_text_field( $_POST['answer'] );

    $result = $wpdb->update(
        $table_name,
        ['question' => $question, 'answer' => $answer],
        ['id' => $id]
    );

    if ( $result !== false ) {
        wp_send_json_success( ['message' => 'Captcha updated successfully.'] );
    } else {
        wp_send_json_error( ['message' => 'Failed to update captcha or no changes were made.'] );
    }
}