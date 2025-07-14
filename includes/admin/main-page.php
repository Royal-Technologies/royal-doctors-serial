<?php
// সরাসরি ফাইল অ্যাক্সেস প্রতিরোধ করুন
if ( ! defined( 'WPINC' ) ) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <hr class="wp-header-end">
    
    <h2>All Serials</h2>
    
    <table id="rd-serials-table" class="display wp-list-table widefat fixed striped" style="width:100%">
        <thead>
            <tr>
                <th>Date</th>
                <th>Chamber</th>
                <th>Total Serial</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            </tbody>
    </table>
</div>