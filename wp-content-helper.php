<?php
/**
 * Plugin Name: WP Content Helper
 * Plugin URI: https://github.com/krtrimtech/wp-content-helper
 * Description: Grammarly-like content writing assistant. Auto-detects language and replaces text directly in editor.
 * Version: 1.5.0
 * Author: Krtrim (Shyanukant Rathi)
 * Author URI: https://shyanukant.github.io/
 * License: GPL v2 or later
 * Text Domain: wp-content-helper
 */

if (!defined('ABSPATH')) exit;

// Define plugin constants
define('AIWA_VERSION', '1.5.0');
define('AIWA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AIWA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load all required files
require_once AIWA_PLUGIN_DIR . 'includes/prompts.php';
require_once AIWA_PLUGIN_DIR . 'includes/class-gemini-api.php';
require_once AIWA_PLUGIN_DIR . 'includes/class-settings.php';
require_once AIWA_PLUGIN_DIR . 'includes/class-editor-button.php';
require_once AIWA_PLUGIN_DIR . 'includes/class-ajax-handlers.php';
require_once AIWA_PLUGIN_DIR . 'includes/class-feedback.php';

// Initialize the plugin
function aiwa_init() {
    AIWA_Settings::get_instance();
    AIWA_Editor_Button::get_instance();
    AIWA_Ajax::get_instance();
    AIWA_Feedback::get_instance();
}
add_action('plugins_loaded', 'aiwa_init');
