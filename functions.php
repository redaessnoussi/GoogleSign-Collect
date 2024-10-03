<?php
function gsc_get_current_user_id() {
    if (is_user_logged_in()) {
        return get_current_user_id();
    } else {
        // For non-logged in users, you might want to use a session-based ID or return a default value
        if (!session_id()) {
            session_start();
        }
        if (!isset($_SESSION['gsc_temp_user_id'])) {
            $_SESSION['gsc_temp_user_id'] = uniqid('gsc_', true);
        }
        return $_SESSION['gsc_temp_user_id'];
    }
}