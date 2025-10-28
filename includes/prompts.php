<?php
/**
 * AI Prompts Configuration
 * All Gemini API prompts in one place for easy editing
 */

if (!defined('ABSPATH')) exit;

class AIWA_Prompts {
    
    /**
     * Improve text prompt - Returns clean text only
     */
    public static function improve_text($text) {
        return "You are a professional writing editor. Improve the following text for better clarity, grammar, and flow.

CRITICAL RULES:
1. Detect the language of the input text (English, Hindi, Bengali, Tamil, Telugu, Marathi, Gujarati, Kannada, Malayalam, Punjabi, Urdu)
2. Respond ONLY in the SAME language as the input
3. Return ONLY the improved text - NO labels, NO prefixes, NO explanations
4. Do NOT add phrases like 'Here is the improved text', 'Rewritten version', or similar
5. Do NOT add 'English Text:', 'Hindi Text:', or any language labels
6. Return the improved text directly without any additional formatting

Text to improve:
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
4. Provide response in JSON format ONLY

Check grammar and provide suggestions in this exact JSON format:
{\"language\": \"detected language\", \"errors\": [{\"type\": \"grammar\", \"original\": \"text\", \"suggestion\": \"fix\", \"explanation\": \"why\"}], \"score\": 85}

Text to check:
{$text}";
    }
    
    /**
     * Rewrite content prompt - Returns clean text only
     */
    public static function rewrite_content($text, $tone) {
        return "You are a professional content rewriter for English and Indian languages.

CRITICAL RULES:
1. Only work with: English, Hindi, Bengali, Tamil, Telugu, Marathi, Gujarati, Kannada, Malayalam, Punjabi, Urdu
2. Detect the language of the input text
3. Rewrite in the SAME language with {$tone} tone
4. Return ONLY the rewritten text - NO labels, NO prefixes, NO explanations
5. Do NOT add phrases like 'Here is', 'Rewritten:', '**Rewritten Text:**', or language labels
6. Do NOT add 'English Rewritten Text:', 'Hindi Version:', or any prefixes
7. Do NOT translate to Spanish, French, German, or any European language
8. If input is English, output must be English only
9. If input is Hindi, output must be Hindi only
10. Return the clean rewritten text directly

Tone: {$tone}

Text to rewrite:
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
