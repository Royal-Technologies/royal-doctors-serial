<?php
// সরাসরি ফাইল অ্যাক্সেস প্রতিরোধ করুন
if ( ! defined( 'WPINC' ) ) {
    die;
}

add_action('admin_menu', 'rds_admin_menu');
function rds_admin_menu() {
    add_menu_page(
        'Royal Doctors Serial',
        'RD Serial',
        'manage_options',
        'rd-serial',
        'rds_main_page_html',
        'dashicons-list-view',
        30
    );
    
    // বন্ধের দিন ম্যানেজ করার জন্য সাব-মেনু (এটি থাকছে)
    add_submenu_page(
        'rd-serial',
        'Off Days',
        'Off Days',
        'manage_options',
        'rd-off-days',
        'rds_off_days_page_html'
    );

    // সেটিংসের জন্য সাব-মেনু (এটি থাকছে)
    add_submenu_page(
        'rd-serial',
        'Settings',
        'Settings',
        'manage_options',
        'rd-serial-settings',
        'rds_settings_page_html'
    );
}

// প্রতিটি পেজের জন্য কলব্যাক ফাংশন
function rds_main_page_html() {
    require_once RDS_PLUGIN_DIR . 'includes/admin/main-page.php';
}

function rds_off_days_page_html() {
    ?>
    <div class="wrap">
        <h1>Off Days Management</h1>
        <a href="#" class="page-title-action" id="add-offday-btn">Add New Off Day</a>

        <hr class="wp-header-end">

        <table id="rd-offdays-table" class="display wp-list-table widefat fixed striped" style="width:100%">
            <thead>
                <tr>
                    <th>Chamber</th>
                    <th>Off Date</th>
                    <th>Note</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                </tbody>
        </table>
    </div>
    <?php
}

function rds_settings_page_html() {
    require_once RDS_PLUGIN_DIR . 'includes/admin/settings-page.php';
}