<?php
class GSC_Google_Auth {
    private $client_id;
    private $client_secret;

    public function __construct() {
        $account_manager = new GSC_Account_Manager();
        $active_account = $account_manager->get_active_account();
    
        if ($active_account) {
            $this->client_id = $active_account->client_id;
            $this->client_secret = $active_account->client_secret;
        } else {
            error_log('No active Google Cloud account set');
        }
    
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
        error_log('Verify Google Token method called');
        $token = isset($_POST['token']) ? $_POST['token'] : '';
        $access_token = isset($_POST['access_token']) ? $_POST['access_token'] : '';
    
        if (empty($token)) {
            wp_send_json_error('No token provided');
            return;
        }
    
        // Verify ID token
        $payload = $this->verify_id_token($token);
        if (!$payload) {
            wp_send_json_error('Invalid token');
            return;
        }
    
        $user_email = $payload['email'];
        $user_name = isset($payload['name']) ? $payload['name'] : '';
    
        error_log('User email: ' . $user_email);
        error_log('User name: ' . $user_name);
    
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    
        $_SESSION['gsc_user_email'] = $user_email;
        $_SESSION['gsc_user_name'] = $user_name;
    
        // If no access token, prevent the user from being saved in the database
        if (empty($access_token)) {
            error_log("No access_token provided for email: $user_email. Skipping email storage.");
            wp_send_json_error('Access token missing. Authentication incomplete.');
            return;
        }
    
        // Store the email and access token
        $email_manager = new GSC_Email_Manager();
        $result = $email_manager->add_or_update_email(
            $user_email,
            $user_name,
            $access_token,
            '', // We don't have a refresh token in this flow
            date('Y-m-d H:i:s', time() + 3600) // Assume 1 hour expiry
        );
    
        if ($result === false) {
            error_log('Failed to save/update email with access token: ' . $user_email);
            wp_send_json_error('Failed to save permissions. Please try again.');
        }
    
        $thank_you_url = get_option('gsc_thank_you_url', home_url());
        error_log('Thank you URL: ' . $thank_you_url);
    
        $response_data = array(
            'message' => 'Authentication successful',
            'thank_you_url' => $thank_you_url
        );
    
        wp_send_json_success($response_data);
    }    

    private function verify_id_token($id_token) {
        $client = new WP_Http();
        $response = $client->get("https://oauth2.googleapis.com/tokeninfo?id_token={$id_token}");
    
        if (is_wp_error($response)) {
            error_log('Token verification failed: ' . print_r($response->get_error_message(), true));
            return false;
        }
    
        $payload = json_decode(wp_remote_retrieve_body($response), true);
    
        if (isset($payload['error']) || $payload['aud'] !== $this->client_id) {
            error_log('Invalid token: Audience mismatch or error');
            return false;
        }
    
        return $payload;
    }
    

    private function exchange_code_for_tokens($code) {
        $client = new WP_Http();
        $response = $client->post('https://oauth2.googleapis.com/token', array(
            'body' => array(
                'code' => $code,
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri' => home_url(),
                'grant_type' => 'authorization_code'
            )
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $tokens = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($tokens['error'])) {
            return false;
        }

        return $tokens;
    }
}