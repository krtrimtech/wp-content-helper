#  <img src="https://img.icons8.com/fluency/48/ai-agent.png" alt="AI Assistant"> WP Content Helper [![Sponsor](https://img.shields.io/badge/💖%20Sponsor-Support%20My%20Work-ff69b4?style=for-the-badge&logo=github&logoColor=white)](https://github.com/sponsors/shyanukant)


> A powerful Grammarly-like AI writing assistant for WordPress, powered by Google Gemini API.

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
 AI-powered writing assistance directly into your WordPress editor. Unlike other AI plugins, each user manages their own Google Gemini API key, ensuring privacy and individual control. The plugin seamlessly integrates with Gutenberg's toolbar, providing instant AI suggestions right where you write.

## ✨ Features

- 🌍 **Multi-language Support** - English + all major Indian languages
- ✏️ **AI Text Improvement** - Enhance clarity and grammar
- ✓ **Grammar Checking** - Find and fix errors with explanations
- 🎨 **Content Rewriting** - 6 different tones (professional, casual, friendly, etc.)
- 🔄 **Direct Text Replacement** - Works like Grammarly - replaces text in-place
- 🔐 **Private API Keys** - Each user uses their own Gemini API key
- 🎯 **Universal Compatibility** - Works with Gutenberg, Classic Editor, and Elementor
## 🎥 How It Works


## 📋 Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Gutenberg Block Editor enabled
- Google Gemini API key (free at [Google AI Studio](https://aistudio.google.com/app/apikey))

## 🚀 Installation

### Method 1: Manual Installation

1. Download the plugin from this repository
2. Upload the `wp-content-helper` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to **Dashboard → AI Assistant** to add your API key

### Method 2: Clone from GitHub

```bash
cd wp-content/plugins/
git clone https://github.com/krtrimtech/wp-content-helper.git
```

Then activate the plugin in WordPress admin.

## 🔑 Getting Your API Key

1. Visit [Google AI Studio](https://aistudio.google.com/app/apikey)
2. Sign in with your Google account
3. Click **"Create API Key"**
4. Copy your API key
5. Go to WordPress → **AI Assistant** → Paste your key → Save

**Note:** The API key is completely free with generous usage limits!

## 📖 Usage Guide

### Setup

1. After activation, go to **Dashboard → AI Assistant**
2. Paste your Google Gemini API key


### Using AI Features

#### 🤖 AI Improve (Quick Action)
- Select any text in your post
- Click the **star icon** (🤖) in the toolbar
- Your text is instantly improved and replaced

#### ✓ Check Grammar
- Select text you want to analyze
- Click the **checkmark icon** (✓) in the toolbar
- View detailed grammar suggestions in a modal
- See your writing score (0-100)

#### ✏️ Rewrite Content
- Select text to rewrite
- Click the **edit icon** (✏️) in the toolbar
- Choose your desired tone:
  - Professional
  - Casual
  - Friendly
  - Academic
  - Creative
  - Simple
- Click **"Rewrite"** and review the result
- Click **"Replace Selected Text"** to apply changes

## 🌍 Supported Languages

- 🇬🇧 English
- 🇮🇳 Hindi (हिंदी)
- 🇮🇳 Bengali (বাংলা)
- 🇮🇳 Tamil (தமிழ்)
- 🇮🇳 Telugu (తెలుగు)
- 🇮🇳 Marathi (मराठी)
- 🇮🇳 Gujarati (ગુજરાતી)
- 🇮🇳 Kannada (ಕನ್ನಡ)
- 🇮🇳 Malayalam (മലയാളം)
- 🇮🇳 Punjabi (ਪੰਜਾਬੀ)
- 🇮🇳 Urdu (اردو)


### Hooks Used

- `admin_menu` - Dashboard settings page
- `enqueue_block_editor_assets` - Toolbar buttons
- `wp_ajax_*` - AJAX handlers for AI requests

### Security

- Nonce verification for all AJAX requests
- API keys stored per-user in WordPress user meta
- Capability checks (`edit_posts`)
- Input sanitization on all user inputs



## 🤝 Contributing

We welcome contributions! Here's how you can help:

1. Fork this repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Development Setup

```bash
git clone https://github.com/krtrimtech/wp-content-helper.git
cd wp-content-helper
# Make your changes
# Test on a local WordPress installation
```

## 📁 File Structure

wp-content-helper/
├── wp-content-helper.php # Main plugin file
├── includes/
│ ├── class-gemini-api.php # Gemini API handler
│ ├── class-settings.php # Settings page
│ ├── class-editor-button.php # Floating button & modal
│ ├── class-ajax-handlers.php # AJAX endpoints
│ └── prompts.php # AI prompts
├── assets/
│ ├── css/
│ │ └── editor-style.css # All CSS
│ └── js/
│ └── editor-script.js # All JavaScript
└── README.md # This file

text

## 🔧 Development

### Adding New Features

1. **New AI Function**: Add to `includes/prompts.php`
2. **New AJAX Endpoint**: Add to `includes/class-ajax-handlers.php`
3. **New UI Element**: Update `assets/css/editor-style.css` and `assets/js/editor-script.js`

### Modifying Prompts

Edit `includes/prompts.php` to change how the AI responds:
```php
public static function your_new_prompt($text) {
return "Your custom prompt here: {$text}";
}
```

## 📝 Changelog

### Version 1.1.0 (Current)
- Added inline toolbar buttons (Grammarly-style)
- Added AI Improve quick action
- Improved modal UI
- Added support for 20+ languages
- Better error handling

### Version 1.0.0
- Initial release
- Basic grammar checking
- Content rewriting
- Dashboard settings page

## 🐛 Bug Reports & Feature Requests

Found a bug or have a feature idea? Please [open an issue](https://github.com/krtrimtech/wp-content-helper/issues) on GitHub!

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Authors & Contributors

**Created by:** [Krtrim](https://github.com/krtrimtech)

**Contributor:** [Shyanukant Rathi](https://shyanukant.github.io/)

## 🙏 Acknowledgments

- Powered by [Google Gemini API](https://ai.google.dev/)
- Built for WordPress Gutenberg Block Editor
- Inspired by Grammarly's inline editing experience

## 📞 Support

Need help? Here's how to get support:

1. Check the [Usage Guide](#usage-guide) above
2. Search [existing issues](https://github.com/krtrimtech/wp-content-helper/issues)
3. [Open a new issue](https://github.com/krtrimtech/wp-content-helper/issues/new) if needed

## ⭐ Show Your Support

If you find this plugin helpful, please consider:
- Giving it a ⭐ star on GitHub
- Sharing it with others
- Contributing to the project

***

**Made with ❤️ for the WordPress community**
