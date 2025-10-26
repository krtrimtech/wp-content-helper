<?php
/**
 * AI Prompts Configuration
 * All Gemini API prompts in one place for easy editing
 */

if (!defined('ABSPATH')) exit;

class AIWA_Prompts {
    
    /**
     * Improve text prompt
     */
    public static function improve_text($text) {
        return "You are a writing assistant for English and Indian languages ONLY (English, Hindi, Bengali, Tamil, Telugu, Marathi, Gujarati, Kannada, Malayalam, Punjabi, Urdu).

CRITICAL RULES:
1. Detect if the text is in English or any Indian language
2. Respond ONLY in the SAME language as the input
3. If text is English, respond in English only
4. If text is Hindi, respond in Hindi only
5. DO NOT use Spanish, French, German, or any European languages
6. Return ONLY the improved text, no explanations

Improve this text for clarity and grammar:

{$text}";
    }
    
    /**
     * Check grammar prompt
     */
    public static function check_grammar($text) {
        return "You are a grammar checker for English and Indian languages ONLY.

STRICT RULES:
1. Only detect these languages: English, Hindi (हिंदी), Bengali (বাংলা), Tamil (தமிழ்), Telugu (తెలుగు), Marathi (मराठी), Gujarati (ગુજરાતી), Kannada (ಕನ್ನಡ), Malayalam (മലയാളം), Punjabi (ਪੰਜਾਬੀ), Urdu (اردو)
2. Respond in the SAME language as the input text
3. DO NOT use Spanish, French, or any other language
4. Provide response in JSON format

Check grammar and provide suggestions in JSON:
{\"language\": \"detected language\", \"errors\": [{\"type\": \"grammar\", \"original\": \"text\", \"suggestion\": \"fix\", \"explanation\": \"why\"}], \"score\": 85}

Text to check:
{$text}";
    }
    
    /**
     * Rewrite content prompt
     */
    public static function rewrite_content($text, $tone) {
        return "You are a content rewriter for English and Indian languages ONLY.

CRITICAL RULES:
1. Only work with: English, Hindi, Bengali, Tamil, Telugu, Marathi, Gujarati, Kannada, Malayalam, Punjabi, Urdu
2. Detect the language of the input text
3. Rewrite in the SAME language with {$tone} tone
4. DO NOT translate to Spanish, French, German, or any European language
5. If input is English, output must be English
6. If input is Hindi, output must be Hindi
7. Return ONLY the rewritten text

Rewrite this text in {$tone} tone:

{$text}";
    }
    
    /**
     * Get supported languages list
     */
    public static function get_supported_languages() {
        return array(
            'en' => 'English',
            'hi' => 'Hindi (हिंदी)',
            'bn' => 'Bengali (বাংলা)',
            'ta' => 'Tamil (தமிழ்)',
            'te' => 'Telugu (తెలుగు)',
            'mr' => 'Marathi (मराठी)',
            'gu' => 'Gujarati (ગુજરાતી)',
            'kn' => 'Kannada (ಕನ್ನಡ)',
            'ml' => 'Malayalam (മലയാളം)',
            'pa' => 'Punjabi (ਪੰਜਾਬੀ)',
            'ur' => 'Urdu (اردو)'
        );
    }
}
