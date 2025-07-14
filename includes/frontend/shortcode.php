<?php
/**
 * Handles the frontend booking form shortcode.
 */
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_shortcode('rd_serial_booking_form', 'rds_booking_form_shortcode');
function rds_booking_form_shortcode() {
    // Check if the license is active. If not, show an error message.
    if (!defined('ROYAL_DOCTORS_SERIAL_ACTIVE') || !ROYAL_DOCTORS_SERIAL_ACTIVE) {
        return '<p style="color: red; border: 1px solid red; padding: 10px;">This feature requires a valid license key. Please activate the plugin license from the admin panel.</p>';
    }

    global $wpdb;
    
    // Get all chambers for the initial dropdown
    $chambers = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}rds_chambers ORDER BY name ASC");
    $captcha = $wpdb->get_row("SELECT id, question FROM {$wpdb->prefix}rds_captcha ORDER BY RAND() LIMIT 1");

    ob_start();
    ?>
    <form id="rd-booking-form" class="rds-form">
        <div class="form-group">
            <label for="chamber">Select Chamber</label>
            <select name="chamber_id" id="chamber" required>
                <option value=""><?php esc_html_e( 'Select a Chamber', 'royal-doctors-serial' ); ?></option>
                <?php foreach ($chambers as $chamber) : ?>
                    <option value="<?php echo esc_attr($chamber->id); ?>"><?php echo esc_html($chamber->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="date">Select Date</label>
            <input type="text" name="serial_date" id="date" required disabled placeholder="Select a chamber first" autocomplete="off">
        </div>

        <div class="form-group">
            <label for="name">Patient Name</label>
            <input type="text" name="patient_name" id="name" required>
        </div>
        <div class="form-group">
            <label for="mobile">Mobile Number</label>
            <input type="tel" name="patient_mobile" id="mobile" required>
        </div>
        <div class="form-group">
            <label for="email">Email (Optional)</label>
            <input type="email" name="patient_email" id="email">
        </div>
        
        <?php if ($captcha) : ?>
            <div class="form-group">
                <label for="captcha"><?php echo esc_html($captcha->question); ?></label>
                <input type="text" name="captcha_answer" id="captcha" required>
                <input type="hidden" name="captcha_id" value="<?php echo esc_attr($captcha->id); ?>">
            </div>
        <?php endif; ?>
        
        <button type="submit">Book Serial</button>
    </form>
    <?php
    return ob_get_clean();
}