<?php

declare(strict_types=1);

namespace App\Realtime\Service;

/**
 * Service for managing typing indicators in conversations.
 */
class TypingIndicatorService
{
    private array $typingUsers = []; // ['conversationId' => ['userId' => timestamp]]

    /**
     * Mark user as typing in a conversation.
     */
    public function startTyping(string $conversationId, string $userId)
    {
        if (!isset($this->typingUsers[$conversationId])) {
            $this->typingUsers[$conversationId] = [];
        }

        $this->typingUsers[$conversationId][$userId] = time();
    }

    /**
     * Mark user as stopped typing in a conversation.
     */
    public function stopTyping(string $conversationId, string $userId)
    {
        if (isset($this->typingUsers[$conversationId][$userId])) {
            unset($this->typingUsers[$conversationId][$userId]);

            // Clean up empty conversation arrays
            if (empty($this->typingUsers[$conversationId])) {
                unset($this->typingUsers[$conversationId]);
            }
        }
    }

    /**
     * Get users currently typing in a conversation.
     */
    public function getTypingUsers(string $conversationId)
    {
        if (!isset($this->typingUsers[$conversationId])) {
            return [];
        }

        $currentTime = time();
        $activeUsers = [];

        foreach ($this->typingUsers[$conversationId] as $userId => $timestamp) {
            // Consider typing active for 5 seconds after last activity
            if ($currentTime - $timestamp < 5) {
                $activeUsers[] = $userId;
            } else {
                // Remove expired typing indicators
                unset($this->typingUsers[$conversationId][$userId]);
            }
        }

        // Clean up empty conversation arrays
        if (empty($this->typingUsers[$conversationId])) {
            unset($this->typingUsers[$conversationId]);
        }

        return $activeUsers;
    }

    /**
     * Get typing status for multiple conversations.
     */
    public function getTypingStatus(array $conversationIds)
    {
        $status = [];
        foreach ($conversationIds as $conversationId) {
            $status[$conversationId] = $this->getTypingUsers($conversationId);
        }

        return $status;
    }

    /**
     * Clean up expired typing indicators.
     */
    public function cleanup()
    {
        $currentTime = time();

        foreach ($this->typingUsers as $conversationId => $users) {
            foreach ($users as $userId => $timestamp) {
                if ($currentTime - $timestamp >= 5) {
                    unset($this->typingUsers[$conversationId][$userId]);
                }
            }

            if (empty($this->typingUsers[$conversationId])) {
                unset($this->typingUsers[$conversationId]);
            }
        }
    }

    /**
     * Get all conversations with active typing.
     */
    public function getActiveConversations()
    {
        $this->cleanup();

        return array_keys($this->typingUsers);
    }
}
