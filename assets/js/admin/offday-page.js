jQuery(document).ready(function($) {

    // This script will only run on the "Off Days" admin page.
    if ($('#rd-offdays-table').length) {

        // Initialize the DataTable for the off-days list.
        var offdaysTable = $('#rd-offdays-table').DataTable({
            "processing": true,
            "ajax": {
                "url": rds_admin_ajax.ajax_url,
                "type": "POST",
                "data": function(d) {
                    d.action = 'rds_get_offdays';
                    d.nonce = rds_admin_ajax.nonce;
                },
                "dataSrc": "data" // Needed to parse wp_send_json_success responses.
            },
            "columns": [
                { "data": "chamber_name" },
                { "data": "off_date" },
                { "data": "note" },
                { 
                    "data": null,
                    "render": function (data, type, row) {
                        return `<button class="button button-secondary edit-offday-btn">Edit</button> 
                                <button class="button button-danger delete-offday-btn">Delete</button>`;
                    },
                    "orderable": false,
                    "searchable": false
                }
            ],
            "order": [[ 1, "desc" ]] // Order by date descending by default.
        });

        // Handler for the "Add New Off Day" button.
        $('#add-offday-btn').on('click', function(e) {
            e.preventDefault();
            // First, get the list of chambers for the dropdown.
            $.ajax({
                url: rds_admin_ajax.ajax_url, type: 'POST',
                data: { action: 'rds_get_chambers', nonce: rds_admin_ajax.nonce },
                success: function(response) {
                    if (response.success) {
                        let chamberOptions = '<option value="">Select Chamber</option>';
                        if (response.data.length > 0) {
                            response.data.forEach(chamber => {
                                chamberOptions += `<option value="${chamber.id}">${chamber.name}</option>`;
                            });
                        }
                        // Once chambers are loaded, show the popup.
                        Swal.fire({
                            title: 'Add New Off Day',
                            html: `<form id="add-offday-form" style="text-align: left;">
                                       <select id="new_offday_chamber" class="swal2-input">${chamberOptions}</select>
                                       <input type="date" id="new_offday_date" class="swal2-input" required>
                                       <textarea id="new_offday_note" class="swal2-textarea" placeholder="Note (e.g., Public Holiday)"></textarea>
                                   </form>`,
                            confirmButtonText: 'Add Off Day',
                            showCancelButton: true,
                            preConfirm: () => {
                                const chamberId = $('#new_offday_chamber').val();
                                const offDate = $('#new_offday_date').val();
                                const note = $('#new_offday_note').val();
                                if (!chamberId || !offDate) { Swal.showValidationMessage('Chamber and Date are required.'); return false; }
                                // AJAX call to save the new off-day.
                                $.ajax({
                                    url: rds_admin_ajax.ajax_url, type: 'POST',
                                    data: { action: 'rds_add_offday', nonce: rds_admin_ajax.nonce, chamber_id: chamberId, off_date: offDate, note: note },
                                    success: (res) => { if (res.success) { offdaysTable.ajax.reload(null, false); Swal.fire('Saved!', 'New off day has been added.', 'success'); } }
                                });
                            }
                        });
                    }
                }
            });
        });

        // Handler for the "Edit" button inside the table (uses event delegation).
        $('#rd-offdays-table tbody').on('click', '.edit-offday-btn', function() {
            var offdayData = offdaysTable.row($(this).parents('tr')).data();
            
            $.ajax({
                url: rds_admin_ajax.ajax_url, type: 'POST',
                data: { action: 'rds_get_chambers', nonce: rds_admin_ajax.nonce },
                success: function(response) {
                    if (response.success) {
                        let chamberOptions = '';
                        response.data.forEach(chamber => {
                            chamberOptions += `<option value="${chamber.id}" ${chamber.id == offdayData.chamber_id ? 'selected' : ''}>${chamber.name}</option>`;
                        });
                        Swal.fire({
                            title: 'Edit Off Day',
                            html: `<form id="edit-offday-form" style="text-align: left;">
                                       <select id="edit_offday_chamber" class="swal2-input">${chamberOptions}</select>
                                       <input type="date" id="edit_offday_date" class="swal2-input" value="${offdayData.off_date}" required>
                                       <textarea id="edit_offday_note" class="swal2-textarea" placeholder="Note (e.g., Public Holiday)">${offdayData.note || ''}</textarea>
                                   </form>`,
                            confirmButtonText: 'Update Off Day',
                            showCancelButton: true,
                            preConfirm: () => {
                                const chamberId = $('#edit_offday_chamber').val();
                                const offDate = $('#edit_offday_date').val();
                                const note = $('#edit_offday_note').val();
                                if (!chamberId || !offDate) { Swal.showValidationMessage('Chamber and Date are required.'); return false; }
                                $.ajax({
                                    url: rds_admin_ajax.ajax_url, type: 'POST',
                                    data: { action: 'rds_update_offday', nonce: rds_admin_ajax.nonce, id: offdayData.id, chamber_id: chamberId, off_date: offDate, note: note },
                                    success: (res) => { if (res.success) { offdaysTable.ajax.reload(null, false); Swal.fire('Updated!', 'Off day has been updated.', 'success'); } }
                                });
                            }
                        });
                    }
                }
            });
        });

        // Handler for the "Delete" button inside the table (uses event delegation).
        $('#rd-offdays-table tbody').on('click', '.delete-offday-btn', function() {
            var offdayData = offdaysTable.row($(this).parents('tr')).data();
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: rds_admin_ajax.ajax_url, type: 'POST',
                        data: { action: 'rds_delete_offday', nonce: rds_admin_ajax.nonce, id: offdayData.id },
                        success: (res) => { if (res.success) { offdaysTable.ajax.reload(null, false); Swal.fire('Deleted!', 'The off day has been deleted.', 'success'); } }
                    });
                }
            });
        });

    } // end of if ($('#rd-offdays-table').length)
});