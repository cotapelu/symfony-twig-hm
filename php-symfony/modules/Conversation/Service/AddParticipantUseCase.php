<?php

declare(strict_types=1);

namespace App\Conversation\Service;

use App\Conversation\Entity\Conversation;
use App\Conversation\Repository\ConversationRepositoryInterface;

/**
 * Use case for adding a participant to a conversation.
 */
class AddParticipantUseCase
{
    private ConversationRepositoryInterface $conversationRepository;

    public function __construct(ConversationRepositoryInterface $conversationRepository)
    {
        $this->conversationRepository = $conversationRepository;
    }

    /**
     * Execute the add participant use case.
     */
    public function execute(AddParticipantRequest $request)
    {
        $conversation = $this->conversationRepository->findById($request->conversationId);
        if (!$conversation) {
            throw new \InvalidArgumentException('Conversation not found');
        }

        // Check if participant is already in conversation
        if ($conversation->hasParticipant($request->participantId)) {
            throw new \InvalidArgumentException('User is already a participant in this conversation');
        }

        // Add participant
        $conversation->addParticipant($request->participantId);

        // Save conversation
        $this->conversationRepository->save($conversation);

        return new AddParticipantResponse($conversation);
    }
}

/**
 * Response DTO for AddParticipantUseCase.
 */
class AddParticipantResponse
{
    public function __construct(
        public Conversation $conversation,
    ) {
    }
}
