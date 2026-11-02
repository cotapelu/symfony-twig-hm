<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Message\Repository\MessageRepositoryInterface;

/**
 * Use case for deleting a message.
 */
class DeleteMessageUseCase
{
    private MessageRepositoryInterface $messageRepository;

    public function __construct(MessageRepositoryInterface $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    /**
     * Execute the delete message use case.
     */
    public function execute(DeleteMessageRequest $request)
    {
        $message = $this->messageRepository->findById($request->messageId);
        if (!$message) {
            throw new \InvalidArgumentException('Message not found');
        }

        // Only allow deleting own messages or if user is conversation admin
        if ($message->getSenderId() !== $request->userId) {
            // In a real implementation, check if user has admin role for the conversation
            // For now, only allow deleting own messages
            throw new \InvalidArgumentException('Cannot delete other users messages');
        }

        // Check if message is within deletion time limit (e.g., 24 hours)
        $messageAge = time() - $message->getCreatedAt()->getTimestamp();
        if ($messageAge > 86400) { // 24 hours
            throw new \InvalidArgumentException('Message is too old to delete');
        }

        // Delete message
        $this->messageRepository->delete($request->messageId);

        return new DeleteMessageResponse(true);
    }
}

/**
 * Request DTO for DeleteMessageUseCase.
 */
class DeleteMessageRequest
{
    public string $messageId;
    public string $userId;

    public function __construct(string $messageId, string $userId)
    {
        $this->messageId = $messageId;
        $this->userId = $userId;
    }
}

/**
 * Response DTO for DeleteMessageUseCase.
 */
class DeleteMessageResponse
{
    public bool $success;

    public function __construct(bool $success)
    {
        $this->success = $success;
    }
}
