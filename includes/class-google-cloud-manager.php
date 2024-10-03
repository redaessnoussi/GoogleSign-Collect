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
            name varchar(100) NOT NULL,
            client_id text NOT NULL,
            client_secret text NOT NULL,
            is_active tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_account($name, $client_id, $client_secret) {
        global $wpdb;
        
        // If this is the first account, set it as active
        $is_active = $this->get_accounts_count() === 0 ? 1 : 0;

        return $wpdb->insert(
            $this->table_name,
            array(
                'name' => $name,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'is_active' => $is_active
            ),
            array('%s', '%s', '%s', '%d')
        );
    }

    public function remove_account($id) {
        global $wpdb;
        return $wpdb->delete($this->table_name, array('id' => $id), array('%d'));
    }

    public function set_active_account($id) {
        global $wpdb;
        $wpdb->update($this->table_name, array('is_active' => 0), array('is_active' => 1));
        return $wpdb->update($this->table_name, array('is_active' => 1), array('id' => $id));
    }

    public function get_active_account() {
        global $wpdb;
        return $wpdb->get_row("SELECT * FROM $this->table_name WHERE is_active = 1");
    }

    public function get_all_accounts() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM $this->table_name");
    }

    private function get_accounts_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM $this->table_name");
    }
}