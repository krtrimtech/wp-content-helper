?php
/**
 * Gutenberg Editor Integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIWA_Gutenberg_Integration {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
    }
    
    /**
     * Enqueue editor scripts and styles
     */
    public function enqueue_editor_assets() {
        // Check if user has API key
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_gemini_api_key', true);
        $has_api_key = !empty($api_key);
        $preferred_language = get_user_meta($user_id, 'aiwa_preferred_language', true);
        if (empty($preferred_language)) {
            $preferred_language = 'en';
        }
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'aiwa-editor-sidebar',
            AIWA_PLUGIN_URL . 'assets/js/editor-sidebar.js',
            array('wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-compose'),
            AIWA_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('aiwa-editor-sidebar', 'aiwaData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiwa_nonce'),
            'hasApiKey' => $has_api_key,
            'preferredLanguage' => $preferred_language,
            'profileUrl' => get_edit_profile_url($user_id)
        ));
        
        // Enqueue CSS
        wp_enqueue_style(
            'aiwa-editor-styles',
            AIWA_PLUGIN_URL . 'assets/css/editor-styles.css',
            array('wp-edit-post'),
            AIWA_VERSION
        );
    }
}