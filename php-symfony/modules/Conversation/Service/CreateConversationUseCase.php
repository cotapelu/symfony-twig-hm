<?php

declare(strict_types=1);

namespace App\Conversation\Service;

use App\Conversation\Entity\Conversation;
use App\Conversation\Repository\ConversationRepositoryInterface;

/**
 * Use case for creating a new conversation.
 */
class CreateConversationUseCase
{
    private ConversationRepositoryInterface $conversationRepository;

    public function __construct(ConversationRepositoryInterface $conversationRepository)
    {
        $this->conversationRepository = $conversationRepository;
    }

    /**
     * Execute the create conversation use case.
     */
    public function execute(CreateConversationRequest $request)
    {
        $userId = $this->requireUserId($request);
        $this->requireNonEmptyTitle($request->title);

        $participantIds = $this->resolveParticipants($request->participantIds ?? [], $request->isPublic, $userId);

        $conversation = $this->createConversationEntity($request, $participantIds, $userId);
        $this->conversationRepository->save($conversation);

        return new CreateConversationResponse($conversation);
    }

    private function requireUserId(CreateConversationRequest $request): string
    {
        $userId = $request->getUserId();
        if (!$userId) {
            throw new \InvalidArgumentException('User ID is required');
        }
        return $userId;
    }

    private function requireNonEmptyTitle(string $title): void
    {
        if (empty(trim($title))) {
            throw new \InvalidArgumentException('Conversation title cannot be empty');
        }
    }

    private function resolveParticipants(array $participantIds, bool $isPublic, string $userId): array
    {
        if (empty($participantIds) && !$isPublic) {
            throw new \InvalidArgumentException('At least one participant is required for private conversations');
        }

        $participantIds = array_unique($participantIds);

        $participantCount = count($participantIds);
        $maxParticipants = (int) ($_ENV['MAX_CONVERSATION_PARTICIPANTS'] ?? 1000);
        if ($participantCount > $maxParticipants) {
            throw new \InvalidArgumentException("Conversation cannot exceed {$maxParticipants} participants");
        }

        if (!$isPublic && !in_array($userId, $participantIds)) {
            $participantIds[] = $userId;
            $participantIds = array_unique($participantIds);
        }

        return $participantIds;
    }

    private function createConversationEntity(CreateConversationRequest $request, array $participantIds, string $userId): Conversation
    {
        $conversationId = uniqid('conv_', true);
        $conversation = new Conversation(
            $conversationId,
            trim($request->title),
            'group',
            $userId
        );

        foreach ($participantIds as $participantId) {
            $conversation->addParticipant($participantId);
        }

        if ($request->description) {
            $conversation->setDescription(trim($request->description));
        }
        if ($request->isPublic) {
            $conversation->setIsPublic($request->isPublic);
        }

        return $conversation;
    }
}

/**
 * Response DTO for CreateConversationUseCase.
 */
class CreateConversationResponse
{
    public Conversation $conversation;

    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation;
    }
}
