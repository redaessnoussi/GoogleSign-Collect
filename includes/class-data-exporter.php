<?php
class GSC_Data_Exporter {
    private $account_manager;
    private $email_manager;

    public function __construct() {
        $this->account_manager = new GSC_Account_Manager();
        $this->email_manager = new GSC_Email_Manager();
    }

    public function export_subscriber_data($user_id) {
        $accounts = $this->account_manager->get_all_accounts($user_id);
        $zip = new ZipArchive();
        $filename = "subscriber_data_" . $user_id . "_" . date("Y-m-d_H-i-s") . ".zip";
        $temp_file = tempnam(sys_get_temp_dir(), "GSC");
    
        if ($zip->open($temp_file, ZipArchive::CREATE) !== TRUE) {
            return false;
        }
    
        foreach ($accounts as $account) {
            $emails = $this->email_manager->get_emails_by_account($user_id, $account->id);
            
            // Debug: Log the number of emails found for this account
            error_log("Account ID: {$account->id}, Emails found: " . count($emails));
            
            $csv_content = $this->generate_csv_content($emails);
            $zip->addFromString($account->name . ".csv", $csv_content);
        }
    
        $zip->close();
    
        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=" . $filename);
        header("Content-Length: " . filesize($temp_file));
        readfile($temp_file);
        unlink($temp_file);
        exit;
    }

    private function generate_csv_content($emails) {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, array('Email', 'Name', 'Access Token', 'Refresh Token', 'Token Expiry', 'Created At', 'Updated At'));

        foreach ($emails as $email) {
            fputcsv($output, array(
                $email->email,
                $email->name,
                $email->access_token,
                $email->refresh_token,
                $email->token_expiry,
                $email->created_at,
                $email->updated_at
            ));
        }

        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);

        return $csv_content;
    }
}