<?php
/**
 * Plugin Name: WP Content Helper
 * Plugin URI: https://github.com/krtrimtech/wp-content-helper
 * Description: Grammarly-like AI writing assistant with Google Gemini API. Each user uses their own API key.
 * Version: 1.0.0
 * Author: Krtrim (Shyanukant Rathi)
 * Author URI: https://shyanukant.github.io/
 * License: GPL v2 or later
 * Text Domain: wp-content-helper
 */

if (!defined('ABSPATH')) exit;

// ========================================
// GEMINI API CLASS
// ========================================
class AIWA_Gemini_API {
    private $api_key;
    private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private $model = 'gemini-2.0-flash-exp';
    
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }
    
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
    
    public function check_grammar($text, $language = 'en') {
        $prompt = "Analyze this text for grammar, spelling, and style. Respond in JSON format:
{\"errors\": [{\"type\": \"grammar\", \"original\": \"text\", \"suggestion\": \"correction\", \"explanation\": \"why\"}], \"overall_score\": 85, \"summary\": \"summary\"}

Text: {$text}";
        
        $result = $this->make_request($prompt, 0.3);
        if (is_wp_error($result)) return $result;
        
        preg_match('/\{[\s\S]*\}/', $result, $matches);
        if ($matches) {
            $parsed = json_decode($matches[0], true);
            if ($parsed) return $parsed;
        }
        
        return array('errors' => array(), 'overall_score' => 90, 'summary' => 'Analysis completed');
    }
    
    public function rewrite_content($text, $tone = 'professional', $language = 'en') {
        $prompt = "Rewrite this text in a {$tone} tone:\n\n{$text}";
        return $this->make_request($prompt, 0.7);
    }
    
    public function generate_content($prompt, $context = '', $language = 'en') {
        $full_prompt = "Generate content:\n{$prompt}";
        if (!empty($context)) {
            $full_prompt .= "\n\nContext:\n{$context}";
        }
        return $this->make_request($full_prompt, 0.8);
    }
}

// ========================================
// USER SETTINGS
// ========================================
class AIWA_User_Settings {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('show_user_profile', array($this, 'add_api_key_field'));
        add_action('edit_user_profile', array($this, 'add_api_key_field'));
        add_action('personal_options_update', array($this, 'save_api_key'));
        add_action('edit_user_profile_update', array($this, 'save_api_key'));
        add_action('admin_notices', array($this, 'api_key_notice'));
    }
    
    public function add_api_key_field($user) {
        if (!current_user_can('edit_posts')) return;
        
        $api_key = get_user_meta($user->ID, 'aiwa_gemini_api_key', true);
        $preferred_lang = get_user_meta($user->ID, 'aiwa_preferred_language', true);
        if (empty($preferred_lang)) $preferred_lang = 'en';
        ?>
        <h2>AI Writing Assistant Settings</h2>
        <table class="form-table">
            <tr>
                <th><label for="aiwa_gemini_api_key">Google Gemini API Key</label></th>
                <td>
                    <input type="text" name="aiwa_gemini_api_key" id="aiwa_gemini_api_key" 
                           value="<?php echo esc_attr($api_key); ?>" class="regular-text" 
                           placeholder="Enter your Gemini API key">
                    <p class="description">
                        Get your free API key: <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>
                        <br><strong>Note:</strong> Your API key is private and only you can use it.
                    </p>
                    <?php if (!empty($api_key)): ?>
                        <p style="color: #16a34a; font-weight: 600;">✓ API Key configured</p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="aiwa_preferred_language">Preferred Language</label></th>
                <td>
                    <select name="aiwa_preferred_language" id="aiwa_preferred_language">
                        <?php
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
                            'kn' => 'Kannada (ಕನ್ನಡ)', 
                            'ml' => 'Malayalam (മലയാളം)',
                            'ar' => 'Arabic (العربية)', 
                            'ja' => 'Japanese (日本語)', 
                            'ko' => 'Korean (한국어)', 
                            'zh' => 'Chinese (中文)',
                            'ru' => 'Russian (Русский)', 
                            'tr' => 'Turkish (Türkçe)', 
                            'vi' => 'Vietnamese (Tiếng Việt)'
                        );
                        
                        foreach ($languages as $code => $name) {
                            $selected = ($code === $preferred_lang) ? 'selected' : '';
                            echo "<option value=\"" . esc_attr($code) . "\" {$selected}>" . esc_html($name) . "</option>";
                        }
                        ?>
                    </select>
                    <p class="description">Default language for AI writing suggestions</p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function save_api_key($user_id) {
        if (!current_user_can('edit_user', $user_id)) return;
        
        if (isset($_POST['aiwa_gemini_api_key'])) {
            update_user_meta($user_id, 'aiwa_gemini_api_key', sanitize_text_field($_POST['aiwa_gemini_api_key']));
        }
        if (isset($_POST['aiwa_preferred_language'])) {
            update_user_meta($user_id, 'aiwa_preferred_language', sanitize_text_field($_POST['aiwa_preferred_language']));
        }
    }
    
    public function api_key_notice() {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, array('post', 'page'))) return;
        
        $api_key = get_user_meta(get_current_user_id(), 'aiwa_gemini_api_key', true);
        if (empty($api_key)) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><strong>WP Content Helper:</strong> Add your Gemini API key in your 
                <a href="<?php echo esc_url(get_edit_profile_url()); ?>">profile settings</a> to use AI features.</p>
            </div>
            <?php
        }
    }
}

// ========================================
// GUTENBERG INTEGRATION
// ========================================
class AIWA_Gutenberg {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_assets'));
    }
    
    public function enqueue_assets() {
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_gemini_api_key', true);
        $preferred_language = get_user_meta($user_id, 'aiwa_preferred_language', true);
        if (empty($preferred_language)) $preferred_language = 'en';
        
        wp_enqueue_script('wp-plugins');
        wp_enqueue_script('wp-edit-post');
        wp_enqueue_script('wp-element');
        wp_enqueue_script('wp-components');
        wp_enqueue_script('wp-data');
        
        wp_add_inline_script('wp-plugins', "
            (function(wp) {
                const { registerPlugin } = wp.plugins;
                const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
                const { PanelBody, Button, TextareaControl, SelectControl, Spinner, Notice } = wp.components;
                const { createElement: el, Fragment, useState } = wp.element;
                
                const AIAssistant = () => {
                    const [isLoading, setIsLoading] = useState(false);
                    const [result, setResult] = useState(null);
                    const [error, setError] = useState(null);
                    const [activeTab, setActiveTab] = useState('grammar');
                    const [tone, setTone] = useState('professional');
                    const [prompt, setPrompt] = useState('');
                    const [language, setLanguage] = useState('{$preferred_language}');
                    
                    const hasApiKey = " . (empty($api_key) ? 'false' : 'true') . ";
                    const profileUrl = '" . esc_url(get_edit_profile_url($user_id)) . "';
                    
                    const getContent = () => {
                        const editor = wp.data.select('core/editor');
                        const blocks = editor.getBlocks();
                        let text = '';
                        blocks.forEach(block => {
                            if (block.attributes && block.attributes.content) {
                                const temp = document.createElement('div');
                                temp.innerHTML = block.attributes.content;
                                text += temp.textContent + ' ';
                            }
                        });
                        return text.trim();
                    };
                    
                    const checkGrammar = async () => {
                        const text = getContent();
                        if (!text) {
                            setError('Please write some content first.');
                            return;
                        }
                        
                        setIsLoading(true);
                        setError(null);
                        setResult(null);
                        
                        try {
                            const response = await fetch(ajaxurl, {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: new URLSearchParams({
                                    action: 'aiwa_check_grammar',
                                    nonce: '" . wp_create_nonce('aiwa_nonce') . "',
                                    text: text,
                                    language: language
                                })
                            });
                            
                            const data = await response.json();
                            if (data.success) {
                                setResult(data.data);
                            } else {
                                setError(data.data.message || 'Error occurred');
                            }
                        } catch (err) {
                            setError('Network error: ' + err.message);
                        } finally {
                            setIsLoading(false);
                        }
                    };
                    
                    const rewriteContent = async () => {
                        const text = getContent();
                        if (!text) {
                            setError('Please write some content first.');
                            return;
                        }
                        
                        setIsLoading(true);
                        setError(null);
                        setResult(null);
                        
                        try {
                            const response = await fetch(ajaxurl, {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: new URLSearchParams({
                                    action: 'aiwa_rewrite_content',
                                    nonce: '" . wp_create_nonce('aiwa_nonce') . "',
                                    text: text,
                                    tone: tone,
                                    language: language
                                })
                            });
                            
                            const data = await response.json();
                            if (data.success) {
                                setResult({ rewritten: data.data });
                            } else {
                                setError(data.data.message || 'Error occurred');
                            }
                        } catch (err) {
                            setError('Network error: ' + err.message);
                        } finally {
                            setIsLoading(false);
                        }
                    };
                    
                    const generateContent = async () => {
                        if (!prompt.trim()) {
                            setError('Please enter what you want to generate.');
                            return;
                        }
                        
                        setIsLoading(true);
                        setError(null);
                        setResult(null);
                        
                        const context = getContent();
                        
                        try {
                            const response = await fetch(ajaxurl, {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: new URLSearchParams({
                                    action: 'aiwa_generate_content',
                                    nonce: '" . wp_create_nonce('aiwa_nonce') . "',
                                    prompt: prompt,
                                    context: context,
                                    language: language
                                })
                            });
                            
                            const data = await response.json();
                            if (data.success) {
                                setResult({ generated: data.data });
                            } else {
                                setError(data.data.message || 'Error occurred');
                            }
                        } catch (err) {
                            setError('Network error: ' + err.message);
                        } finally {
                            setIsLoading(false);
                        }
                    };
                    
                    const copyToClipboard = (text) => {
                        navigator.clipboard.writeText(text).then(() => {
                            alert('Copied to clipboard!');
                        }).catch(() => {
                            alert('Failed to copy');
                        });
                    };
                    
                    const renderTabs = () => {
                        const tabs = [
                            { id: 'grammar', label: '✓ Grammar' },
                            { id: 'rewrite', label: '✏️ Rewrite' },
                            { id: 'generate', label: '✨ Generate' }
                        ];
                        
                        return el('div', { style: { display: 'flex', gap: '8px', marginBottom: '20px', borderBottom: '2px solid #e5e7eb' } },
                            tabs.map(tab => 
                                el(Button, {
                                    key: tab.id,
                                    onClick: () => {
                                        setActiveTab(tab.id);
                                        setResult(null);
                                        setError(null);
                                    },
                                    style: {
                                        padding: '8px 12px',
                                        background: activeTab === tab.id ? '#667eea' : 'transparent',
                                        color: activeTab === tab.id ? 'white' : '#64748b',
                                        border: 'none',
                                        borderRadius: '4px 4px 0 0',
                                        fontWeight: '600',
                                        cursor: 'pointer'
                                    }
                                }, tab.label)
                            )
                        );
                    };
                    
                    const renderGrammarTab = () => {
                        return el(Fragment, {},
                            el('p', { style: { marginBottom: '15px', color: '#64748b' } }, 'Check grammar and get suggestions'),
                            el(Button, {
                                isPrimary: true,
                                onClick: checkGrammar,
                                disabled: isLoading,
                                style: { width: '100%', marginBottom: '15px' }
                            }, isLoading ? 'Analyzing...' : '🔍 Check Grammar'),
                            
                            result && result.errors && el('div', { style: { marginTop: '20px', padding: '15px', background: '#f8f9fa', borderRadius: '8px' } },
                                el('div', { style: { background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', color: 'white', padding: '15px', borderRadius: '8px', marginBottom: '15px' } },
                                    el('h3', { style: { margin: '0 0 5px 0' } }, 'Score: ' + result.overall_score + '/100'),
                                    el('p', { style: { margin: '0' } }, result.summary)
                                ),
                                result.errors.length > 0 ?
                                    el('div', {},
                                        el('h4', { style: { marginTop: '0' } }, 'Suggestions:'),
                                        result.errors.map((err, idx) => 
                                            el('div', { 
                                                key: idx, 
                                                style: { 
                                                    background: 'white', 
                                                    padding: '12px', 
                                                    marginBottom: '10px', 
                                                    borderRadius: '6px', 
                                                    borderLeft: '4px solid #f59e0b' 
                                                } 
                                            },
                                                el('div', { style: { fontWeight: 'bold', color: '#f59e0b', marginBottom: '5px' } }, err.type),
                                                el('div', { style: { marginBottom: '5px' } }, el('strong', {}, 'Original: '), err.original),
                                                el('div', { style: { marginBottom: '5px' } }, el('strong', {}, 'Fix: '), err.suggestion),
                                                el('p', { style: { margin: '5px 0 0 0', fontSize: '13px', color: '#64748b' } }, err.explanation)
                                            )
                                        )
                                    ) :
                                    el('p', { style: { color: '#16a34a', fontWeight: '600' } }, '✓ No errors found!')
                            )
                        );
                    };
                    
                    const renderRewriteTab = () => {
                        return el(Fragment, {},
                            el(SelectControl, {
                                label: 'Tone',
                                value: tone,
                                options: [
                                    { label: 'Professional', value: 'professional' },
                                    { label: 'Casual', value: 'casual' },
                                    { label: 'Friendly', value: 'friendly' },
                                    { label: 'Academic', value: 'academic' },
                                    { label: 'Creative', value: 'creative' },
                                    { label: 'Simple', value: 'simple' },
                                    { label: 'Persuasive', value: 'persuasive' }
                                ],
                                onChange: setTone
                            }),
                            el(Button, {
                                isPrimary: true,
                                onClick: rewriteContent,
                                disabled: isLoading,
                                style: { width: '100%' }
                            }, isLoading ? 'Rewriting...' : '✏️ Rewrite Content'),
                            
                            result && result.rewritten && el('div', { style: { marginTop: '20px', padding: '15px', background: '#f8f9fa', borderRadius: '8px' } },
                                el('div', { style: { background: 'white', padding: '15px', borderRadius: '6px', marginBottom: '10px', lineHeight: '1.6' } }, 
                                    result.rewritten
                                ),
                                el(Button, { 
                                    onClick: () => copyToClipboard(result.rewritten),
                                    isSecondary: true
                                }, '📋 Copy to Clipboard')
                            )
                        );
                    };
                    
                    const renderGenerateTab = () => {
                        return el(Fragment, {},
                            el(TextareaControl, {
                                label: 'What do you want to write about?',
                                value: prompt,
                                onChange: setPrompt,
                                placeholder: 'e.g., Write an introduction about artificial intelligence...',
                                rows: 4
                            }),
                            el(Button, {
                                isPrimary: true,
                                onClick: generateContent,
                                disabled: isLoading,
                                style: { width: '100%' }
                            }, isLoading ? 'Generating...' : '✨ Generate Content'),
                            
                            result && result.generated && el('div', { style: { marginTop: '20px', padding: '15px', background: '#f8f9fa', borderRadius: '8px' } },
                                el('div', { style: { background: 'white', padding: '15px', borderRadius: '6px', marginBottom: '10px', lineHeight: '1.6' } }, 
                                    result.generated
                                ),
                                el(Button, { 
                                    onClick: () => copyToClipboard(result.generated),
                                    isSecondary: true
                                }, '📋 Copy to Clipboard')
                            )
                        );
                    };
                    
                    return el(Fragment, {},
                        el(PluginSidebarMoreMenuItem, { 
                            target: 'ai-assistant-sidebar', 
                            icon: 'edit' 
                        }, 'AI Writing Assistant'),
                        
                        el(PluginSidebar, { 
                            name: 'ai-assistant-sidebar', 
                            icon: 'edit', 
                            title: 'AI Writing Assistant' 
                        },
                            el(PanelBody, {},
                                !hasApiKey ?
                                    el(Notice, { 
                                        status: 'warning', 
                                        isDismissible: false 
                                    },
                                        el('p', {}, 
                                            'Please add your Gemini API key in your ',
                                            el('a', { 
                                                href: profileUrl, 
                                                target: '_blank',
                                                style: { textDecoration: 'underline' }
                                            }, 'profile settings'),
                                            ' to use AI features.'
                                        )
                                    ) :
                                    el(Fragment, {},
                                        renderTabs(),
                                        el('div', { style: { marginTop: '15px' } },
                                            activeTab === 'grammar' && renderGrammarTab(),
                                            activeTab === 'rewrite' && renderRewriteTab(),
                                            activeTab === 'generate' && renderGenerateTab()
                                        ),
                                        error && el(Notice, { 
                                            status: 'error', 
                                            onRemove: () => setError(null) 
                                        }, error),
                                        isLoading && el('div', { style: { textAlign: 'center', padding: '20px' } }, 
                                            el(Spinner)
                                        )
                                    )
                            )
                        )
                    );
                };
                
                registerPlugin('ai-writing-assistant', { render: AIAssistant });
            })(window.wp);
        ");
    }
}

// ========================================
// MAIN PLUGIN CLASS
// ========================================
class AI_Writing_Assistant {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        AIWA_User_Settings::get_instance();
        AIWA_Gutenberg::get_instance();
        
        add_action('wp_ajax_aiwa_check_grammar', array($this, 'ajax_check_grammar'));
        add_action('wp_ajax_aiwa_rewrite_content', array($this, 'ajax_rewrite_content'));
        add_action('wp_ajax_aiwa_generate_content', array($this, 'ajax_generate_content'));
    }
    
    public function ajax_check_grammar() {
        check_ajax_referer('aiwa_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_gemini_api_key', true);
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'Please add your API key in profile settings.'));
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
    
    public function ajax_rewrite_content() {
        check_ajax_referer('aiwa_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_gemini_api_key', true);
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'Please add your API key.'));
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
    
    public function ajax_generate_content() {
        check_ajax_referer('aiwa_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_gemini_api_key', true);
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'Please add your API key.'));
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
}

// Initialize plugin
add_action('plugins_loaded', array('AI_Writing_Assistant', 'get_instance'));