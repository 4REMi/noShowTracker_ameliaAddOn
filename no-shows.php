<?php
/**
 * Plugin Name: No-Show Tracker
 * Description: Track and manage no-show appointments in the Amelia booking system
 * Version: 2.2
 * Author: Remi
 * Text Domain: amelia-no-shows
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ANS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ANS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ANS_VERSION', '2.2');

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    $prefix = 'AmeliaNoShows\\';
    $base_dir = ANS_PLUGIN_PATH . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize the plugin
function ans_init() {
    if (class_exists('AmeliaBooking\Application\Services\Booking\BookingApplicationService')) {
        require_once ANS_PLUGIN_PATH . 'includes/class-no-show-tracker.php';
        $tracker = new AmeliaNoShows\NoShowTracker();
        $tracker->init();
    }
}
add_action('plugins_loaded', 'ans_init');

// Activation hook
register_activation_hook(__FILE__, 'ans_activate');
function ans_activate() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    error_log('Starting plugin activation and table creation...');

    // Create penalties table
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}amelia_no_show_penalties (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        customerId int(11) NOT NULL,
        noShowCount int(11) NOT NULL DEFAULT 0,
        penaltyAmount decimal(10,2) NOT NULL DEFAULT 0.00,
        lastUpdated datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY customer_id (customerId)
    ) $charset_collate;";

    // Create history table
    $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}amelia_no_show_history (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        customerId int(11) NOT NULL,
        appointmentId int(11) NOT NULL,
        penaltyAmount decimal(10,2) NOT NULL,
        paidDate datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY customer_id (customerId)
    ) $charset_collate;";

    // Create paid history table
    $paid_history_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}amelia_noshow_paid_history (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        customerId int(11) NOT NULL,
        appointmentId int(11) NOT NULL,
        serviceName varchar(255) NOT NULL,
        providerName varchar(255) NOT NULL,
        bookingStart datetime NOT NULL,
        penaltyAmount decimal(10,2) NOT NULL,
        paidDate datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY customer_id (customerId)
    ) $charset_collate;";

    // Create penalty amount settings table
    $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}amelia_noshow_settings (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        setting_name varchar(50) NOT NULL,
        setting_value decimal(10,2) NOT NULL,
        lastUpdated datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY setting_name (setting_name)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    error_log('Creating paid history table...');
    dbDelta($paid_history_table);
    
    // Verify table creation
    $table_name = $wpdb->prefix . 'amelia_noshow_paid_history';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    error_log('Paid history table created successfully: ' . ($table_exists ? 'yes' : 'no'));

    error_log('Creating other tables...');
    dbDelta($sql);

    // Initialize penalty amount if not exists
    $wpdb->query($wpdb->prepare(
        "INSERT IGNORE INTO {$wpdb->prefix}amelia_noshow_settings (setting_name, setting_value) VALUES (%s, %f)",
        'penalty_amount',
        10.00
    ));

    error_log('Plugin activation completed.');

    // Initialize tables with existing no-show data
    ans_initialize_tables();
}

// Function to populate plugin tables with existing no-show data
function ans_initialize_tables() {
    global $wpdb;

    // Get all customers with no-shows
    $customers_with_no_shows = $wpdb->get_results("
        SELECT 
            cb.customerId,
            COUNT(*) as no_show_count
        FROM {$wpdb->prefix}amelia_customer_bookings cb
        WHERE cb.status = 'no-show'
        GROUP BY cb.customerId
    ");

    if (!empty($customers_with_no_shows)) {
        foreach ($customers_with_no_shows as $customer) {
            // Calculate penalty amount (using same logic as in NoShowTracker class)
            $penalty_amount = $customer->no_show_count * 10.00;

            // Insert or update penalties record
            $wpdb->replace(
                $wpdb->prefix . 'amelia_no_show_penalties',
                array(
                    'customerId' => $customer->customerId,
                    'noShowCount' => $customer->no_show_count,
                    'penaltyAmount' => $penalty_amount,
                    'lastUpdated' => current_time('mysql')
                ),
                array('%d', '%d', '%f', '%s')
            );

            // Get all no-show appointments for this customer
            $no_show_appointments = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    id,
                    appointmentId,
                    customerId
                FROM {$wpdb->prefix}amelia_customer_bookings
                WHERE customerId = %d AND status = 'no-show'
            ", $customer->customerId));

            // Add entries to history table
            foreach ($no_show_appointments as $appointment) {
                // Check if this appointment is already in history
                $exists = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*)
                    FROM {$wpdb->prefix}amelia_no_show_history
                    WHERE customerId = %d AND appointmentId = %d
                ", $appointment->customerId, $appointment->appointmentId));

                if (!$exists) {
                    $wpdb->insert(
                        $wpdb->prefix . 'amelia_no_show_history',
                        array(
                            'customerId' => $appointment->customerId,
                            'appointmentId' => $appointment->appointmentId,
                            'penaltyAmount' => 0, // Set to 0 for initial data
                            'paidDate' => current_time('mysql')
                        ),
                        array('%d', '%d', '%f', '%s')
                    );
                }
            }
        }
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'ans_deactivate');
function ans_deactivate() {
    // Cleanup tasks if needed
} 