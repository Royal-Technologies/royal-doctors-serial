//admin-script.js

jQuery(document).ready(function($) {

    // ===================================
    // চেম্বার ম্যানেজমেন্ট (সেটিংস পেজ থেকে)
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
                        chamberHtml = `<table class="wp-list-table widefat fixed striped"><thead><tr><th>Chamber Name</th><th>Description</th><th style="width: 120px;">Actions</th></tr></thead><tbody>`;
                        response.data.forEach(function(chamber) {
                            const desc = chamber.description ? unescape(chamber.description) : '';
                            chamberHtml += `<tr data-id="${chamber.id}" data-name="${escape(chamber.name)}" data-description="${escape(desc)}">
                                                <td>${chamber.name}</td>
                                                <td>${desc}</td>
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

    // Event Delegation কারণ বাটনটি AJAX দিয়ে লোড হয়
    $('body').on('click', '#manage-chambers-btn', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Manage Chambers',
            width: '700px',
            html: `<div id="chamber-list-container" style="margin-bottom: 20px;"></div><hr>
                   <form id="add-chamber-form" style="text-align: left;"><h4>Add New Chamber</h4>
                       <input type="text" id="new_chamber_name" class="swal2-input" placeholder="Chamber Name" required>
                       <textarea id="new_chamber_desc" class="swal2-textarea" placeholder="Chamber Description (optional)"></textarea>
                   </form>`,
            confirmButtonText: 'Add New Chamber',
            showCancelButton: true,
            cancelButtonText: 'Close',
            didOpen: () => { loadChambers(); },
            preConfirm: () => {
                const name = $('#new_chamber_name').val();
                const description = $('#new_chamber_desc').val();
                if (!name) { Swal.showValidationMessage('Chamber name is required.'); return false; }
                $.ajax({
                    url: rds_admin_ajax.ajax_url, type: 'POST',
                    data: { action: 'rds_add_chamber', nonce: rds_admin_ajax.nonce, name: name, description: description },
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
        Swal.fire({
            title: 'Edit Chamber',
            html: `<input id="edit_chamber_name" class="swal2-input" value="${chamberName}">
                   <textarea id="edit_chamber_desc" class="swal2-textarea" placeholder="Chamber Description (optional)">${chamberDesc}</textarea>`,
            confirmButtonText: 'Update Chamber',
            showCancelButton: true,
            preConfirm: () => {
                const newName = $('#edit_chamber_name').val();
                const newDesc = $('#edit_chamber_desc').val();
                if (!newName) { Swal.showValidationMessage('Chamber name is required.'); return false; }
                $.ajax({
                    url: rds_admin_ajax.ajax_url, type: 'POST',
                    data: { action: 'rds_update_chamber', nonce: rds_admin_ajax.nonce, id: chamberId, name: newName, description: newDesc },
                    success: function(response) { if (response.success) { loadChambers(); Swal.fire('Updated!', 'Chamber details updated.', 'success'); } }
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
                        scheduleHtml = `<table class="wp-list-table widefat fixed striped"><thead><tr><th>Chamber</th><th>Day</th><th>Time</th><th style="width: 80px;">Action</th></tr></thead><tbody>`;
                        response.data.forEach(function(schedule) {
                            let visitTime = new Date('1970-01-01T' + schedule.visit_time + 'Z').toLocaleTimeString('en-US', { timeZone: 'UTC', hour: 'numeric', minute: 'numeric', hour12: true });
                            scheduleHtml += `<tr><td>${schedule.chamber_name}</td><td>${schedule.visit_day}</td><td>${visitTime}</td><td><button class="button button-danger delete-schedule-btn" data-id="${schedule.id}">Delete</button></td></tr>`;
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
                    Swal.fire({
                        title: 'Manage Schedule', width: '700px',
                        html: `<div id="schedule-list-container" style="margin-bottom: 20px;"></div><hr>
                            <form id="add-schedule-form" style="text-align: left;"><h4>Add New Schedule</h4>
                                <select id="new_schedule_chamber" class="swal2-input">${chamberOptions}</select>
                                ${buildDayOptions()}
                                <input type="time" id="new_schedule_time" class="swal2-input" required>
                            </form>`,
                        confirmButtonText: 'Add Schedule', showCancelButton: true, cancelButtonText: 'Close',
                        didOpen: () => { loadSchedules(); },
                        preConfirm: () => {
                            const chamberId = $('#new_schedule_chamber').val();
                            const day = $('#chamber_day').val();
                            const time = $('#new_schedule_time').val();
                            if (!chamberId || !day || !time) { Swal.showValidationMessage('All fields are required.'); return false; }
                            $.ajax({
                                url: rds_admin_ajax.ajax_url, type: 'POST',
                                data: { action: 'rds_add_schedule', nonce: rds_admin_ajax.nonce, chamber_id: chamberId, day: day, time: time },
                                success: function(res) { if (res.success) { loadSchedules(); Swal.fire({ icon: 'success', title: 'Saved!', timer: 1500, showConfirmButton: false }); } }
                            });
                            return false;
                        }
                    });
                }
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














    

});