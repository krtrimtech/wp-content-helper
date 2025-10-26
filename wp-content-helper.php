<?php

/**
 * Plugin Name: WP Content Helper
 * Plugin URI: https://github.com/krtrimtech/wp-content-helper
 * Description: Grammarly-like AI writing assistant. Auto-detects language and replaces text directly in editor.
 * Version: 1.5.0
 * Author: Krtrim (Shyanukant Rathi)
 * Author URI: https://shyanukant.github.io/
 * License: GPL v2 or later
 * Text Domain: wp-content-helper
 */

if (!defined('ABSPATH')) exit;

// ========================================
// GEMINI API CLASS
// ========================================
class AIWA_Gemini_API
{
    private $api_key;
    private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private $model = 'gemini-2.0-flash-exp';

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    private function make_request($prompt, $temperature = 0.7)
    {
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

    public function improve_text($text)
    {
        $prompt = "Improve this text for better clarity, grammar, and flow. Detect the language and respond in THE SAME LANGUAGE. Return ONLY the improved text:\n\n{$text}";
        return $this->make_request($prompt, 0.7);
    }

    public function check_grammar($text)
    {
        $prompt = "Detect language and check grammar. Provide in JSON format in the DETECTED LANGUAGE:
{\"language\": \"detected language\", \"errors\": [{\"type\": \"grammar\", \"original\": \"text\", \"suggestion\": \"fix\", \"explanation\": \"why\"}], \"score\": 85}

Text: {$text}";

        $result = $this->make_request($prompt, 0.3);
        if (is_wp_error($result)) return $result;

        preg_match('/\{[\s\S]*\}/', $result, $matches);
        if ($matches) {
            $parsed = json_decode($matches[0], true);
            if ($parsed) return $parsed;
        }

        return array('language' => 'Unknown', 'errors' => array(), 'score' => 90);
    }

    public function rewrite_content($text, $tone = 'professional')
    {
        $prompt = "Detect language and rewrite in {$tone} tone IN THE SAME LANGUAGE:\n\n{$text}";
        return $this->make_request($prompt, 0.7);
    }
}

// ========================================
// SETTINGS PAGE
// ========================================
class AIWA_Settings
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_post_aiwa_save', array($this, 'save'));
    }

    public function add_menu()
    {
        // Settings page
        add_menu_page(
            'AI Assistant',
            'AI Assistant',
            'edit_posts',
            'wp-content-helper',
            array($this, 'render'),
            'dashicons-edit',
            30
        );

        // Instructions & About submenu
        add_submenu_page(
            'wp-content-helper',
            'Instructions & About',
            'Instructions',
            'edit_posts',
            'wp-content-helper-instructions',
            array($this, 'render_instructions')
        );
    }

    public function render()
    {
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_api_key', true);

        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success"><p><strong>Settings saved!</strong></p></div>';
        }
?>
        <div class="wrap">
            <h1>🤖 AI Writing Assistant - Settings</h1>
            <p>Auto-detects language (Hindi, English, etc.) and corrects directly in editor</p>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="aiwa_save">
                <?php wp_nonce_field('aiwa'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="api_key">Google Gemini API Key</label>
                        </th>
                        <td>
                            <input type="text" name="api_key" id="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                            <p class="description">
                                Get your free API key from:
                                <a href="https://aistudio.google.com/app/apikey" target="_blank" style="font-weight:600;">
                                    Google AI Studio →
                                </a>
                            </p>
                            <?php if ($api_key): ?>
                                <p style="color:#16a34a;font-weight:600;margin-top:10px;">✓ API Key is configured!</p>
                            <?php else: ?>
                                <p style="color:#f59e0b;font-weight:600;margin-top:10px;">⚠ Please add your API key to use AI features</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save Settings'); ?>
            </form>

            <hr style="margin:40px 0;">

            <div style="background:#f0f6fc;padding:20px;border-radius:8px;border-left:4px solid #667eea;">
                <h2 style="margin-top:0;">📖 Quick Start Guide</h2>
                <ol style="line-height:1.8;">
                    <li><strong>Add your API key above</strong> and save</li>
                    <li><strong>Go to any Post/Page editor</strong></li>
                    <li><strong>Look for the green 🤖 button</strong> at bottom-right corner</li>
                    <li><strong>Select text</strong> in your editor</li>
                    <li><strong>Click the 🤖 button</strong> to open AI Assistant</li>
                    <li><strong>Choose action:</strong> Improve, Grammar Check, or Rewrite</li>
                    <li><strong>Click "Replace Selected Text"</strong> to apply changes</li>
                </ol>

                <p style="margin-bottom:0;">
                    <a href="<?php echo admin_url('admin.php?page=wp-content-helper-instructions'); ?>" class="button button-primary">
                        📚 View Full Instructions & About
                    </a>
                </p>
            </div>
        </div>
    <?php
    }

    public function render_instructions()
    {
    ?>
        <div class="wrap">
            <h1>🤖 WP Content Helper - Instructions & About</h1>

            <div style="max-width:900px;">

                <!-- About Section -->
                <div style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;padding:30px;border-radius:12px;margin:20px 0;">
                    <h2 style="color:white;margin-top:0;">About WP Content Helper</h2>
                    <p style="font-size:16px;line-height:1.6;margin:0;">
                        A powerful Grammarly-like AI writing assistant for WordPress that helps you write better content in any language.
                        Uses Google Gemini AI to automatically detect your language and provide intelligent suggestions.
                    </p>
                </div>

                <!-- Features -->
                <div style="background:#fff;padding:25px;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                    <h2>✨ Features</h2>
                    <ul style="line-height:1.8;font-size:15px;">
                        <li>🌍 <strong>Multi-language Support</strong> - Auto-detects Hindi, English, and other languages</li>
                        <li>✏️ <strong>AI Text Improvement</strong> - Enhance clarity, grammar, and flow</li>
                        <li>✓ <strong>Grammar Checking</strong> - Find and fix errors with explanations</li>
                        <li>🎨 <strong>Content Rewriting</strong> - Change tone: professional, casual, friendly, etc.</li>
                        <li>🔄 <strong>Direct Text Replacement</strong> - Works like Grammarly - replaces text in-place</li>
                        <li>🔐 <strong>Private API Keys</strong> - Each user uses their own Gemini API key</li>
                        <li>🎯 <strong>Works Everywhere</strong> - Compatible with Gutenberg, Classic Editor, and Elementor</li>
                    </ul>
                </div>

                <!-- How to Use -->
                <div style="background:#fff;padding:25px;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                    <h2>📖 How to Use</h2>

                    <h3 style="color:#667eea;">Step 1: Get Your API Key</h3>
                    <ol style="line-height:1.8;">
                        <li>Visit <a href="https://aistudio.google.com/app/apikey" target="_blank" style="font-weight:600;">Google AI Studio</a></li>
                        <li>Sign in with your Google account</li>
                        <li>Click "Create API Key"</li>
                        <li>Copy the API key</li>
                    </ol>

                    <h3 style="color:#667eea;">Step 2: Configure Plugin</h3>
                    <ol style="line-height:1.8;">
                        <li>Go to <strong>WordPress Admin → AI Assistant → Settings</strong></li>
                        <li>Paste your API key in the field</li>
                        <li>Click "Save Settings"</li>
                    </ol>

                    <h3 style="color:#667eea;">Step 3: Use AI Features</h3>
                    <ol style="line-height:1.8;">
                        <li><strong>Open any post/page editor</strong> (Gutenberg or Classic)</li>
                        <li><strong>Write or select text</strong> you want to improve</li>
                        <li><strong>Click the green 🤖 button</strong> at bottom-right corner</li>
                        <li><strong>The selected text will auto-fill</strong> in the modal</li>
                        <li><strong>Choose an action:</strong>
                            <ul style="margin-top:8px;">
                                <li><strong>✨ Improve</strong> - Enhance clarity and grammar</li>
                                <li><strong>✓ Grammar</strong> - Check for errors and get suggestions</li>
                                <li><strong>✏️ Rewrite</strong> - Change tone (professional, casual, etc.)</li>
                            </ul>
                        </li>
                        <li><strong>Click "📝 Replace Selected Text"</strong> to apply changes</li>
                    </ol>

                    <div style="background:#f0fdf4;padding:15px;border-left:4px solid #16a34a;border-radius:4px;margin-top:20px;">
                        <strong style="color:#16a34a;">💡 Pro Tip:</strong> The AI automatically detects your language!
                        Write in Hindi (हिंदी), English, or any supported language and get suggestions in the same language.
                    </div>
                </div>

                <!-- Screenshots/Visual Guide -->
                <div style="background:#fff;padding:25px;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                    <h2>📸 Visual Guide</h2>

                    <div style="margin:20px 0;">
                        <h3 style="font-size:16px;">1. Green AI Button Location</h3>
                        <div style="background:#f8f9fa;padding:40px;text-align:center;border:2px dashed #e5e7eb;border-radius:8px;">
                            <div style="display:inline-block;width:70px;height:70px;background:#16a34a;border-radius:50%;border:4px solid #fff;box-shadow:0 4px 20px rgba(22,163,74,0.5);font-size:32px;display:flex;align-items:center;justify-content:center;">
                                🤖
                            </div>
                            <p style="margin-top:15px;color:#64748b;font-style:italic;">Green button appears at bottom-right of editor</p>
                        </div>
                    </div>

                    <div style="margin:20px 0;">
                        <h3 style="font-size:16px;">2. AI Assistant Modal</h3>
                        <div style="background:#f8f9fa;padding:20px;border:2px solid #e5e7eb;border-radius:8px;">
                            <div style="background:linear-gradient(135deg,#16a34a 0%,#15803d 100%);color:white;padding:15px;border-radius:8px 8px 0 0;font-weight:600;">
                                🤖 AI Writing Assistant
                            </div>
                            <div style="background:white;padding:20px;border-radius:0 0 8px 8px;">
                                <div style="display:flex;gap:10px;border-bottom:2px solid #e5e7eb;padding-bottom:10px;margin-bottom:15px;">
                                    <span style="padding:8px 12px;background:#16a34a;color:white;border-radius:4px;font-size:13px;font-weight:600;">✨ Improve</span>
                                    <span style="padding:8px 12px;color:#64748b;font-size:13px;font-weight:600;">✓ Grammar</span>
                                    <span style="padding:8px 12px;color:#64748b;font-size:13px;font-weight:600;">✏️ Rewrite</span>
                                </div>
                                <p style="color:#64748b;font-size:13px;margin:10px 0;">Select text in editor, then improve</p>
                                <div style="border:2px solid #e5e7eb;padding:10px;border-radius:6px;color:#94a3b8;font-style:italic;">
                                    Your selected text appears here...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Supported Languages -->
                <div style="background:#fff;padding:25px;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                    <h2>🌍 Supported Languages</h2>
                    <p>The AI automatically detects and responds in these languages:</p>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;margin-top:15px;">
                        <div style="padding:10px;background:#f8f9fa;border-radius:6px;">🇬🇧 English</div>
                        <div style="padding:10px;background:#f8f9fa;border-radius:6px;">🇮🇳 Hindi (हिंदी)</div>
                        <div style="padding:10px;background:#f8f9fa;border-radius:6px;">🇮🇳 Bengali (বাংলা)</div>
                        <div style="padding:10px;background:#f8f9fa;border-radius:6px;">🇮🇳 Punjabi (ਪੰਜਾਬੀ)</div>
                        <div style="padding:10px;background:#f8f9fa;border-radius:6px;">🇮🇳 Telugu (తెలుగు)</div>
                        <div style="padding:10px;background:#f8f9fa;border-radius:6px;">🇮🇳 Marathi (मराठी)</div>
                        <div style="padding:10px;background:#f8f9fa;border-radius:6px;">🇪🇸 Spanish</div>
                        <div style="padding:10px;background:#f8f9fa;border-radius:6px;">🇫🇷 French</div>
                        <div style="padding:10px;background:#f8f9fa;border-radius:6px;">🇩🇪 German</div>
                        <div style="padding:10px;background:#f8f9fa;border-radius:6px;">+ many more...</div>
                    </div>
                </div>

                <!-- Troubleshooting -->
                <div style="background:#fff;padding:25px;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                    <h2>🔧 Troubleshooting</h2>

                    <div style="margin:15px 0;">
                        <h4 style="color:#f59e0b;margin-bottom:8px;">❓ Green button not showing?</h4>
                        <ul style="margin:5px 0 15px 20px;line-height:1.6;">
                            <li>Make sure you're on a Post or Page edit screen</li>
                            <li>Clear browser cache and reload</li>
                            <li>Check if plugin is activated</li>
                        </ul>
                    </div>

                    <div style="margin:15px 0;">
                        <h4 style="color:#f59e0b;margin-bottom:8px;">❓ "Network error" message?</h4>
                        <ul style="margin:5px 0 15px 20px;line-height:1.6;">
                            <li>Check if your API key is valid</li>
                            <li>Make sure you have internet connection</li>
                            <li>Try regenerating your API key from Google AI Studio</li>
                        </ul>
                    </div>

                    <div style="margin:15px 0;">
                        <h4 style="color:#f59e0b;margin-bottom:8px;">❓ Text not replacing?</h4>
                        <ul style="margin:5px 0 15px 20px;line-height:1.6;">
                            <li>Make sure you selected text before opening the modal</li>
                            <li>Click directly on the "Replace Selected Text" button</li>
                            <li>Try selecting text again and reopening the modal</li>
                        </ul>
                    </div>
                </div>

                <!-- About & Credits -->
                <div style="background:#fff;padding:25px;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                    <h2>ℹ️ About & Credits</h2>

                    <table style="width:100%;line-height:2;">
                        <tr>
                            <td style="width:150px;"><strong>Plugin Name:</strong></td>
                            <td>WP Content Helper</td>
                        </tr>
                        <tr>
                            <td><strong>Version:</strong></td>
                            <td>1.5.0</td>
                        </tr>
                        <tr>
                            <td><strong>Created by:</strong></td>
                            <td>
                                <a href="https://github.com/krtrimtech" target="_blank" style="font-weight:600;">Krtrim</a>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Contributor:</strong></td>
                            <td>
                                <a href="https://shyanukant.github.io/" target="_blank" style="font-weight:600;">Shyanukant Rathi</a>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>GitHub:</strong></td>
                            <td>
                                <a href="https://github.com/krtrimtech/wp-content-helper" target="_blank" style="font-weight:600;">
                                    github.com/krtrimtech/wp-content-helper →
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>License:</strong></td>
                            <td>GPL v2 or later</td>
                        </tr>
                        <tr>
                            <td><strong>AI Model:</strong></td>
                            <td>Google Gemini 2.0 Flash</td>
                        </tr>
                    </table>

                    <div style="margin-top:25px;padding:15px;background:#f0f6fc;border-left:4px solid #667eea;border-radius:4px;">
                        <p style="margin:0;"><strong>💝 Support Development:</strong></p>
                        <p style="margin:8px 0 0 0;">If you find this plugin helpful, please consider:</p>
                        <ul style="margin:8px 0 0 20px;">
                            <li>⭐ Star the project on <a href="https://github.com/krtrimtech/wp-content-helper" target="_blank">GitHub</a></li>
                            <li>📝 Leave a review</li>
                            <li>🐛 Report bugs or suggest features on GitHub</li>
                            <li>🔗 Share with others who might find it useful</li>
                        </ul>
                    </div>
                </div>

                <!-- API Key Info -->
                <div style="background:#fff;padding:25px;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                    <h2>🔑 About API Keys</h2>
                    <p><strong>Why do I need an API key?</strong></p>
                    <p>This plugin uses Google's Gemini AI to process your text. Each user needs their own free API key from Google to ensure:</p>
                    <ul style="line-height:1.8;">
                        <li>🔐 <strong>Privacy</strong> - Your content goes directly to Google, not through our servers</li>
                        <li>💰 <strong>No cost to you</strong> - Google provides generous free tier usage</li>
                        <li>⚡ <strong>Better performance</strong> - Direct API calls are faster</li>
                        <li>📊 <strong>Usage control</strong> - You control your own API usage limits</li>
                    </ul>

                    <p><strong>Is it really free?</strong></p>
                    <p>Yes! Google Gemini provides a generous free tier that includes:</p>
                    <ul>
                        <li>60 requests per minute</li>
                        <li>1,500 requests per day</li>
                        <li>1 million tokens per month (plenty for most users)</li>
                    </ul>

                    <p style="margin-top:15px;">
                        <a href="https://aistudio.google.com/app/apikey" target="_blank" class="button button-primary">
                            Get Your Free API Key →
                        </a>
                    </p>
                </div>

                <!-- Contact & Support -->
                <div style="background:#fff;padding:25px;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                    <h2>📞 Contact & Support</h2>
                    <p>Need help or have questions?</p>
                    <ul style="line-height:2;">
                        <li>🐛 <strong>Report Issues:</strong> <a href="https://github.com/krtrimtech/wp-content-helper/issues" target="_blank">GitHub Issues</a></li>
                        <li>💬 <strong>Discussions:</strong> <a href="https://github.com/krtrimtech/wp-content-helper/discussions" target="_blank">GitHub Discussions</a></li>
                        <li>📧 <strong>Email:</strong> <a href="mailto:contact@krtrim.com">contact@krtrim.com</a></li>
                        <li>🌐 <strong>Website:</strong> <a href="https://shyanukant.github.io/" target="_blank">shyanukant.github.io</a></li>
                    </ul>
                </div>

            </div>
        </div>

        <style>
            .wrap h2 {
                margin-top: 25px;
                color: #1e293b;
            }

            .wrap h3 {
                margin-top: 20px;
                font-size: 18px;
            }

            .wrap h4 {
                margin-top: 15px;
                font-size: 16px;
            }

            .wrap a {
                color: #667eea;
                text-decoration: none;
            }

            .wrap a:hover {
                color: #5568d3;
                text-decoration: underline;
            }

            .wrap code {
                background: #f1f5f9;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 13px;
            }
        </style>
    <?php
    }

    public function save()
    {
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
// FLOATING GREEN BUTTON (Keep the rest of your existing code)
// ========================================
class AIWA_Editor_Button
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_footer', array($this, 'add_button'));
    }

    public function add_button()
    {
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
                transition: all 0.3s !important;
                animation: aiwa-pulse 2s infinite !important;
                overflow: hidden !important;
            }

            #aiwa-green-btn img {
                width: 48px !important;
                height: 48px !important;
                pointer-events: none !important;
            }

            #aiwa-green-btn:hover {
                transform: scale(1.1) !important;
                background: #15803d !important;
            }

            @keyframes aiwa-pulse {

                0%,
                100% {
                    box-shadow: 0 4px 20px rgba(22, 163, 74, 0.5);
                }

                50% {
                    box-shadow: 0 4px 30px rgba(22, 163, 74, 0.8);
                }
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
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
                z-index: 1000000 !important;
                overflow: hidden !important;
            }

            #aiwa-modal.show {
                display: block !important;
            }

            #aiwa-overlay {
                display: none;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                background: rgba(0, 0, 0, 0.5) !important;
                z-index: 999999 !important;
            }

            #aiwa-overlay.show {
                display: block !important;
            }

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

            .aiwa-btn:hover {
                background: #15803d !important;
            }

            .aiwa-btn:disabled {
                background: #ccc !important;
                cursor: not-allowed !important;
            }

            .aiwa-btn-apply {
                background: #667eea !important;
                margin-top: 10px !important;
            }

            .aiwa-btn-apply:hover {
                background: #5568d3 !important;
            }

            .aiwa-result {
                margin-top: 15px !important;
                padding: 15px !important;
                background: #f0fdf4 !important;
                border-radius: 8px !important;
                border-left: 4px solid #16a34a !important;
                line-height: 1.6 !important;
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
        <button id="aiwa-green-btn" title="AI Writing Assistant">
            <img src="https://img.icons8.com/fluency/48/ai-agent.png" alt="AI Assistant">
        </button>


        <div id="aiwa-modal">
            <div class="aiwa-header">
                <span style="font-size:18px;font-weight:600;"><img src="https://img.icons8.com/fluency/48/ai-agent.png" alt="AI Assistant"> AI Writing Assistant</span>
                <button class="aiwa-close">×</button>
            </div>
            <div class="aiwa-body">
                <div class="aiwa-tabs">
                    <button class="aiwa-tab active" data-tab="improve">✨ Improve</button>
                    <button class="aiwa-tab" data-tab="grammar">✓ Grammar</button>
                    <button class="aiwa-tab" data-tab="rewrite">✏️ Rewrite</button>
                </div>

                <div id="tab-improve" class="aiwa-content active">
                    <p style="margin:0 0 10px;color:#64748b;font-size:13px;">Select text in editor, then improve</p>
                    <textarea id="text-improve" class="aiwa-textarea" rows="5" placeholder="अपना टेक्स्ट चुनें / Select text..."></textarea>
                    <button id="btn-improve" class="aiwa-btn">✨ Improve Text</button>
                    <div id="result-improve"></div>
                </div>

                <div id="tab-grammar" class="aiwa-content">
                    <p style="margin:0 0 10px;color:#64748b;font-size:13px;">Auto-detects language</p>
                    <textarea id="text-grammar" class="aiwa-textarea" rows="5" placeholder="किसी भी भाषा में..."></textarea>
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
                const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
                const nonce = '<?php echo wp_create_nonce('aiwa'); ?>';

                let savedSelection = null;
                let savedRange = null;

                function saveSelection() {
                    const sel = window.getSelection();
                    if (sel.rangeCount > 0) {
                        savedRange = sel.getRangeAt(0);
                        savedSelection = sel.toString().trim();
                        return savedSelection;
                    }
                    return '';
                }

                function replaceAtSelection(newText) {
                    if (!savedRange) {
                        alert('No text selected. Please select text first.');
                        return false;
                    }

                    const sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(savedRange);

                    savedRange.deleteContents();

                    const textNode = document.createTextNode(newText);
                    savedRange.insertNode(textNode);

                    savedRange.setStartAfter(textNode);
                    savedRange.setEndAfter(textNode);
                    sel.removeAllRanges();
                    sel.addRange(savedRange);

                    return true;
                }

                $('#aiwa-green-btn').click(function() {
                    const selected = saveSelection();
                    $('#aiwa-modal, #aiwa-overlay').addClass('show');

                    if (selected) {
                        const activeTab = $('.aiwa-tab.active').data('tab');
                        $('#text-' + activeTab).val(selected);
                    }
                });

                $('#aiwa-overlay, .aiwa-close').click(function() {
                    $('#aiwa-modal, #aiwa-overlay').removeClass('show');
                });

                $('.aiwa-tab').click(function() {
                    const tab = $(this).data('tab');
                    $('.aiwa-tab').removeClass('active');
                    $('.aiwa-content').removeClass('active');
                    $(this).addClass('active');
                    $('#tab-' + tab).addClass('active');

                    if (savedSelection) {
                        $('#text-' + tab).val(savedSelection);
                    }
                });

                $(document).on('mouseup keyup', function() {
                    if ($('#aiwa-modal').hasClass('show')) {
                        const selected = window.getSelection().toString().trim();
                        if (selected) {
                            saveSelection();
                            const activeTab = $('.aiwa-tab.active').data('tab');
                            $('#text-' + activeTab).val(selected);
                        }
                    }
                });

                $('#btn-improve').click(function() {
                    if (!hasKey) {
                        alert('Please add API key first!');
                        window.open(settingsUrl, '_blank');
                        return;
                    }

                    const text = $('#text-improve').val().trim();
                    if (!text) return alert('Please select text first');

                    const btn = $(this);
                    btn.prop('disabled', true).text('Improving...');
                    $('#result-improve').html('<div style="text-align:center;color:#16a34a;">🔄 Processing...</div>');

                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'aiwa_improve',
                            nonce: nonce,
                            text: text
                        },
                        success: function(res) {
                            if (res.success) {
                                $('#result-improve').html(
                                    '<div class="aiwa-result">' + res.data + '</div>' +
                                    '<button class="aiwa-btn aiwa-btn-apply" data-text="' + res.data.replace(/"/g, '&quot;') + '">📝 Replace Selected Text</button>'
                                );

                                $('.aiwa-btn-apply').click(function() {
                                    const newText = $(this).data('text');
                                    if (replaceAtSelection(newText)) {
                                        alert('✓ Text replaced!');
                                        $('#aiwa-modal, #aiwa-overlay').removeClass('show');
                                    }
                                });
                            } else {
                                $('#result-improve').html('<div class="aiwa-result" style="border-left-color:#f59e0b;">Error: ' + (res.data || 'Failed') + '</div>');
                            }
                        },
                        error: function() {
                            $('#result-improve').html('<div class="aiwa-result" style="border-left-color:#ef4444;">Network error</div>');
                        },
                        complete: function() {
                            btn.prop('disabled', false).text('✨ Improve Text');
                        }
                    });
                });

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

                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'aiwa_grammar',
                            nonce: nonce,
                            text: text
                        },
                        success: function(res) {
                            if (res.success) {
                                const lang = res.data.language || 'Unknown';
                                let html = '<div class="aiwa-result">';
                                html += '<strong>Language: ' + lang + ' | Score: ' + (res.data.score || 90) + '/100</strong><hr style="margin:10px 0;">';

                                if (res.data.errors && res.data.errors.length > 0) {
                                    res.data.errors.forEach(function(err) {
                                        html += '<div style="margin:10px 0;padding:8px;background:#fff;border-left:3px solid #f59e0b;border-radius:4px;">';
                                        html += '<strong style="color:#f59e0b;">' + err.type + '</strong><br>';
                                        html += '<div><strong>Original:</strong> ' + err.original + '</div>';
                                        html += '<div style="color:#16a34a;"><strong>Fix:</strong> ' + err.suggestion + '</div>';
                                        html += '</div>';
                                    });
                                } else {
                                    html += '<div style="color:#16a34a;">✓ No errors found!</div>';
                                }
                                html += '</div>';
                                $('#result-grammar').html(html);
                            } else {
                                $('#result-grammar').html('<div class="aiwa-result" style="border-left-color:#f59e0b;">Error</div>');
                            }
                        },
                        error: function() {
                            $('#result-grammar').html('<div class="aiwa-result" style="border-left-color:#ef4444;">Network error</div>');
                        },
                        complete: function() {
                            btn.prop('disabled', false).text('✓ Check Grammar');
                        }
                    });
                });

                $('#btn-rewrite').click(function() {
                    if (!hasKey) {
                        alert('Please add API key first!');
                        window.open(settingsUrl, '_blank');
                        return;
                    }

                    const text = $('#text-rewrite').val().trim();
                    const tone = $('#tone').val();
                    if (!text) return alert('Please select text first');

                    const btn = $(this);
                    btn.prop('disabled', true).text('Rewriting...');
                    $('#result-rewrite').html('<div style="text-align:center;color:#16a34a;">🔄 Rewriting...</div>');

                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'aiwa_rewrite',
                            nonce: nonce,
                            text: text,
                            tone: tone
                        },
                        success: function(res) {
                            if (res.success) {
                                $('#result-rewrite').html(
                                    '<div class="aiwa-result">' + res.data + '</div>' +
                                    '<button class="aiwa-btn aiwa-btn-apply" data-text="' + res.data.replace(/"/g, '&quot;') + '">📝 Replace Selected Text</button>'
                                );

                                $('.aiwa-btn-apply').click(function() {
                                    const newText = $(this).data('text');
                                    if (replaceAtSelection(newText)) {
                                        alert('✓ Text replaced!');
                                        $('#aiwa-modal, #aiwa-overlay').removeClass('show');
                                    }
                                });
                            } else {
                                $('#result-rewrite').html('<div class="aiwa-result" style="border-left-color:#f59e0b;">Error</div>');
                            }
                        },
                        error: function() {
                            $('#result-rewrite').html('<div class="aiwa-result" style="border-left-color:#ef4444;">Network error</div>');
                        },
                        complete: function() {
                            btn.prop('disabled', false).text('✏️ Rewrite');
                        }
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
class AIWA_Ajax
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('wp_ajax_aiwa_improve', array($this, 'improve'));
        add_action('wp_ajax_aiwa_grammar', array($this, 'grammar'));
        add_action('wp_ajax_aiwa_rewrite', array($this, 'rewrite'));
    }

    public function improve()
    {
        check_ajax_referer('aiwa', 'nonce');

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

    public function grammar()
    {
        check_ajax_referer('aiwa', 'nonce');

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

    public function rewrite()
    {
        check_ajax_referer('aiwa', 'nonce');

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
