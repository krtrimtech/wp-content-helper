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
    
    // Clean unwanted prefixes and labels
    $result = $this->clean_ai_output($result);
    
    wp_send_json_success($result);
}

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
    
    // Clean unwanted prefixes and labels
    $result = $this->clean_ai_output($result);
    
    wp_send_json_success($result);
}

/**
 * Clean AI output - remove unwanted prefixes and labels
 */
private function clean_ai_output($text) {
    // Remove common prefixes
    $patterns = array(
        '/^\*\*English Rewritten Text:\*\*\s*/i',
        '/^\*\*Rewritten Text:\*\*\s*/i',
        '/^\*\*Improved Text:\*\*\s*/i',
        '/^Here is the rewritten text:\s*/i',
        '/^Here is the improved text:\s*/i',
        '/^Rewritten:\s*/i',
        '/^Improved:\s*/i',
        '/^\*\*Hindi Text:\*\*\s*/i',
        '/^\*\*[A-Za-z\s]+:\*\*\s*/i', // Any **Label:**
        '/^[A-Za-z\s]+:\s*(?=\S)/', // Label: (without **)
    );
    
    foreach ($patterns as $pattern) {
        $text = preg_replace($pattern, '', $text);
    }
    
    // Remove leading/trailing whitespace
    $text = trim($text);
    
    return $text;
}


    
}
