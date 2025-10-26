<?php
/**
 * Plugin Name: WP Content Helper
 * Plugin URI: https://github.com/krtrimtech/wp-content-helper
 * Description: Grammarly-like AI writing assistant with Google Gemini API. Shows green icon in editor.
 * Version: 1.3.0
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
    
    public function improve_text($text, $language = 'en') {
        $prompt = "Improve this text for better clarity, grammar, and flow. Return ONLY the improved text:\n\n{$text}";
        return $this->make_request($prompt, 0.7);
    }
    
    public function check_grammar($text, $language = 'en') {
        $prompt = "Check grammar and provide suggestions in JSON: {\"errors\": [{\"type\": \"grammar\", \"original\": \"text\", \"suggestion\": \"fix\", \"explanation\": \"why\"}], \"score\": 85}\n\nText: {$text}";
        
        $result = $this->make_request($prompt, 0.3);
        if (is_wp_error($result)) return $result;
        
        preg_match('/\{[\s\S]*\}/', $result, $matches);
        if ($matches) {
            $parsed = json_decode($matches[0], true);
            if ($parsed) return $parsed;
        }
        
        return array('errors' => array(), 'score' => 90);
    }
    
    public function rewrite_content($text, $tone = 'professional', $language = 'en') {
        $prompt = "Rewrite this text in a {$tone} tone:\n\n{$text}";
        return $this->make_request($prompt, 0.7);
    }
}

// ========================================
// SETTINGS PAGE
// ========================================
class AIWA_Settings {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_post_aiwa_save', array($this, 'save'));
    }
    
    public function add_menu() {
        add_menu_page('AI Assistant', 'AI Assistant', 'edit_posts', 'wp-content-helper', array($this, 'render'), 'dashicons-edit', 30);
    }
    
    public function render() {
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_api_key', true);
        
        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success"><p><strong>Saved!</strong></p></div>';
        }
        ?>
        <div class="wrap">
            <h1>🤖 AI Writing Assistant</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="aiwa_save">
                <?php wp_nonce_field('aiwa'); ?>
                <table class="form-table">
                    <tr>
                        <th>Gemini API Key</th>
                        <td>
                            <input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                            <p class="description">Get free key: <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a></p>
                            <?php if ($api_key): ?>
                                <p style="color:#16a34a;font-weight:600;">✓ Configured</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <hr>
            <h2>How to Use</h2>
            <p>1. Add API key above<br>2. Go to any post/page<br>3. <strong>Look for GREEN 🟢 button at bottom-right</strong><br>4. Click it to use AI!</p>
        </div>
        <?php
    }
    
    public function save() {
        check_admin_referer('aiwa');
        $user_id = get_current_user_id();
        if (isset($_POST['api_key'])) {
            update_user_meta($user_id, 'aiwa_api_key', sanitize_text_field($_POST['api_key']));
        }
        wp_redirect(admin_url('admin.php?page=wp-content-helper&saved=1'));
        exit;
    }
}

// ========================================
// FLOATING GREEN BUTTON (Like Grammarly!)
// ========================================
class AIWA_Editor_Button {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_footer', array($this, 'add_button'));
    }
    
    public function add_button() {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->base, array('post', 'page'))) {
            return;
        }
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_api_key', true);
        ?>
        <style>
            #aiwa-green-btn {
                position: fixed !important;
                bottom: 20px !important;
                right: 20px !important;
                width: 70px !important;
                height: 70px !important;
                background: #16a34a !important;
                border-radius: 50% !important;
                border: 4px solid #fff !important;
                box-shadow: 0 4px 20px rgba(22, 163, 74, 0.5) !important;
                cursor: pointer !important;
                z-index: 999999 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 32px !important;
                transition: all 0.3s !important;
                animation: aiwa-pulse 2s infinite !important;
            }
            #aiwa-green-btn:hover {
                transform: scale(1.1) !important;
                background: #15803d !important;
            }
            @keyframes aiwa-pulse {
                0%, 100% { box-shadow: 0 4px 20px rgba(22, 163, 74, 0.5); }
                50% { box-shadow: 0 4px 30px rgba(22, 163, 74, 0.8); }
            }
            #aiwa-modal {
                display: none;
                position: fixed !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                width: 500px !important;
                max-width: 90vw !important;
                max-height: 80vh !important;
                background: white !important;
                border-radius: 12px !important;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3) !important;
                z-index: 1000000 !important;
                overflow: hidden !important;
            }
            #aiwa-modal.show { display: block !important; }
            #aiwa-overlay {
                display: none;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                background: rgba(0,0,0,0.5) !important;
                z-index: 999999 !important;
            }
            #aiwa-overlay.show { display: block !important; }
            .aiwa-header {
                background: linear-gradient(135deg, #16a34a 0%, #15803d 100%) !important;
                color: white !important;
                padding: 20px !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
            }
            .aiwa-close {
                background: none !important;
                border: none !important;
                color: white !important;
                font-size: 28px !important;
                cursor: pointer !important;
                width: 30px !important;
                height: 30px !important;
            }
            .aiwa-body {
                padding: 20px !important;
                max-height: calc(80vh - 80px) !important;
                overflow-y: auto !important;
            }
            .aiwa-tabs {
                display: flex !important;
                gap: 10px !important;
                margin-bottom: 20px !important;
                border-bottom: 2px solid #e5e7eb !important;
            }
            .aiwa-tab {
                padding: 10px 15px !important;
                background: none !important;
                border: none !important;
                cursor: pointer !important;
                font-weight: 600 !important;
                color: #64748b !important;
                border-bottom: 3px solid transparent !important;
            }
            .aiwa-tab.active {
                color: #16a34a !important;
                border-bottom-color: #16a34a !important;
            }
            .aiwa-content {
                display: none;
            }
            .aiwa-content.active {
                display: block !important;
            }
            .aiwa-textarea {
                width: 100% !important;
                padding: 12px !important;
                border: 2px solid #e5e7eb !important;
                border-radius: 8px !important;
                font-family: inherit !important;
                font-size: 14px !important;
                resize: vertical !important;
            }
            .aiwa-btn {
                width: 100% !important;
                padding: 12px !important;
                background: #16a34a !important;
                color: white !important;
                border: none !important;
                border-radius: 8px !important;
                cursor: pointer !important;
                font-weight: 600 !important;
                margin-top: 10px !important;
            }
            .aiwa-btn:hover { background: #15803d !important; }
            .aiwa-btn:disabled { background: #ccc !important; cursor: not-allowed !important; }
            .aiwa-result {
                margin-top: 15px !important;
                padding: 15px !important;
                background: #f0fdf4 !important;
                border-radius: 8px !important;
                border-left: 4px solid #16a34a !important;
            }
            .aiwa-select {
                width: 100% !important;
                padding: 10px !important;
                border: 2px solid #e5e7eb !important;
                border-radius: 8px !important;
                margin-bottom: 10px !important;
            }
        </style>

        <div id="aiwa-overlay"></div>
        <button id="aiwa-green-btn" title="AI Writing Assistant">🤖</button>
        
        <div id="aiwa-modal">
            <div class="aiwa-header">
                <span style="font-size:18px;font-weight:600;">🤖 AI Writing Assistant</span>
                <button class="aiwa-close">×</button>
            </div>
            <div class="aiwa-body">
                <div class="aiwa-tabs">
                    <button class="aiwa-tab active" data-tab="improve">✨ Improve</button>
                    <button class="aiwa-tab" data-tab="grammar">✓ Grammar</button>
                    <button class="aiwa-tab" data-tab="rewrite">✏️ Rewrite</button>
                </div>
                
                <div id="tab-improve" class="aiwa-content active">
                    <p style="margin:0 0 10px;color:#64748b;font-size:13px;">Paste text to improve it instantly</p>
                    <textarea id="text-improve" class="aiwa-textarea" rows="5" placeholder="Paste your text here..."></textarea>
                    <button id="btn-improve" class="aiwa-btn">✨ Improve Text</button>
                    <div id="result-improve"></div>
                </div>
                
                <div id="tab-grammar" class="aiwa-content">
                    <p style="margin:0 0 10px;color:#64748b;font-size:13px;">Check grammar and spelling</p>
                    <textarea id="text-grammar" class="aiwa-textarea" rows="5" placeholder="Paste text to check..."></textarea>
                    <button id="btn-grammar" class="aiwa-btn">✓ Check Grammar</button>
                    <div id="result-grammar"></div>
                </div>
                
                <div id="tab-rewrite" class="aiwa-content">
                    <p style="margin:0 0 10px;color:#64748b;font-size:13px;">Rewrite in different tone</p>
                    <select id="tone" class="aiwa-select">
                        <option value="professional">Professional</option>
                        <option value="casual">Casual</option>
                        <option value="friendly">Friendly</option>
                        <option value="academic">Academic</option>
                        <option value="creative">Creative</option>
                        <option value="simple">Simple & Clear</option>
                    </select>
                    <textarea id="text-rewrite" class="aiwa-textarea" rows="5" placeholder="Text to rewrite..."></textarea>
                    <button id="btn-rewrite" class="aiwa-btn">✏️ Rewrite</button>
                    <div id="result-rewrite"></div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            const hasKey = <?php echo $api_key ? 'true' : 'false'; ?>;
            const settingsUrl = '<?php echo admin_url('admin.php?page=wp-content-helper'); ?>';
            
            // Toggle modal
            $('#aiwa-green-btn, #aiwa-overlay').click(function() {
                $('#aiwa-modal, #aiwa-overlay').toggleClass('show');
            });
            
            $('.aiwa-close').click(function() {
                $('#aiwa-modal, #aiwa-overlay').removeClass('show');
            });
            
            // Tab switching
            $('.aiwa-tab').click(function() {
                const tab = $(this).data('tab');
                $('.aiwa-tab').removeClass('active');
                $('.aiwa-content').removeClass('active');
                $(this).addClass('active');
                $('#tab-' + tab).addClass('active');
            });
            
            // Auto-fill from selection
            $(document).on('selectionchange', function() {
                const sel = window.getSelection().toString().trim();
                if (sel && $('#aiwa-modal').hasClass('show')) {
                    const activeTab = $('.aiwa-tab.active').data('tab');
                    $('#text-' + activeTab).val(sel);
                }
            });
            
            // Improve
            $('#btn-improve').click(function() {
                if (!hasKey) {
                    alert('Please add API key first!');
                    window.open(settingsUrl, '_blank');
                    return;
                }
                
                const text = $('#text-improve').val().trim();
                if (!text) return alert('Please enter text');
                
                const btn = $(this);
                btn.prop('disabled', true).text('Improving...');
                $('#result-improve').html('<div style="text-align:center;color:#16a34a;">🔄 Processing...</div>');
                
                $.post(ajaxurl, {
                    action: 'aiwa_improve',
                    nonce: '<?php echo wp_create_nonce('aiwa'); ?>',
                    text: text
                }, function(res) {
                    if (res.success) {
                        $('#result-improve').html('<div class="aiwa-result"><strong>Improved:</strong><br>' + res.data + '</div>');
                    } else {
                        $('#result-improve').html('<div class="aiwa-result" style="border-left-color:#f59e0b;">Error: ' + (res.data || 'Failed') + '</div>');
                    }
                }).fail(function() {
                    $('#result-improve').html('<div class="aiwa-result" style="border-left-color:#ef4444;">Network error</div>');
                }).always(function() {
                    btn.prop('disabled', false).text('✨ Improve Text');
                });
            });
            
            // Grammar
            $('#btn-grammar').click(function() {
                if (!hasKey) {
                    alert('Please add API key first!');
                    window.open(settingsUrl, '_blank');
                    return;
                }
                
                const text = $('#text-grammar').val().trim();
                if (!text) return alert('Please enter text');
                
                const btn = $(this);
                btn.prop('disabled', true).text('Checking...');
                $('#result-grammar').html('<div style="text-align:center;color:#16a34a;">🔄 Analyzing...</div>');
                
                $.post(ajaxurl, {
                    action: 'aiwa_grammar',
                    nonce: '<?php echo wp_create_nonce('aiwa'); ?>',
                    text: text
                }, function(res) {
                    if (res.success) {
                        let html = '<div class="aiwa-result"><strong>Score: ' + (res.data.score || 90) + '/100</strong>';
                        if (res.data.errors && res.data.errors.length > 0) {
                            html += '<hr style="margin:10px 0;">';
                            res.data.errors.forEach(function(err) {
                                html += '<div style="margin:10px 0;padding:8px;background:#fff;border-left:3px solid #f59e0b;border-radius:4px;">';
                                html += '<strong style="color:#f59e0b;">' + err.type + '</strong><br>';
                                html += '<div style="margin:5px 0;"><strong>Original:</strong> ' + err.original + '</div>';
                                html += '<div style="color:#16a34a;"><strong>Fix:</strong> ' + err.suggestion + '</div>';
                                html += '</div>';
                            });
                        } else {
                            html += '<div style="color:#16a34a;margin-top:10px;">✓ No errors found!</div>';
                        }
                        html += '</div>';
                        $('#result-grammar').html(html);
                    } else {
                        $('#result-grammar').html('<div class="aiwa-result" style="border-left-color:#f59e0b;">Error: ' + (res.data || 'Failed') + '</div>');
                    }
                }).fail(function() {
                    $('#result-grammar').html('<div class="aiwa-result" style="border-left-color:#ef4444;">Network error</div>');
                }).always(function() {
                    btn.prop('disabled', false).text('✓ Check Grammar');
                });
            });
            
            // Rewrite
            $('#btn-rewrite').click(function() {
                if (!hasKey) {
                    alert('Please add API key first!');
                    window.open(settingsUrl, '_blank');
                    return;
                }
                
                const text = $('#text-rewrite').val().trim();
                const tone = $('#tone').val();
                if (!text) return alert('Please enter text');
                
                const btn = $(this);
                btn.prop('disabled', true).text('Rewriting...');
                $('#result-rewrite').html('<div style="text-align:center;color:#16a34a;">🔄 Rewriting...</div>');
                
                $.post(ajaxurl, {
                    action: 'aiwa_rewrite',
                    nonce: '<?php echo wp_create_nonce('aiwa'); ?>',
                    text: text,
                    tone: tone
                }, function(res) {
                    if (res.success) {
                        $('#result-rewrite').html('<div class="aiwa-result"><strong>Rewritten:</strong><br>' + res.data + '</div>');
                    } else {
                        $('#result-rewrite').html('<div class="aiwa-result" style="border-left-color:#f59e0b;">Error: ' + (res.data || 'Failed') + '</div>');
                    }
                }).fail(function() {
                    $('#result-rewrite').html('<div class="aiwa-result" style="border-left-color:#ef4444;">Network error</div>');
                }).always(function() {
                    btn.prop('disabled', false).text('✏️ Rewrite');
                });
            });
        });
        </script>
        <?php
    }
}

// ========================================
// AJAX HANDLERS
// ========================================
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
    
    public function improve() {
-        check_ajax_referer('aiwa');
+        check_ajax_referer('aiwa', 'nonce');
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_api_key', true);
        
        if (!$api_key) {
            wp_send_json_error('No API key');
        }
        
        $text = isset($_POST['text']) ? sanitize_textarea_field($_POST['text']) : '';
        if (!$text) {
            wp_send_json_error('No text');
        }
        
        $gemini = new AIWA_Gemini_API($api_key);
        $result = $gemini->improve_text($text);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    public function grammar() {
-        check_ajax_referer('aiwa');
+        check_ajax_referer('aiwa', 'nonce');
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_api_key', true);
        
        if (!$api_key) {
            wp_send_json_error('No API key');
        }
        
        $text = isset($_POST['text']) ? sanitize_textarea_field($_POST['text']) : '';
        if (!$text) {
            wp_send_json_error('No text');
        }
        
        $gemini = new AIWA_Gemini_API($api_key);
        $result = $gemini->check_grammar($text);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    public function rewrite() {
-        check_ajax_referer('aiwa');
+        check_ajax_referer('aiwa', 'nonce');
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_api_key', true);
        
        if (!$api_key) {
            wp_send_json_error('No API key');
        }
        
        $text = isset($_POST['text']) ? sanitize_textarea_field($_POST['text']) : '';
        $tone = isset($_POST['tone']) ? sanitize_text_field($_POST['tone']) : 'professional';
        
        if (!$text) {
            wp_send_json_error('No text');
        }
        
        $gemini = new AIWA_Gemini_API($api_key);
        $result = $gemini->rewrite_content($text, $tone);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
}

// Initialize
AIWA_Settings::get_instance();
AIWA_Editor_Button::get_instance();
AIWA_Ajax::get_instance();