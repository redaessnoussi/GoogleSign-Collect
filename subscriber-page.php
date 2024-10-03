<?php
function gsc_subscriber_page() {
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_die(__('You must be logged in to access this page.'));
    }

    $account_manager = new GSC_Account_Manager();
    $email_manager = new GSC_Email_Manager();

    // Handle form submissions
    if (isset($_POST['gsc_add_google_account'])) {
        gsc_add_google_account($account_manager, $user_id);
    }
    if (isset($_POST['gsc_remove_google_account'])) {
        gsc_remove_google_account($account_manager, $user_id);
    }
    if (isset($_POST['gsc_set_active_account'])) {
        gsc_set_active_account($account_manager, $user_id);
    }

    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Google Sign-In & Collect Dashboard', 'google-sign-collect'); ?></h1>
        <nav class="nav-tab-wrapper">
            <a href="?page=gsc-subscriber&tab=accounts" class="nav-tab <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'accounts') ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Google Cloud Accounts', 'google-sign-collect'); ?></a>
            <a href="?page=gsc-subscriber&tab=emails" class="nav-tab <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'emails') ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Collected Emails', 'google-sign-collect'); ?></a>
        </nav>
        
        <div class="tab-content">
            <?php
            $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'accounts';
            
            switch ($active_tab) {
                case 'accounts':
                    gsc_subscriber_accounts_tab($user_id, $account_manager);
                    break;
                case 'emails':
                    gsc_subscriber_emails_tab($user_id, $email_manager);
                    break;
            }
            ?>
        </div>
    </div>
    <?php
}

function gsc_remove_google_account($account_manager, $user_id) {
    $account_id = intval($_POST['account_id']);
    $result = $account_manager->remove_account($account_id, $user_id);

    if ($result) {
        add_settings_error('gsc_messages', 'gsc_message', __('Google Cloud account removed successfully.', 'google-sign-collect'), 'updated');
    } else {
        add_settings_error('gsc_messages', 'gsc_message', __('Failed to remove Google Cloud account.', 'google-sign-collect'), 'error');
    }
}

function gsc_set_active_account($account_manager, $user_id) {
    $account_id = intval($_POST['account_id']);
    $result = $account_manager->set_active_account($account_id, $user_id);

    if ($result) {
        add_settings_error('gsc_messages', 'gsc_message', __('Active Google Cloud account updated successfully.', 'google-sign-collect'), 'updated');
    } else {
        add_settings_error('gsc_messages', 'gsc_message', __('Failed to update active Google Cloud account.', 'google-sign-collect'), 'error');
    }
}

function gsc_add_google_account($account_manager, $user_id) {
    $account_name = sanitize_text_field($_POST['account_name']);
    $client_id = sanitize_text_field($_POST['client_id']);
    $client_secret = sanitize_text_field($_POST['client_secret']);

    $result = $account_manager->add_account($user_id, $account_name, $client_id, $client_secret);

    if ($result) {
        add_settings_error('gsc_messages', 'gsc_message', __('Google Cloud account added successfully.', 'google-sign-collect'), 'updated');
    } else {
        add_settings_error('gsc_messages', 'gsc_message', __('Failed to add Google Cloud account.', 'google-sign-collect'), 'error');
    }
}


function gsc_subscriber_accounts_tab($user_id, $account_manager) {
    $accounts = $account_manager->get_all_accounts($user_id);
    ?>
    <h2><?php echo esc_html__('Your Google Cloud Accounts', 'google-sign-collect'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Account Name', 'google-sign-collect'); ?></th>
                <th><?php echo esc_html__('Client ID', 'google-sign-collect'); ?></th>
                <th><?php echo esc_html__('Actions', 'google-sign-collect'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($accounts)): ?>
                <tr>
                    <td colspan="3"><?php echo esc_html__('No Google Cloud accounts found.', 'google-sign-collect'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($accounts as $account): ?>
                    <tr>
                        <td><?php echo esc_html($account->name); ?></td>
                        <td><?php echo esc_html($account->client_id); ?></td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="account_id" value="<?php echo esc_attr($account->id); ?>">
                                <?php if ($account->is_active == 0): ?>
                                    <input type="submit" name="gsc_set_active_account" value="<?php echo esc_attr__('Set as Active', 'google-sign-collect'); ?>" class="button button-primary">
                                <?php else: ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span> <?php echo esc_html__('Active', 'google-sign-collect'); ?>
                                <?php endif; ?>
                                <input type="submit" name="gsc_remove_google_account" value="<?php echo esc_attr__('Remove', 'google-sign-collect'); ?>" class="button button-secondary" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to remove this account?', 'google-sign-collect')); ?>');">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <h3><?php echo esc_html__('Add New Google Cloud Account', 'google-sign-collect'); ?></h3>
    <form method="post">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="account_name"><?php echo esc_html__('Account Name', 'google-sign-collect'); ?></label></th>
                <td><input type="text" id="account_name" name="account_name" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="client_id"><?php echo esc_html__('Google Client ID', 'google-sign-collect'); ?></label></th>
                <td><input type="text" id="client_id" name="client_id" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="client_secret"><?php echo esc_html__('Google Client Secret', 'google-sign-collect'); ?></label></th>
                <td><input type="password" id="client_secret" name="client_secret" required></td>
            </tr>
        </table>
        <?php submit_button(__('Add Google Account', 'google-sign-collect'), 'primary', 'gsc_add_google_account'); ?>
    </form>
    <?php
}

function gsc_subscriber_emails_tab($user_id, $email_manager) {
    $emails = $email_manager->get_emails($user_id, 100, 0);
    ?>
    <h2><?php echo esc_html__('Your Collected Emails', 'google-sign-collect'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html__('Email', 'google-sign-collect'); ?></th>
                <th><?php echo esc_html__('Name', 'google-sign-collect'); ?></th>
                <th><?php echo esc_html__('Date', 'google-sign-collect'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($emails as $email): ?>
                <tr>
                    <td><?php echo esc_html($email->email); ?></td>
                    <td><?php echo esc_html($email->name); ?></td>
                    <td><?php echo esc_html($email->created_at); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php
}

// Add these to your main plugin file
// add_action('admin_post_gsc_export_subscriber_csv', 'gsc_export_subscriber_csv');

// function gsc_export_subscriber_csv() {
//     $user_id = get_current_user_id();
//     if (!$user_id) {
//         wp_die(__('You must be logged in to access this page.'));
//     }

//     $email_manager = new GSC_Email_Manager();
//     $email_manager->export_csv($user_id);
// }