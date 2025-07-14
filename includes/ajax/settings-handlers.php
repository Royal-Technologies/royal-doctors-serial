<?php
/**
 * AJAX handlers for the plugin's settings page.
 *
 * @package RoyalDoctorsSerial
 */

// Security check:
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Register all AJAX actions
add_action( 'wp_ajax_rds_load_settings_tab', 'rds_load_settings_tab_callback' );
add_action( 'wp_ajax_rds_save_general_settings', 'rds_save_general_settings_callback' );


/**
 * Loads the content for a specific settings tab via AJAX.
 */
function rds_load_settings_tab_callback() {
	check_ajax_referer( 'rds_admin_nonce', 'nonce' );
	$tab = isset( $_POST['tab'] ) ? sanitize_key( $_POST['tab'] ) : 'general';
	switch ( $tab ) {
		case 'doctor_info':
            rds_render_doctor_info_tab();
            break;
		case 'email':
			rds_render_email_tab();
			break;
		case 'about':
			rds_render_about_us_tab();
			break;
		case 'license':
			rds_render_license_tab();
			break;	
		default:
			rds_render_general_tab();
			break;
	}
	wp_die(); // End AJAX request
}

/**
 * Handles saving the general settings form data.
 */
function rds_save_general_settings_callback() {
	check_ajax_referer( 'rds_admin_nonce', 'nonce' );

	if ( isset( $_POST['time_per_patient'] ) ) {
		$time = intval( $_POST['time_per_patient'] );
		update_option( 'rds_time_per_patient', $time );
	}

	wp_send_json_success( [ 'message' => 'Settings saved successfully.' ] );
    // wp_send_json_success() automatically calls wp_die()
}


/**
 * Renders the HTML content for the 'General' tab.
 */
function rds_render_general_tab() {
	?>
	<form id="rds-general-settings-form" method="post">
		<input type="hidden" name="action" value="rds_save_general_settings">
		<?php wp_nonce_field( 'rds_admin_nonce', 'nonce' ); // Nonce field for security ?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">Chamber Management</th>
					<td><button type="button" class="button" id="manage-chambers-btn">Manage Chambers</button>
						<p class="description">Add, edit, or delete chamber names and descriptions.</p></td>
				</tr>
				<tr>
					<th scope="row">Schedule Management</th>
					<td><button type="button" class="button" id="manage-schedule-btn">Manage Schedule</button>
						<p class="description">Set visiting days and times for each chamber.</p></td>
				</tr>
				<tr>
					<th scope="row"><label for="time_per_patient">Time (in minutes)</label></th>
					<td><input name="time_per_patient" type="number" id="time_per_patient" value="<?php echo esc_attr( get_option( 'rds_time_per_patient', '10' ) ); ?>" class="regular-text">
						<p class="description">Time allocated for each patient serial.</p></td>
				</tr>
				<tr>
					<th scope="row">Captcha Management</th>
					<td><button type="button" class="button" id="manage-captcha-btn">Manage Math Captcha</button>
						<p class="description">Add, edit, or delete math captchas for the booking form.</p></td>
				</tr>
			</tbody>
		</table>
		<p class="submit">
			<button type="submit" class="button button-primary" id="save-general-settings">Save Changes</button>
			<span class="spinner"></span>
		</p>
	</form>
	<?php
}

add_action( 'wp_ajax_rds_save_doctor_info', function() {
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );

    $doctor_info = [];
    if (isset($_POST['doctor_info'])) {
        // Sanitize all fields from the form
        $fields = $_POST['doctor_info'];
        $doctor_info['name'] = sanitize_text_field($fields['name']);
        $doctor_info['specialization'] = sanitize_text_field($fields['specialization']);
        $doctor_info['contact'] = sanitize_text_field($fields['contact']);
        $doctor_info['note'] = sanitize_textarea_field($fields['note']);
        $doctor_info['photo_id'] = intval($fields['photo_id']);
        $doctor_info['photo_url'] = esc_url_raw($fields['photo_url']);
    }

    update_option('rds_doctor_info', $doctor_info);

    wp_send_json_success( [ 'message' => 'Doctor info saved successfully.' ] );
});

/**
 * Renders the HTML content for the 'Doctor Info' tab.
 */
function rds_render_doctor_info_tab() {
    $info = get_option('rds_doctor_info', [
        'name' => '', 'specialization' => '', 'contact' => '',
        'note' => '', 'photo_id' => 0, 'photo_url' => ''
    ]);
    ?>
    <form id="rds-doctor-info-form" method="post">
        <input type="hidden" name="action" value="rds_save_doctor_info">
        
        <?php wp_nonce_field( 'rds_admin_nonce', 'nonce' ); ?>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="doc_name">Doctor's Name</label></th>
                    <td><input name="doctor_info[name]" type="text" id="doc_name" value="<?php echo esc_attr($info['name']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="doc_spec">Specialization</label></th>
                    <td><input name="doctor_info[specialization]" type="text" id="doc_spec" value="<?php echo esc_attr($info['specialization']); ?>" class="regular-text">
                        <p class="description">e.g., Cardiologist, Pediatrician</p></td>
                </tr>
                 <tr>
                    <th scope="row"><label for="doc_contact">Contact Number</label></th>
                    <td><input name="doctor_info[contact]" type="text" id="doc_contact" value="<?php echo esc_attr($info['contact']); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="doc_note">Note</label></th>
                    <td><textarea name="doctor_info[note]" id="doc_note" class="large-text" rows="4"><?php echo esc_textarea($info['note']); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row">Doctor's Photo</th>
                    <td>
                        <div class="rds-image-uploader">
                            <img src="<?php echo esc_url($info['photo_url'] ?: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'); ?>" class="rds-doctor-photo-preview" style="max-width: 150px; height: auto; border: 1px solid #ccc; padding: 5px;">
                            <input type="hidden" name="doctor_info[photo_id]" class="rds-doctor-photo-id" value="<?php echo esc_attr($info['photo_id']); ?>">
                            <input type="hidden" name="doctor_info[photo_url]" class="rds-doctor-photo-url" value="<?php echo esc_url($info['photo_url']); ?>">
                            <br>
                            <button type="button" class="button rds-upload-photo-btn">Upload Image</button>
                            <button type="button" class="button rds-remove-photo-btn" style="<?php echo $info['photo_id'] ? '' : 'display:none;'; ?>">Remove Image</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <button type="submit" class="button button-primary">Save Doctor Info</button>
        </p>
    </form>
    <?php
}

/**
 * Renders the HTML content for the 'Email' tab.
 */
function rds_render_email_tab() {
	?>
	<h3>SMTP Configuration</h3>
	<p>To send emails reliably, we recommend using a dedicated SMTP plugin like <a href="https://wordpress.org/plugins/wp-mail-smtp/" target="_blank">WP Mail SMTP</a>. It's more secure and reliable. You can configure it from its own settings page.</p>
	<p>This plugin will automatically use your WordPress site's default email sending settings.</p>
	<?php
}

/**
 * Renders the HTML content for the 'About Us' tab.
 */
function rds_render_about_us_tab() {
	?>
	<div class="rds-about-card">
		<h3>Royal Doctors Serial</h3>
		<p>This plugin is developed and maintained by Royal Technologies.</p>
		<ul>
			<li><strong>Address:</strong> D - 408, Housing Esate, Kushtia</li>
			<li><strong>Email:</strong> <a href="mailto:info@royaltechbd.com">info@royaltechbd.com</a></li>
			<li><strong>Website:</strong> <a href="https://www.royaltechbd.com" target="_blank">www.royaltechbd.com</a></li>
		</ul>
	</div>
	<?php
}


/**
 * Handles saving the license key.
 */
add_action( 'wp_ajax_rds_save_license_key', function() {
    check_ajax_referer( 'rds_admin_nonce', 'nonce' );

    if ( isset( $_POST['license_key'] ) ) {
        $license_key = sanitize_text_field( $_POST['license_key'] );
        update_option( 'royaldoctors_license_key', $license_key );
    }

    wp_send_json_success( [ 'message' => 'License key saved.' ] );
});

/**
 * Renders the HTML content for the 'License' tab.
 */
function rds_render_license_tab() {
    // Check if the license is valid using the global constant
    if ( defined('ROYAL_DOCTORS_SERIAL_ACTIVE') && ROYAL_DOCTORS_SERIAL_ACTIVE ) :
        $domain = get_base_domain_for_rds($_SERVER['SERVER_NAME']);
        $shortcode_text = '[rd_serial_booking_form]';
    ?>
        <div class="rds-license-valid">
            <p style="color: green; font-weight: bold;">License is valid.</p>
            <p>Use the following shortcode to display the booking form:</p>
            <input type="text" readonly value="<?php echo $shortcode_text; ?>" class="large-text" onfocus="this.select();">
        </div>

    <?php else : ?>
        <p>Please activate your license to enable the serial booking functionality.</p>
        <form id="rds-license-form" method="post">
            <input type="hidden" name="action" value="rds_save_license_key">
            <?php wp_nonce_field( 'rds_admin_nonce', 'nonce' ); ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="license_key">License Key</label></th>
                        <td><input name="license_key" type="text" id="license_key" value="" class="regular-text"></td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary">Save License</button>
            </p>
        </form>
    <?php endif;
}