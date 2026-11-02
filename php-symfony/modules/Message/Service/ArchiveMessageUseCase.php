<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Message\Entity\Message as MessageEntity;
use App\Message\Repository\MessageRepositoryInterface;

/**
 * Use case for archiving a message.
 */
class ArchiveMessageUseCase
{
    private MessageRepositoryInterface $messageRepository;

    public function __construct(MessageRepositoryInterface $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    /**
     * Execute the archive message use case.
     */
    public function execute(ArchiveMessageRequest $request)
    {
        $message = $this->messageRepository->findById($request->messageId);
        if (!$message) {
            throw new \InvalidArgumentException('Message not found');
        }

        // Only allow archiving own messages or if user has admin role
        if ($message->getSenderId() !== $request->userId) {
            // In a real implementation, check if user has admin role for the conversation
            // For now, only allow archiving own messages
            throw new \InvalidArgumentException('Cannot archive other users messages');
        }

        // For archiving, we can add an archived flag or move to archive table
        // For now, we'll mark as deleted (soft delete) by modifying content
        $message->setContent('[Archived by '.$request->userId.'] '.$message->getContent());

        // Save message
        $this->messageRepository->save($message);

        return new ArchiveMessageResponse($message);
    }
}

/**
 * Request DTO for ArchiveMessageUseCase.
 */
class ArchiveMessageRequest
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
 * Response DTO for ArchiveMessageUseCase.
 */
class ArchiveMessageResponse
{
    public MessageEntity $message;

    public function __construct(MessageEntity $message)
    {
        $this->message = $message;
    }
}
