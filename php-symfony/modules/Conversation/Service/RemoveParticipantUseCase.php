<?php

declare(strict_types=1);

namespace App\Conversation\Service;

use App\Conversation\Entity\Conversation;
use App\Conversation\Repository\ConversationRepositoryInterface;

/**
 * Use case for removing a participant from a conversation.
 */
class RemoveParticipantUseCase
{
    private ConversationRepositoryInterface $conversationRepository;

    public function __construct(ConversationRepositoryInterface $conversationRepository)
    {
        $this->conversationRepository = $conversationRepository;
    }

    /**
     * Execute the remove participant use case.
     */
    public function execute(RemoveParticipantRequest $request)
    {
        $conversation = $this->conversationRepository->findById($request->conversationId);
        if (!$conversation) {
            throw new \InvalidArgumentException('Conversation not found');
        }

        // Check if participant is in conversation
        if (!$conversation->hasParticipant($request->participantId)) {
            throw new \InvalidArgumentException('User is not a participant in this conversation');
        }

        // Remove participant
        $conversation->removeParticipant($request->participantId);

        // Save conversation
        $this->conversationRepository->save($conversation);

        return new RemoveParticipantResponse($conversation);
    }
}

/**
 * Response DTO for RemoveParticipantUseCase.
 */
class RemoveParticipantResponse
{
    public function __construct(
        public Conversation $conversation,
    ) {
    }
}
