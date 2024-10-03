<?php
class GSC_Email_Sender {
    private $client_id;
    private $client_secret;

    public function __construct() {
        $this->client_id = get_option('gsc_google_client_id');
        $this->client_secret = get_option('gsc_google_client_secret');
    }

    public function send_email($sender_email, $recipient_email, $subject, $message) {
        $email_manager = new GSC_Email_Manager();
        $user_id = get_current_user_id(); // Get the current user ID
        $user_data = $email_manager->get_email($user_id, $sender_email);

        if (!$user_data || empty($user_data->access_token)) {
            error_log("No access token found for user: $sender_email");
            return false;
        }

        $access_token = $user_data->access_token;

        $email_content = $this->create_email_content($sender_email, $recipient_email, $subject, $message);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/gmail/v1/users/me/messages/send');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['raw' => $email_content]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            error_log("Email sent successfully from $sender_email to $recipient_email");
            return true;
        } else {
            error_log("Failed to send email. HTTP Code: $http_code, Response: $response");
            return false;
        }
    }

    private function create_email_content($sender, $recipient, $subject, $message) {
        $email  = "From: <{$sender}>\r\n";
        $email .= "To: <{$recipient}>\r\n";
        $email .= 'Subject: =?utf-8?B?' . base64_encode($subject) . "?=\r\n";
        $email .= "MIME-Version: 1.0\r\n";
        $email .= "Content-Type: text/html; charset=utf-8\r\n";
        $email .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
        $email .= base64_encode($message);

        return base64_encode($email);
    }
}