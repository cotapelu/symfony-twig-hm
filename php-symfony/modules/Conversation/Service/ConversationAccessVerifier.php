<?php

declare(strict_types=1);

namespace App\Conversation\Service;

use App\Conversation\Repository\ConversationRepositoryInterface;

/**
 * Implementation of conversation access verifier.
 * Provides limited access to conversation module for Message module.
 */
class ConversationAccessVerifier implements ConversationAccessVerifierInterface
{
    public function __construct(
        private ConversationRepositoryInterface $conversationRepository,
    ) {
    }

    public function isParticipant(string $conversationId, string $userId): bool
    {
        $conversation = $this->conversationRepository->findById((int)$conversationId);
        if (!$conversation) {
            return false;
        }
        return in_array($userId, $conversation->getParticipantIds(), true);
    }

    public function exists(string $conversationId): bool
    {
        $id = (int) $conversationId;
        $conversation = $this->conversationRepository->findById($id);
        return null !== $conversation;
    }

    public function getUserConversationIds(string $userId): array
    {
        return $this->conversationRepository->findConversationIdsByParticipant($userId);
    }

    public function getParticipantIds(string $conversationId): array
    {
        $id = (int) $conversationId;
        $conversation = $this->conversationRepository->findById($id);
        if (!$conversation) {
            return [];
        }
        return $conversation->getParticipantIds();
    }
}
