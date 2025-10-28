<?php
/**
 * Feedback System Class - Google Form with Multiple Donation Options
 */

if (!defined('ABSPATH')) exit;

class AIWA_Feedback {
    private static $instance = null;
    
    // Your Google Form URL (replace with your actual form URL)
    private $form_url = 'https://docs.google.com/forms/d/e/1FAIpQLSfUqDJbYqVUjJEIECalr_5zQT8uK6kLuAwsiQ7znkp7b0bEzA/viewform';
    
    // Donation/Sponsor URLs
    private $github_sponsor_url = 'https://github.com/sponsors/shyanukant';
    private $upi_id = 'shyanukant@upi'; // Update with your UPI ID
    
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
     * Render feedback page with donation options
     */
    public function render_page() {
        $current_user = wp_get_current_user();
        
        // Pre-fill data for Google Form
        $params = array(
            'entry.YOUR_NAME_FIELD' => $current_user->display_name,
            'entry.YOUR_EMAIL_FIELD' => $current_user->user_email,
            'entry.YOUR_PLUGIN_FIELD' => 'WP Content Helper v' . (defined('AIWA_VERSION') ? AIWA_VERSION : '1.5.0'),
            'entry.YOUR_SITE_FIELD' => get_site_url()
        );
        
        $form_url_with_params = add_query_arg($params, $this->form_url);
        $embed_url = str_replace('/viewform', '/viewform?embedded=true', $form_url_with_params);
        ?>
        <div class="wrap">
            <h1>💬 Send Feedback & Support</h1>
            <p>Share your thoughts and help keep this plugin free and open-source!</p>
            
            <div style="max-width:900px;">
                
                <!-- Support/Donate Section -->
                <div style="background:linear-gradient(135deg, #ff69b4 0%, #ff1493 100%);color:white;padding:35px;border-radius:12px;margin:20px 0;box-shadow:0 8px 24px rgba(255,105,180,0.3);">
                    <h2 style="color:white;margin:0 0 10px 0;font-size:28px;text-align:center;">💖 Support This Plugin</h2>
                    <p style="margin:10px 0 25px 0;opacity:0.95;font-size:16px;text-align:center;">
                        Love WP Content Helper? Your support helps me create more free & open-source tools!<br>
                        Choose your preferred donation method:
                    </p>
                    
                    <!-- Donation Buttons Grid -->
                    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:15px;max-width:600px;margin:0 auto;">
                        
                        <!-- GitHub Sponsors -->
                        <a href="<?php echo esc_url($this->github_sponsor_url); ?>" 
                           target="_blank" 
                           class="aiwa-donate-btn"
                           style="display:flex;align-items:center;justify-content:center;gap:10px;background:#fff;color:#333;padding:15px 20px;font-size:16px;font-weight:700;text-decoration:none;border-radius:10px;box-shadow:0 4px 15px rgba(0,0,0,0.2);transition:all 0.3s;"
                           onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,0.3)';"
                           onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 15px rgba(0,0,0,0.2)';">
                            <span style="font-size:24px;">💖</span>
                            <span>GitHub Sponsors</span>
                        </a>
                        
                        <!-- PayPal -->
                       
                        
                        
                        
                        <!-- UPI (India) -->
                        <button 
                           onclick="document.getElementById('upi-modal').style.display='flex'"
                           class="aiwa-donate-btn"
                           style="display:flex;align-items:center;justify-content:center;gap:10px;background:#fff;color:#5f259f;padding:15px 20px;font-size:16px;font-weight:700;border:none;border-radius:10px;box-shadow:0 4px 15px rgba(0,0,0,0.2);transition:all 0.3s;cursor:pointer;"
                           onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,0.3)';"
                           onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='0 4px 15px rgba(0,0,0,0.2)';">
                            <span style="font-size:24px;">🇮🇳</span>
                            <span>UPI (India)</span>
                        </button>
                        
                    </div>
                    
                    <p style="margin:25px 0 0 0;font-size:14px;opacity:0.9;text-align:center;">
                        ⭐ Every contribution, big or small, makes a huge difference!
                    </p>
                </div>
                
                <!-- UPI Modal -->
                <div id="upi-modal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);align-items:center;justify-content:center;z-index:99999;">
                    <div style="background:white;padding:30px;border-radius:12px;max-width:400px;text-align:center;position:relative;">
                        <button onclick="document.getElementById('upi-modal').style.display='none'" 
                                style="position:absolute;top:10px;right:15px;background:none;border:none;font-size:24px;cursor:pointer;color:#999;">×</button>
                        
                        <h3 style="margin:0 0 20px 0;color:#5f259f;">🇮🇳 Donate via UPI</h3>
                        <p style="margin:0 0 15px 0;color:#64748b;">Scan QR Code or Copy UPI ID</p>
                        
                        <!-- QR Code Placeholder (you can generate QR code for your UPI ID) -->
                        <div style="background:#f8f9fa;padding:20px;border-radius:8px;margin:0 0 15px 0;">
                            <div style="width:200px;height:200px;margin:0 auto;background:#e5e7eb;display:flex;align-items:center;justify-content:center;border-radius:8px;">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=upi://pay?pa=<?php echo urlencode($this->upi_id); ?>&pn=<?php echo urlencode('Shyanukant Rathi'); ?>&cu=INR" 
                                     alt="UPI QR Code" 
                                     style="width:250px;height:250px;border-radius:8px;border:3px solid #e5e7eb;">
                            </div>
                            <p style="margin:15px 0 0 0;font-size:13px;color:#64748b;">Scan Me </p>
                        </div>
                        
                        <!-- UPI ID -->
                        <div style="background:#f0fdf4;padding:15px;border-radius:8px;border-left:4px solid #16a34a;margin:0 0 15px 0;">
                            <p style="margin:0 0 10px 0;font-size:12px;color:#16a34a;font-weight:600;">UPI ID:</p>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <input type="text" 
                                       value="<?php echo esc_attr($this->upi_id); ?>" 
                                       id="upi-id-input"
                                       readonly 
                                       style="flex:1;padding:10px;border:1px solid #e5e7eb;border-radius:6px;font-family:monospace;font-size:14px;">
                                <button onclick="navigator.clipboard.writeText('<?php echo esc_js($this->upi_id); ?>');this.textContent='✓ Copied!';setTimeout(()=>this.textContent='Copy',2000);"
                                        style="padding:10px 15px;background:#16a34a;color:white;border:none;border-radius:6px;cursor:pointer;font-weight:600;">
                                    Copy
                                </button>
                            </div>
                        </div>
                        
                        <p style="margin:0;font-size:13px;color:#64748b;">
                            Works with Google Pay, PhonePe, Paytm, and all UPI apps
                        </p>
                    </div>
                </div>
                
                <!-- Google Form Header -->
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
                
                <!-- Info Boxes -->
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin:20px 0;">
                    
                    <!-- Why Support -->
                    <div style="background:#fff0f6;padding:20px;border-radius:8px;border-left:4px solid #ff69b4;">
                        <h3 style="margin-top:0;color:#ff1493;">💖 Your Support</h3>
                        <ul style="margin:0;line-height:1.8;font-size:14px;">
                            <li>🚀 New Features</li>
                            <li>🐛 Faster Fixes</li>
                            <li>📚 Documentation</li>
                            <li>🎯 Support</li>
                            <li>❤️ Open Source</li>
                        </ul>
                    </div>
                    
                   
                    
                    <!-- Why Google Forms -->
                    <div style="background:#f0fdf4;padding:20px;border-radius:8px;border-left:4px solid #16a34a;">
                        <h3 style="margin-top:0;color:#16a34a;">✨ Feedback</h3>
                        <ul style="margin:0;line-height:1.8;font-size:14px;">
                            <li>✅ Free & Secure</li>
                            <li>✅ Instant</li>
                            <li>✅ Easy to Use</li>
                            <li>✅ Well Organized</li>
                            <li>✅ Reliable</li>
                        </ul>
                    </div>
                    
                </div>
                
                <!-- Contact Methods -->
                <div style="background:#fff;padding:25px;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                    <h3 style="margin-top:0;">📞 Other Ways to Reach Us</h3>
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px;">
                        <a href="mailto:contact@krtrim.tech" class="aiwa-contact-btn" style="text-align:center;padding:15px;background:#f8f9fa;border-radius:8px;text-decoration:none;color:#334155;border:2px solid #e5e7eb;transition:all 0.3s;">
                            <div style="font-size:24px;margin-bottom:8px;">📧</div>
                            <div style="font-weight:600;font-size:14px;">Email</div>
                        </a>
                        <a href="https://github.com/krtrimtech/wp-content-helper/issues" target="_blank" style="text-align:center;padding:15px;background:#f8f9fa;border-radius:8px;text-decoration:none;color:#334155;border:2px solid #e5e7eb;transition:all 0.3s;">
                            <div style="font-size:24px;margin-bottom:8px;">🐛</div>
                            <div style="font-weight:600;font-size:14px;">Bug Report</div>
                        </a>
                        <a href="https://github.com/krtrimtech/wp-content-helper/discussions" target="_blank" style="text-align:center;padding:15px;background:#f8f9fa;border-radius:8px;text-decoration:none;color:#334155;border:2px solid #e5e7eb;transition:all 0.3s;">
                            <div style="font-size:24px;margin-bottom:8px;">💬</div>
                            <div style="font-weight:600;font-size:14px;">Discussions</div>
                        </a>
                        <a href="https://www.krtrim.tech/tool/" target="_blank" style="text-align:center;padding:15px;background:#f8f9fa;border-radius:8px;text-decoration:none;color:#334155;border:2px solid #e5e7eb;transition:all 0.3s;">
                            <div style="font-size:24px;margin-bottom:8px;">🌐</div>
                            <div style="font-weight:600;font-size:14px;">Website</div>
                        </a>
                    </div>
                </div>
                
            </div>
        </div>
        
        <style>
            .aiwa-contact-btn:hover {
                border-color: #667eea !important;
                transform: translateY(-2px);
            }
        </style>
        <?php
    }
}
