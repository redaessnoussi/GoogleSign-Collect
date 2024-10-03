<?php
/*
Template Name: GSC Google-only Email Capture
*/

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Include the functions file
require_once plugin_dir_path(__FILE__) . '../functions.php';

$post_id = get_the_ID();
$custom_css = get_post_meta($post_id, '_gsc_custom_css', true);

// Instantiate the Account Manager
if (class_exists('GSC_Account_Manager')) {
    $account_manager = new GSC_Account_Manager();
} else {
    // Handle the error if the class doesn't exist
    echo '<p>Error: Account Manager class not found.</p>';
    exit;
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['gsc_user_email']);

// Get active account
$user_id = gsc_get_current_user_id();
$active_account = $account_manager->get_active_account($user_id);

// Check if active account exists
if ($active_account) {
    $client_id = $active_account->client_id;
} else {
    $client_id = '';
    // Optionally, display an error message or redirect to admin
    echo '<p>No active Google Cloud account set. Please contact the administrator.</p>';
    exit;
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php wp_title('|', true, 'right'); ?></title>
    <?php wp_head(); ?>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: Arial, sans-serif;
        }
        .gsc-email-capture-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(to bottom, #4a0e8f, #130428);
        }
        .gsc-email-capture-form {
            background-color: #1c1c28;
            border-radius: 10px;
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            color: #ffffff;
            text-align: center;
        }
        .gsc-email-capture-form h2 {
            margin-bottom: 1rem;
        }
        .gsc-email-capture-form p {
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #a0a0a0;
        }
        .gsc-google-signin-button {
            width: 100% !important;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .gsc-signout-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4285f4;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .gsc-signout-button:hover {
            background-color: #357ae8;
        }
        .gsc-custom-content {
            text-align: center;
            padding: 24px;
            background-color: #fff;
        }
        .gsc-custom-content img {
            max-width: 100%;
            height: auto;
        }
        <?php 
        if (!empty($custom_css)) {
            echo wp_kses_post($custom_css);
        }
        ?>
    </style>
    <!-- Load the Google Identity Services library -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body <?php body_class(); ?>>
    <div class="gsc-email-capture-container">
        <div class="gsc-custom-content">
            <?php
            // Display the custom content
            while (have_posts()) :
                the_post();
                the_content();
            endwhile;
            ?>
        </div>
        <div class="gsc-email-capture-form">
            <?php if ($is_logged_in): ?>
                <h2 style="color: #fff;">Welcome, <?php echo esc_html($_SESSION['gsc_user_name']); ?>!</h2>
                <p>You are currently signed in with Google.</p>
                <button onclick="signOut()" class="gsc-signout-button">Sign Out</button>
            <?php else: ?>
                <h2 style="color: #fff;">Sign In / Sign Up with Google</h2>
                <p>Use your Google account to sign in or sign up.</p>
                
                <div id="g_id_onload"
                     data-client_id="<?php echo esc_attr($client_id); ?>"
                     data-callback="handleCredentialResponse">
                </div>
                <div class="g_id_signin"
                     data-type="standard"
                     data-size="large"
                     data-theme="outline"
                     data-text="sign_in_with"
                     data-shape="rectangular"
                     data-logo_alignment="left">
                </div>
                <div id="manual-auth" style="display: none; margin-top: 20px;">
                    <p>If the popup is blocked, please click the button below to grant permissions:</p>
                    <button onclick="requestAdditionalScopes()" class="gsc-submit-button">Grant Email Permissions</button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
let googleUser = null;

function handleCredentialResponse(response) {
    console.log('Credential response:', response);
    googleUser = response;
    document.getElementById('manual-auth').style.display = 'block';
    sendTokenToServer(response.credential);
}

function sendTokenToServer(idToken) {
    let formData = new FormData();
    formData.append('action', 'gsc_verify_google_token');
    formData.append('token', idToken);
    formData.append('user_id', '<?php echo esc_js(gsc_get_current_user_id()); ?>');

    console.log('Sending token to server:', idToken.substring(0, 20) + '...');

    fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        console.log("Server response:", data);
        if (data.success) {
            if (data.data.is_new_user) {
                requestAdditionalScopes();
            } else {
                window.location.reload();
            }
        } else {
            console.error('Error from server:', data.data);
            alert('Authentication failed. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

function requestAdditionalScopes() {
    const client = google.accounts.oauth2.initTokenClient({
        client_id: '<?php echo esc_js($client_id); ?>',
        scope: 'https://www.googleapis.com/auth/gmail.send',
        callback: (tokenResponse) => {
            if (tokenResponse && tokenResponse.access_token) {
                sendAccessTokenToServer(tokenResponse.access_token);
            } else {
                console.error('Failed to obtain access token');
                alert('Failed to obtain necessary permissions. Please try again.');
            }
        },
    });
    client.requestAccessToken({ prompt: 'consent' });
}

function sendAccessTokenToServer(accessToken) {
    let formData = new FormData();
    formData.append('action', 'gsc_verify_google_token');
    formData.append('token', googleUser.credential);
    formData.append('access_token', accessToken);
    formData.append('user_id', '<?php echo esc_js(gsc_get_current_user_id()); ?>');

    console.log('action', 'gsc_verify_google_token');
    console.log('token', googleUser.credential);
    console.log('access_token', accessToken);
    console.log('user_id', '<?php echo esc_js(gsc_get_current_user_id()); ?>');

    fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.data.thank_you_url;
        } else {
            alert('Failed to save permissions. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

function signOut() {
    fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=gsc_google_signout'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            google.accounts.id.disableAutoSelect();
            window.location.reload();
        } else {
            alert('Sign out failed. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during sign out. Please try again.');
    });
}
</script>

<?php wp_footer(); ?>
</body>
</html>
