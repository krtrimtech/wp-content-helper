<?php
/**
 * Feedback System Class
 * Allows users to submit feedback via email (free & secure)
 */

if (!defined('ABSPATH')) exit;

class AIWA_Feedback {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_post_aiwa_submit_feedback', array($this, 'submit_feedback'));
    }
    
    /**
     * Render feedback page
     */
    public function render_page() {
        $current_user = wp_get_current_user();
        
        if (isset($_GET['submitted']) && $_GET['submitted'] === '1') {
            echo '<div class="notice notice-success"><p><strong>Thank you!</strong> Your feedback has been sent successfully.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>💬 Send Feedback</h1>
            <p>Help us improve WP Content Helper! Your feedback is valuable to us.</p>
            
            <div style="max-width:700px;">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="background:#fff;padding:30px;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                    <input type="hidden" name="action" value="aiwa_submit_feedback">
                    <?php wp_nonce_field('aiwa_feedback'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="feedback_name">Your Name *</label>
                            </th>
                            <td>
                                <input type="text" name="feedback_name" id="feedback_name" 
                                       value="<?php echo esc_attr($current_user->display_name); ?>" 
                                       class="regular-text" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="feedback_email">Your Email *</label>
                            </th>
                            <td>
                                <input type="email" name="feedback_email" id="feedback_email" 
                                       value="<?php echo esc_attr($current_user->user_email); ?>" 
                                       class="regular-text" required>
                                <p class="description">We'll respond to this email if needed</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="feedback_type">Feedback Type *</label>
                            </th>
                            <td>
                                <select name="feedback_type" id="feedback_type" required>
                                    <option value="">Select type...</option>
                                    <option value="bug">🐛 Bug Report</option>
                                    <option value="feature">✨ Feature Request</option>
                                    <option value="improvement">🎨 Improvement Suggestion</option>
                                    <option value="question">❓ Question</option>
                                    <option value="praise">❤️ Praise / Thank You</option>
                                    <option value="other">💭 Other</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="feedback_rating">How would you rate the plugin?</label>
                            </th>
                            <td>
                                <select name="feedback_rating" id="feedback_rating">
                                    <option value="">Select rating...</option>
                                    <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                                    <option value="4">⭐⭐⭐⭐ Good</option>
                                    <option value="3">⭐⭐⭐ Average</option>
                                    <option value="2">⭐⭐ Below Average</option>
                                    <option value="1">⭐ Poor</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="feedback_message">Your Feedback *</label>
                            </th>
                            <td>
                                <textarea name="feedback_message" id="feedback_message" 
                                          rows="8" class="large-text" required 
                                          placeholder="Please share your thoughts, suggestions, or report issues..."></textarea>
                                <p class="description">Be as detailed as possible</p>
                            </td>
                        </tr>
                    </table>
                    
                    <div style="margin-top:20px;">
                        <?php submit_button('Send Feedback', 'primary', 'submit', false); ?>
                    </div>
                </form>
                
                <!-- Info Box -->
                <div style="background:#f0fdf4;padding:20px;border-radius:8px;border-left:4px solid #16a34a;margin:20px 0;">
                    <h3 style="margin-top:0;color:#16a34a;">🔒 Your Privacy</h3>
                    <ul style="margin:0;line-height:1.8;">
                        <li>✓ Feedback sent securely via WordPress email</li>
                        <li>✓ No external services or tracking</li>
                        <li>✓ Your email used only for responses</li>
                        <li>✓ We never share your information</li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div style="background:#fff;padding:20px;border:1px solid #e5e7eb;border-radius:8px;margin:20px 0;">
                    <h3 style="margin-top:0;">Other Ways to Reach Us</h3>
                    <ul style="line-height:2;">
                        <li>📧 <strong>Email:</strong> <a href="mailto:contact@krtrim.tech">contact@krtrim.tech</a></li>
                        <li>🐛 <strong>GitHub Issues:</strong> <a href="https://github.com/krtrimtech/wp-content-helper/issues" target="_blank">Report bugs</a></li>
                        <li>💬 <strong>Discussions:</strong> <a href="https://github.com/krtrimtech/wp-content-helper/discussions" target="_blank">Ask questions</a></li>
                        <li>🌐 <strong>Website:</strong> <a href="https://www.krtrim.tech/tool/" target="_blank">Visit our site</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Submit feedback via email
     */
    public function submit_feedback() {
        check_admin_referer('aiwa_feedback');
        
        // Get form data
        $name = isset($_POST['feedback_name']) ? sanitize_text_field($_POST['feedback_name']) : '';
        $email = isset($_POST['feedback_email']) ? sanitize_email($_POST['feedback_email']) : '';
        $type = isset($_POST['feedback_type']) ? sanitize_text_field($_POST['feedback_type']) : '';
        $rating = isset($_POST['feedback_rating']) ? sanitize_text_field($_POST['feedback_rating']) : '';
        $message = isset($_POST['feedback_message']) ? sanitize_textarea_field($_POST['feedback_message']) : '';
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($type) || empty($message)) {
            wp_redirect(admin_url('admin.php?page=wp-content-helper-feedback&error=1'));
            exit;
        }
        
        // Get WordPress and plugin info
        global $wp_version;
        $php_version = PHP_VERSION;
        $plugin_version = AIWA_VERSION;
        $site_url = get_site_url();
        $user = wp_get_current_user();
        
        // Type labels
        $type_labels = array(
            'bug' => '🐛 Bug Report',
            'feature' => '✨ Feature Request',
            'improvement' => '🎨 Improvement Suggestion',
            'question' => '❓ Question',
            'praise' => '❤️ Praise',
            'other' => '💭 Other'
        );
        $type_label = isset($type_labels[$type]) ? $type_labels[$type] : $type;
        
        // Rating label
        $rating_label = $rating ? str_repeat('⭐', intval($rating)) : 'Not rated';
        
        // Compose email
        $to = 'contact@krtrim.tech'; // Your email
        $subject = "[WP Content Helper] {$type_label} from {$name}";
        
        $body = "New feedback received from WP Content Helper plugin:\n\n";
        $body .= "====================================\n";
        $body .= "SENDER INFORMATION\n";
        $body .= "====================================\n";
        $body .= "Name: {$name}\n";
        $body .= "Email: {$email}\n";
        $body .= "Username: {$user->user_login}\n";
        $body .= "User Role: " . implode(', ', $user->roles) . "\n";
        $body .= "\n";
        $body .= "====================================\n";
        $body .= "FEEDBACK DETAILS\n";
        $body .= "====================================\n";
        $body .= "Type: {$type_label}\n";
        $body .= "Rating: {$rating_label}\n";
        $body .= "\n";
        $body .= "Message:\n";
        $body .= $message . "\n";
        $body .= "\n";
        $body .= "====================================\n";
        $body .= "TECHNICAL INFORMATION\n";
        $body .= "====================================\n";
        $body .= "Site URL: {$site_url}\n";
        $body .= "WordPress Version: {$wp_version}\n";
        $body .= "PHP Version: {$php_version}\n";
        $body .= "Plugin Version: {$plugin_version}\n";
        $body .= "Timestamp: " . current_time('mysql') . "\n";
        
        $headers = array(
            'From: ' . $name . ' <' . $email . '>',
            'Reply-To: ' . $email,
            'Content-Type: text/plain; charset=UTF-8'
        );
        
        // Send email
        $sent = wp_mail($to, $subject, $body, $headers);
        
        // Send confirmation to user
        if ($sent) {
            $user_subject = "Thank you for your feedback - WP Content Helper";
            $user_body = "Hi {$name},\n\n";
            $user_body .= "Thank you for your feedback! We've received your message and will review it shortly.\n\n";
            $user_body .= "Your feedback helps us improve WP Content Helper.\n\n";
            $user_body .= "Feedback Type: {$type_label}\n";
            if ($rating) {
                $user_body .= "Rating: {$rating_label}\n";
            }
            $user_body .= "\n";
            $user_body .= "Best regards,\n";
            $user_body .= "Krtrim Team\n";
            $user_body .= "https://github.com/krtrimtech\n";
            
            $user_headers = array(
                'From: WP Content Helper <contact@krtrim.tech>',
                'Content-Type: text/plain; charset=UTF-8'
            );
            
            wp_mail($email, $user_subject, $user_body, $user_headers);
        }
        
        // Redirect with success message
        if ($sent) {
            wp_redirect(admin_url('admin.php?page=wp-content-helper-feedback&submitted=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=wp-content-helper-feedback&error=2'));
        }
        exit;
    }
}
