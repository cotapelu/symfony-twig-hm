<?php

declare(strict_types=1);

namespace App\Message\Service;

class MeetingSuggestionGenerator implements ContentSuggestionGeneratorInterface
{
    public function canHandle(string $type): bool
    {
        return 'meeting' === $type;
    }

    public function generateSuggestions(): array
    {
        return [
            "Let's schedule a follow-up meeting.",
            'I suggest we create an action plan.',
            'We should assign responsibilities.',
            "Let's set clear deadlines.",
            'I recommend documenting our decisions.',
        ];
    }
}
