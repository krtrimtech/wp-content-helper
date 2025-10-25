?php
/**
 * Gemini API Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

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
                    'parts' => array(
                        array('text' => $prompt)
                    )
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
     * Check grammar and provide suggestions
     */
    public function check_grammar($text, $language = 'en') {
        $language_names = $this->get_language_names();
        $lang_name = isset($language_names[$language]) ? $language_names[$language] : 'English';
        
        $prompt = "You are a professional grammar checker and writing assistant. 

Analyze the following text in {$lang_name} and provide:
1. Grammar errors with corrections
2. Spelling mistakes
3. Style improvements
4. Clarity suggestions

Format your response as JSON with this structure:
{
  \"errors\": [
    {
      \"type\": \"grammar|spelling|style|clarity\",
      \"original\": \"the original text\",
      \"suggestion\": \"the corrected text\",
      \"explanation\": \"why this change improves the text\"
    }
  ],
  \"overall_score\": 85,
  \"summary\": \"brief summary of writing quality\"
}

Text to analyze:
{$text}";
        
        $result = $this->make_request($prompt, 0.3);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Extract JSON from response
        $json_match = preg_match('/\{[\s\S]*\}/', $result, $matches);
        if ($json_match) {
            $parsed = json_decode($matches[0], true);
            if ($parsed) {
                return $parsed;
            }
        }
        
        return array(
            'errors' => array(),
            'overall_score' => 90,
            'summary' => 'Analysis completed',
            'raw_response' => $result
        );
    }
    
    /**
     * Rewrite content in different tone
     */
    public function rewrite_content($text, $tone = 'professional', $language = 'en') {
        $language_names = $this->get_language_names();
        $lang_name = isset($language_names[$language]) ? $language_names[$language] : 'English';
        
        $tone_descriptions = array(
            'professional' => 'formal and professional',
            'casual' => 'casual and conversational',
            'friendly' => 'warm and friendly',
            'academic' => 'academic and scholarly',
            'creative' => 'creative and engaging',
            'simple' => 'simple and easy to understand',
            'persuasive' => 'persuasive and compelling'
        );
        
        $tone_desc = isset($tone_descriptions[$tone]) ? $tone_descriptions[$tone] : 'professional';
        
        $prompt = "Rewrite the following text in a {$tone_desc} tone while maintaining the core message and meaning. Write in {$lang_name}.

Original text:
{$text}

Rewritten version:";
        
        return $this->make_request($prompt, 0.7);
    }
    
    /**
     * Generate content based on prompt
     */
    public function generate_content($prompt, $context = '', $language = 'en') {
        $language_names = $this->get_language_names();
        $lang_name = isset($language_names[$language]) ? $language_names[$language] : 'English';
        
        $full_prompt = "You are a helpful writing assistant. Generate content in {$lang_name} based on the following:

User's request: {$prompt}";
        
        if (!empty($context)) {
            $full_prompt .= "\n\nContext/existing content:\n{$context}";
        }
        
        $full_prompt .= "\n\nGenerate helpful, accurate, and well-written content:";
        
        return $this->make_request($full_prompt, 0.8);
    }
    
    /**
     * Detect language of text
     */
    public function detect_language($text) {
        $prompt = "Detect the language of the following text and respond with ONLY the ISO 639-1 language code (e.g., 'en' for English, 'es' for Spanish, 'hi' for Hindi, 'fr' for French).

Text: {$text}

Language code:";
        
        $result = $this->make_request($prompt, 0.1);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $code = strtolower(trim($result));
        
        return array(
            'code' => $code,
            'name' => $this->get_language_name($code)
        );
    }
    
    /**
     * Get language name from code
     */
    private function get_language_name($code) {
        $names = $this->get_language_names();
        return isset($names[$code]) ? $names[$code] : 'Unknown';
    }
    
    /**
     * Get supported languages
     */
    private function get_language_names() {
        return array(
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ru' => 'Russian',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'zh' => 'Chinese',
            'ar' => 'Arabic',
            'hi' => 'Hindi',
            'bn' => 'Bengali',
            'pa' => 'Punjabi',
            'te' => 'Telugu',
            'mr' => 'Marathi',
            'ta' => 'Tamil',
            'ur' => 'Urdu',
            'gu' => 'Gujarati',
            'kn' => 'Kannada',
            'ml' => 'Malayalam',
            'nl' => 'Dutch',
            'pl' => 'Polish',
            'tr' => 'Turkish',
            'vi' => 'Vietnamese',
            'th' => 'Thai',
            'id' => 'Indonesian',
            'ms' => 'Malay',
            'uk' => 'Ukrainian',
            'ro' => 'Romanian'
        );
    }
}
                