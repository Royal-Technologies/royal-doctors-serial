<?php
/**
 * AJAX handlers for populating DataTables and handling print requests.
 * @package RoyalDoctorsSerial
 */
if ( ! defined( 'WPINC' ) ) die;

// Provides data for the main serials list DataTable
add_action( 'wp_ajax_rds_get_all_serials', 'rds_get_all_serials_callback' );
function rds_get_all_serials_callback() {
    global $wpdb;
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );
    $serials_table  = $wpdb->prefix . 'rds_serials';
    $chambers_table = $wpdb->prefix . 'rds_chambers';

    $results = $wpdb->get_results(
        "SELECT s.serial_date, c.name as chamber_name, c.id as chamber_id, COUNT(s.id) as total_serials
        FROM {$serials_table} s JOIN {$chambers_table} c ON s.chamber_id = c.id
        GROUP BY s.serial_date, s.chamber_id ORDER BY s.serial_date DESC"
    );

    $data = [];
    if ( ! empty( $results ) ) {
        foreach ( $results as $row ) {
            $data[] = [
                'date'    => date( 'd-m-Y', strtotime( $row->serial_date ) ),
                'chamber' => esc_html( $row->chamber_name ),
                'total'   => $row->total_serials,
                'actions' => '<a href="#" class="button print-btn" data-date="' . esc_attr($row->serial_date) . '" data-chamber-id="' . esc_attr($row->chamber_id) . '">Print</a>
                            <a href="#" class="button button-primary pdf-btn" data-date="' . esc_attr($row->serial_date) . '" data-chamber-id="' . esc_attr($row->chamber_id) . '">PDF</a>',
            ];
        }
    }
    wp_send_json( [ 'data' => $data ] );
}


function _rds_get_serial_list_html( $doctor_info, $chamber_name, $date, $serials ) {
    $doc_name    = $doctor_info['name'] ?? 'N/A';
    $doc_spec    = $doctor_info['specialization'] ?? 'N/A';
    $doc_contact = $doctor_info['contact'] ?? 'N/A';

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Serial List for <?php echo esc_html( $chamber_name ); ?></title>
        <style>
            body { font-family: 'dejavusans', sans-serif; color: #333; }
            .print-container { max-width: 800px; margin: 20px auto; }
            h1, h2, h3 { text-align: center; margin: 5px 0; }
            h1 { font-size: 24px; }
            h2 { font-size: 20px; }
            h3 { font-size: 18px; margin-bottom: 20px; }
            
            /* CSS for the new table-based layout */
            .doctor-info-table {
                width: 100%;
                border-top: 1px solid #ccc;
                border-bottom: 1px solid #ccc;
                margin-bottom: 20px;
                padding: 10px 0;
            }
            .doctor-info-table td {
                font-size: 14px;
                border: none; /* No borders inside this table */
                padding: 5px;
            }
            .doctor-info-table .doc-col-1 { text-align: left; }
            .doctor-info-table .doc-col-2 { text-align: center; }
            .doctor-info-table .doc-col-3 { text-align: right; }

            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
            th { background-color: #f2f2f2; }
            @media print { .no-print { display: none; } }
        </style>
    </head>
    <body>
        <div class="print-container">
            <h1>Patient Serial List</h1>
            <h2>Chamber: <?php echo esc_html( $chamber_name ); ?></h2>
            <h3>Date: <?php echo esc_html( date("d F, Y", strtotime($date)) ); ?></h3>

            <table class="doctor-info-table">
                <tr>
                    <td class="doc-col-1"><strong>Doctor:</strong> <?php echo esc_html($doc_name); ?></td>
                    <td class="doc-col-2"><strong>Specialization:</strong> <?php echo esc_html($doc_spec); ?></td>
                    <td class="doc-col-3"><strong>Contact:</strong> <?php echo esc_html($doc_contact); ?></td>
                </tr>
            </table>

            <table>
                <thead>
                    <tr>
                        <th style="width:85px;">Sl. No.</th>
                        <th>Patient Name</th>
                        <th style="width:135px;">Mobile</th>
                        <th style="width:30%;">Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $serials ) ) : foreach ( $serials as $serial ) : ?>
                    <tr>
                        <td><?php echo esc_html( $serial->serial_number ); ?></td>
                        <td><?php echo esc_html( $serial->patient_name ); ?></td>
                        <td><?php echo esc_html( $serial->patient_mobile ); ?></td>
                        <td></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="3" style="text-align:center;">No serials found for this date.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}


/**
 * 2. (Updated) Generates a print-friendly page.
 */
add_action( 'wp_ajax_rds_print_serials', 'rds_print_serials_callback' );
function rds_print_serials_callback() {
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'rds_admin_nonce' ) ) wp_die( 'Security check failed!' );
    global $wpdb;
    $date = isset( $_GET['date'] ) ? sanitize_text_field( $_GET['date'] ) : '';
    $chamber_id = isset( $_GET['chamber_id'] ) ? intval( $_GET['chamber_id'] ) : 0;
    if ( empty( $date ) || empty( $chamber_id ) ) wp_die( 'Missing required data.' );

    // Fetch all necessary data
    $doctor_info = get_option('rds_doctor_info', []);
    $chamber_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}rds_chambers WHERE id = %d", $chamber_id));
    $serials = $wpdb->get_results( $wpdb->prepare("SELECT serial_number, patient_name, patient_mobile FROM {$wpdb->prefix}rds_serials WHERE chamber_id = %d AND serial_date = %s ORDER BY serial_number ASC", $chamber_id, $date ) );

    // Get HTML from the helper function
    $html = _rds_get_serial_list_html($doctor_info, $chamber_name, $date, $serials);

    // Add a print script to the HTML
    $html .= '<script>window.onload = function() { window.print(); };</script>';

    echo $html;
    wp_die();
}


/**
 * 3. (Updated) Generates a downloadable PDF file.
 */
add_action( 'wp_ajax_rds_generate_pdf', 'rds_generate_pdf_callback' );
function rds_generate_pdf_callback() {
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'rds_admin_nonce' ) ) wp_die( 'Security check failed!' );
    global $wpdb;
    $date = isset( $_GET['date'] ) ? sanitize_text_field( $_GET['date'] ) : '';
    $chamber_id = isset( $_GET['chamber_id'] ) ? intval( $_GET['chamber_id'] ) : 0;
    if ( empty( $date ) || empty( $chamber_id ) ) wp_die( 'Missing required data.' );

    // Fetch all necessary data
    $doctor_info = get_option('rds_doctor_info', []);
    $chamber_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}rds_chambers WHERE id = %d", $chamber_id));
    $serials = $wpdb->get_results( $wpdb->prepare("SELECT serial_number, patient_name, patient_mobile FROM {$wpdb->prefix}rds_serials WHERE chamber_id = %d AND serial_date = %s ORDER BY serial_number ASC", $chamber_id, $date ) );

    // Get HTML from the same helper function
    $html = _rds_get_serial_list_html($doctor_info, $chamber_name, $date, $serials);

    try {
        $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4']);
        $mpdf->WriteHTML($html);
        $filename = "serial-list-{$date}.pdf";
        $mpdf->Output($filename, 'D'); // 'D' forces download
    } catch (\Mpdf\MpdfException $e) {
        wp_die( "mPDF Error: " . $e->getMessage() );
    }
    
    wp_die();
}