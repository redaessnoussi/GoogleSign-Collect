<?php
class GSC_Google_Auth {
    private $client_id;

    public function __construct() {
        $this->client_id = get_option('gsc_google_client_id');
        add_action('wp_ajax_gsc_verify_google_token', array($this, 'verify_google_token'));
        add_action('wp_ajax_nopriv_gsc_verify_google_token', array($this, 'verify_google_token'));
    }

    public function verify_google_token() {
        $token = $_POST['token'];

        $client = new WP_Http();
        $response = $client->get("https://oauth2.googleapis.com/tokeninfo?id_token={$token}");

        if (is_wp_error($response)) {
            wp_send_json_error('Failed to verify token');
            return;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            wp_send_json_error('Invalid token');
            return;
        }

        if ($body['aud'] !== $this->client_id) {
            wp_send_json_error('Token client ID does not match');
            return;
        }

        // Token is valid, you can now use the user information
        $user_email = $body['email'];
        $user_name = $body['name'];

        // Store user info in session
        $_SESSION['gsc_user_email'] = $user_email;
        $_SESSION['gsc_user_name'] = $user_name;

        // Save email to database
        $email_manager = new GSC_Email_Manager();
        $result = $email_manager->add_email($user_email, $user_name);

        if (!$result) {
            error_log('Failed to save email: ' . $user_email);
        }

        wp_send_json_success('Authentication successful');
    }
}