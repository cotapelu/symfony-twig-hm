<?php

declare(strict_types=1);

namespace App\Message\Strategy;

/**
 * Basic Text Message Strategy
 * Handles regular text messages without special processing.
 */
class TextMessageStrategy implements MessageProcessingStrategyInterface
{
    public function getStrategyName(): string
    {
        return 'text';
    }

    public function canHandle(array $messageData): bool
    {
        return isset($messageData['type']) && 'text' === $messageData['type'];
    }

    public function validateMessage(array $messageData): bool
    {
        return isset($messageData['content'])
               && is_string($messageData['content'])
               && strlen(trim($messageData['content'])) > 0
               && strlen($messageData['content']) <= 2000; // Max length
    }

    public function processMessage(array $messageData): array
    {
        // Basic text processing - trim whitespace, basic sanitization
        $messageData['content'] = trim($messageData['content']);

        // Remove excessive line breaks
        $messageData['content'] = preg_replace('/\n{3,}/', "\n\n", $messageData['content']);

        return $messageData;
    }
}
