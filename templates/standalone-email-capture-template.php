<?php
/*
Template Name: GSC Google-only Email Capture
*/

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

$post_id = get_the_ID();
$custom_css = get_post_meta($post_id, '_gsc_custom_css', true);

// Check if user is logged in
$is_logged_in = isset($_SESSION['gsc_user_email']);
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
        <?php 
        if (!empty($custom_css)) {
            echo wp_kses_post($custom_css);
        }
        ?>
    </style>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body <?php body_class(); ?>>
    <div class="gsc-email-capture-container">
        <div class="gsc-email-capture-form">
            <?php if ($is_logged_in): ?>
                <h2 style="color: #fff;">Welcome, <?php echo esc_html($_SESSION['gsc_user_name']); ?>!</h2>
                <p>You are currently signed in with Google.</p>
                <button onclick="signOut()" class="gsc-signout-button">Sign Out</button>
            <?php else: ?>
                <h2 style="color: #fff;">Sign In / Sign Up with Google</h2>
                <p>Use your Google account to sign in or sign up.</p>
                
                <div style=" display: flex; flex-direction: row; justify-content: center; ">
                    <div id="g_id_onload"
                        data-client_id="<?php echo esc_attr(get_option('gsc_google_client_id')); ?>"
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
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function handleCredentialResponse(response) {
        console.log('Google response received:', response);
        // Send the ID token to your server
        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=gsc_verify_google_token&token=' + response.credential
        })
        .then(res => {
            console.log('Server response status:', res.status);
            return res.json();
        })
        .then(data => {
            console.log('Server response data:', data);
            if (data.success) {
                console.log('Authentication successful');
                console.log('Is new user:', data.data.is_new_user);
                console.log('Thank you URL:', data.data.thank_you_url);
                if (data.data.is_new_user) {
                    console.log('New user detected. Redirecting to:', data.data.thank_you_url);
                    window.location.href = data.data.thank_you_url;
                    alert('New user detected. Redirecting to:', data.data.thank_you_url);
                } else {
                    console.log('Existing user detected. Reloading page.');
                    window.location.reload();
                    alert('Existing user detected. Reloading page.');
                }
            } else {
                console.error('Authentication failed:', data.data.message);
                alert('Authentication failed. Please try again.');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
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
                // Sign out from Google
                google.accounts.id.disableAutoSelect();
                // Reload the page to show logged-out state
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