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

        add_action('init', array($this, 'create_table'));
    }

    public function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            email varchar(100) NOT NULL UNIQUE,
            name varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
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

    public function get_emails($limit = 100, $offset = 0) {
        global $wpdb;

        $sql = $wpdb->prepare("SELECT * FROM $this->table_name ORDER BY created_at DESC LIMIT %d OFFSET %d", $limit, $offset);
        return $wpdb->get_results($sql);
    }

    public function get_email($email) {
        global $wpdb;

        $sql = $wpdb->prepare("SELECT * FROM $this->table_name WHERE email = %s", $email);
        return $wpdb->get_row($sql);
    }

    public function export_csv() {
        global $wpdb;

        $emails = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY created_at DESC", ARRAY_A);

        $filename = 'email_list_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, array('Email', 'Name', 'Date'));

        foreach ($emails as $row) {
            fputcsv($output, array($row['email'], $row['name'], $row['created_at']));
        }

        fclose($output);
        exit;
    }
}