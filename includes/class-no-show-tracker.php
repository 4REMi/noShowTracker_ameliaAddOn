<?php
namespace AmeliaNoShows;

class NoShowTracker {
    private $version;

    public function __construct() {
        $this->version = ANS_VERSION;
    }

    public function init() {
        // Add menu item
        add_action('admin_menu', array($this, 'add_menu_page'));
        
        // Register assets
        add_action('admin_enqueue_scripts', array($this, 'register_assets'));
        
        // Ajax handlers
        add_action('wp_ajax_ans_get_no_shows', array($this, 'get_no_shows'));
        add_action('wp_ajax_ans_get_customer_details', array($this, 'get_customer_details'));
        add_action('wp_ajax_ans_mark_as_paid', array($this, 'mark_as_paid'));
        add_action('wp_ajax_ans_mark_all_as_paid', array($this, 'mark_all_as_paid'));
    }

    public function add_menu_page() {
        add_menu_page(
            __('No-Show Tracker', 'amelia-no-shows'),
            __('No-Show Tracker', 'amelia-no-shows'),
            'manage_options',
            'amelia-no-shows',
            array($this, 'render_admin_page'),
            'dashicons-money-alt',
            30
        );

        // Add submenu pages
        add_submenu_page(
            'amelia-no-shows',
            __('No-Shows', 'amelia-no-shows'),
            __('No-Shows', 'amelia-no-shows'),
            'manage_options',
            'amelia-no-shows'
        );

        add_submenu_page(
            'amelia-no-shows',
            __('Monto de Penalización', 'amelia-no-shows'),
            __('Monto', 'amelia-no-shows'),
            'manage_options',
            'amelia-no-shows-amount',
            array($this, 'render_amount_page')
        );

        add_submenu_page(
            'amelia-no-shows',
            __('Historial de Pagos', 'amelia-no-shows'),
            __('Historial', 'amelia-no-shows'),
            'manage_options',
            'amelia-no-shows-history',
            array($this, 'render_history_page')
        );
    }

    public function register_assets($hook) {
        if ('toplevel_page_amelia-no-shows' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'amelia-no-shows-admin',
            ANS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $this->version
        );

        wp_enqueue_script(
            'amelia-no-shows-admin',
            ANS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_localize_script('amelia-no-shows-admin', 'ansAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ans-ajax-nonce'),
            'timezone' => get_option('timezone_string') ?: 'UTC'
        ));
    }

    public function render_admin_page() {
        include ANS_PLUGIN_PATH . 'views/admin-page.php';
    }

    public function get_no_shows() {
        check_ajax_referer('ans-ajax-nonce', 'nonce');

        global $wpdb;

        // Check only essential Amelia tables
        $tables_to_check = array(
            $wpdb->prefix . 'amelia_customer_bookings',
            $wpdb->prefix . 'amelia_users'
        );

        foreach ($tables_to_check as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                error_log("Essential Amelia table $table does not exist");
                wp_send_json_error("Required Amelia table $table does not exist");
                return;
            }
        }

        // Get current penalty amount
        $current_penalty = $this->get_penalty_amount();

        // Simplified query using only core Amelia tables
        $query = $wpdb->prepare("
            SELECT 
                cb.customerId,
                u.firstName,
                u.lastName,
                u.email,
                COUNT(*) as no_show_count
            FROM {$wpdb->prefix}amelia_customer_bookings cb
            JOIN {$wpdb->prefix}amelia_users u ON u.id = cb.customerId
            WHERE cb.status = %s
            GROUP BY cb.customerId, u.firstName, u.lastName, u.email
            ORDER BY no_show_count DESC
        ", 'no-show');

        error_log('Executing simplified query: ' . $query);
        
        $results = $wpdb->get_results($query);
        
        if ($wpdb->last_error) {
            error_log('Database error: ' . $wpdb->last_error);
            wp_send_json_error('Database error: ' . $wpdb->last_error);
            return;
        }

        // Calculate current penalties for each customer
        foreach ($results as $result) {
            $result->penalty_amount = $current_penalty * $result->no_show_count;
        }

        error_log('Query results count: ' . count($results));
        error_log('Query results: ' . print_r($results, true));

        wp_send_json_success($results);
    }

    public function get_customer_details() {
        check_ajax_referer('ans-ajax-nonce', 'nonce');
        
        if (!isset($_POST['customerId'])) {
            wp_send_json_error('Customer ID is required');
        }

        global $wpdb;
        
        $customer_id = intval($_POST['customerId']);
        
        $query = $wpdb->prepare("
            SELECT 
                cb.id as booking_id,
                a.bookingStart,
                s.name as service_name,
                CONCAT(p.firstName, ' ', p.lastName) as provider_name
            FROM {$wpdb->prefix}amelia_customer_bookings cb
            JOIN {$wpdb->prefix}amelia_appointments a ON a.id = cb.appointmentId
            JOIN {$wpdb->prefix}amelia_services s ON s.id = a.serviceId
            JOIN {$wpdb->prefix}amelia_users p ON p.id = a.providerId
            WHERE cb.customerId = %d AND cb.status = 'no-show'
            ORDER BY a.bookingStart DESC
        ", $customer_id);

        $results = $wpdb->get_results($query);
        
        wp_send_json_success($results);
    }

    public function render_amount_page() {
        if (isset($_POST['submit_penalty_amount']) && check_admin_referer('update_penalty_amount')) {
            $amount = floatval($_POST['penalty_amount']);
            $this->update_penalty_amount($amount);
            echo '<div class="notice notice-success"><p>' . esc_html__('Monto de penalización actualizado.', 'amelia-no-shows') . '</p></div>';
        }

        $current_amount = $this->get_penalty_amount();
        include ANS_PLUGIN_PATH . 'views/amount-page.php';
    }

    public function render_history_page() {
        global $wpdb;
        
        // Verify if table exists
        $table_name = $wpdb->prefix . 'amelia_noshow_paid_history';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        error_log('History table exists: ' . ($table_exists ? 'yes' : 'no'));
        
        if (!$table_exists) {
            echo '<div class="notice notice-error"><p>' . 
                 esc_html__('La tabla de historial no existe. Por favor, desactive y reactive el plugin.', 'amelia-no-shows') . 
                 '</p></div>';
            return;
        }

        // Pagination settings
        $items_per_page = 15;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $items_per_page;

        // Build query with date filters
        $where = array();
        $where_values = array();

        if (!empty($_GET['date_from'])) {
            $where[] = 'DATE(h.paidDate) >= %s';
            $where_values[] = $_GET['date_from'];
        }

        if (!empty($_GET['date_to'])) {
            $where[] = 'DATE(h.paidDate) <= %s';
            $where_values[] = $_GET['date_to'];
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count total items
        $count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}amelia_noshow_paid_history h " . $where_clause;
        if (!empty($where_values)) {
            $count_query = $wpdb->prepare($count_query, $where_values);
        }
        $total_items = $wpdb->get_var($count_query);
        $total_pages = ceil($total_items / $items_per_page);

        // Get paginated results
        $query = "
            SELECT 
                h.*,
                CONCAT(u.firstName, ' ', u.lastName) as customerName,
                u.email as customerEmail
            FROM {$wpdb->prefix}amelia_noshow_paid_history h
            JOIN {$wpdb->prefix}amelia_users u ON u.id = h.customerId
            $where_clause
            ORDER BY h.paidDate DESC
            LIMIT %d OFFSET %d
        ";

        $query_values = array_merge($where_values, array($items_per_page, $offset));
        $history = $wpdb->get_results($wpdb->prepare($query, $query_values));
        
        if ($wpdb->last_error) {
            error_log('History query error: ' . $wpdb->last_error);
            echo '<div class="notice notice-error"><p>' . 
                 esc_html__('Error al obtener el historial: ', 'amelia-no-shows') . 
                 esc_html($wpdb->last_error) . '</p></div>';
        }

        include ANS_PLUGIN_PATH . 'views/history-page.php';
    }

    private function get_penalty_amount() {
        global $wpdb;
        return floatval($wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM {$wpdb->prefix}amelia_noshow_settings WHERE setting_name = %s",
            'penalty_amount'
        ))) ?: 10.00;
    }

    private function update_penalty_amount($amount) {
        global $wpdb;
        $wpdb->replace(
            $wpdb->prefix . 'amelia_noshow_settings',
            array(
                'setting_name' => 'penalty_amount',
                'setting_value' => $amount,
                'lastUpdated' => current_time('mysql')
            ),
            array('%s', '%f', '%s')
        );
    }

    public function mark_as_paid() {
        check_ajax_referer('ans-ajax-nonce', 'nonce');
        
        if (!isset($_POST['bookingId'])) {
            wp_send_json_error('Booking ID is required');
        }

        global $wpdb;
        
        $booking_id = intval($_POST['bookingId']);
        
        // Get booking details before updating status
        $booking_details = $wpdb->get_row($wpdb->prepare("
            SELECT 
                cb.customerId,
                cb.appointmentId,
                a.bookingStart,
                s.name as serviceName,
                CONCAT(p.firstName, ' ', p.lastName) as providerName
            FROM {$wpdb->prefix}amelia_customer_bookings cb
            JOIN {$wpdb->prefix}amelia_appointments a ON a.id = cb.appointmentId
            JOIN {$wpdb->prefix}amelia_services s ON s.id = a.serviceId
            JOIN {$wpdb->prefix}amelia_users p ON p.id = a.providerId
            WHERE cb.id = %d
        ", $booking_id));

        if ($booking_details) {
            // Update booking status
            $wpdb->update(
                $wpdb->prefix . 'amelia_customer_bookings',
                array('status' => 'approved'),
                array('id' => $booking_id),
                array('%s'),
                array('%d')
            );

            // Get current penalty amount
            $penalty_amount = $this->get_penalty_amount();

            // Add to paid history
            $wpdb->insert(
                $wpdb->prefix . 'amelia_noshow_paid_history',
                array(
                    'customerId' => $booking_details->customerId,
                    'appointmentId' => $booking_details->appointmentId,
                    'serviceName' => $booking_details->serviceName,
                    'providerName' => $booking_details->providerName,
                    'bookingStart' => $booking_details->bookingStart,
                    'penaltyAmount' => $penalty_amount,
                    'paidDate' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%s', '%f', '%s')
            );

            // Update penalties
            $this->update_customer_penalties($booking_details->customerId);
        }

        wp_send_json_success();
    }

    private function update_customer_penalties($customer_id) {
        global $wpdb;
        
        // Get current no-show count
        $no_show_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->prefix}amelia_customer_bookings
            WHERE customerId = %d AND status = 'no-show'
        ", $customer_id));

        // Calculate penalty amount based on count
        $penalty_amount = $this->calculate_penalty_amount($no_show_count);

        // Update or insert penalties record
        $wpdb->replace(
            $wpdb->prefix . 'amelia_no_show_penalties',
            array(
                'customerId' => $customer_id,
                'noShowCount' => $no_show_count,
                'penaltyAmount' => $penalty_amount,
                'lastUpdated' => current_time('mysql')
            ),
            array('%d', '%d', '%f', '%s')
        );
    }

    private function calculate_penalty_amount($no_show_count) {
        // Get current penalty amount from settings
        $penalty_amount = $this->get_penalty_amount();
        return $no_show_count * $penalty_amount;
    }

    public function mark_all_as_paid() {
        check_ajax_referer('ans-ajax-nonce', 'nonce');
        
        if (!isset($_POST['customerId'])) {
            wp_send_json_error('Customer ID is required');
        }

        global $wpdb;
        
        $customer_id = intval($_POST['customerId']);
        
        // Get all unpaid no-shows for this customer
        $no_shows = $wpdb->get_results($wpdb->prepare("
            SELECT 
                cb.id as booking_id,
                cb.appointmentId,
                a.bookingStart,
                s.name as serviceName,
                CONCAT(p.firstName, ' ', p.lastName) as providerName
            FROM {$wpdb->prefix}amelia_customer_bookings cb
            JOIN {$wpdb->prefix}amelia_appointments a ON a.id = cb.appointmentId
            JOIN {$wpdb->prefix}amelia_services s ON s.id = a.serviceId
            JOIN {$wpdb->prefix}amelia_users p ON p.id = a.providerId
            WHERE cb.customerId = %d AND cb.status = 'no-show'
        ", $customer_id));

        if ($no_shows) {
            // Get current penalty amount
            $penalty_amount = $this->get_penalty_amount() * count($no_shows);

            // Update all bookings status
            $wpdb->query($wpdb->prepare("
                UPDATE {$wpdb->prefix}amelia_customer_bookings 
                SET status = 'approved'
                WHERE customerId = %d AND status = 'no-show'
            ", $customer_id));

            // Add single entry to paid history for all no-shows
            $wpdb->insert(
                $wpdb->prefix . 'amelia_noshow_paid_history',
                array(
                    'customerId' => $customer_id,
                    'appointmentId' => 0, // 0 indicates bulk payment
                    'serviceName' => 'Pago múltiple',
                    'providerName' => 'N/A',
                    'bookingStart' => current_time('mysql'),
                    'penaltyAmount' => $penalty_amount,
                    'paidDate' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%s', '%f', '%s')
            );

            // Update penalties
            $this->update_customer_penalties($customer_id);
        }

        wp_send_json_success();
    }
} 