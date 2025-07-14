jQuery(document).ready(function($) {

    // This script will only run on the main "RD Serial" page.
    if ($('#rd-serials-table').length) {

        // Initialize the DataTable for the serials list.
        var serialsTable = $('#rd-serials-table').DataTable({
            "processing": true,
            "ajax": {
                "url": rds_admin_ajax.ajax_url,
                "type": "POST",
                "data": function(d) {
                    d.action = 'rds_get_all_serials';
                    d.nonce = rds_admin_ajax.nonce;
                }
            },
            "columns": [
                { "data": "date" },
                { "data": "chamber" },
                { "data": "total" },
                { "data": "actions", "orderable": false, "searchable": false }
            ],
            "order": [[0, "desc"]]
        });

        // Handler for the "Print" button
        $('#rd-serials-table tbody').on('click', '.print-btn', function(e) {
            e.preventDefault();
            const date = $(this).data('date');
            const chamberId = $(this).data('chamber-id');
            const nonce = rds_admin_ajax.nonce;
            const printUrl = `${rds_admin_ajax.ajax_url}?action=rds_print_serials&date=${date}&chamber_id=${chamberId}&_wpnonce=${nonce}`;
            window.open(printUrl, '_blank');
        });

        // Handler for the "PDF" button
        $('#rd-serials-table tbody').on('click', '.pdf-btn', function(e) {
            e.preventDefault();
            const date = $(this).data('date');
            const chamberId = $(this).data('chamber-id');
            const nonce = rds_admin_ajax.nonce;
            const pdfUrl = `${rds_admin_ajax.ajax_url}?action=rds_generate_pdf&date=${date}&chamber_id=${chamberId}&_wpnonce=${nonce}`;
            // This will trigger the file download
            window.location.href = pdfUrl;
        });

    } // end of if ($('#rd-serials-table').length)

});