<?php
/**
 * Settings Page Class
 * Handles admin settings and instructions page
 */

if (!defined('ABSPATH')) exit;

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
    
    /**
     * Add admin menu pages
     */
    public function add_menu() {
        // Settings page
        add_menu_page(
            'Content Helper', 
            'Content Helper', 
            'edit_posts', 
            'wp-content-helper', 
            array($this, 'render_settings'), 
            'dashicons-edit', 
            30
        );
        
        // Instructions submenu
        add_submenu_page(
            'wp-content-helper',
            'Instructions & About',
            'Instructions',
            'edit_posts',
            'wp-content-helper-instructions',
            array($this, 'render_instructions')
        );
        
        // Feedback submenu
        add_submenu_page(
            'wp-content-helper',
            'Feedback',
            'Feedback',
            'edit_posts',
            'wp-content-helper-feedback',
            array(AIWA_Feedback::get_instance(), 'render_page')
        );
    }
    
    /**
     * Render settings page
     */
    public function render_settings() {
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_api_key', true);
        
        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success"><p><strong>Settings saved!</strong></p></div>';
        }
        ?>
        <div class="wrap">
            <h1>✏️ WP Content Helper - Settings</h1>
            <p>Auto-detects English and Indian languages (Hindi, Bengali, Tamil, Telugu, etc.)</p>
            
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
                                Get your free API key: 
                                <a href="https://aistudio.google.com/app/apikey" target="_blank" style="font-weight:600;">Google AI Studio →</a>
                            </p>
                            <?php if ($api_key): ?>
                                <p style="color:#16a34a;font-weight:600;margin-top:10px;">✓ API Key configured!</p>
                            <?php else: ?>
                                <p style="color:#f59e0b;font-weight:600;margin-top:10px;">⚠ Please add your API key</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Settings'); ?>
            </form>
            
            <hr style="margin:40px 0;">
            
            <div style="background:#f0f6fc;padding:20px;border-radius:8px;border-left:4px solid #667eea;">
                <h2 style="margin-top:0;">📖 Quick Start</h2>
                <ol style="line-height:1.8;">
                    <li>Add your API key above and save</li>
                    <li>Go to any Post/Page editor</li>
                    <li>Look for the <strong>green content helper button</strong> at bottom-right</li>
                    <li>Select text and click the button</li>
                    <li>Use AI to improve, check grammar, or rewrite</li>
                </ol>
                
                <p style="margin-bottom:0;">
                    <a href="<?php echo admin_url('admin.php?page=wp-content-helper-instructions'); ?>" class="button button-primary">
                        📚 View Full Instructions
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=wp-content-helper-feedback'); ?>" class="button" style="margin-left:10px;">
                        💬 Send Feedback
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render instructions page
     */
    public function render_instructions() {
        $languages = AIWA_Prompts::get_supported_languages();
        ?>
        <div class="wrap">
            <h1>✏️ WP Content Helper - Instructions & About</h1>
            
            <div style="max-width:900px;">
                
                <!-- About -->
                <div style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;padding:30px;border-radius:12px;margin:20px 0;">
                    <h2 style="color:white;margin-top:0;">About WP Content Helper</h2>
                    <p style="font-size:16px;line-height:1.6;margin:0;">
                        A Grammarly-like content writing assistant for WordPress. Auto-detects English and Indian languages 
                        and provides intelligent writing suggestions using Google Gemini AI.
                    </p>
                </div>
                
                <!-- Features -->
                <div style="background:#fff;padding:25px;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                    <h2>✨ Features</h2>
                    <ul style="line-height:1.8;">
                        <li>🌍 <strong>Multi-language:</strong> English + all major Indian languages</li>
                        <li>✏️ <strong>Content Improvement</strong> - Better clarity and grammar</li>
                        <li>✓ <strong>Grammar Checking</strong> - Find and fix errors</li>
                        <li>🎨 <strong>Content Rewriting</strong> - 6 different tones</li>
                        <li>🔄 <strong>Direct Replacement</strong> - Works like Grammarly</li>
                        <li>🔐 <strong>Private Keys</strong> - Each user uses own API key</li>
                        <li>🎯 <strong>Universal</strong> - Works with Gutenberg, Classic, Elementor</li>
                    </ul>
                </div>
                
                <!-- How to Use -->
                <div style="background:#fff;padding:25px;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                    <h2>📖 How to Use</h2>
                    
                    <h3 style="color:#667eea;">Step 1: Get API Key</h3>
                    <ol style="line-height:1.8;">
                        <li>Visit <a href="https://aistudio.google.com/app/apikey" target="_blank"><strong>Google AI Studio</strong></a></li>
                        <li>Sign in with Google account</li>
                        <li>Click "Create API Key"</li>
                        <li>Copy the key</li>
                    </ol>
                    
                    <h3 style="color:#667eea;">Step 2: Configure</h3>
                    <ol style="line-height:1.8;">
                        <li>Go to <strong>Content Helper → Settings</strong></li>
                        <li>Paste your API key</li>
                        <li>Click "Save Settings"</li>
                    </ol>
                    
                    <h3 style="color:#667eea;">Step 3: Use Content Helper</h3>
                    <ol style="line-height:1.8;">
                        <li>Open any post/page editor</li>
                        <li>Write or select text</li>
                        <li>Click the <strong>green content helper button</strong> at bottom-right</li>
                        <li>Choose: Improve, Grammar, or Rewrite</li>
                        <li>Click "Replace Selected Text"</li>
                    </ol>
                </div>
                
                <!-- Supported Languages -->
                <div style="background:#fff;padding:25px;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                    <h2>🌍 Supported Languages</h2>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;">
                        <?php foreach ($languages as $code => $name): ?>
                            <div style="padding:10px;background:#f8f9fa;border-radius:6px;">
                                <?php echo esc_html($name); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- About & Credits -->
                <div style="background:#fff;padding:25px;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                    <h2>ℹ️ About & Credits</h2>
                    <table style="width:100%;line-height:2;">
                        <tr>
                            <td style="width:150px;"><strong>Plugin:</strong></td>
                            <td>WP Content Helper</td>
                        </tr>
                        <tr>
                            <td><strong>Version:</strong></td>
                            <td><?php echo AIWA_VERSION; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Created by:</strong></td>
                            <td><a href="https://github.com/krtrimtech" target="_blank">Krtrim</a></td>
                        </tr>
                        <tr>
                            <td><strong>Contributor:</strong></td>
                            <td><a href="https://shyanukant.github.io/" target="_blank">Shyanukant Rathi</a></td>
                        </tr>
                        <tr>
                            <td><strong>GitHub:</strong></td>
                            <td><a href="https://github.com/krtrimtech/wp-content-helper" target="_blank">github.com/krtrimtech/wp-content-helper</a></td>
                        </tr>
                        <tr>
                            <td><strong>License:</strong></td>
                            <td>GPL v2 or later</td>
                        </tr>
                    </table>
                    
                    <div style="margin-top:20px;">
                        <a href="<?php echo admin_url('admin.php?page=wp-content-helper-feedback'); ?>" class="button button-primary">
                            💬 Send Us Feedback
                        </a>
                    </div>
                </div>
                
            </div>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
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
