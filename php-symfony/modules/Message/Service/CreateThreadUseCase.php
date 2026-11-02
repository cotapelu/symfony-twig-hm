<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Message\Repository\MessageRepositoryInterface;
use App\Message\Repository\ThreadRepositoryInterface;

/**
 * Use case for creating a new message thread.
 */
class CreateThreadUseCase
{
    private ThreadRepositoryInterface $threadRepository;
    private MessageRepositoryInterface $messageRepository;

    public function __construct(
        ThreadRepositoryInterface $threadRepository,
        MessageRepositoryInterface $messageRepository,
    ) {
        $this->threadRepository = $threadRepository;
        $this->messageRepository = $messageRepository;
    }

    /**
     * Execute the create thread use case.
     */
    public function execute(CreateThreadRequest $request)
    {
        // Validate that the root message exists
        $rootMessage = $this->messageRepository->findById($request->rootMessageId);
        if (!$rootMessage) {
            throw new \InvalidArgumentException('Root message not found');
        }

        // Check if message is already in a thread
        if ($this->threadRepository->isInThread($request->rootMessageId)) {
            throw new \InvalidArgumentException('Message is already part of a thread');
        }

        // Generate thread ID
        $threadId = $request->threadId ?: uniqid('thread_', true);

        // Create the thread
        $this->threadRepository->createThread($threadId, $request->rootMessageId, $rootMessage->getConversationId());

        return new CreateThreadResponse($threadId, $rootMessage);
    }
}

/**
 * Response DTO for CreateThreadUseCase.
 */
class CreateThreadResponse
{
    public string $threadId;
    public \App\Message\Entity\Message $rootMessage;

    public function __construct(string $threadId, \App\Message\Entity\Message $rootMessage)
    {
        $this->threadId = $threadId;
        $this->rootMessage = $rootMessage;
    }
}
