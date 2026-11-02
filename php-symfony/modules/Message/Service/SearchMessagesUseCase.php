<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Message\Repository\MessageRepositoryInterface;

/**
 * Use case for searching messages.
 */
class SearchMessagesUseCase
{
    private MessageRepositoryInterface $messageRepository;

    public function __construct(
        MessageRepositoryInterface $messageRepository,
    ) {
        $this->messageRepository = $messageRepository;
    }

    /**
     * Execute the search messages use case.
     */
    public function execute(SearchMessagesRequest $request)
    {
        // Perform search
        $messages = $this->messageRepository->searchMessages(
            $request->query,
            $request->conversationId,
            $request->userId,
            $request->limit
        );

        // Get unique conversation IDs from messages
        $conversationIds = array_unique(array_column($messages, 'conversationId'));

        return new SearchMessagesResponse($messages, $conversationIds);
    }
}

/**
 * Response DTO for SearchMessagesUseCase.
 */
class SearchMessagesResponse
{
    public array $messages;
    public array $conversations;

    public function __construct(array $messages, array $conversations = [])
    {
        $this->messages = $messages;
        $this->conversations = $conversations;
    }
}
