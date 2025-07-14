<?php
// সরাসরি ফাইল অ্যাক্সেস প্রতিরোধ করুন
if ( ! defined( 'WPINC' ) ) {
    die;
}

function rds_admin_enqueue_scripts( $hook ) {
    // An array of all our plugin's admin pages
    $plugin_pages = [
        'toplevel_page_rd-serial',
        'rd-serial_page_rd-off-days',
        'rd-serial_page_rd-serial-settings'
    ];

    // Only load scripts on our plugin's pages
    if ( ! in_array( $hook, $plugin_pages ) ) {
        return;
    }

    // Common libraries for all plugin pages
    wp_enqueue_style( 'rds-datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css' );
    wp_enqueue_style( 'rds-sweetalert2-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css' );
    wp_enqueue_style( 'rds-admin-style', RDS_PLUGIN_URL . 'assets/css/admin-style.css', [], RDS_VERSION );
    
    wp_enqueue_script( 'rds-datatables-js', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', ['jquery'], '1.13.6', true );
    wp_enqueue_script( 'rds-sweetalert2-js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js', [], '11.7.27', true );

    // Load page-specific JS file based on the hook
    $handle = '';
    $dependencies = ['jquery']; // Start with jQuery as a base dependency

    switch ($hook) {
        case 'toplevel_page_rd-serial':
            $handle = 'rds-serial-list-script';
            $dependencies[] = 'rds-datatables-js';
            wp_enqueue_script($handle, RDS_PLUGIN_URL . 'assets/js/admin/serial-list-page.js', $dependencies, RDS_VERSION, true);
            break;

        case 'rd-serial_page_rd-serial-settings':
            // This is the crucial part for the media uploader
            wp_enqueue_media(); // 1. Enqueue the media uploader scripts
            $handle = 'rds-settings-script';
            // 2. Add 'wp-mediaelement' as a dependency for our script
            $dependencies[] = 'rds-sweetalert2-js';
            $dependencies[] = 'wp-mediaelement'; 
            wp_enqueue_script($handle, RDS_PLUGIN_URL . 'assets/js/admin/settings-page.js', $dependencies, RDS_VERSION, true);
            break;

        case 'rd-serial_page_rd-off-days':
            $handle = 'rds-offday-script';
            $dependencies[] = 'rds-datatables-js';
            $dependencies[] = 'rds-sweetalert2-js';
            wp_enqueue_script($handle, RDS_PLUGIN_URL . 'assets/js/admin/offday-page.js', $dependencies, RDS_VERSION, true);
            break;
    }
    
    // Pass AJAX URL and Nonce to the loaded script
    if (!empty($handle)) {
        wp_localize_script( $handle, 'rds_admin_ajax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'rds_admin_nonce' )
        ]);
    }
}
add_action( 'admin_enqueue_scripts', 'rds_admin_enqueue_scripts' );


function rds_frontend_enqueue_scripts() {
    global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'rd_serial_booking_form' ) ) {

        // Enqueue jQuery UI Datepicker script and styles
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/base/jquery-ui.css');

        // SweetAlert2 and our custom scripts (as before)
        wp_enqueue_style( 'rds-sweetalert2-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css' );
        wp_enqueue_style( 'rds-frontend-style', RDS_PLUGIN_URL . 'assets/css/frontend-style.css', [], RDS_VERSION );
        
        wp_enqueue_script( 'rds-sweetalert2-js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js', [], '11.7.27', true );
        wp_enqueue_script( 'rds-frontend-script', RDS_PLUGIN_URL . 'assets/js/frontend-script.js', ['jquery', 'jquery-ui-datepicker'], RDS_VERSION, true );

        wp_localize_script( 'rds-frontend-script', 'rds_frontend_ajax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'rds_frontend_nonce' )
        ]);
    }
}
add_action( 'wp_enqueue_scripts', 'rds_frontend_enqueue_scripts' );