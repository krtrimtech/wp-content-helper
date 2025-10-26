# Contributing to WP Content Helper

Thank you for considering contributing to WP Content Helper! 🎉

## Code of Conduct

- Be respectful and inclusive
- Provide constructive feedback
- Focus on what is best for the community
- Show empathy towards other community members

## How to Contribute

### Reporting Bugs

1. **Check existing issues** - Search if the bug has already been reported
2. **Create detailed bug report** with:
   - Clear title and description
   - Steps to reproduce
   - Expected vs actual behavior
   - WordPress version, PHP version
   - Browser and OS information
   - Screenshots if applicable

### Suggesting Features

1. **Check existing feature requests** first
2. **Create feature request** with:
   - Clear description of the feature
   - Use cases and benefits
   - Possible implementation approach
   - Any alternatives considered

### Pull Requests

#### Before Submitting

1. Fork the repository
2. Create a new branch: `git checkout -b feature/your-feature-name`
3. Make your changes
4. Test thoroughly
5. Commit with clear messages

#### PR Guidelines

- **One feature/fix per PR**
- **Clear, descriptive title**
- **Detailed description** of changes
- **Link related issues**
- **Update documentation** if needed
- **Add comments** for complex code
- **Follow coding standards**

#### Code Standards

**PHP Coding Standards:**
// Use proper indentation (4 spaces)
// Follow WordPress Coding Standards
// Add PHPDoc comments for functions

/**

Function description

@param string $param Description

@return bool
*/
public function my_function($param) {
// Code here
}



**JavaScript Standards:**
// Use JSDoc comments
// Follow consistent formatting
// Use meaningful variable names

/**

Function description

@param {string} text - The text to process

@returns {string} Processed text
*/
function processText(text) {
// Code here
}



**CSS Standards:**
/* Use BEM naming or consistent structure /
/ Add comments for complex sections /
/ Use !important sparingly */

.aiwa-button {
/* Button styles */
}


## Development Setup

1. **Clone the repository:**
git clone https://github.com/krtrimtech/wp-content-helper.git
cd wp-content-helper

2. **Install in WordPress:**
- Copy to `/wp-content/plugins/wp-content-helper/`
- Activate the plugin

3. **Get API Key:**
- Visit [Google AI Studio](https://aistudio.google.com/app/apikey)
- Create and copy API key
- Add to plugin settings

## File Structure

wp-content-helper/
├── wp-content-helper.php # Main plugin file
├── includes/ # PHP classes
│ ├── prompts.php # AI prompts
│ ├── class-gemini-api.php # API handler
│ ├── class-settings.php # Settings
│ ├── class-editor-button.php # UI
│ └── class-ajax-handlers.php # AJAX
├── assets/
│ ├── css/
│ │ └── editor-style.css # Styles
│ └── js/
│ └── editor-script.js # Scripts
├── README.md # Documentation
├── CONTRIBUTING.md # This file
├── LICENSE # GPL v2
├── SECURITY.md # Security policy
└── .gitignore # Git ignore



## Testing Guidelines

### Before Submitting PR

- [ ] Test with WordPress 5.0+
- [ ] Test with PHP 7.4+
- [ ] Test with Gutenberg editor
- [ ] Test with Classic Editor
- [ ] Test with Elementor (if applicable)
- [ ] Verify API key validation
- [ ] Check error handling
- [ ] Test with Hindi/English text
- [ ] Verify text replacement works
- [ ] Check mobile responsiveness

### Security Checks

- [ ] All inputs sanitized
- [ ] All outputs escaped
- [ ] Nonces verified
- [ ] Capability checks present
- [ ] No SQL injection vulnerabilities
- [ ] No XSS vulnerabilities
- [ ] API keys stored securely

## Adding New Features

### Adding New AI Function

1. **Add prompt to `includes/prompts.php`:**
public static function your_new_function($text) {
return "Your prompt here: {$text}";
}



2. **Add method to `includes/class-gemini-api.php`:**
public function your_new_function($text) {
$prompt = AIWA_Prompts::your_new_function($text);
return $this->make_request($prompt, 0.5);
}



3. **Add AJAX handler to `includes/class-ajax-handlers.php`:**
public function your_function() {
check_ajax_referer('aiwa', 'nonce');
// Implementation
wp_send_json_success($result);
}



4. **Add UI in `includes/class-editor-button.php`**
5. **Add JavaScript in `assets/js/editor-script.js`**

## Commit Message Format

Use clear, descriptive commit messages:

feat: Add support for Malayalam language
fix: Resolve text replacement issue in Classic Editor
docs: Update README with new features
style: Format CSS according to standards
refactor: Reorganize AJAX handlers
test: Add unit tests for API class
chore: Update dependencies



## Questions?

- **GitHub Issues:** [Report here](https://github.com/krtrimtech/wp-content-helper/issues)
- **Discussions:** [Ask here](https://github.com/krtrimtech/wp-content-helper/discussions)
- **Email:** contact@krtrim.tech

## License

By contributing, you agree that your contributions will be licensed under the GPL v2 or later license.

---

Thank you for contributing! 🙏