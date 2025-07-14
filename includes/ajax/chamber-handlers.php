<?php
// security check
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * AJAX handlers for Chamber Management (CRUD).
 */

// Handler to get the list of all chambers.
add_action( 'wp_ajax_rds_get_chambers', function() {
    global $wpdb;
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );
    $table_name = $wpdb->prefix . 'rds_chambers';
    
    // Updated query to include the 'advance_booking_days' column
    $chambers = $wpdb->get_results( "SELECT id, name, description, advance_booking_days FROM $table_name ORDER BY name ASC" );
    
    // Ensure description is not null to prevent issues in JS
    foreach ($chambers as $chamber) {
        if (is_null($chamber->description)) {
            $chamber->description = '';
        }
    }

    wp_send_json_success( $chambers );
});

// Handler to add a new chamber.
add_action( 'wp_ajax_rds_add_chamber', function() {
    global $wpdb;
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );
    $table_name = $wpdb->prefix . 'rds_chambers';
    
    if ( ! isset( $_POST['name'] ) || empty( $_POST['name'] ) ) {
        wp_send_json_error( ['message' => 'Chamber name is required.'] );
    }

    $name = sanitize_text_field( $_POST['name'] );
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    $advance_days = isset($_POST['advance_days']) ? intval($_POST['advance_days']) : 7;

    $result = $wpdb->insert($table_name, [
        'name' => $name, 
        'description' => $description,
        'advance_booking_days' => $advance_days
    ]);
    
    if ( $result ) {
        wp_send_json_success( ['message' => 'Chamber added successfully.'] );
    } else {
        wp_send_json_error( ['message' => 'Failed to add chamber.'] );
    }
});

// Handler to update an existing chamber.
add_action( 'wp_ajax_rds_update_chamber', function() {
    global $wpdb;
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );
    $table_name = $wpdb->prefix . 'rds_chambers';

    if ( ! isset( $_POST['id'], $_POST['name'] ) || empty( $_POST['id'] ) ) {
        wp_send_json_error( ['message' => 'Chamber ID and name are required.'] );
    }

    $id = intval($_POST['id']);
    $name = sanitize_text_field($_POST['name']);
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    $advance_days = isset($_POST['advance_days']) ? intval($_POST['advance_days']) : 7;
    
    $result = $wpdb->update($table_name, 
        ['name' => $name, 'description' => $description, 'advance_booking_days' => $advance_days], 
        ['id' => $id]
    );
    
    if ( $result !== false ) {
        wp_send_json_success( ['message' => 'Chamber updated successfully.'] );
    } else {
        wp_send_json_error( ['message' => 'Failed to update chamber or no changes were made.'] );
    }
});

// Handler to delete a chamber.
add_action( 'wp_ajax_rds_delete_chamber', function() {
    global $wpdb;
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );
    $table_name = $wpdb->prefix . 'rds_chambers';

    if ( !isset($_POST['chamber_id']) || empty($_POST['chamber_id']) ) {
        wp_send_json_error(['message' => 'Invalid Chamber ID.']);
    }

    $id = intval($_POST['chamber_id']);
    $result = $wpdb->delete($table_name, ['id' => $id]);

    if ($result) {
        wp_send_json_success(['message' => 'Chamber deleted successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to delete chamber.']);
    }
});