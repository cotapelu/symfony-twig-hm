<?php

declare(strict_types=1);

namespace App\Conversation\Service;

/**
 * Interface for verifying conversation access without exposing full repository.
 * Following DDD-Lite principle: Cross-module communication via interfaces.
 */
interface ConversationAccessVerifierInterface
{
    /**
     * Check if a user is a participant in a conversation.
     */
    public function isParticipant(string $conversationId, string $userId): bool;

    /**
     * Check if conversation exists.
     */
    public function exists(string $conversationId): bool;

    /**
     * Get all conversation IDs for a user.
     */
    public function getUserConversationIds(string $userId): array;

    /**
     * Get participant IDs for a specific conversation.
     */
    public function getParticipantIds(string $conversationId): array;
}
