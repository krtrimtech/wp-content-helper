<?php
/**
 * Feedback System Class - Google Form with Sponsor Button
 */

if (!defined('ABSPATH')) exit;

class AIWA_Feedback {
    private static $instance = null;
    
    // Your Google Form URL (replace with your actual form URL after creating it)
    private $form_url = 'https://docs.google.com/forms/d/e/1FAIpQLSfUqDJbYqVUjJEIECalr_5zQT8uK6kLuAwsiQ7znkp7b0bEzA/viewform';
    
    // GitHub Sponsor URL
    private $sponsor_url = 'https://github.com/sponsors/shyanukant';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // No actions needed - just rendering
    }
    
    /**
     * Render feedback page with embedded Google Form and Sponsor Button
     */
    public function render_page() {
        $current_user = wp_get_current_user();
        
        // Pre-fill data for the form URL (update entry.XXX IDs after creating your form)
        $params = array(
            'entry.YOUR_NAME_FIELD' => $current_user->display_name,
            'entry.YOUR_EMAIL_FIELD' => $current_user->user_email,
            'entry.YOUR_PLUGIN_FIELD' => 'WP Content Helper v' . (defined('AIWA_VERSION') ? AIWA_VERSION : '1.5.0'),
            'entry.YOUR_SITE_FIELD' => get_site_url()
        );
        
        $form_url_with_params = add_query_arg($params, $this->form_url);
        
        // Convert to embed URL
        $embed_url = str_replace('/viewform', '/viewform?embedded=true', $form_url_with_params);
        ?>
        <div class="wrap">
            <h1>💬 Send Feedback & Support</h1>
            <p>Share your thoughts about WP Content Helper! Your feedback helps us improve.</p>
            
            <div style="max-width:900px;">
                
                <!-- Sponsor Card -->
                <div style="background:linear-gradient(135deg, #ff69b4 0%, #ff1493 100%);color:white;padding:30px;border-radius:12px;margin:20px 0;text-align:center;box-shadow:0 8px 24px rgba(255,105,180,0.3);">
                    <h2 style="color:white;margin:0 0 10px 0;font-size:28px;">💖 Support My Work</h2>
                    <p style="margin:10px 0 20px 0;opacity:0.95;font-size:16px;">
                        Love this plugin? Consider sponsoring me on GitHub!<br>
                        Your support helps me create more free & open-source tools.
                    </p>
                    <a href="<?php echo esc_url($this->sponsor_url); ?>" 
                       target="_blank" 
                       style="display:inline-block;background:#fff;color:#ff69b4;padding:15px 40px;font-size:18px;font-weight:700;text-decoration:none;border-radius:50px;box-shadow:0 4px 15px rgba(0,0,0,0.2);transition:all 0.3s;"
                       onmouseover="this.style.transform='scale(1.05)';this.style.boxShadow='0 6px 20px rgba(0,0,0,0.3)';"
                       onmouseout="this.style.transform='scale(1)';this.style.boxShadow='0 4px 15px rgba(0,0,0,0.2)';">
                        💖 Sponsor on GitHub
                    </a>
                    <p style="margin:20px 0 0 0;font-size:14px;opacity:0.9;">
                        ⭐ Even a small sponsorship makes a huge difference!
                    </p>
                </div>
                
                <!-- Header Card -->
                <div style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;padding:30px;border-radius:12px 12px 0 0;margin:20px 0 0 0;">
                    <h2 style="color:white;margin:0;font-size:24px;">📝 Feedback Form</h2>
                    <p style="margin:10px 0 0 0;opacity:0.9;">Help us make WP Content Helper better for everyone!</p>
                </div>
                
                <!-- Embedded Google Form -->
                <div style="background:white;padding:20px;border:1px solid #e5e7eb;border-radius:0 0 12px 12px;margin:0 0 20px 0;">
                    <iframe 
                        src="<?php echo esc_url($embed_url); ?>" 
                        width="100%" 
                        height="1200" 
                        frameborder="0" 
                        marginheight="0" 
                        marginwidth="0"
                        style="border:none;">
                        Loading feedback form...
                    </iframe>
                </div>
                
                <!-- Info Boxes Row -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:20px 0;">
                    
                    <!-- Why Google Forms -->
                    <div style="background:#f0fdf4;padding:20px;border-radius:8px;border-left:4px solid #16a34a;">
                        <h3 style="margin-top:0;color:#16a34a;">✨ Why Google Forms?</h3>
                        <ul style="margin:0;line-height:1.8;font-size:14px;">
                            <li>✅ 100% Free</li>
                            <li>✅ Secure & Reliable</li>
                            <li>✅ Instant Delivery</li>
                            <li>✅ Easy to Use</li>
                            <li>✅ Well Organized</li>
                        </ul>
                    </div>
                    
                    <!-- Why Sponsor -->
                    <div style="background:#fff0f6;padding:20px;border-radius:8px;border-left:4px solid #ff69b4;">
                        <h3 style="margin-top:0;color:#ff1493;">💖 Why Sponsor?</h3>
                        <ul style="margin:0;line-height:1.8;font-size:14px;">
                            <li>🚀 More Features</li>
                            <li>🐛 Faster Bug Fixes</li>
                            <li>📚 Better Documentation</li>
                            <li>🎯 Priority Support</li>
                            <li>❤️ Support Open Source</li>
                        </ul>
                    </div>
                    
                </div>
                
                <!-- Other Contact Methods -->
                <div style="background:#fff;padding:25px;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                    <h3 style="margin-top:0;">📞 Other Ways to Reach Us</h3>
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px;">
                        <a href="mailto:contact@krtrim.tech" class="aiwa-contact-btn" style="text-align:center;padding:15px;background:#f8f9fa;border-radius:8px;text-decoration:none;color:#334155;border:2px solid #e5e7eb;transition:all 0.3s;"
                           onmouseover="this.style.borderColor='#667eea';this.style.transform='translateY(-2px)';"
                           onmouseout="this.style.borderColor='#e5e7eb';this.style.transform='translateY(0)';">
                            <div style="font-size:24px;margin-bottom:8px;">📧</div>
                            <div style="font-weight:600;font-size:14px;">Email Us</div>
                        </a>
                        <a href="https://github.com/krtrimtech/wp-content-helper/issues" target="_blank" class="aiwa-contact-btn" style="text-align:center;padding:15px;background:#f8f9fa;border-radius:8px;text-decoration:none;color:#334155;border:2px solid #e5e7eb;transition:all 0.3s;"
                           onmouseover="this.style.borderColor='#667eea';this.style.transform='translateY(-2px)';"
                           onmouseout="this.style.borderColor='#e5e7eb';this.style.transform='translateY(0)';">
                            <div style="font-size:24px;margin-bottom:8px;">🐛</div>
                            <div style="font-weight:600;font-size:14px;">Report Bug</div>
                        </a>
                        <a href="https://github.com/krtrimtech/wp-content-helper/discussions" target="_blank" class="aiwa-contact-btn" style="text-align:center;padding:15px;background:#f8f9fa;border-radius:8px;text-decoration:none;color:#334155;border:2px solid #e5e7eb;transition:all 0.3s;"
                           onmouseover="this.style.borderColor='#667eea';this.style.transform='translateY(-2px)';"
                           onmouseout="this.style.borderColor='#e5e7eb';this.style.transform='translateY(0)';">
                            <div style="font-size:24px;margin-bottom:8px;">💬</div>
                            <div style="font-weight:600;font-size:14px;">Discussions</div>
                        </a>
                        <a href="https://www.krtrim.tech/tool/" target="_blank" class="aiwa-contact-btn" style="text-align:center;padding:15px;background:#f8f9fa;border-radius:8px;text-decoration:none;color:#334155;border:2px solid #e5e7eb;transition:all 0.3s;"
                           onmouseover="this.style.borderColor='#667eea';this.style.transform='translateY(-2px)';"
                           onmouseout="this.style.borderColor='#e5e7eb';this.style.transform='translateY(0)';">
                            <div style="font-size:24px;margin-bottom:8px;">🌐</div>
                            <div style="font-weight:600;font-size:14px;">Visit Website</div>
                        </a>
                    </div>
                </div>
                
                <!-- What to Include -->
                <div style="background:#fff;padding:25px;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                    <h3 style="margin-top:0;">💡 What to Include in Your Feedback</h3>
                    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:15px;">
                        <div>
                            <strong style="color:#667eea;">🐛 Bug Reports:</strong>
                            <p style="margin:5px 0 0 0;font-size:14px;color:#64748b;">Steps to reproduce, expected vs actual behavior</p>
                        </div>
                        <div>
                            <strong style="color:#667eea;">✨ Feature Requests:</strong>
                            <p style="margin:5px 0 0 0;font-size:14px;color:#64748b;">What feature you'd like and why it's useful</p>
                        </div>
                        <div>
                            <strong style="color:#667eea;">🎨 Improvements:</strong>
                            <p style="margin:5px 0 0 0;font-size:14px;color:#64748b;">Suggestions to make things work better</p>
                        </div>
                        <div>
                            <strong style="color:#667eea;">❤️ Praise:</strong>
                            <p style="margin:5px 0 0 0;font-size:14px;color:#64748b;">What you love about the plugin!</p>
                        </div>
                    </div>
                </div>
                
                <!-- Auto-filled Info -->
                <div style="background:#f8f9fa;padding:15px;border-radius:8px;margin:20px 0;border:1px solid #e5e7eb;">
                    <p style="margin:0;font-size:13px;color:#64748b;">
                        <strong>ℹ️ Privacy Note:</strong> The form above may auto-fill your name, email, and site URL to save you time. 
                        All information is kept secure and used only to respond to your feedback.
                    </p>
                </div>
                
            </div>
        </div>
        <?php
    }
}
