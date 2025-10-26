<?php
/**
 * AJAX Handlers Class
 * Handles all AJAX requests from the frontend
 */

if (!defined('ABSPATH')) exit;

class AIWA_Ajax {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_aiwa_improve', array($this, 'improve'));
        add_action('wp_ajax_aiwa_grammar', array($this, 'grammar'));
        add_action('wp_ajax_aiwa_rewrite', array($this, 'rewrite'));
    }
    
    /**
     * Improve text endpoint
     */
    public function improve() {
        check_ajax_referer('aiwa', 'nonce');
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_api_key', true);
        
        if (!$api_key) {
            wp_send_json_error('No API key configured');
        }
        
        $text = isset($_POST['text']) ? sanitize_textarea_field($_POST['text']) : '';
        if (!$text) {
            wp_send_json_error('No text provided');
        }
        
        $gemini = new AIWA_Gemini_API($api_key);
        $result = $gemini->improve_text($text);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Grammar check endpoint
     */
    public function grammar() {
        check_ajax_referer('aiwa', 'nonce');
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_api_key', true);
        
        if (!$api_key) {
            wp_send_json_error('No API key configured');
        }
        
        $text = isset($_POST['text']) ? sanitize_textarea_field($_POST['text']) : '';
        if (!$text) {
            wp_send_json_error('No text provided');
        }
        
        $gemini = new AIWA_Gemini_API($api_key);
        $result = $gemini->check_grammar($text);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Rewrite content endpoint
     */
    public function rewrite() {
        check_ajax_referer('aiwa', 'nonce');
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_api_key', true);
        
        if (!$api_key) {
            wp_send_json_error('No API key configured');
        }
        
        $text = isset($_POST['text']) ? sanitize_textarea_field($_POST['text']) : '';
        $tone = isset($_POST['tone']) ? sanitize_text_field($_POST['tone']) : 'professional';
        
        if (!$text) {
            wp_send_json_error('No text provided');
        }
        
        $gemini = new AIWA_Gemini_API($api_key);
        $result = $gemini->rewrite_content($text, $tone);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
}
