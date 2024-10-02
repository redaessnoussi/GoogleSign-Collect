<?php
/**
 * Plugin Name: GoogleSign Collect
 * Description: Custom landing page with Google login to collect user emails and export them as CSV.
 * Version: 1.0
 * Author: Your Name
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/class-google-auth.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-landing-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-email-manager.php';

// Initialize plugin
class GoogleSignCollect {

    public function __construct() {
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('init', array($this, 'handle_google_login'));
    }

    // Register admin menu
    public function register_admin_menu() {
        add_menu_page(
            'GoogleSign Collect',
            'GoogleSign Collect',
            'manage_options',
            'google-sign-collect',
            array($this, 'admin_page'),
            'dashicons-email-alt',
            25
        );
    }

    // Admin page content
    public function admin_page() {
        echo '<div class="wrap"><h1>GoogleSign Collect Settings</h1>';
        // Handle Google API setup, emails display, and export options here
    }

    public function handle_google_login() {
        // Handle Google login process here
    }

}

// Initialize plugin
new GoogleSignCollect();

// Create the database table for storing emails on plugin activation
register_activation_hook(__FILE__, 'google_sign_collect_create_table');

function google_sign_collect_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'google_sign_collect_emails';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        email varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
