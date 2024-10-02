<?php

class LandingPage {

    public function __construct() {
        add_shortcode('google_sign_landing', array($this, 'render_landing_page'));
    }

    // Render custom landing page
    public function render_landing_page() {
        $custom_html = get_option('google_sign_landing_html');
        $custom_css = get_option('google_sign_landing_css');
        return '<style>' . $custom_css . '</style>' . $custom_html;
    }

    // Save custom landing page content
    public function save_custom_landing_page($html, $css) {
        update_option('google_sign_landing_html', wp_kses_post($html));
        update_option('google_sign_landing_css', sanitize_text_field($css));
    }

}
