?php
/**
 * User Settings - Manage per-user API keys
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIWA_User_Settings {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add API key field to user profile
        add_action('show_user_profile', array($this, 'add_api_key_field'));
        add_action('edit_user_profile', array($this, 'add_api_key_field'));
        
        // Save API key
        add_action('personal_options_update', array($this, 'save_api_key'));
        add_action('edit_user_profile_update', array($this, 'save_api_key'));
        
        // Add admin notice if API key is not set
        add_action('admin_notices', array($this, 'api_key_notice'));
    }
    
    /**
     * Add API key field to user profile
     */
    public function add_api_key_field($user) {
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        $api_key = get_user_meta($user->ID, 'aiwa_gemini_api_key', true);
        $masked_key = $this->mask_api_key($api_key);
        ?>
        &lt;h2&gt;AI Writing Assistant Settings&lt;/h2&gt;
        &lt;table class="form-table"&gt;
            &lt;tr&gt;
                &lt;th&gt;
                    &lt;label for="aiwa_gemini_api_key"&gt;Google Gemini API Key&lt;/label&gt;
                &lt;/th&gt;
                &lt;td&gt;
                    &lt;input type="text" 
                           name="aiwa_gemini_api_key" 
                           id="aiwa_gemini_api_key" 
                           value="&lt;?php echo esc_attr($api_key); ?&gt;" 
                           class="regular-text" 
                           placeholder="Enter your Gemini API key"&gt;
                    &lt;p class="description"&gt;
                        Enter your personal Google Gemini API key to use AI writing features.
                        &lt;br&gt;Get your free API key from: 
                        &lt;a href="https://aistudio.google.com/app/apikey" target="_blank"&gt;Google AI Studio&lt;/a&gt;
                        &lt;br&gt;&lt;strong&gt;Note:&lt;/strong&gt; Your API key is private and only you can use it.
                    &lt;/p&gt;
                    &lt;?php if (!empty($api_key)): ?&gt;
                        &lt;p style="color: #16a34a; font-weight: 600;"&gt;
                            ✓ API Key configured: &lt;code&gt;&lt;?php echo esc_html($masked_key); ?&gt;&lt;/code&gt;
                        &lt;/p&gt;
                    &lt;?php endif; ?&gt;
                &lt;/td&gt;
            &lt;/tr&gt;
            &lt;tr&gt;
                &lt;th&gt;
                    &lt;label for="aiwa_preferred_language"&gt;Preferred Language&lt;/label&gt;
                &lt;/th&gt;
                &lt;td&gt;
                    &lt;select name="aiwa_preferred_language" id="aiwa_preferred_language"&gt;
                        &lt;?php
                        $preferred_lang = get_user_meta($user->ID, 'aiwa_preferred_language', true);
                        if (empty($preferred_lang)) {
                            $preferred_lang = 'en';
                        }
                        
                        $languages = array(
                            'en' => 'English',
                            'es' => 'Spanish (Español)',
                            'fr' => 'French (Français)',
                            'de' => 'German (Deutsch)',
                            'it' => 'Italian (Italiano)',
                            'pt' => 'Portuguese (Português)',
                            'hi' => 'Hindi (हिंदी)',
                            'bn' => 'Bengali (বাংলা)',
                            'pa' => 'Punjabi (ਪੰਜਾਬੀ)',
                            'te' => 'Telugu (తెలుగు)',
                            'mr' => 'Marathi (मराठी)',
                            'ta' => 'Tamil (தமிழ்)',
                            'ur' => 'Urdu (اردو)',
                            'gu' => 'Gujarati (ગુજરાતી)',
                            'ar' => 'Arabic (العربية)',
                            'ja' => 'Japanese (日本語)',
                            'ko' => 'Korean (한국어)',
                            'zh' => 'Chinese (中文)',
                            'ru' => 'Russian (Русский)'
                        );
                        
                        foreach ($languages as $code => $name) {
                            $selected = ($code === $preferred_lang) ? 'selected' : '';
                            echo "&lt;option value=\"" . esc_attr($code) . "\" {$selected}&gt;" . esc_html($name) . "&lt;/option&gt;";
                        }
                        ?&gt;
                    &lt;/select&gt;
                    &lt;p class="description"&gt;
                        Default language for AI writing suggestions
                    &lt;/p&gt;
                &lt;/td&gt;
            &lt;/tr&gt;
        &lt;/table&gt;
        &lt;?php
    }
    
    /**
     * Save API key
     */
    public function save_api_key($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        if (isset($_POST['aiwa_gemini_api_key'])) {
            $api_key = sanitize_text_field($_POST['aiwa_gemini_api_key']);
            update_user_meta($user_id, 'aiwa_gemini_api_key', $api_key);
        }
        
        if (isset($_POST['aiwa_preferred_language'])) {
            $language = sanitize_text_field($_POST['aiwa_preferred_language']);
            update_user_meta($user_id, 'aiwa_preferred_language', $language);
        }
    }
    
    /**
     * Show admin notice if API key not set
     */
    public function api_key_notice() {
        $screen = get_current_screen();
        
        if (!in_array($screen->id, array('post', 'page')) && !in_array($screen->post_type, get_post_types())) {
            return;
        }
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_gemini_api_key', true);
        
        if (empty($api_key)) {
            $profile_url = get_edit_profile_url($user_id);
            ?>
            &lt;div class="notice notice-warning is-dismissible"&gt;
                &lt;p&gt;
                    &lt;strong&gt;AI Writing Assistant:&lt;/strong&gt; 
                    To use AI writing features, please add your Gemini API key in your 
                    &lt;a href="&lt;?php echo esc_url($profile_url); ?&gt;"&gt;profile settings&lt;/a&gt;.
                &lt;/p&gt;
            &lt;/div&gt;
            &lt;?php
        }
    }
    
    /**
     * Mask API key for display
     */
    private function mask_api_key($key) {
        if (empty($key)) {
            return '';
        }
        
        $length = strlen($key);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }
        
        return substr($key, 0, 4) . str_repeat('*', $length - 8) . substr($key, -4);
    }
}