jQuery(document).ready(function($) {

    // Define variables for elements that exist in the form.
    const $chamberSelect = $('#chamber');
    const $dateInput = $('#date');

    // Initialize datepicker as disabled initially.
    $dateInput.datepicker({
        dateFormat: "yy-mm-dd",
        minDate: 0, // Users cannot select past dates.
        beforeShowDay: () => [false, ""] // By default, all dates are disabled.
    });

    // This is the main logic handler. It runs when a user selects a chamber.
    $chamberSelect.on('change', function() {
        const chamberId = $(this).val();
        
        // Reset the date field whenever the chamber changes.
        $dateInput.val('').datepicker('refresh');

        if (!chamberId) {
            // If no chamber is selected, disable the datepicker.
            $dateInput.prop('disabled', true).attr('placeholder', 'Select a chamber first');
            return;
        }

        $dateInput.prop('disabled', true).attr('placeholder', 'Loading, please wait...');

        // AJAX call to get the schedule rules (allowed days, off days, etc.) for the selected chamber.
        $.ajax({
            type: 'POST',
            url: rds_frontend_ajax.ajax_url,
            data: { 
                action: 'rds_get_chamber_schedule', 
                nonce: rds_frontend_ajax.nonce, 
                chamber_id: chamberId 
            },
            success: function(response) {
                if (response.success) {
                    const schedule = response.data;
                    // Configure and enable the datepicker based on the received schedule.
                    $dateInput.datepicker('option', {
                        maxDate: schedule.advanceBookingDays > 0 ? schedule.advanceBookingDays - 1 : 0,
                        beforeShowDay: function(date) {
                            const day = date.getDay(); // 0=Sunday, 1=Monday...
                            const dateString = $.datepicker.formatDate('yy-mm-dd', date);
                            
                            const isDayAllowed = (schedule.allowedDays.indexOf(day) !== -1);
                            const isDateBlocked = (schedule.blockedDates.indexOf(dateString) !== -1);
                            
                            // A date is selectable only if its day is allowed AND it's not a specific off-day.
                            return (isDayAllowed && !isDateBlocked) ? [true, "available-day"] : [false, "unavailable-day"];
                        }
                    }).prop('disabled', false).attr('placeholder', 'Select an available date');
                } else {
                    $dateInput.prop('disabled', true).attr('placeholder', 'Could not load schedule');
                }
            },
            error: function() {
                $dateInput.prop('disabled', true).attr('placeholder', 'Error loading schedule');
            }
        });
    });

    // Form submission logic.
    $('#rd-booking-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const formData = $form.serialize();
        const data = formData + '&action=rds_book_serial&nonce=' + rds_frontend_ajax.nonce;

        $.ajax({
            type: 'POST',
            url: rds_frontend_ajax.ajax_url,
            data: data,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Booking Confirmed!',
                        html: `Your Serial Number: <strong>${response.data.serial_number}</strong><br>Estimated Time: <strong>${response.data.estimated_time}</strong>`,
                        confirmButtonText: 'OK'
                    });
                    $form[0].reset();
                    // Reset the datepicker to its initial disabled state.
                    $dateInput.prop('disabled', true).attr('placeholder', 'Select a chamber first').val('');
                } else {
                    Swal.fire({ icon: 'error', title: 'Oops...', text: response.data.message });
                }
            },
            error: function() {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong! Please try again.' });
            }
        });
    });
});