<?php
/**
 * Plugin Name: WP Content Helper
 * Plugin URI: https://github.com/krtrimtech/wp-content-helper
 * Description: Grammarly-like AI writing assistant with Google Gemini API. Works with Gutenberg, Elementor, and Classic Editor.
 * Version: 1.2.0
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
        $prompt = "Improve this text for better clarity, grammar, and flow. Return ONLY the improved text:\n\n{$text}";
        return $this->make_request($prompt, 0.7);
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
            echo '<div class="notice notice-success is-dismissible"><p><strong>Settings saved!</strong></p></div>';
        }
        ?>
        <div class="wrap">
            <h1>🤖 AI Writing Assistant</h1>
            <p>Configure your AI writing assistant - works with Gutenberg, Elementor, and all editors!</p>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="aiwa_save_settings">
                <?php wp_nonce_field('aiwa_settings', 'aiwa_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="aiwa_gemini_api_key">Gemini API Key</label></th>
                        <td>
                            <input type="text" name="aiwa_gemini_api_key" id="aiwa_gemini_api_key" 
                                   value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                            <p class="description">
                                Get free API: <a href="https://aistudio.google.com/app/apikey" target="_blank"><strong>Google AI Studio →</strong></a>
                            </p>
                            <?php if (!empty($api_key)): ?>
                                <p style="color: #16a34a; font-weight: 600;">✓ API Key configured!</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="aiwa_preferred_language">Language</label></th>
                        <td>
                            <select name="aiwa_preferred_language" id="aiwa_preferred_language">
                                <?php
                                $languages = array('en' => 'English', 'hi' => 'Hindi', 'bn' => 'Bengali');
                                foreach ($languages as $code => $name) {
                                    echo "<option value=\"{$code}\"" . selected($preferred_lang, $code, false) . ">{$name}</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <hr>
            <h2>📖 How to Use</h2>
            <div style="background: #f0f6fc; padding: 20px; border-radius: 8px;">
                <ol>
                    <li><strong>Save your API key above</strong></li>
                    <li><strong>Go to any Post/Page editor</strong> (Gutenberg or Elementor)</li>
                    <li><strong>Look for the floating 🤖 AI button</strong> at bottom-right</li>
                    <li><strong>Select text and click AI features!</strong></li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    public function save_settings() {
        if (!isset($_POST['aiwa_settings_nonce']) || !wp_verify_nonce($_POST['aiwa_settings_nonce'], 'aiwa_settings')) {
            wp_die('Security check failed');
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
        if (!$screen || !in_array($screen->base, array('post', 'page'))) return;
        
        $api_key = get_user_meta(get_current_user_id(), 'aiwa_gemini_api_key', true);
        if (empty($api_key)) {
            ?>
            <div class="notice notice-info">
                <p>🤖 <strong>AI Assistant:</strong> <a href="<?php echo admin_url('admin.php?page=wp-content-helper'); ?>">Add your API key</a> to use AI features</p>
            </div>
            <?php
        }
    }
}

// ========================================
// FLOATING AI PANEL (Works with ALL editors)
// ========================================
class AIWA_Floating_Panel {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    public function enqueue_assets($hook) {
        // Only load on post/page edit screens
        if (!in_array($hook, array('post.php', 'post-new.php', 'page.php', 'page-new.php'))) {
            return;
        }
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_gemini_api_key', true);
        $preferred_language = get_user_meta($user_id, 'aiwa_preferred_language', true);
        if (empty($preferred_language)) $preferred_language = 'en';
        
        // Enqueue styles and scripts
        wp_enqueue_style('aiwa-floating-panel', false);
        wp_add_inline_style('aiwa-floating-panel', "
            #aiwa-floating-btn {
                position: fixed;
                bottom: 30px;
                right: 30px;
                width: 60px;
                height: 60px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 50%;
                border: none;
                cursor: pointer;
                box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
                z-index: 99999;
                font-size: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s;
            }
            #aiwa-floating-btn:hover {
                transform: scale(1.1);
                box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6);
            }
            #aiwa-panel {
                position: fixed;
                bottom: 100px;
                right: 30px;
                width: 400px;
                max-height: 600px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                z-index: 99998;
                display: none;
                overflow: hidden;
            }
            #aiwa-panel.open { display: block; }
            .aiwa-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 15px 20px;
                font-weight: 600;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .aiwa-close {
                background: none;
                border: none;
                color: white;
                font-size: 24px;
                cursor: pointer;
                padding: 0;
                width: 30px;
                height: 30px;
            }
            .aiwa-content {
                padding: 20px;
                max-height: 500px;
                overflow-y: auto;
            }
            .aiwa-tab-buttons {
                display: flex;
                gap: 8px;
                margin-bottom: 20px;
                border-bottom: 2px solid #e5e7eb;
            }
            .aiwa-tab-btn {
                padding: 10px 15px;
                background: transparent;
                border: none;
                cursor: pointer;
                font-weight: 600;
                color: #64748b;
                border-bottom: 3px solid transparent;
                transition: all 0.2s;
            }
            .aiwa-tab-btn.active {
                color: #667eea;
                border-bottom-color: #667eea;
            }
            .aiwa-tab-content { display: none; }
            .aiwa-tab-content.active { display: block; }
            .aiwa-btn {
                width: 100%;
                padding: 12px;
                background: #667eea;
                color: white;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-weight: 600;
                margin-top: 10px;
            }
            .aiwa-btn:hover { background: #5568d3; }
            .aiwa-btn:disabled {
                background: #ccc;
                cursor: not-allowed;
            }
            .aiwa-textarea {
                width: 100%;
                padding: 10px;
                border: 2px solid #e5e7eb;
                border-radius: 6px;
                font-family: inherit;
                resize: vertical;
            }
            .aiwa-select {
                width: 100%;
                padding: 10px;
                border: 2px solid #e5e7eb;
                border-radius: 6px;
                margin-bottom: 10px;
            }
            .aiwa-result {
                margin-top: 15px;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 8px;
                border-left: 4px solid #667eea;
            }
            .aiwa-loading {
                text-align: center;
                padding: 20px;
                color: #667eea;
            }
        ");
        
        wp_enqueue_script('aiwa-floating-panel', false, array(), '1.2.0', true);
        wp_add_inline_script('aiwa-floating-panel', "
(function() {
    const hasApiKey = " . (empty($api_key) ? 'false' : 'true') . ";
    const settingsUrl = '" . admin_url('admin.php?page=wp-content-helper') . "';
    const ajaxUrl = '" . admin_url('admin-ajax.php') . "';
    const nonce = '" . wp_create_nonce('aiwa_nonce') . "';
    
    // Create floating button and panel
    const html = `
        <button id=\"aiwa-floating-btn\">🤖</button>
        <div id=\"aiwa-panel\">
            <div class=\"aiwa-header\">
                <span>AI Writing Assistant</span>
                <button class=\"aiwa-close\">×</button>
            </div>
            <div class=\"aiwa-content\">
                <div class=\"aiwa-tab-buttons\">
                    <button class=\"aiwa-tab-btn active\" data-tab=\"improve\">✨ Improve</button>
                    <button class=\"aiwa-tab-btn\" data-tab=\"grammar\">✓ Grammar</button>
                    <button class=\"aiwa-tab-btn\" data-tab=\"rewrite\">✏️ Rewrite</button>
                </div>
                
                <div id=\"aiwa-tab-improve\" class=\"aiwa-tab-content active\">
                    <p style=\"margin-top:0;color:#64748b;font-size:13px;\">Select text, paste it here, and improve it instantly</p>
                    <textarea id=\"aiwa-improve-text\" class=\"aiwa-textarea\" rows=\"5\" placeholder=\"Paste your text here or select text on page\"></textarea>
                    <button id=\"aiwa-improve-btn\" class=\"aiwa-btn\">✨ Improve Text</button>
                    <div id=\"aiwa-improve-result\"></div>
                </div>
                
                <div id=\"aiwa-tab-grammar\" class=\"aiwa-tab-content\">
                    <p style=\"margin-top:0;color:#64748b;font-size:13px;\">Check grammar and get suggestions</p>
                    <textarea id=\"aiwa-grammar-text\" class=\"aiwa-textarea\" rows=\"5\" placeholder=\"Paste text to check\"></textarea>
                    <button id=\"aiwa-grammar-btn\" class=\"aiwa-btn\">✓ Check Grammar</button>
                    <div id=\"aiwa-grammar-result\"></div>
                </div>
                
                <div id=\"aiwa-tab-rewrite\" class=\"aiwa-tab-content\">
                    <p style=\"margin-top:0;color:#64748b;font-size:13px;\">Rewrite in different tone</p>
                    <select id=\"aiwa-tone\" class=\"aiwa-select\">
                        <option value=\"professional\">Professional</option>
                        <option value=\"casual\">Casual</option>
                        <option value=\"friendly\">Friendly</option>
                        <option value=\"academic\">Academic</option>
                        <option value=\"creative\">Creative</option>
                        <option value=\"simple\">Simple</option>
                    </select>
                    <textarea id=\"aiwa-rewrite-text\" class=\"aiwa-textarea\" rows=\"5\" placeholder=\"Text to rewrite\"></textarea>
                    <button id=\"aiwa-rewrite-btn\" class=\"aiwa-btn\">✏️ Rewrite</button>
                    <div id=\"aiwa-rewrite-result\"></div>
                </div>
            </div>
        </div>
    `;
    
    document.addEventListener('DOMContentLoaded', function() {
        document.body.insertAdjacentHTML('beforeend', html);
        
        const btn = document.getElementById('aiwa-floating-btn');
        const panel = document.getElementById('aiwa-panel');
        const closeBtn = document.querySelector('.aiwa-close');
        
        // Toggle panel
        btn.addEventListener('click', () => {
            panel.classList.toggle('open');
        });
        
        closeBtn.addEventListener('click', () => {
            panel.classList.remove('open');
        });
        
        // Tab switching
        document.querySelectorAll('.aiwa-tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.aiwa-tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.aiwa-tab-content').forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById('aiwa-tab-' + btn.dataset.tab).classList.add('active');
            });
        });
        
        // Auto-fill with selected text
        document.addEventListener('selectionchange', () => {
            const selection = window.getSelection().toString().trim();
            if (selection && panel.classList.contains('open')) {
                const activeTab = document.querySelector('.aiwa-tab-btn.active').dataset.tab;
                document.getElementById('aiwa-' + activeTab + '-text').value = selection;
            }
        });
        
        // Improve text
        document.getElementById('aiwa-improve-btn').addEventListener('click', async function() {
            if (!hasApiKey) {
                alert('Please add API key in settings');
                window.open(settingsUrl, '_blank');
                return;
            }
            
            const text = document.getElementById('aiwa-improve-text').value.trim();
            if (!text) return alert('Please enter text');
            
            const btn = this;
            const resultDiv = document.getElementById('aiwa-improve-result');
            btn.disabled = true;
            btn.textContent = 'Improving...';
            resultDiv.innerHTML = '<div class=\"aiwa-loading\">🔄 Processing...</div>';
            
            try {
                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'aiwa_improve_text',
                        nonce: nonce,
                        text: text,
                        language: '{$preferred_language}'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = '<div class=\"aiwa-result\"><strong>Improved:</strong><br>' + data.data + '</div>';
                } else {
                    resultDiv.innerHTML = '<div class=\"aiwa-result\" style=\"border-left-color:#f59e0b;\">Error: ' + (data.data.message || 'Failed') + '</div>';
                }
            } catch (err) {
                resultDiv.innerHTML = '<div class=\"aiwa-result\" style=\"border-left-color:#ef4444;\">Network error</div>';
            } finally {
                btn.disabled = false;
                btn.textContent = '✨ Improve Text';
            }
        });
        
        // Grammar check
        document.getElementById('aiwa-grammar-btn').addEventListener('click', async function() {
            if (!hasApiKey) {
                alert('Please add API key');
                window.open(settingsUrl, '_blank');
                return;
            }
            
            const text = document.getElementById('aiwa-grammar-text').value.trim();
            if (!text) return alert('Please enter text');
            
            const btn = this;
            const resultDiv = document.getElementById('aiwa-grammar-result');
            btn.disabled = true;
            btn.textContent = 'Checking...';
            resultDiv.innerHTML = '<div class=\"aiwa-loading\">🔄 Analyzing...</div>';
            
            try {
                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'aiwa_check_grammar',
                        nonce: nonce,
                        text: text,
                        language: '{$preferred_language}'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const result = data.data;
                    let html = '<div class=\"aiwa-result\"><strong>Score: ' + result.overall_score + '/100</strong><br>' + result.summary;
                    
                    if (result.errors && result.errors.length > 0) {
                        html += '<hr style=\"margin:10px 0;\">';
                        result.errors.forEach(err => {
                            html += '<div style=\"margin:10px 0;padding:10px;background:#fff;border-left:4px solid #f59e0b;border-radius:4px;\">';
                            html += '<strong style=\"color:#f59e0b;text-transform:uppercase;font-size:11px;\">' + err.type + '</strong><br>';
                            html += '<div style=\"margin:5px 0;\"><strong>Original:</strong> ' + err.original + '</div>';
                            html += '<div style=\"color:#16a34a;\"><strong>Fix:</strong> ' + err.suggestion + '</div>';
                            if (err.explanation) html += '<div style=\"font-size:12px;color:#64748b;margin-top:5px;\">' + err.explanation + '</div>';
                            html += '</div>';
                        });
                    } else {
                        html += '<div style=\"color:#16a34a;margin-top:10px;\">✓ No errors found!</div>';
                    }
                    
                    html += '</div>';
                    resultDiv.innerHTML = html;
                } else {
                    resultDiv.innerHTML = '<div class=\"aiwa-result\" style=\"border-left-color:#f59e0b;\">Error: ' + (data.data.message || 'Failed') + '</div>';
                }
            } catch (err) {
                resultDiv.innerHTML = '<div class=\"aiwa-result\" style=\"border-left-color:#ef4444;\">Network error</div>';
            } finally {
                btn.disabled = false;
                btn.textContent = '✓ Check Grammar';
            }
        });
        
        // Rewrite
        document.getElementById('aiwa-rewrite-btn').addEventListener('click', async function() {
            if (!hasApiKey) {
                alert('Please add API key');
                window.open(settingsUrl, '_blank');
                return;
            }
            
            const text = document.getElementById('aiwa-rewrite-text').value.trim();
            const tone = document.getElementById('aiwa-tone').value;
            if (!text) return alert('Please enter text');
            
            const btn = this;
            const resultDiv = document.getElementById('aiwa-rewrite-result');
            btn.disabled = true;
            btn.textContent = 'Rewriting...';
            resultDiv.innerHTML = '<div class=\"aiwa-loading\">🔄 Rewriting...</div>';
            
            try {
                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        action: 'aiwa_rewrite_content',
                        nonce: nonce,
                        text: text,
                        tone: tone,
                        language: '{$preferred_language}'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = '<div class=\"aiwa-result\"><strong>Rewritten:</strong><br>' + data.data + '</div>';
                } else {
                    resultDiv.innerHTML = '<div class=\"aiwa-result\" style=\"border-left-color:#f59e0b;\">Error: ' + (data.data.message || 'Failed') + '</div>';
                }
            } catch (err) {
                resultDiv.innerHTML = '<div class=\"aiwa-result\" style=\"border-left-color:#ef4444;\">Network error</div>';
            } finally {
                btn.disabled = false;
                btn.textContent = '✏️ Rewrite';
            }
        });
    });
})();
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
        AIWA_Floating_Panel::get_instance();
        
        add_action('wp_ajax_aiwa_check_grammar', array($this, 'ajax_check_grammar'));
        add_action('wp_ajax_aiwa_rewrite_content', array($this, 'ajax_rewrite_content'));
        add_action('wp_ajax_aiwa_improve_text', array($this, 'ajax_improve_text'));
    }
    
    public function ajax_check_grammar() {
        check_ajax_referer('aiwa_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_gemini_api_key', true);
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'Please add API key'));
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
            wp_send_json_error(array('message' => 'Please add API key'));
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
    
    public function ajax_improve_text() {
        check_ajax_referer('aiwa_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_gemini_api_key', true);
        
        if (empty($api_key)) {
            wp_send_json_error(array('message' => 'Please add API key'));
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
