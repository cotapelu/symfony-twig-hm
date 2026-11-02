<?php

declare(strict_types=1);

namespace App\Message\Service;

class FeedbackSuggestionGenerator implements ContentSuggestionGeneratorInterface
{
    public function canHandle(string $type): bool
    {
        return 'feedback' === $type;
    }

    public function generateSuggestions(): array
    {
        return [
            'Great work on this project!',
            'I appreciate your attention to detail.',
            'This looks excellent.',
            'Well done on meeting the deadline.',
            'Your contribution is valuable.',
        ];
    }
}
