<?php
/**
 * Plugin Name: WP Content Helper
 * Plugin URI: https://github.com/krtrimtech/wp-content-helper
 * Description: Grammarly-like AI writing assistant with Google Gemini API. Each user uses their own API key.
 * Version: 1.0.2
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
// USER SETTINGS - NOW IN DASHBOARD
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
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Save settings
        add_action('admin_post_aiwa_save_settings', array($this, 'save_settings'));
        
        // Admin notice
        add_action('admin_notices', array($this, 'api_key_notice'));
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_menu_page(
            'AI Writing Assistant',           // Page title
            'AI Assistant',                   // Menu title
            'edit_posts',                     // Capability
            'wp-content-helper',              // Menu slug
            array($this, 'render_settings_page'), // Callback
            'dashicons-edit',                 // Icon
            30                                // Position
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_gemini_api_key', true);
        $preferred_lang = get_user_meta($user_id, 'aiwa_preferred_language', true);
        if (empty($preferred_lang)) $preferred_lang = 'en';
        
        // Show success message if saved
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
                                Don't have an API key? Get your free API key from: 
                                <a href="https://aistudio.google.com/app/apikey" target="_blank" style="font-weight: 600;">Google AI Studio →</a>
                            </p>
                            <?php if (!empty($api_key)): ?>
                                <p style="color: #16a34a; font-weight: 600; margin-top: 10px;">
                                    ✓ API Key is configured and ready to use!
                                </p>
                            <?php else: ?>
                                <p style="color: #f59e0b; font-weight: 600; margin-top: 10px;">
                                    ⚠ Please add your API key to start using AI features
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
                                    'en' => 'English', 
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
                                    'es' => 'Spanish (Español)', 
                                    'fr' => 'French (Français)', 
                                    'de' => 'German (Deutsch)',
                                    'ar' => 'Arabic (العربية)', 
                                    'ja' => 'Japanese (日本語)',
                                    'ko' => 'Korean (한국어)', 
                                    'zh' => 'Chinese (中文)',
                                    'ru' => 'Russian (Русский)', 
                                    'pt' => 'Portuguese (Português)'
                                );
                                
                                foreach ($languages as $code => $name) {
                                    $selected = ($code === $preferred_lang) ? 'selected' : '';
                                    echo "<option value=\"" . esc_attr($code) . "\" {$selected}>" . esc_html($name) . "</option>";
                                }
                                ?>
                            </select>
                            <p class="description">Select your preferred language for AI-generated content</p>
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
                        <strong>Click the three dots (⋮)</strong> at the top right of the editor
                    </li>
                    <li style="margin-bottom: 10px;">
                        <strong>Select "AI Writing Assistant"</strong> from the menu
                    </li>
                    <li>
                        <strong>Start using AI features:</strong>
                        <ul style="margin-top: 5px;">
                            <li>✓ Grammar Check - Analyze your content for errors</li>
                            <li>✏️ Rewrite - Change tone and style</li>
                            <li>✨ Generate - Create new content from prompts</li>
                        </ul>
                    </li>
                </ol>
            </div>
            
            <hr style="margin: 40px 0;">
            
            <h2>ℹ️ About</h2>
            <p style="color: #666;">
                <strong>WP Content Helper</strong> is a powerful AI writing assistant plugin for WordPress.<br>
                Created by <a href="https://github.com/krtrimtech" target="_blank">Krtrim</a> | 
                Contributor: <a href="https://shyanukant.github.io/" target="_blank">Shyanukant Rathi</a><br>
                <a href="https://github.com/krtrimtech/wp-content-helper" target="_blank">GitHub Repository →</a>
            </p>
        </div>
        
        <style>
            .wrap h1 { font-size: 28px; margin-bottom: 10px; }
            .wrap > p { font-size: 16px; color: #666; margin-bottom: 30px; }
            .form-table th { width: 200px; font-weight: 600; }
            .form-table input[type="text"], .form-table select { padding: 8px; }
        </style>
        <?php
    }
    
    /**
     * Save settings
     */
    public function save_settings() {
        // Check nonce
        if (!isset($_POST['aiwa_settings_nonce']) || !wp_verify_nonce($_POST['aiwa_settings_nonce'], 'aiwa_settings')) {
            wp_die('Security check failed');
        }
        
        // Check user capability
        if (!current_user_can('edit_posts')) {
            wp_die('You do not have permission to do this');
        }
        
        $user_id = get_current_user_id();
        
        // Save API key
        if (isset($_POST['aiwa_gemini_api_key'])) {
            update_user_meta($user_id, 'aiwa_gemini_api_key', sanitize_text_field($_POST['aiwa_gemini_api_key']));
        }
        
        // Save language
        if (isset($_POST['aiwa_preferred_language'])) {
            update_user_meta($user_id, 'aiwa_preferred_language', sanitize_text_field($_POST['aiwa_preferred_language']));
        }
        
        // Redirect with success message
        wp_redirect(add_query_arg('settings-updated', 'true', admin_url('admin.php?page=wp-content-helper')));
        exit;
    }
    
    /**
     * Show admin notice if API key not set
     */
    public function api_key_notice() {
        $screen = get_current_screen();
        
        if (!$screen) return;
        if (!in_array($screen->id, array('post', 'page'))) return;
        
        $api_key = get_user_meta(get_current_user_id(), 'aiwa_gemini_api_key', true);
        if (empty($api_key)) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>🤖 WP Content Helper:</strong> Configure your AI Assistant settings to start using AI features.
                    <a href="<?php echo admin_url('admin.php?page=wp-content-helper'); ?>" style="font-weight: 600;">Go to Settings →</a>
                </p>
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
        
        // Complete JavaScript with all 3 tabs
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
                    const settingsUrl = '" . admin_url('admin.php?page=wp-content-helper') . "';
                    
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
                        
                        return el('div', { style: { display: 'flex', gap: '4px', marginBottom: '20px', borderBottom: '2px solid #e5e7eb' } },
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
                            el('p', { style: { marginBottom: '15px', color: '#64748b', fontSize: '13px' } }, 'Check grammar, spelling, and style'),
                            el(Button, {
                                isPrimary: true,
                                onClick: checkGrammar,
                                disabled: isLoading,
                                style: { width: '100%', marginBottom: '15px', justifyContent: 'center' }
                            }, isLoading ? 'Analyzing...' : '🔍 Check Grammar'),
                            
                            result && result.errors && el('div', { style: { marginTop: '20px', padding: '15px', background: '#f8f9fa', borderRadius: '8px' } },
                                el('div', { style: { background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)', color: 'white', padding: '15px', borderRadius: '8px', marginBottom: '15px' } },
                                    el('h3', { style: { margin: '0 0 8px 0', fontSize: '18px' } }, 'Score: ' + result.overall_score + '/100'),
                                    el('p', { style: { margin: '0', fontSize: '14px' } }, result.summary)
                                ),
                                result.errors.length > 0 ?
                                    el('div', {},
                                        el('h4', { style: { marginTop: '0', fontSize: '14px' } }, 'Suggestions:'),
                                        result.errors.slice(0, 10).map((err, idx) => 
                                            el('div', { 
                                                key: idx, 
                                                style: { 
                                                    background: 'white', 
                                                    padding: '10px', 
                                                    marginBottom: '8px', 
                                                    borderRadius: '6px', 
                                                    borderLeft: '4px solid #f59e0b',
                                                    fontSize: '13px'
                                                } 
                                            },
                                                el('div', { style: { fontWeight: 'bold', color: '#f59e0b', marginBottom: '5px', textTransform: 'uppercase', fontSize: '11px' } }, err.type),
                                                el('div', { style: { marginBottom: '5px' } }, el('strong', {}, 'Original: '), err.original),
                                                el('div', { style: { marginBottom: '5px', color: '#16a34a' } }, el('strong', {}, 'Fix: '), err.suggestion),
                                                err.explanation && el('p', { style: { margin: '5px 0 0 0', fontSize: '12px', color: '#64748b', fontStyle: 'italic' } }, err.explanation)
                                            )
                                        )
                                    ) :
                                    el('p', { style: { color: '#16a34a', fontWeight: '600', textAlign: 'center' } }, '✓ No errors found!')
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
                                style: { width: '100%', justifyContent: 'center' }
                            }, isLoading ? 'Rewriting...' : '✏️ Rewrite'),
                            
                            result && result.rewritten && el('div', { style: { marginTop: '20px', padding: '15px', background: '#f8f9fa', borderRadius: '8px' } },
                                el('h4', { style: { marginTop: 0, fontSize: '14px' } }, 'Rewritten:'),
                                el('div', { style: { background: 'white', padding: '15px', borderRadius: '6px', marginBottom: '10px', lineHeight: '1.6', fontSize: '14px' } }, 
                                    result.rewritten
                                ),
                                el(Button, { 
                                    onClick: () => copyToClipboard(result.rewritten),
                                    isSecondary: true,
                                    style: { width: '100%' }
                                }, '📋 Copy')
                            )
                        );
                    };
                    
                    const renderGenerateTab = () => {
                        return el(Fragment, {},
                            el(TextareaControl, {
                                label: 'What do you want to write about?',
                                value: prompt,
                                onChange: setPrompt,
                                placeholder: 'e.g., Write an introduction about...',
                                rows: 4
                            }),
                            el(Button, {
                                isPrimary: true,
                                onClick: generateContent,
                                disabled: isLoading || !prompt.trim(),
                                style: { width: '100%', justifyContent: 'center' }
                            }, isLoading ? 'Generating...' : '✨ Generate'),
                            
                            result && result.generated && el('div', { style: { marginTop: '20px', padding: '15px', background: '#f8f9fa', borderRadius: '8px' } },
                                el('h4', { style: { marginTop: 0, fontSize: '14px' } }, 'Generated:'),
                                el('div', { style: { background: 'white', padding: '15px', borderRadius: '6px', marginBottom: '10px', lineHeight: '1.6', fontSize: '14px' } }, 
                                    result.generated
                                ),
                                el(Button, { 
                                    onClick: () => copyToClipboard(result.generated),
                                    isSecondary: true,
                                    style: { width: '100%' }
                                }, '📋 Copy')
                            )
                        );
                    };
                    
                    return el(Fragment, {},
                        el(PluginSidebarMoreMenuItem, { 
                            target: 'ai-writing-assistant-sidebar', 
                            icon: 'edit' 
                        }, 'AI Writing Assistant'),
                        
                        el(PluginSidebar, { 
                            name: 'ai-writing-assistant-sidebar', 
                            icon: 'edit', 
                            title: '🤖 AI Writing Assistant' 
                        },
                            el(PanelBody, {},
                                !hasApiKey ?
                                    el(Notice, { 
                                        status: 'warning', 
                                        isDismissible: false 
                                    },
                                        el('div', {},
                                            el('p', { style: { margin: '0 0 8px 0' } }, 
                                                '⚠️ Please configure your AI Assistant settings first.'
                                            ),
                                            el('a', { 
                                                href: settingsUrl,
                                                style: { 
                                                    display: 'inline-block',
                                                    padding: '6px 12px',
                                                    background: '#667eea',
                                                    color: 'white',
                                                    borderRadius: '4px',
                                                    textDecoration: 'none',
                                                    fontSize: '13px'
                                                }
                                            }, '➜ Go to Settings')
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
}

// Initialize plugin
add_action('plugins_loaded', array('AI_Writing_Assistant', 'get_instance'));
