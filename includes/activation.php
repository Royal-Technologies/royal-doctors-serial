<?php
/**
 * Plugin activation functions.
 * This file creates the necessary database tables upon plugin activation.
 */

// সরাসরি ফাইল অ্যাক্সেস প্রতিরোধ করুন
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * The function that runs when the plugin is activated.
 */
function rds_plugin_activate() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();

    // --- নতুন টেবিল কাঠামো ---

    // ১. চেম্বারের নাম ও বিবরণের জন্য টেবিল
    $table_chambers = $wpdb->prefix . 'rds_chambers';
    $sql_chambers = "CREATE TABLE {$table_chambers} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        contact varchar(100) NULL,
        email varchar(100) NULL,
        advance_booking_days int(3) NOT NULL DEFAULT 7,
        PRIMARY KEY  (id)
    ) {$charset_collate};";
    dbDelta( $sql_chambers );

    // ২. চেম্বারের সময়সূচীর জন্য টেবিল
    $table_chambers_time = $wpdb->prefix . 'rds_chambers_time';
    $sql_chambers_time = "CREATE TABLE {$table_chambers_time} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        chamber_id mediumint(9) NOT NULL,
        visit_day varchar(15) NOT NULL,
        visit_time time NOT NULL,
        serial_limit INT(4) NOT NULL DEFAULT 50,
        PRIMARY KEY  (id),
        KEY chamber_id (chamber_id)
    ) {$charset_collate};";
    dbDelta( $sql_chambers_time );

    // ৩. বন্ধের দিনের জন্য টেবিল
    $table_offday = $wpdb->prefix . 'rds_offday';
    $sql_offday = "CREATE TABLE {$table_offday} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        chamber_id mediumint(9) NOT NULL,
        off_date date NOT NULL,
        note text,
        PRIMARY KEY  (id),
        KEY chamber_id (chamber_id)
    ) {$charset_collate};";
    dbDelta( $sql_offday );

    // --- অপরিবর্তিত টেবিল ---

    // ৪. সিরিয়ালের টেবিল (আগের মতোই)
    $table_serials = $wpdb->prefix . 'rds_serials';
    $sql_serials = "CREATE TABLE {$table_serials} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        chamber_id mediumint(9) NOT NULL,
        schedule_id mediumint(9) NOT NULL,
        patient_name varchar(100) NOT NULL,
        patient_mobile varchar(20) NOT NULL,
        patient_email varchar(100) DEFAULT '' NOT NULL,
        serial_number int(11) NOT NULL,
        serial_date date NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY chamber_id (chamber_id),
        KEY schedule_id (schedule_id)
    ) {$charset_collate};";
    dbDelta( $sql_serials );

    // ৫. ক্যাপচা টেবিল (আগের মতোই)
    $table_captcha = $wpdb->prefix . 'rds_captcha';
    $sql_captcha = "CREATE TABLE {$table_captcha} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        question varchar(255) NOT NULL,
        answer varchar(100) NOT NULL,
        PRIMARY KEY  (id)
    ) {$charset_collate};";
    dbDelta( $sql_captcha );

    // ডিফল্ট ক্যাপচা যুক্ত করা (যদি টেবিল খালি থাকে)
    $existing_captcha = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_captcha}" );
    if ( 0 === (int) $existing_captcha ) {
        $wpdb->insert( $table_captcha, ['question' => 'What is 7 + 5 = ?', 'answer' => '12'] );
        $wpdb->insert( $table_captcha, ['question' => 'What is 10 - 3 = ?', 'answer' => '7'] );
        $wpdb->insert( $table_captcha, ['question' => 'What is 4 * 2 = ?', 'answer' => '8'] );
    }
}