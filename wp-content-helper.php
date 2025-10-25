<?php
/**
 * Plugin Name: WP Content Helper
 * Plugin URI: https://github.com/krtrimtech/wp-content-helper
 * Description: Grammarly-like AI writing assistant with Google Gemini API. Each user uses their own API key. Supports all post types and multiple languages.
 * Version: 1.0.0
 * Author: Krtrim (Shyanukant Rathi)
 * Author URI: https://shyanukant.github.io/
 * License: GPL v2 or later
 * Text Domain: wp-content-helper
 * GitHub Plugin URI: krtrimtech/wp-content-helper
 * GitHub Branch: main
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AIWA_VERSION', '1.0.0');
define('AIWA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AIWA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once AIWA_PLUGIN_DIR . 'includes/class-gemini-api.php';
require_once AIWA_PLUGIN_DIR . 'includes/class-user-settings.php';
require_once AIWA_PLUGIN_DIR . 'includes/class-gutenberg-integration.php';

/**
 * Initialize the plugin
 */
class AI_Writing_Assistant {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialize components
        AIWA_User_Settings::get_instance();
        AIWA_Gutenberg_Integration::get_instance();
        
        // Add AJAX endpoints
        add_action('wp_ajax_aiwa_check_grammar', array($this, 'ajax_check_grammar'));
        add_action('wp_ajax_aiwa_rewrite_content', array($this, 'ajax_rewrite_content'));
        add_action('wp_ajax_aiwa_generate_content', array($this, 'ajax_generate_content'));
        add_action('wp_ajax_aiwa_detect_language', array($this, 'ajax_detect_language'));
    }
    
    /**
     * AJAX: Check grammar and provide suggestions
     */
    public function ajax_check_grammar() {
        check_ajax_referer('aiwa_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_gemini_api_key', true);
        
        if (empty($api_key)) {
            wp_send_json_error(array(
                'message' => 'Please add your Gemini API key in your profile settings.'
            ));
        }
        
        $text = isset($_POST['text']) ? sanitize_textarea_field($_POST['text']) : '';
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'en';
        
        if (empty($text)) {
            wp_send_json_error(array('message' => 'No text provided'));
        }
        
        $gemini = new AIWA_Gemini_API($api_key);
        $result = $gemini->check_grammar($text, $language);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Rewrite content
     */
    public function ajax_rewrite_content() {
        check_ajax_referer('aiwa_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_gemini_api_key', true);
        
        if (empty($api_key)) {
            wp_send_json_error(array(
                'message' => 'Please add your Gemini API key in your profile settings.'
            ));
        }
        
        $text = isset($_POST['text']) ? sanitize_textarea_field($_POST['text']) : '';
        $tone = isset($_POST['tone']) ? sanitize_text_field($_POST['tone']) : 'professional';
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'en';
        
        $gemini = new AIWA_Gemini_API($api_key);
        $result = $gemini->rewrite_content($text, $tone, $language);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Generate content suggestions
     */
    public function ajax_generate_content() {
        check_ajax_referer('aiwa_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_gemini_api_key', true);
        
        if (empty($api_key)) {
            wp_send_json_error(array(
                'message' => 'Please add your Gemini API key in your profile settings.'
            ));
        }
        
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        $context = isset($_POST['context']) ? sanitize_textarea_field($_POST['context']) : '';
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'en';
        
        $gemini = new AIWA_Gemini_API($api_key);
        $result = $gemini->generate_content($prompt, $context, $language);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Detect language
     */
    public function ajax_detect_language() {
        check_ajax_referer('aiwa_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_gemini_api_key', true);
        
        if (empty($api_key)) {
            wp_send_json_error(array(
                'message' => 'Please add your Gemini API key in your profile settings.'
            ));
        }
        
        $text = isset($_POST['text']) ? sanitize_textarea_field($_POST['text']) : '';
        
        $gemini = new AIWA_Gemini_API($api_key);
        $result = $gemini->detect_language($text);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
}

// Initialize the plugin
add_action('plugins_loaded', array('AI_Writing_Assistant', 'get_instance'));