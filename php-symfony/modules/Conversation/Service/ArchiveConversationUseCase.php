<?php

declare(strict_types=1);

namespace App\Conversation\Service;

use App\Conversation\Entity\Conversation;
use App\Conversation\Repository\ConversationRepositoryInterface;

class ArchiveConversationUseCase
{
    private ConversationRepositoryInterface $conversationRepository;

    public function __construct(ConversationRepositoryInterface $conversationRepository)
    {
        $this->conversationRepository = $conversationRepository;
    }

    public function execute(ArchiveConversationRequest $request)
    {
        $conversation = $this->conversationRepository->findById($request->conversationId);
        if (!$conversation) {
            throw new \InvalidArgumentException('Conversation not found');
        }

        if (!$conversation->hasParticipant($request->userId)) {
            throw new \InvalidArgumentException('User is not a participant in this conversation');
        }

        if ($request->archive) {
            $conversation->archive();
        } else {
            $conversation->unarchive();
        }

        $this->conversationRepository->save($conversation);

        return new ArchiveConversationResponse($conversation);
    }
}

class ArchiveConversationRequest
{
    public string $conversationId;
    public string $userId;
    public bool $archive;

    public function __construct(string $conversationId, string $userId, bool $archive)
    {
        $this->conversationId = $conversationId;
        $this->userId = $userId;
        $this->archive = $archive;
    }
}

class ArchiveConversationResponse
{
    public Conversation $conversation;

    public function __construct(Conversation $conversation)
    {
        $this->conversation = $conversation;
    }
}
