<?php

declare(strict_types=1);

namespace App\Message\Strategy;

/**
 * Media Message Strategy
 * Handles messages with file attachments.
 */
class MediaMessageStrategy implements MessageProcessingStrategyInterface
{
    public function getStrategyName(): string
    {
        return 'media';
    }

    public function canHandle(array $messageData): bool
    {
        return isset($messageData['attachments']) && !empty($messageData['attachments']);
    }

    public function validateMessage(array $messageData): bool
    {
        if (!isset($messageData['attachments']) || !is_array($messageData['attachments'])) {
            return false;
        }

        foreach ($messageData['attachments'] as $attachment) {
            if (!isset($attachment['id'], $attachment['name'], $attachment['url'])) {
                return false;
            }

            // Validate file type if needed
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'application/pdf'];
            if (isset($attachment['mime_type']) && !in_array($attachment['mime_type'], $allowedTypes)) {
                return false;
            }
        }

        return true;
    }

    public function processMessage(array $messageData): array
    {
        // Process attachments - could add thumbnails, metadata, etc.
        if (isset($messageData['attachments'])) {
            foreach ($messageData['attachments'] as &$attachment) {
                // Add processed metadata
                $attachment['processed_at'] = date('c');
                $attachment['size_mb'] = round(($attachment['size'] ?? 0) / 1024 / 1024, 2);
            }
        }

        return $messageData;
    }
}
