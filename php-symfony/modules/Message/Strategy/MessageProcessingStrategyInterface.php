<?php

declare(strict_types=1);

namespace App\Message\Strategy;

/**
 * Strategy Pattern: Message Processing Strategy.
 *
 * Allows different message processing implementations without modifying existing code
 */
interface MessageProcessingStrategyInterface
{
    /**
     * Process a message before saving.
     */
    public function processMessage(array $messageData): array;

    /**
     * Validate message content.
     */
    public function validateMessage(array $messageData): bool;

    /**
     * Get strategy name.
     */
    public function getStrategyName(): string;

    /**
     * Check if this strategy can handle the message.
     */
    public function canHandle(array $messageData): bool;
}
