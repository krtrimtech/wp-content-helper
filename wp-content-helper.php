<?php
/**
 * Plugin Name: WP Content Helper
 * Plugin URI: https://github.com/krtrimtech/wp-content-helper
 * Description: Grammarly-like AI writing assistant with Google Gemini API. Each user uses their own API key.
 * Version: 1.1.0
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
    
    public function improve_text($text, $language = 'en') {
        $prompt = "Improve this text for better clarity, grammar, and flow. Return ONLY the improved text without explanations:\n\n{$text}";
        return $this->make_request($prompt, 0.7);
    }
}

// ========================================
// USER SETTINGS - DASHBOARD PAGE
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_aiwa_save_settings', array($this, 'save_settings'));
        add_action('admin_notices', array($this, 'api_key_notice'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'AI Writing Assistant',
            'AI Assistant',
            'edit_posts',
            'wp-content-helper',
            array($this, 'render_settings_page'),
            'dashicons-edit',
            30
        );
    }
    
    public function render_settings_page() {
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_gemini_api_key', true);
        $preferred_lang = get_user_meta($user_id, 'aiwa_preferred_language', true);
        if (empty($preferred_lang)) $preferred_lang = 'en';
        
        if (isset($_GET['settings-updated'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Settings saved successfully!</strong></p>
            </div>
            <?php
        }
        ?>
        <div class="wrap">
            <h1>🤖 AI Writing Assistant Settings</h1>
            <p>Configure your personal AI writing assistant powered by Google Gemini API.</p>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="aiwa_save_settings">
                <?php wp_nonce_field('aiwa_settings', 'aiwa_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="aiwa_gemini_api_key">Google Gemini API Key</label>
                        </th>
                        <td>
                            <input type="text" 
                                   name="aiwa_gemini_api_key" 
                                   id="aiwa_gemini_api_key" 
                                   value="<?php echo esc_attr($api_key); ?>" 
                                   class="regular-text"
                                   placeholder="AIzaSy...">
                            <p class="description">
                                Get your free API key from: 
                                <a href="https://aistudio.google.com/app/apikey" target="_blank" style="font-weight: 600;">Google AI Studio →</a>
                            </p>
                            <?php if (!empty($api_key)): ?>
                                <p style="color: #16a34a; font-weight: 600; margin-top: 10px;">
                                    ✓ API Key configured!
                                </p>
                            <?php else: ?>
                                <p style="color: #f59e0b; font-weight: 600; margin-top: 10px;">
                                    ⚠ Please add your API key to use AI features
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="aiwa_preferred_language">Preferred Language</label>
                        </th>
                        <td>
                            <select name="aiwa_preferred_language" id="aiwa_preferred_language" class="regular-text">
                                <?php
                                $languages = array(
                                    'en' => 'English', 'hi' => 'Hindi (हिंदी)', 'bn' => 'Bengali (বাংলা)',
                                    'pa' => 'Punjabi (ਪੰਜਾਬੀ)', 'te' => 'Telugu (తెలుగు)', 
                                    'mr' => 'Marathi (मराठी)', 'ta' => 'Tamil (தமிழ்)'
                                );
                                
                                foreach ($languages as $code => $name) {
                                    $selected = ($code === $preferred_lang) ? 'selected' : '';
                                    echo "<option value=\"" . esc_attr($code) . "\" {$selected}>" . esc_html($name) . "</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Settings'); ?>
            </form>
            
            <hr style="margin: 40px 0;">
            
            <h2>📖 How to Use</h2>
            <div style="background: #f0f6fc; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
                <ol style="margin: 0; padding-left: 20px;">
                    <li style="margin-bottom: 10px;">
                        <strong>Add your API key above</strong> and click "Save Settings"
                    </li>
                    <li style="margin-bottom: 10px;">
                        <strong>Go to Posts → Add New</strong> (or edit any existing post)
                    </li>
                    <li style="margin-bottom: 10px;">
                        <strong>Select any text</strong> in the editor
                    </li>
                    <li>
                        <strong>Click AI buttons in the toolbar:</strong>
                        <ul style="margin-top: 5px;">
                            <li>🤖 <strong>AI Improve</strong> - Automatically improve selected text</li>
                            <li>✓ <strong>Check Grammar</strong> - Analyze for errors</li>
                            <li>✏️ <strong>Rewrite</strong> - Change tone and style</li>
                        </ul>
                    </li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    public function save_settings() {
        if (!isset($_POST['aiwa_settings_nonce']) || !wp_verify_nonce($_POST['aiwa_settings_nonce'], 'aiwa_settings')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die('You do not have permission');
        }
        
        $user_id = get_current_user_id();
        
        if (isset($_POST['aiwa_gemini_api_key'])) {
            update_user_meta($user_id, 'aiwa_gemini_api_key', sanitize_text_field($_POST['aiwa_gemini_api_key']));
        }
        
        if (isset($_POST['aiwa_preferred_language'])) {
            update_user_meta($user_id, 'aiwa_preferred_language', sanitize_text_field($_POST['aiwa_preferred_language']));
        }
        
        wp_redirect(add_query_arg('settings-updated', 'true', admin_url('admin.php?page=wp-content-helper')));
        exit;
    }
    
    public function api_key_notice() {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, array('post', 'page'))) return;
        
        $api_key = get_user_meta(get_current_user_id(), 'aiwa_gemini_api_key', true);
        if (empty($api_key)) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>🤖 WP Content Helper:</strong> Configure your AI Assistant to start using features.
                    <a href="<?php echo admin_url('admin.php?page=wp-content-helper'); ?>" style="font-weight: 600;">Go to Settings →</a>
                </p>
            </div>
            <?php
        }
    }
}

// ========================================
// GUTENBERG TOOLBAR INTEGRATION
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
        
        wp_enqueue_script('wp-rich-text');
        wp_enqueue_script('wp-block-editor');
        wp_enqueue_script('wp-components');
        wp_enqueue_script('wp-element');
        wp_enqueue_script('wp-data');
        
        // Add toolbar buttons
        wp_add_inline_script('wp-rich-text', "
(function(wp) {
    const { registerFormatType } = wp.richText;
    const { RichTextToolbarButton } = wp.blockEditor;
    const { createElement: el, Fragment, useState } = wp.element;
    const { Modal, Button, SelectControl, Spinner } = wp.components;
    const { useSelect } = wp.data;
    
    const hasApiKey = " . (empty($api_key) ? 'false' : 'true') . ";
    const settingsUrl = '" . admin_url('admin.php?page=wp-content-helper') . "';
    
    // AI Improve Button (Quick Action)
    const AIImproveButton = (props) => {
        const [isLoading, setIsLoading] = useState(false);
        
        const selectedBlock = useSelect((select) => {
            return select('core/block-editor').getSelectedBlock();
        }, []);
        
        if (!selectedBlock || (selectedBlock.name !== 'core/paragraph' && selectedBlock.name !== 'core/heading')) {
            return null;
        }
        
        const improveText = async () => {
            if (!hasApiKey) {
                alert('Please add your API key in AI Assistant settings first.');
                window.open(settingsUrl, '_blank');
                return;
            }
            
            const selection = window.getSelection();
            const selectedText = selection.toString().trim();
            
            if (!selectedText) {
                alert('Please select some text first');
                return;
            }
            
            setIsLoading(true);
            
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'aiwa_improve_text',
                        nonce: '" . wp_create_nonce('aiwa_nonce') . "',
                        text: selectedText,
                        language: '{$preferred_language}'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Replace selected text with improved version
                    const improved = data.data;
                    document.execCommand('insertText', false, improved);
                    alert('✓ Text improved!');
                } else {
                    alert('Error: ' + (data.data.message || 'Failed to improve text'));
                }
            } catch (err) {
                alert('Network error: ' + err.message);
            } finally {
                setIsLoading(false);
            }
        };
        
        return el(RichTextToolbarButton, {
            icon: 'star-filled',
            title: 'AI Improve (select text first)',
            onClick: improveText,
            isDisabled: isLoading,
            style: isLoading ? { color: '#667eea' } : {}
        });
    };
    
    // Grammar Check Button
    const GrammarCheckButton = (props) => {
        const [isOpen, setIsOpen] = useState(false);
        const [isLoading, setIsLoading] = useState(false);
        const [result, setResult] = useState(null);
        
        const selectedBlock = useSelect((select) => {
            return select('core/block-editor').getSelectedBlock();
        }, []);
        
        if (!selectedBlock || (selectedBlock.name !== 'core/paragraph' && selectedBlock.name !== 'core/heading')) {
            return null;
        }
        
        const checkGrammar = async () => {
            if (!hasApiKey) {
                alert('Please add your API key in AI Assistant settings first.');
                window.open(settingsUrl, '_blank');
                return;
            }
            
            const selection = window.getSelection();
            const selectedText = selection.toString().trim();
            
            if (!selectedText) {
                alert('Please select some text first');
                return;
            }
            
            setIsOpen(true);
            setIsLoading(true);
            setResult(null);
            
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'aiwa_check_grammar',
                        nonce: '" . wp_create_nonce('aiwa_nonce') . "',
                        text: selectedText,
                        language: '{$preferred_language}'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    setResult(data.data);
                } else {
                    alert('Error: ' + (data.data.message || 'Failed to check grammar'));
                    setIsOpen(false);
                }
            } catch (err) {
                alert('Network error: ' + err.message);
                setIsOpen(false);
            } finally {
                setIsLoading(false);
            }
        };
        
        return el(Fragment, {},
            el(RichTextToolbarButton, {
                icon: 'yes-alt',
                title: 'Check Grammar (select text first)',
                onClick: checkGrammar
            }),
            isOpen && el(Modal, {
                title: '✓ Grammar Check Results',
                onRequestClose: () => setIsOpen(false),
                style: { maxWidth: '600px' }
            },
                isLoading ? el(Spinner) :
                result && el('div', {},
                    el('div', { style: { background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', color: 'white', padding: '15px', borderRadius: '8px', marginBottom: '15px' } },
                        el('h3', { style: { margin: '0 0 8px 0' } }, 'Score: ' + result.overall_score + '/100'),
                        el('p', { style: { margin: 0 } }, result.summary)
                    ),
                    result.errors && result.errors.length > 0 ?
                        result.errors.map((err, idx) => 
                            el('div', { 
                                key: idx,
                                style: { 
                                    background: '#f8f9fa', 
                                    padding: '12px', 
                                    marginBottom: '10px', 
                                    borderRadius: '6px',
                                    borderLeft: '4px solid #f59e0b'
                                }
                            },
                                el('strong', { style: { color: '#f59e0b', textTransform: 'uppercase', fontSize: '12px' } }, err.type),
                                el('div', { style: { marginTop: '5px' } }, 
                                    el('strong', {}, 'Original: '), err.original
                                ),
                                el('div', { style: { marginTop: '5px', color: '#16a34a' } }, 
                                    el('strong', {}, 'Fix: '), err.suggestion
                                ),
                                err.explanation && el('p', { style: { margin: '5px 0 0 0', fontSize: '13px', color: '#64748b', fontStyle: 'italic' } }, err.explanation)
                            )
                        ) :
                        el('p', { style: { color: '#16a34a', fontWeight: '600', textAlign: 'center', padding: '20px' } }, '✓ No errors found!')
                )
            )
        );
    };
    
    // Rewrite Button
    const RewriteButton = (props) => {
        const [isOpen, setIsOpen] = useState(false);
        const [isLoading, setIsLoading] = useState(false);
        const [tone, setTone] = useState('professional');
        const [result, setResult] = useState(null);
        const [originalText, setOriginalText] = useState('');
        
        const selectedBlock = useSelect((select) => {
            return select('core/block-editor').getSelectedBlock();
        }, []);
        
        if (!selectedBlock || (selectedBlock.name !== 'core/paragraph' && selectedBlock.name !== 'core/heading')) {
            return null;
        }
        
        const openModal = () => {
            if (!hasApiKey) {
                alert('Please add your API key in AI Assistant settings first.');
                window.open(settingsUrl, '_blank');
                return;
            }
            
            const selection = window.getSelection();
            const selectedText = selection.toString().trim();
            
            if (!selectedText) {
                alert('Please select some text first');
                return;
            }
            
            setOriginalText(selectedText);
            setIsOpen(true);
            setResult(null);
        };
        
        const rewriteText = async () => {
            setIsLoading(true);
            setResult(null);
            
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'aiwa_rewrite_content',
                        nonce: '" . wp_create_nonce('aiwa_nonce') . "',
                        text: originalText,
                        tone: tone,
                        language: '{$preferred_language}'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    setResult(data.data);
                } else {
                    alert('Error: ' + (data.data.message || 'Failed to rewrite'));
                }
            } catch (err) {
                alert('Network error: ' + err.message);
            } finally {
                setIsLoading(false);
            }
        };
        
        const replaceText = () => {
            if (result) {
                document.execCommand('insertText', false, result);
                setIsOpen(false);
                alert('✓ Text replaced!');
            }
        };
        
        return el(Fragment, {},
            el(RichTextToolbarButton, {
                icon: 'edit',
                title: 'Rewrite (select text first)',
                onClick: openModal
            }),
            isOpen && el(Modal, {
                title: '✏️ Rewrite Text',
                onRequestClose: () => setIsOpen(false),
                style: { maxWidth: '600px' }
            },
                el(SelectControl, {
                    label: 'Tone',
                    value: tone,
                    options: [
                        { label: 'Professional', value: 'professional' },
                        { label: 'Casual', value: 'casual' },
                        { label: 'Friendly', value: 'friendly' },
                        { label: 'Academic', value: 'academic' },
                        { label: 'Creative', value: 'creative' },
                        { label: 'Simple', value: 'simple' }
                    ],
                    onChange: setTone
                }),
                el(Button, {
                    isPrimary: true,
                    onClick: rewriteText,
                    disabled: isLoading
                }, isLoading ? 'Rewriting...' : 'Rewrite'),
                result && el('div', { style: { marginTop: '20px', padding: '15px', background: '#f8f9fa', borderRadius: '8px' } },
                    el('h4', { style: { marginTop: 0 } }, 'Rewritten:'),
                    el('div', { style: { background: 'white', padding: '15px', borderRadius: '6px', marginBottom: '10px', lineHeight: '1.6' } }, result),
                    el(Button, { isPrimary: true, onClick: replaceText }, 'Replace Selected Text')
                )
            )
        );
    };
    
    // Register format types
    registerFormatType('aiwa/ai-improve', {
        title: 'AI Improve',
        tagName: 'span',
        className: null,
        edit: AIImproveButton
    });
    
    registerFormatType('aiwa/grammar-check', {
        title: 'Grammar Check',
        tagName: 'span',
        className: null,
        edit: GrammarCheckButton
    });
    
    registerFormatType('aiwa/rewrite', {
        title: 'Rewrite',
        tagName: 'span',
        className: null,
        edit: RewriteButton
    });
    
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
        add_action('wp_ajax_aiwa_improve_text', array($this, 'ajax_improve_text'));
    }
    
    public function ajax_check_grammar() {
        check_ajax_referer('aiwa_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_gemini_api_key', true);
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'Please add your API key in settings.'));
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
    
    public function ajax_improve_text() {
        check_ajax_referer('aiwa_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_gemini_api_key', true);
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'Please add your API key.'));
        }
        
        $text = isset($_POST['text']) ? sanitize_textarea_field($_POST['text']) : '';
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'en';
        
        $gemini = new AIWA_Gemini_API($api_key);
        $result = $gemini->improve_text($text, $language);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }
}

add_action('plugins_loaded', array('AI_Writing_Assistant', 'get_instance'));
