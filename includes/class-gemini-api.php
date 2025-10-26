<?php
/**
 * Gemini API Handler Class
 * Handles all communication with Google Gemini API
 */

if (!defined('ABSPATH')) exit;

class AIWA_Gemini_API {
    private $api_key;
    private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private $model = 'gemini-2.0-flash-exp';
    
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }
    
    /**
     * Make API request to Gemini
     */
    private function make_request($prompt, $temperature = 0.7) {
        $url = $this->api_url . $this->model . ':generateContent?key=' . $this->api_key;
        
        $data = array(
            'contents' => array(
                array(
                    'role' => 'user',
                    'parts' => array(array('text' => $prompt))
                )
            ),
            'generationConfig' => array(
                'temperature' => $temperature,
                'maxOutputTokens' => 2048
            )
        );
        
        $response = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if (isset($result['error'])) {
            return new WP_Error('api_error', $result['error']['message']);
        }
        
        if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return new WP_Error('api_error', 'Invalid API response');
        }
        
        return $result['candidates'][0]['content']['parts'][0]['text'];
    }
    
    /**
     * Improve text
     */
    public function improve_text($text) {
        $prompt = AIWA_Prompts::improve_text($text);
        return $this->make_request($prompt, 0.3);
    }
    
    /**
     * Check grammar
     */
    public function check_grammar($text) {
        $prompt = AIWA_Prompts::check_grammar($text);
        $result = $this->make_request($prompt, 0.3);
        
        if (is_wp_error($result)) return $result;
        
        // Extract JSON from response
        preg_match('/\{[\s\S]*\}/', $result, $matches);
        if ($matches) {
            $parsed = json_decode($matches[0], true);
            if ($parsed) return $parsed;
        }
        
        return array('language' => 'Unknown', 'errors' => array(), 'score' => 90);
    }
    
    /**
     * Rewrite content with specific tone
     */
    public function rewrite_content($text, $tone = 'professional') {
        $prompt = AIWA_Prompts::rewrite_content($text, $tone);
        return $this->make_request($prompt, 0.3);
    }
}
