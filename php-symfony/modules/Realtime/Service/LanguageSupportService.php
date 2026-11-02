<?php

declare(strict_types=1);

namespace App\Realtime\Service;

class LanguageSupportService
{
    private array $supportedLanguages = [
        'en' => 'English', 'es' => 'Spanish', 'fr' => 'French',
        'de' => 'German', 'it' => 'Italian', 'pt' => 'Portuguese',
        'ru' => 'Russian', 'ja' => 'Japanese', 'ko' => 'Korean',
        'zh' => 'Chinese', 'ar' => 'Arabic', 'hi' => 'Hindi',
    ];

    public function isSupported(string $language)
    {
        return isset($this->supportedLanguages[$language]);
    }

    public function getAll()
    {
        return $this->supportedLanguages;
    }

    public function getName(string $language)
    {
        return $this->supportedLanguages[$language] ?? null;
    }
}
