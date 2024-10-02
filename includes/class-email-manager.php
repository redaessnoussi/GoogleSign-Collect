<?php

class EmailManager {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_email_management_page'));
    }

    // Create admin page to display and export emails
    public function add_email_management_page() {
        add_submenu_page(
            'google-sign-collect',
            'Collected Emails',
            'Collected Emails',
            'manage_options',
            'google-sign-collect-emails',
            array($this, 'render_email_page')
        );
    }

    // Render the collected emails
    public function render_email_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'google_sign_collect_emails';
        $emails = $wpdb->get_results("SELECT * FROM $table");

        echo '<h2>Collected Emails</h2>';
        echo '<textarea readonly>';
        foreach ($emails as $email) {
            echo $email->email . "\n";
        }
        echo '</textarea>';
        
        // Export button
        echo '<a href="' . admin_url('admin-post.php?action=export_emails_csv') . '" class="button">Export as CSV</a>';
    }

    // Export emails as CSV
    public function export_emails_csv() {
        global $wpdb;
        $table = $wpdb->prefix . 'google_sign_collect_emails';
        $emails = $wpdb->get_results("SELECT * FROM $table");

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="collected-emails.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Email'));

        foreach ($emails as $email) {
            fputcsv($output, array($email->email));
        }

        fclose($output);
        exit;
    }
}
