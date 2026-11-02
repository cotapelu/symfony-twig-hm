<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Shared\Service\CircuitBreakerService;

/**
 * Simple translation service for multilingual communication
 * In production, integrate with Google Translate API or similar.
 */
class TranslationService
{
    private array $supportedLanguages = [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'it' => 'Italian',
        'pt' => 'Portuguese',
        'ru' => 'Russian',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'zh' => 'Chinese',
    ];

    public function __construct(
        private CircuitBreakerService $circuitBreaker,
        
    ) {
    }

    /**
     * Translate text from one language to another.
     */
    public function translate(string $text, string $fromLang, string $toLang)
    {
        $text = $this->prepareText($text);
        if ($text === '') return '';

        if (!in_array($toLang, array_keys($this->supportedLanguages))) {
            throw new \InvalidArgumentException('Unsupported target language: '.$toLang);
        }
        if (!in_array($fromLang, array_keys($this->supportedLanguages))) {
            $fromLang = 'auto';
        }
        if ($fromLang === $toLang) return $text;

        if (!$apiKey = getenv('GOOGLE_TRANSLATE_API_KEY')) {
            return $this->getMockTranslation($text, $fromLang, $toLang);
        }

        return $this->circuitBreaker->execute(
            fn() => $this->callGoogleTranslate($text, $fromLang, $toLang),
            'google_translate_api', 5, 60,
            fn() => $this->getMockTranslation($text, $fromLang, $toLang)
        ) ?: $this->getMockTranslation($text, $fromLang, $toLang);
    }

    private function callGoogleTranslate(string $text, string $fromLang, string $toLang): string
    {
        $apiKey = getenv('GOOGLE_TRANSLATE_API_KEY');
        $url = 'https://translation.googleapis.com/language/translate/v2?key='.$apiKey;
        $postData = ['q'=>$text, 'source'=>$fromLang, 'target'=>$toLang, 'format'=>'text'];
        $result = $this->executeCurlRequest($url, $postData);
        return $result['data']['translations'][0]['translatedText'] ?? $text;
    }

    private function getMockTranslation(string $text, string $fromLang, string $toLang)
    {
        $mockTranslations = [
            'en' => [
                'es' => ['Hello' => 'Hola', 'Thank you' => 'Gracias', 'Goodbye' => 'Adiós'],
                'fr' => ['Hello' => 'Bonjour', 'Thank you' => 'Merci', 'Goodbye' => 'Au revoir'],
                'de' => ['Hello' => 'Hallo', 'Thank you' => 'Danke', 'Goodbye' => 'Auf Wiedersehen'],
            ],
        ];

        if (isset($mockTranslations[$fromLang][$toLang][$text])) {
            return $mockTranslations[$fromLang][$toLang][$text];
        }

        return $text.' [Translated from '.strtoupper($fromLang).']';
    }

    /**
     * Detect language of text.
     */
    public function detectLanguage(string $text)
    {
        // Simple language detection - in production, use a proper language detection service
        $text = strtolower($text);

        if (preg_match('/(hola|gracias|adiós)/i', $text)) {
            return 'es';
        }
        if (preg_match('/(bonjour|merci|au revoir)/i', $text)) {
            return 'fr';
        }
        if (preg_match('/(hallo|danke|auf wiedersehen)/i', $text)) {
            return 'de';
        }

        return 'en'; // Default to English
    }

    /**
     * Get supported languages.
     */
    public function getSupportedLanguages()
    {
        return $this->supportedLanguages;
    }

    /**
     * Check if language is supported.
     */
    public function isLanguageSupported(string $langCode)
    {
        return isset($this->supportedLanguages[$langCode]);
    }

    private function prepareText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        if (strlen($text) > 5000) {
            $text = substr($text, 0, 5000) . '...';
        }
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        }
        return $text;
    }

    private function executeCurlRequest(string $url, array $postFields): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postFields),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            throw new \Exception('Curl error: ' . $error);
        }
        if (200 !== $httpCode) {
            throw new \Exception('Google Translate API error: ' . $response);
        }
        $result = json_decode($response, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \Exception('Invalid JSON response from Google Translate');
        }
        return $result;
    }
}
