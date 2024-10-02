<?php

class GoogleAuth {

    private $client_id = 'YOUR_GOOGLE_CLIENT_ID';
    private $client_secret = 'YOUR_GOOGLE_CLIENT_SECRET';
    private $redirect_uri;
    
    public function __construct() {
        $this->redirect_uri = admin_url('admin.php?page=google-sign-collect');
    }

    // Initiates Google login process
    public function initiate_google_login() {
        $google_login_url = "https://accounts.google.com/o/oauth2/auth?" . http_build_query([
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'scope' => 'email profile',
        ]);

        wp_redirect($google_login_url);
        exit;
    }

    // Handle Google response and get user email
    public function handle_google_response() {
        if (isset($_GET['code'])) {
            $code = sanitize_text_field($_GET['code']);
            // Exchange code for access token
            $token_url = "https://accounts.google.com/o/oauth2/token";
            $response = wp_remote_post($token_url, array(
                'body' => array(
                    'client_id' => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'redirect_uri' => $this->redirect_uri,
                    'code' => $code,
                    'grant_type' => 'authorization_code',
                )
            ));

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);

            if (isset($data->access_token)) {
                $user_info = $this->get_google_user_info($data->access_token);
                // Store email
                $this->store_user_email($user_info->email);
            }
        }
    }

    // Get user info from Google
    public function get_google_user_info($token) {
        $user_info_url = "https://www.googleapis.com/oauth2/v1/userinfo?access_token=$token";
        $response = wp_remote_get($user_info_url);
        return json_decode(wp_remote_retrieve_body($response));
    }

    // Store user email in database
    public function store_user_email($email) {
        global $wpdb;
        $table = $wpdb->prefix . 'google_sign_collect_emails';
        $wpdb->insert($table, array('email' => sanitize_email($email)));
    }
}
