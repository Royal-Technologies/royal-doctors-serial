<?php
/**
 * Plugin Name:       Royal Doctors Serial
 * Plugin URI:        https://www.royaltechbd.com
 * Description:       A complete system for managing doctor's serials and patient bookings. Developed for Royal Technologies.
 * Version:           1.0.0
 * Author:            Royal Technologies
 * Author URI:        https://www.royaltechbd.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       royal-doctors-serial
 * Domain Path:       /languages
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

require_once __DIR__ . '/vendor/autoload.php';

define( 'RDS_VERSION', '1.0.0' );
define( 'RDS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RDS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RDS_MAIN_FILE', __FILE__ );

/**
 * Initializes the plugin updater.
 * This function should be hooked to 'admin_init'.
 */
function royal_doctors_serial_initialize_updater() {
    // Ensure this function only runs in the admin area.
    if ( ! is_admin() ) {
        return;
    }

    // Load the updater class from the 'includes' folder.
    require_once( plugin_dir_path( __FILE__ ) . 'includes/updater.php' );

    // The full URL to the update JSON file hosted on your server.
    $update_url = 'https://www.royaltechbd.com/royalsoftwares/royal-doctors-serial.json';

    // Initialize the updater class.
    new Royal_Doctors_Serial_Updater( __FILE__, $update_url );
}
add_action( 'admin_init', 'royal_doctors_serial_initialize_updater' );


/* Language */
add_action( 'plugins_loaded', 'royal_doctors_serial_for_elementor_load_text_domain' );
function royal_doctors_serial_for_elementor_load_text_domain() {
    load_plugin_textdomain( 'royal-doctors-serial', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

function get_base_domain_for_rds($host) {
    // Use a static variable to cache the ccSLD list. This improves performance
    // by preventing the file from being read on every function call.
    static $all_ccslds = null;

    if ($all_ccslds === null) {
        $all_ccslds = [];
        // Construct the full path to the ccsld.json file
        $ccsld_path = RDS_PLUGIN_DIR . 'includes/ccsld.json';

        if (file_exists($ccsld_path)) {
            // Read and decode the JSON file
            $ccsld_json = file_get_contents($ccsld_path);
            $ccsld_data = json_decode($ccsld_json, true);

            if (is_array($ccsld_data)) {
                // Flatten the array of all ccSLDs from the JSON structure
                foreach ($ccsld_data as $country_slds) {
                    if (is_array($country_slds)) {
                        $all_ccslds = array_merge($all_ccslds, $country_slds);
                    }
                }
                // Sort by length in descending order to match longer TLDs first (e.g., 'com.bd' before 'bd')
                usort($all_ccslds, function($a, $b) {
                    return strlen($b) - strlen($a);
                });
            }
        }
    }

    $host = strtolower(trim($host));
    
    // First, check the host against the ccSLD list
    if (!empty($all_ccslds)) {
        foreach ($all_ccslds as $sld) {
            // Check if the host ends with the current ccSLD (e.g., .com.bd)
            if (preg_match('/\\.' . preg_quote($sld, '/') . '$/i', $host)) {
                // Remove the ccSLD from the end to get the remaining part of the host
                $host_without_sld = substr($host, 0, strlen($host) - strlen($sld) - 1);
                
                // The domain part is the last segment before the ccSLD
                $parts = explode('.', $host_without_sld);
                $domain_part = end($parts);
                
                // Return the correctly formed domain and exit the function
                return $domain_part . '.' . $sld;
            }
        }
    }

    // --- Fallback Logic ---
    // If no match was found in the ccSLD list, use the original logic for standard domains (.com, .org etc.).
    $host = preg_replace('/^www\./', '', $host);
    $parts = explode('.', $host);
    $count = count($parts);

    if ($count >= 2) {
        return $parts[$count - 2] . '.' . $parts[$count - 1];
    }

    return $host;
}

function royaldoctors_is_license_valid() {
    $domain = get_base_domain_for_rds($_SERVER['SERVER_NAME']);
    $secret = 'royaldoctorsserial';
    $generated_license = md5($domain . $secret);

    // Secure predefined valid licenses (don't store actual domain names)
    $predefined_valid_licenses = [
        'f7cfd9d00366358e6aecf118e80da995',
        '29aed1786543a5ad608ad4ca6e7595e2',
        '01b8fe512edbeb0e59a8cfe45b91a887',
        '480c26d245e19e7f54978e474aa81ad1',
    ];

    // Check if generated license is in allowed list or saved license matches
    $saved_license = get_option('royaldoctors_license_key', '');

    if (in_array($generated_license, $predefined_valid_licenses, true)) {
        return true;
    }

    if ($saved_license === $generated_license) {
        return true;
    }

    return false;
}


// Define global constant
if (!defined('ROYAL_DOCTORS_SERIAL_ACTIVE')) {
    define('ROYAL_DOCTORS_SERIAL_ACTIVE', royaldoctors_is_license_valid());
}


require_once RDS_PLUGIN_DIR . 'includes/activation.php';
register_activation_hook( RDS_MAIN_FILE, 'rds_plugin_activate' );


require_once RDS_PLUGIN_DIR . 'includes/common/enqueue-scripts.php';
require_once RDS_PLUGIN_DIR . 'includes/updater.php';
if ( is_admin() ) {
    require_once RDS_PLUGIN_DIR . 'includes/admin/menu.php';
    require_once RDS_PLUGIN_DIR . 'includes/admin/ajax-handlers.php';
}

require_once RDS_PLUGIN_DIR . 'includes/frontend/shortcode.php';
require_once RDS_PLUGIN_DIR . 'includes/frontend/booking-handler.php';



function rds_add_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=rd-serial-settings">' . __( 'Settings', 'royal-doctors-serial' ) . '</a>';
    array_unshift( $links, $settings_link ); 
    return $links;
}

add_filter( "plugin_action_links_" . plugin_basename( RDS_MAIN_FILE ), 'rds_add_settings_link' );

?>