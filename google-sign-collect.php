<?php
/**
 * Plugin Name: Google Sign-In and Email Collection
 * Plugin URI: http://example.com/google-sign-collect
 * Description: A WordPress plugin for creating landing pages with Google Sign-In and email collection.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: http://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('GSC_VERSION', '1.0.0');
define('GSC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GSC_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once GSC_PLUGIN_DIR . 'includes/class-google-auth.php';
require_once GSC_PLUGIN_DIR . 'includes/class-email-manager.php';
require_once GSC_PLUGIN_DIR . 'includes/class-landing-page.php';
require_once GSC_PLUGIN_DIR . 'includes/class-email-sender.php';
require_once GSC_PLUGIN_DIR . 'includes/class-google-cloud-manager.php';
require_once GSC_PLUGIN_DIR . 'includes/class-data-exporter.php';
require_once plugin_dir_path(__FILE__) . 'functions.php';
require_once GSC_PLUGIN_DIR . 'subscriber-page.php';
require_once GSC_PLUGIN_DIR . 'admin-page.php';


// Initialize the plugin
function gsc_init() {
    $google_auth = new GSC_Google_Auth();
    $email_manager = new GSC_Email_Manager();
    $landing_page = new GSC_Landing_Page();

    // Add admin menu
    add_action('admin_menu', 'gsc_add_admin_menu');

    // Register settings
    add_action('admin_init', 'gsc_register_settings');

    // Enqueue admin styles
    add_action('admin_enqueue_scripts', 'gsc_enqueue_admin_styles');

    add_action('admin_post_gsc_export_subscriber_data', 'gsc_export_subscriber_data');
}
add_action('plugins_loaded', 'gsc_init');

function gsc_export_subscriber_data() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    if ($user_id === 0) {
        wp_die(__('Invalid user ID.'));
    }

    $exporter = new GSC_Data_Exporter();
    $exporter->export_subscriber_data($user_id);
}

// Add admin menu
function gsc_add_admin_menu() {
    add_menu_page(
        'Google Sign-In & Collect',
        'GS Collect',
        'manage_options',
        'google-sign-collect',
        'gsc_admin_page',
        'dashicons-email-alt',
        30
    );
}

function gsc_start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'gsc_start_session', 1);

function gsc_get_active_google_account() {
    $active_account_name = get_option('gsc_active_google_account', '');
    $google_accounts = get_option('gsc_google_accounts', array());

    if (!empty($active_account_name) && isset($google_accounts[$active_account_name])) {
        return array(
            'name' => $active_account_name,
            'client_id' => $google_accounts[$active_account_name]['client_id'],
            'client_secret' => $google_accounts[$active_account_name]['client_secret']
        );
    }

    return false;
}

// Register plugin settings
function gsc_register_settings() {
    register_setting('gsc_settings', 'gsc_google_client_id');
    register_setting('gsc_settings', 'gsc_google_client_secret');
    register_setting('gsc_settings', 'gsc_thank_you_url');
}

// Enqueue admin styles
function gsc_enqueue_admin_styles() {
    wp_enqueue_style('gsc-admin-style', GSC_PLUGIN_URL . 'assets/css/admin-style.css', array(), GSC_VERSION);
}

function gsc_activate() {
    // Activation tasks go here
    gsc_register_post_type();
    gsc_add_subscriber_capability();
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'gsc_activate');

function gsc_deactivate() {
    // Deactivation tasks go here
    gsc_remove_subscriber_capability();
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'gsc_deactivate');

function gsc_add_landing_page_capabilities() {
    $roles = array('administrator', 'subscriber');
    
    foreach ($roles as $the_role) {
        $role = get_role($the_role);

        $role->add_cap('read');
        $role->add_cap('edit_gsc_landing_page');
        $role->add_cap('read_gsc_landing_page');
        $role->add_cap('delete_gsc_landing_page');
        $role->add_cap('edit_gsc_landing_pages');
        $role->add_cap('publish_gsc_landing_pages');

        if ($the_role === 'administrator') {
            $role->add_cap('edit_others_gsc_landing_pages');
            $role->add_cap('read_private_gsc_landing_pages');
        }
    }
}

register_activation_hook(__FILE__, 'gsc_add_landing_page_capabilities');

function gsc_add_capabilities() {
    $roles = array('administrator', 'subscriber');
    
    foreach ($roles as $the_role) {
        $role = get_role($the_role);

        $role->add_cap('read');
        $role->add_cap('edit_gsc_landing_page');
        $role->add_cap('read_gsc_landing_page');
        $role->add_cap('delete_gsc_landing_page');
        $role->add_cap('edit_gsc_landing_pages');
        $role->add_cap('publish_gsc_landing_pages');

        if ($the_role === 'administrator') {
            $role->add_cap('edit_others_gsc_landing_pages');
            $role->add_cap('read_private_gsc_landing_pages');
        }
    }
}

register_activation_hook(__FILE__, 'gsc_add_capabilities');

function gsc_register_post_type() {
    $landing_page = new GSC_Landing_Page();
    $landing_page->register_post_type();
}
add_action('init', 'gsc_register_post_type');

add_action('wp_ajax_gsc_save_email', 'gsc_save_email');
add_action('wp_ajax_nopriv_gsc_save_email', 'gsc_save_email');

function gsc_save_email() {
    if (isset($_POST['formData'])) {
        parse_str($_POST['formData'], $formData);
        $email = sanitize_email($formData['email']);
        $name = sanitize_text_field($formData['name']);

        $email_manager = new GSC_Email_Manager();
        $result = $email_manager->add_email($email, $name);

        if ($result) {
            wp_send_json_success('Email saved successfully');
        } else {
            wp_send_json_error('Failed to save email');
        }
    } else {
        wp_send_json_error('Invalid data');
    }
}

function gsc_add_subscriber_menu() {
    add_menu_page(
        __('GS Collect', 'google-sign-collect'),
        __('GS Collect', 'google-sign-collect'),
        'gsc_subscriber',
        'gsc-subscriber',
        'gsc_subscriber_page',
        'dashicons-email-alt',
        30
    );
}
add_action('admin_menu', 'gsc_add_subscriber_menu');

function gsc_add_subscriber_capability() {
    $role = get_role('subscriber');
    if ($role) {
        $role->add_cap('gsc_subscriber');
    }
}
register_activation_hook(__FILE__, 'gsc_add_subscriber_capability');

function gsc_remove_subscriber_capability() {
    $role = get_role('subscriber');
    if ($role) {
        $role->remove_cap('gsc_subscriber');
    }
}
register_deactivation_hook(__FILE__, 'gsc_remove_subscriber_capability');