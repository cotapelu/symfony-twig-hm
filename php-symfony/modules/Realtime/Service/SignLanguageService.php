<?php

declare(strict_types=1);

namespace App\Realtime\Service;

class SignLanguageService
{
    private array $interpreters = [
        'asl' => 'American Sign Language', 'bsl' => 'British Sign Language',
        'fsl' => 'French Sign Language', 'dgs' => 'German Sign Language',
        'lis' => 'Italian Sign Language', 'lsp' => 'Portuguese Sign Language',
        'rsl' => 'Russian Sign Language', 'jsl' => 'Japanese Sign Language',
        'ksl' => 'Korean Sign Language', 'csl' => 'Chinese Sign Language',
        'arsl' => 'Arabic Sign Language', 'isl' => 'Indian Sign Language',
    ];

    public function isSupported(string $signLanguage)
    {
        return isset($this->interpreters[$signLanguage]);
    }

    public function getAll()
    {
        return $this->interpreters;
    }

    public function startInterpretation(string $callId, string $sourceLanguage, string $targetSignLanguage)
    {
        return [
            'interpreter_id' => 'int_' . uniqid(),
            'call_id' => $callId,
            'source_language' => $sourceLanguage,
            'target_sign_language' => $targetSignLanguage,
            'status' => 'connecting',
            'estimated_delay' => 2.0,
        ];
    }

    public function stopInterpretation(string $interpreterId)
    {
        return true;
    }
}
