<?php

declare(strict_types=1);

namespace App\Message\Service;

class MessageSuggestionGenerator implements ContentSuggestionGeneratorInterface
{
    public function canHandle(string $type): bool
    {
        return 'message' === $type;
    }

    public function generateSuggestions(): array
    {
        return [
            'How can I help you today?',
            'Let me know if you need any assistance.',
            "I'm here to help with any questions.",
            'What would you like to discuss?',
            'Feel free to ask me anything.',
        ];
    }
}
