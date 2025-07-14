//setting-page.js

jQuery(document).ready(function($) {

    // ===================================
    // চেম্বার ম্যানেজমেন্ট (বাগ ফিক্স এবং নতুন লেআউট)
    // ===================================
    function loadChambers() {
        $('#chamber-list-container').html('<p>Loading chambers...</p>');
        $.ajax({
            url: rds_admin_ajax.ajax_url,
            type: 'POST',
            data: { action: 'rds_get_chambers', nonce: rds_admin_ajax.nonce },
            success: function(response) {
                if (response.success) {
                    let chamberHtml = '<p>No chambers found. Add a new one.</p>';
                    if (response.data.length > 0) {
                        chamberHtml = `<table class="wp-list-table widefat fixed striped rds-swal-table">
                                        <thead><tr><th>Chamber Name</th><th>Description</th><th>Advance Days</th><th style="width: 120px;">Actions</th></tr></thead>
                                        <tbody>`;
                        response.data.forEach(function(chamber) {
                            const desc = chamber.description ? unescape(chamber.description) : '';
                            const advanceDays = chamber.advance_booking_days;
                            chamberHtml += `<tr data-id="${chamber.id}" data-name="${escape(chamber.name)}" data-description="${escape(desc)}" data-advance_days="${advanceDays}">
                                                <td>${chamber.name}</td>
                                                <td>${desc}</td>
                                                <td>${advanceDays}</td>
                                                <td><button class="button button-secondary edit-chamber-btn">Edit</button> <button class="button button-danger delete-chamber-btn">Delete</button></td>
                                            </tr>`;
                        });
                        chamberHtml += '</tbody></table>';
                    }
                    $('#chamber-list-container').html(chamberHtml);
                }
            }
        });
    }

    $('body').on('click', '#manage-chambers-btn', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Manage Chambers',
            width: '800px',
            html: `<div id="chamber-list-container" style="margin-bottom: 20px;"></div><hr>
                <form id="add-chamber-form" style="text-align: left;">
                    <h4>Add New Chamber</h4>
                    <div class="rds-form-row">
                        <input type="text" id="new_chamber_name" class="swal2-input" placeholder="Chamber Name" required>
                        <textarea id="new_chamber_desc" class="swal2-textarea" placeholder="Description (optional)"></textarea>
                        <input type="number" id="new_advance_days" class="swal2-input" placeholder="Advance Days (e.g., 7)" required>
                    </div>
                </form>`,
            confirmButtonText: 'Add New Chamber', showCancelButton: true, cancelButtonText: 'Close',
            didOpen: () => { loadChambers(); },
            preConfirm: () => {
                const name = $('#new_chamber_name').val();
                const description = $('#new_chamber_desc').val();
                const advance_days = $('#new_advance_days').val();
                if (!name || !advance_days) { Swal.showValidationMessage('Name and Advance Days are required.'); return false; }
                $.ajax({
                    url: rds_admin_ajax.ajax_url, type: 'POST',
                    data: { action: 'rds_add_chamber', nonce: rds_admin_ajax.nonce, name: name, description: description, advance_days: advance_days },
                    success: function(response) { if (response.success) { loadChambers(); $('#add-chamber-form')[0].reset(); Swal.fire({ icon: 'success', title: 'Saved!', timer: 2000, showConfirmButton: false }); } }
                });
                return false;
            }
        });
    });

    $('body').on('click', '.edit-chamber-btn', function() {
        const row = $(this).closest('tr');
        const chamberId = row.data('id');
        const chamberName = unescape(row.data('name'));
        const chamberDesc = unescape(row.data('description'));
        const chamberAdvanceDays = row.data('advance_days');
        Swal.fire({
            title: 'Edit Chamber',
            width: '800px',
            html: `<form id="edit-chamber-form" style="text-align: left;">
                    <div class="rds-form-row">
                        <input id="edit_chamber_name" class="swal2-input" value="${chamberName}">
                        <textarea id="edit_chamber_desc" class="swal2-textarea" placeholder="Description (optional)">${chamberDesc}</textarea>
                        <input type="number" id="edit_advance_days" class="swal2-input" value="${chamberAdvanceDays}">
                    </div>
                </form>`,
            confirmButtonText: 'Update Chamber',
            showCancelButton: true,
            preConfirm: () => {
                const newName = $('#edit_chamber_name').val();
                const newDesc = $('#edit_chamber_desc').val();
                const newAdvanceDays = $('#edit_advance_days').val(); // এই মানটি এখন নেওয়া হচ্ছে
                if (!newName || !newAdvanceDays) { Swal.showValidationMessage('Name and Advance Days are required.'); return false; }
                $.ajax({
                    url: rds_admin_ajax.ajax_url, type: 'POST',
                    data: {
                        action: 'rds_update_chamber',
                        nonce: rds_admin_ajax.nonce,
                        id: chamberId,
                        name: newName,
                        description: newDesc,
                        advance_days: newAdvanceDays // এবং এখানে পাঠানো হচ্ছে
                    },
                    success: function(response) { if (response.success) { loadChambers(); Swal.fire('Updated!', 'Chamber details have been updated.', 'success'); } }
                });
            }
        });
    });



    $('body').on('click', '.delete-chamber-btn', function() {
        const chamberId = $(this).closest('tr').data('id');
        Swal.fire({
            title: 'Are you sure?', text: "You won't be able to revert this!", icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: rds_admin_ajax.ajax_url, type: 'POST',
                    data: { action: 'rds_delete_chamber', nonce: rds_admin_ajax.nonce, chamber_id: chamberId },
                    success: function(response) { if (response.success) { loadChambers(); Swal.fire('Deleted!', 'The chamber has been deleted.', 'success'); } }
                });
            }
        });
    });









    // ===================================
    // সময়সূচী ম্যানেজমেন্ট (সেটিংস পেজ থেকে)
    // ===================================
    const daysOfWeek = ["Saturday", "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
    function buildDayOptions(selectedDay = '') {
        let options = '';
        daysOfWeek.forEach(day => {
            options += `<option value="${day}" ${day === selectedDay ? 'selected' : ''}>${day}</option>`;
        });
        return `<select id="chamber_day" class="swal2-input">${options}</select>`;
    }

    function loadSchedules() {
        $('#schedule-list-container').html('<p>Loading schedules...</p>');
        $.ajax({
            url: rds_admin_ajax.ajax_url, type: 'POST',
            data: { action: 'rds_get_schedules', nonce: rds_admin_ajax.nonce },
            success: function(response) {
                if (response.success) {
                    let scheduleHtml = '<p>No schedules found.</p>';
                    if (response.data.length > 0) {
                        scheduleHtml = `<table class="wp-list-table widefat fixed striped rds-swal-table"><thead><tr><th>Chamber</th><th>Day</th><th>Time</th><th>Limit</th><th style="width:120px;">Actions</th></tr></thead><tbody>`;
                        response.data.forEach(function(schedule) {
                            let visitTime = new Date('1970-01-01T' + schedule.visit_time + 'Z').toLocaleTimeString('en-US', { timeZone: 'UTC', hour: 'numeric', minute: 'numeric', hour12: true });
                            scheduleHtml += `<tr data-id="${schedule.id}" data-day="${schedule.visit_day}" data-time="${schedule.visit_time}" data-limit="${schedule.serial_limit}">
                                                <td>${schedule.chamber_name}</td><td>${schedule.visit_day}</td><td>${visitTime}</td><td>${schedule.serial_limit}</td>
                                                <td><button class="button button-secondary edit-schedule-btn">Edit</button> <button class="button button-danger delete-schedule-btn">Delete</button></td>
                                            </tr>`;
                        });
                        scheduleHtml += '</tbody></table>';
                    }
                    $('#schedule-list-container').html(scheduleHtml);
                }
            }
        });
    }

    $('body').on('click', '#manage-schedule-btn', function(e) {
        e.preventDefault();
        $.ajax({
            url: rds_admin_ajax.ajax_url, type: 'POST', data: { action: 'rds_get_chambers', nonce: rds_admin_ajax.nonce },
            success: function(response) {
                if (response.success) {
                    let chamberOptions = '<option value="">Select Chamber</option>';
                    response.data.forEach(chamber => { chamberOptions += `<option value="${chamber.id}">${chamber.name}</option>`; });
                    Swal.fire({
                        title: 'Manage Schedule', width: '800px',
                        html: `<div id="schedule-list-container" style="margin-bottom: 20px;"></div><hr>
                            <form id="add-schedule-form" style="text-align: left;"><h4>Add New Schedule</h4>
                                <div class="rds-form-row">
                                    <select id="new_schedule_chamber" class="swal2-input">${chamberOptions}</select>
                                    ${buildDayOptions()}
                                    <input type="time" id="new_schedule_time" class="swal2-input" required>
                                    <input type="number" id="new_schedule_limit" class="swal2-input" placeholder="Limit" value="50" required>
                                </div>
                            </form>`,
                        confirmButtonText: 'Add Schedule', showCancelButton: true, cancelButtonText: 'Close',
                        didOpen: () => { loadSchedules(); },
                        preConfirm: () => {
                            const chamberId = $('#new_schedule_chamber').val();
                            const day = $('#chamber_day').val();
                            const time = $('#new_schedule_time').val();
                            const limit = $('#new_schedule_limit').val();
                            if (!chamberId || !day || !time || !limit) { Swal.showValidationMessage('All fields are required.'); return false; }
                            $.ajax({
                                url: rds_admin_ajax.ajax_url, type: 'POST',
                                data: { action: 'rds_add_schedule', nonce: rds_admin_ajax.nonce, chamber_id: chamberId, day: day, time: time, limit: limit },
                                success: (res) => { if (res.success) { loadSchedules(); Swal.fire({ icon: 'success', title: 'Saved!', timer: 1500, showConfirmButton: false }); } }
                            });
                            return false;
                        }
                    });
                }
            }
        });
    });

    $('body').on('click', '.edit-schedule-btn', function() {
        const row = $(this).closest('tr');
        const scheduleId = row.data('id'), day = row.data('day'), time = row.data('time'), limit = row.data('limit');
        Swal.fire({
            title: 'Edit Schedule',
            html: `<div class="rds-form-row">${buildDayOptions(day)}<input type="time" id="edit_schedule_time" class="swal2-input" value="${time}"><input type="number" id="edit_schedule_limit" class="swal2-input" value="${limit}"></div>`,
            confirmButtonText: 'Update Schedule', showCancelButton: true,
            preConfirm: () => {
                const newDay = $('#chamber_day').val(), newTime = $('#edit_schedule_time').val(), newLimit = $('#edit_schedule_limit').val();
                if (!newDay || !newTime || !newLimit) { Swal.showValidationMessage('All fields are required.'); return false; }
                $.ajax({
                    url: rds_admin_ajax.ajax_url, type: 'POST',
                    data: { action: 'rds_update_schedule', nonce: rds_admin_ajax.nonce, id: scheduleId, day: newDay, time: newTime, limit: newLimit },
                    success: (res) => { if (res.success) { loadSchedules(); Swal.fire('Updated!', 'Schedule has been updated.', 'success'); } }
                });
            }
        });
    });

    $('body').on('click', '.delete-schedule-btn', function() {
        const scheduleId = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?', text: "You want to delete this schedule?", icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: rds_admin_ajax.ajax_url, type: 'POST',
                    data: { action: 'rds_delete_schedule', nonce: rds_admin_ajax.nonce, schedule_id: scheduleId },
                    success: function(res) { if (res.success) { loadSchedules(); Swal.fire('Deleted!', 'The schedule has been deleted.', 'success'); } }
                });
            }
        });
    });




    // ===================================
    // সেটিংস পেজের ট্যাব ম্যানেজমেন্ট
    // ===================================
    if ($('.rds-settings-wrap').length) {
        load_settings_tab('general');
    }
    $('.nav-tab-wrapper .nav-tab').on('click', function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        var tab_id = $(this).attr('href').substring(1);
        load_settings_tab(tab_id);
    });

    function load_settings_tab(tab) {
        $('#settings-tab-content').html('<p class="loading-content">Loading...</p>');
        $.ajax({
            url: rds_admin_ajax.ajax_url, type: 'POST',
            data: { action: 'rds_load_settings_tab', nonce: rds_admin_ajax.nonce, tab: tab },
            success: function(response) { $('#settings-tab-content').html(response); },
            error: function() { $('#settings-tab-content').html('<p>Error loading content.</p>'); }
        });
    }



    // ===================================
    // ক্যাপচা ম্যানেজমেন্ট
    // ===================================
    function loadCaptchas() {
        $('#captcha-list-container').html('<p>Loading captchas...</p>');
        $.ajax({
            url: rds_admin_ajax.ajax_url, type: 'POST', data: { action: 'rds_get_captchas', nonce: rds_admin_ajax.nonce },
            success: function(response) {
                if (response.success) {
                    let captchaHtml = '<p>No captchas found.</p>';
                    if (response.data.length > 0) {
                        captchaHtml = `<table class="wp-list-table widefat fixed striped"><thead><tr><th>Question</th><th>Answer</th><th style="width: 120px;">Actions</th></tr></thead><tbody>`;
                        response.data.forEach(function(captcha) {
                            captchaHtml += `<tr data-id="${captcha.id}" data-question="${escape(captcha.question)}" data-answer="${escape(captcha.answer)}"><td>${captcha.question}</td><td>${captcha.answer}</td><td><button class="button button-secondary edit-captcha-btn">Edit</button> <button class="button button-danger delete-captcha-btn">Delete</button></td></tr>`;
                        });
                        captchaHtml += '</tbody></table>';
                    }
                    $('#captcha-list-container').html(captchaHtml);
                }
            }
        });
    }

    $('body').on('click', '#manage-captcha-btn', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Manage Math Captcha', width: '700px',
            html: `<div id="captcha-list-container" style="margin-bottom: 20px;"></div><hr><form id="add-captcha-form" style="text-align: left;"><h4>Add New Captcha</h4><input type="text" id="new_captcha_question" class="swal2-input" placeholder="Question (e.g., 5 + 3 = ?)" required><input type="text" id="new_captcha_answer" class="swal2-input" placeholder="Answer (e.g., 8)" required></form>`,
            confirmButtonText: 'Add New Captcha', showCancelButton: true, cancelButtonText: 'Close',
            didOpen: () => { loadCaptchas(); },
            preConfirm: () => {
                const question = $('#new_captcha_question').val();
                const answer = $('#new_captcha_answer').val();
                if (!question || !answer) { Swal.showValidationMessage('Please fill out all fields.'); return false; }
                $.ajax({
                    url: rds_admin_ajax.ajax_url, type: 'POST',
                    data: { action: 'rds_add_captcha', nonce: rds_admin_ajax.nonce, question: question, answer: answer },
                    success: function(response) { if (response.success) { loadCaptchas(); $('#add-captcha-form')[0].reset(); Swal.fire({ icon: 'success', title: 'Saved!', timer: 2000, showConfirmButton: false }); } }
                });
                return false;
            }
        });
    });

$('body').on('click', '.edit-captcha-btn', function() {
        const row = $(this).closest('tr');
        const captchaId = row.data('id');
        const captchaQuestion = unescape(row.data('question'));
        const captchaAnswer = unescape(row.data('answer'));
        Swal.fire({
            title: 'Edit Captcha', html: `<input id="edit_captcha_question" class="swal2-input" value="${captchaQuestion}"><input id="edit_captcha_answer" class="swal2-input" value="${captchaAnswer}">`,
            confirmButtonText: 'Update Captcha', showCancelButton: true,
            preConfirm: () => {
                const newQuestion = $('#edit_captcha_question').val();
                const newAnswer = $('#edit_captcha_answer').val();
                if (!newQuestion || !newAnswer) { Swal.showValidationMessage('Please fill out all fields.'); return false; }
                $.ajax({
                    url: rds_admin_ajax.ajax_url, type: 'POST',
                    data: { action: 'rds_update_captcha', nonce: rds_admin_ajax.nonce, id: captchaId, question: newQuestion, answer: newAnswer },
                    success: function(response) { if (response.success) { loadCaptchas(); Swal.fire('Updated!', 'Captcha has been updated.', 'success'); } }
                });
            }
        });
    });

    $('body').on('click', '.delete-captcha-btn', function() {
        const captchaId = $(this).closest('tr').data('id');
        Swal.fire({
            title: 'Are you sure?', text: "You want to delete this captcha?", icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: rds_admin_ajax.ajax_url, type: 'POST',
                    data: { action: 'rds_delete_captcha', nonce: rds_admin_ajax.nonce, captcha_id: captchaId },
                    success: function(response) { if (response.success) { loadCaptchas(); Swal.fire('Deleted!', 'The captcha has been deleted.', 'success'); } }
                });
            }
        });
    });





// Handler for saving the General Settings form
$('body').on('submit', '#rds-general-settings-form', function(e) {
    e.preventDefault();
    
    const $form = $(this);
    const $button = $form.find('#save-general-settings');
    const $spinner = $form.find('.spinner');

    $button.prop('disabled', true);
    $spinner.addClass('is-active');

    // Serialize the form data, which includes the action, nonce, and time_per_patient
    const data = $form.serialize();

    $.ajax({
        url: rds_admin_ajax.ajax_url,
        type: 'POST',
        data: data,
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Settings Saved!',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Could not save settings.' });
            }
        },
        error: function() {
            Swal.fire({ icon: 'error', title: 'Request Failed', text: 'Something went wrong.' });
        },
        complete: function() {
            $button.prop('disabled', false);
            $spinner.removeClass('is-active');
        }
    });
});    

    



// ===================================
// Doctor Info Management
// ===================================

// Handler for the image upload button
$('body').on('click', '.rds-upload-photo-btn', function(e) {
    e.preventDefault();
    const $button = $(this);
    const uploaderContainer = $button.closest('.rds-image-uploader');

    const frame = wp.media({
        title: 'Select Doctor Photo',
        button: { text: 'Use this photo' },
        multiple: false
    });

    frame.on('select', function() {
        const attachment = frame.state().get('selection').first().toJSON();
        uploaderContainer.find('.rds-doctor-photo-id').val(attachment.id);
        uploaderContainer.find('.rds-doctor-photo-url').val(attachment.url);
        uploaderContainer.find('.rds-doctor-photo-preview').attr('src', attachment.url);
        uploaderContainer.find('.rds-remove-photo-btn').show();
    });

    frame.open();
});

// Handler for the image remove button
$('body').on('click', '.rds-remove-photo-btn', function(e) {
    e.preventDefault();
    const uploaderContainer = $(this).closest('.rds-image-uploader');
    uploaderContainer.find('.rds-doctor-photo-id').val('');
    uploaderContainer.find('.rds-doctor-photo-url').val('');
    uploaderContainer.find('.rds-doctor-photo-preview').attr('src', 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'); // Transparent pixel
    $(this).hide();
});

// Handler for saving the Doctor Info form
$('body').on('submit', '#rds-doctor-info-form', function(e) {
    e.preventDefault();
    const $form = $(this);
    $.ajax({
        url: rds_admin_ajax.ajax_url,
        type: 'POST',
        data: $form.serialize(), // Includes action, nonce, and all form fields
        success: function(response) {
            if (response.success) {
                Swal.fire({ icon: 'success', title: 'Saved!', text: 'Doctor information has been updated.', timer: 2000, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Could not save information.' });
            }
        }
    });
});




// Handler for saving the License Key form
$('body').on('submit', '#rds-license-form', function(e) {
    e.preventDefault();
    const $form = $(this);
    $.ajax({
        url: rds_admin_ajax.ajax_url,
        type: 'POST',
        data: $form.serialize(),
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'License Saved!',
                    text: 'The page will now reload to activate the license.',
                    timer: 2500,
                    showConfirmButton: false,
                    willClose: () => {
                        location.reload(); // Reload the page to show the new status
                    }
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Could not save license key.' });
            }
        }
    });
});

});