<?php
/**
 * Editor Button Class
 * Handles the floating green button and modal interface
 */

if (!defined('ABSPATH')) exit;

class AIWA_Editor_Button {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_footer', array($this, 'render_modal'));
    }
    
    /**
     * Enqueue CSS and JS
     */
    public function enqueue_assets($hook) {
        // Only load on post/page edit screens
        if (!in_array($hook, array('post.php', 'post-new.php', 'page.php', 'page-new.php'))) {
            return;
        }
        
        $user_id = get_current_user_id();
        $api_key = get_user_meta($user_id, 'aiwa_api_key', true);
        
        // Enqueue CSS
        wp_enqueue_style(
            'aiwa-editor-style',
            AIWA_PLUGIN_URL . 'assets/css/editor-style.css',
            array(),
            AIWA_VERSION
        );
        
        // Enqueue JS
        wp_enqueue_script(
            'aiwa-editor-script',
            AIWA_PLUGIN_URL . 'assets/js/editor-script.js',
            array('jquery'),
            AIWA_VERSION,
            true
        );
        
        // Pass data to JS
        wp_localize_script('aiwa-editor-script', 'aiwaData', array(
            'hasKey' => !empty($api_key),
            'settingsUrl' => admin_url('admin.php?page=wp-content-helper'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aiwa')
        ));
    }
    
    /**
     * Render modal HTML
     */
    public function render_modal() {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->base, array('post', 'page'))) {
            return;
        }
        ?>
        
        <div id="aiwa-overlay"></div>
        
            <button id="aiwa-green-btn" title="Content Helper">

            <img src="https://img.icons8.com/fluency/48/ai-agent.png" alt="AI Assistant">
        </button>
        
        <div id="aiwa-modal">
            <div class="aiwa-header">
                <span style="font-size:18px;font-weight:600;">✏️ Content Helper</span>

                <button class="aiwa-close">×</button>
            </div>
            <div class="aiwa-body">
                <div class="aiwa-tabs">
                    <button class="aiwa-tab active" data-tab="improve">✨ Improve</button>
                    <button class="aiwa-tab" data-tab="grammar">✓ Grammar</button>
                    <button class="aiwa-tab" data-tab="rewrite">✏️ Rewrite</button>
                </div>
                
                <div id="tab-improve" class="aiwa-content active">
                    <p class="aiwa-hint">Select text in editor, then improve</p>
                    <textarea id="text-improve" class="aiwa-textarea" rows="5" placeholder="अपना टेक्स्ट चुनें / Select text..."></textarea>
                    <button id="btn-improve" class="aiwa-btn">✨ Improve Text</button>
                    <div id="result-improve"></div>
                </div>
                
                <div id="tab-grammar" class="aiwa-content">
                    <p class="aiwa-hint">Auto-detects language</p>
                    <textarea id="text-grammar" class="aiwa-textarea" rows="5" placeholder="किसी भी भाषा में..."></textarea>
                    <button id="btn-grammar" class="aiwa-btn">✓ Check Grammar</button>
                    <div id="result-grammar"></div>
                </div>
                
                <div id="tab-rewrite" class="aiwa-content">
                    <p class="aiwa-hint">Rewrite in different tone</p>
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
        
        <?php
    }
}
