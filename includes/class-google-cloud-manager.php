<?php
class GSC_Account_Manager {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'gsc_google_accounts';
        $this->create_table();
    }

    public function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            name varchar(100) NOT NULL,
            client_id text NOT NULL,
            client_secret text NOT NULL,
            is_active tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_account($user_id, $name, $client_id, $client_secret) {
        global $wpdb;
        
        // If this is the first account for this user, set it as active
        $is_active = $this->get_accounts_count($user_id) === 0 ? 1 : 0;

        return $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'name' => $name,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'is_active' => $is_active
            ),
            array('%d', '%s', '%s', '%s', '%d')
        );
    }

    public function remove_account($id, $user_id) {
        global $wpdb;
        return $wpdb->delete($this->table_name, array('id' => $id, 'user_id' => $user_id), array('%d', '%d'));
    }

    public function set_active_account($id, $user_id) {
        global $wpdb;
        $wpdb->update($this->table_name, array('is_active' => 0), array('user_id' => $user_id));
        return $wpdb->update($this->table_name, array('is_active' => 1), array('id' => $id, 'user_id' => $user_id));
    }

    public function get_active_account($user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE user_id = %d AND is_active = 1", $user_id));
    }

    public function get_all_accounts($user_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $this->table_name WHERE user_id = %d", $user_id));
    }

    private function get_accounts_count($user_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $this->table_name WHERE user_id = %d", $user_id));
    }

    // New method for admin to get all accounts
    public function get_all_accounts_admin() {
        global $wpdb;
        return $wpdb->get_results("SELECT a.*, u.user_login, u.user_email FROM $this->table_name a JOIN {$wpdb->users} u ON a.user_id = u.ID");
    }
}