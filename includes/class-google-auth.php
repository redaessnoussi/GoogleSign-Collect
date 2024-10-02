<?php
class GSC_Google_Auth {
    private $client_id;

    public function __construct() {
        $this->client_id = get_option('gsc_google_client_id');
        add_action('wp_ajax_gsc_verify_google_token', array($this, 'verify_google_token'));
        add_action('wp_ajax_nopriv_gsc_verify_google_token', array($this, 'verify_google_token'));
        add_action('wp_ajax_gsc_google_signout', array($this, 'handle_signout'));
        add_action('wp_ajax_nopriv_gsc_google_signout', array($this, 'handle_signout'));
    }

    public function handle_signout() {
        // Clear the session
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['gsc_user_email']);
        unset($_SESSION['gsc_user_name']);
        session_destroy();

        wp_send_json_success('Signed out successfully');
    }

    public function verify_google_token() {
        $token = $_POST['token'];
    
        $client = new WP_Http();
        $response = $client->get("https://oauth2.googleapis.com/tokeninfo?id_token={$token}");
    
        if (is_wp_error($response)) {
            error_log('Token verification failed: ' . $response->get_error_message());
            wp_send_json_error('Failed to verify token');
            return;
        }
    
        $body = json_decode(wp_remote_retrieve_body($response), true);
    
        if (isset($body['error'])) {
            error_log('Invalid token: ' . $body['error']);
            wp_send_json_error('Invalid token');
            return;
        }
    
        if ($body['aud'] !== $this->client_id) {
            error_log('Token client ID mismatch');
            wp_send_json_error('Token client ID does not match');
            return;
        }
    
        $user_email = $body['email'];
        $user_name = $body['name'];
    
        $_SESSION['gsc_user_email'] = $user_email;
        $_SESSION['gsc_user_name'] = $user_name;
    
        $email_manager = new GSC_Email_Manager();
        $existing_user = $email_manager->get_email($user_email);
    
        $is_new_user = !$existing_user;
    
        error_log('User status - Email: ' . $user_email . ', Is new: ' . ($is_new_user ? 'Yes' : 'No'));
    
        $result = $email_manager->add_email($user_email, $user_name);
    
        if ($result === false) {
            error_log('Failed to save/update email: ' . $user_email);
        }
    
        $thank_you_url = get_option('gsc_thank_you_url', home_url());
        error_log('Thank you URL: ' . $thank_you_url);
    
        wp_send_json_success(array(
            'message' => 'Authentication successful',
            'is_new_user' => $is_new_user,
            'thank_you_url' => $thank_you_url
        ));
    }
}