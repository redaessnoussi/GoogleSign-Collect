<?php
/**
 * Class GSC_Landing_Page
 * Handles custom landing page creation and display
 */
class GSC_Landing_Page {
    private $templates = [
        'standalone-email-capture' => 'Standalone Email Capture Page'
    ];

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_filter('template_include', array($this, 'load_landing_page_template'));
        add_action('add_meta_boxes', array($this, 'add_custom_fields'));
        add_action('save_post', array($this, 'save_custom_fields'));
    }

    public function register_post_type() {
        $args = array(
            'public' => true,
            'label'  => 'Landing Pages',
            'supports' => array('title', 'editor', 'custom-fields'),
            'menu_icon' => 'dashicons-welcome-write-blog',
            'has_archive' => false,
            'rewrite' => array(
                'slug' => 'landing',
                'with_front' => false
            ),
            'publicly_queryable' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'show_in_menu' => true,
            'show_ui' => true,
            'capabilities' => array(
                'edit_post' => 'edit_gsc_landing_page',
                'read_post' => 'read_gsc_landing_page',
                'delete_post' => 'delete_gsc_landing_page',
                'edit_posts' => 'edit_gsc_landing_pages',
                'edit_others_posts' => 'edit_others_gsc_landing_pages',
                'publish_posts' => 'publish_gsc_landing_pages',
                'read_private_posts' => 'read_private_gsc_landing_pages',
            ),
        );
        register_post_type('gsc_landing_page', $args);
    }

    public function load_landing_page_template($template) {
        global $post;
        error_log('Template include filter called');
        error_log('Current template: ' . $template);
        error_log('Post type: ' . (isset($post) ? $post->post_type : 'Not set'));
        
        if (isset($post) && $post->post_type == 'gsc_landing_page') {
            error_log('This is a gsc_landing_page');
            $post_id = $post->ID;
            error_log('Post ID: ' . $post_id);
            
            $template_slug = get_post_meta($post_id, '_gsc_template_type', true);
            error_log('Template slug from post meta: ' . $template_slug);
            
            if (!$template_slug || !isset($this->templates[$template_slug])) {
                $template_slug = 'default';
            }
            error_log('Final template slug: ' . $template_slug);
            
            $new_template = GSC_PLUGIN_DIR . 'templates/' . $template_slug . '-template.php';
            error_log('Attempting to load template: ' . $new_template);
            
            if (file_exists($new_template)) {
                error_log('Custom template found and loaded');
                return $new_template;
            } else {
                error_log('Custom template not found');
            }
        } else {
            error_log('Not a gsc_landing_page');
        }
        
        error_log('Returning original template: ' . $template);
        return $template;
    }

    public function add_custom_fields() {
        add_meta_box(
            'gsc_landing_page_fields',
            'Landing Page Settings',
            array($this, 'render_custom_fields'),
            'gsc_landing_page',
            'normal',
            'high'
        );
    }

    public function render_custom_fields($post) {
        wp_nonce_field('gsc_landing_page_nonce', 'gsc_landing_page_nonce');
        $custom_css = get_post_meta($post->ID, '_gsc_custom_css', true);
        $template_type = get_post_meta($post->ID, '_gsc_template_type', true);
        ?>
        <p>
            <label for="gsc_template_type">Template Type:</label><br>
            <select id="gsc_template_type" name="gsc_template_type">
                <?php foreach ($this->templates as $slug => $name): ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($template_type, $slug); ?>>
                        <?php echo esc_html($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="gsc_custom_css">Custom CSS:</label><br>
            <textarea id="gsc_custom_css" name="gsc_custom_css" rows="5" style="width: 100%;"><?php echo esc_textarea($custom_css); ?></textarea>
        </p>
        <?php
    }

    public function save_custom_fields($post_id) {
        if (!isset($_POST['gsc_landing_page_nonce']) || !wp_verify_nonce($_POST['gsc_landing_page_nonce'], 'gsc_landing_page_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['gsc_custom_css'])) {
            update_post_meta($post_id, '_gsc_custom_css', wp_kses_post($_POST['gsc_custom_css']));
        }

        if (isset($_POST['gsc_template_type'])) {
            update_post_meta($post_id, '_gsc_template_type', sanitize_key($_POST['gsc_template_type']));
        }
    }
}