<?php
/**
 * Class GSC_Email_Manager
 * Handles email storage and management
 */
class GSC_Email_Manager {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'gsc_emails';
        $this->create_table();
    }

    public function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            account_id mediumint(9) NOT NULL,
            email varchar(100) NOT NULL,
            name varchar(100),
            access_token text,
            refresh_token text,
            token_expiry datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY email (email),
            KEY user_id (user_id),
            KEY account_id (account_id)
        ) $charset_collate;";
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_or_update_email($user_id, $account_id, $email, $name = '', $access_token = '', $refresh_token = '', $token_expiry = '') {
        global $wpdb;
    
        if (empty($email) || !is_email($email)) {
            error_log("Invalid email provided: $email");
            return false;
        }
    
        $data = array(
            'user_id' => $user_id,
            'account_id' => $account_id,
            'email' => sanitize_email($email),
            'name' => sanitize_text_field($name),
            'access_token' => sanitize_text_field($access_token),
            'refresh_token' => sanitize_text_field($refresh_token),
            'token_expiry' => sanitize_text_field($token_expiry),
            'updated_at' => current_time('mysql')
        );
    
        $format = array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s');
    
        $existing_email = $this->get_email($user_id, $email);
    
        if ($existing_email) {
            $result = $wpdb->update($this->table_name, $data, array('user_id' => $user_id, 'email' => $email), $format, array('%d', '%s'));
        } else {
            $data['created_at'] = current_time('mysql');
            $format[] = '%s';
            $result = $wpdb->insert($this->table_name, $data, $format);
        }
    
        if ($result === false) {
            error_log("Database error occurred while " . ($existing_email ? "updating" : "inserting") . " email: " . $wpdb->last_error);
            return false;
        }
    
        return true;
    }

    public function add_email($email, $name = '') {
        global $wpdb;

        $existing_email = $this->get_email($email);

        if ($existing_email) {
            // Email already exists, update the name and updated_at timestamp
            $result = $wpdb->update(
                $this->table_name,
                array('name' => $name, 'updated_at' => current_time('mysql')),
                array('email' => $email),
                array('%s', '%s'),
                array('%s')
            );
            return $result !== false; // Return true if update was successful
        } else {
            // Email doesn't exist, insert new record
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'email' => $email,
                    'name' => $name,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s')
            );
            return $result !== false; // Return true if insert was successful
        }
    }

    public function get_emails($user_id, $limit = 100, $offset = 0) {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM $this->table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d", $user_id, $limit, $offset);
        return $wpdb->get_results($sql);
    }

    public function get_email($user_id, $email) {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM $this->table_name WHERE user_id = %d AND email = %s", $user_id, $email);
        return $wpdb->get_row($sql);
    }

    public function export_csv($user_id) {
        global $wpdb;

        $emails = $wpdb->get_results($wpdb->prepare("SELECT * FROM $this->table_name WHERE user_id = %d ORDER BY created_at DESC", $user_id), ARRAY_A);

        $filename = 'email_list_user_' . $user_id . '_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, array('Email', 'Name', 'Access Token', 'Date'));

        foreach ($emails as $row) {
            fputcsv($output, array($row['email'], $row['name'], $row['access_token'], $row['created_at']));
        }

        fclose($output);
        exit;
    }

     // New method for admin to get all emails
     public function get_all_emails_admin($limit = 100, $offset = 0) {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT e.*, u.user_login, u.user_email as user_email FROM $this->table_name e JOIN {$wpdb->users} u ON e.user_id = u.ID ORDER BY e.created_at DESC LIMIT %d OFFSET %d", $limit, $offset);
        return $wpdb->get_results($sql);
    }

    // New method for admin to export all emails
    public function export_all_csv_admin() {
        global $wpdb;

        $emails = $wpdb->get_results("SELECT e.*, u.user_login, u.user_email as user_email FROM $this->table_name e JOIN {$wpdb->users} u ON e.user_id = u.ID ORDER BY e.created_at DESC", ARRAY_A);

        $filename = 'all_email_list_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, array('User', 'User Email', 'Collected Email', 'Name', 'Access Token', 'Date'));

        foreach ($emails as $row) {
            fputcsv($output, array($row['user_login'], $row['user_email'], $row['email'], $row['name'], $row['access_token'], $row['created_at']));
        }

        fclose($output);
        exit;
    }

    public function get_total_emails_count($user_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $this->table_name WHERE user_id = %d", $user_id));
    }
    
    public function get_emails_by_account($user_id) {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM $this->table_name WHERE user_id = %d ORDER BY created_at DESC", $user_id);
        
        // Debug: Log the SQL query
        error_log("SQL Query: " . $sql);
        
        $results = $wpdb->get_results($sql);
        
        // Debug: Log the number of results
        error_log("Results found: " . count($results));
        
        return $results;
    }
}