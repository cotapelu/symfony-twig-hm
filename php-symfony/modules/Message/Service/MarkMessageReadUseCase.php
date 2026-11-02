<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Message\Repository\MessageRepositoryInterface;

/**
 * Use case for marking a message as read.
 */
class MarkMessageReadUseCase
{
    private MessageRepositoryInterface $messageRepository;

    public function __construct(MessageRepositoryInterface $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    /**
     * Execute the mark message read use case.
     */
    public function execute(MarkMessageReadRequest $request)
    {
        $message = $this->messageRepository->findById($request->messageId);
        if (!$message) {
            throw new \InvalidArgumentException('Message not found');
        }

        // Only allow marking as read if user is not the sender
        if ($message->getSenderId() === $request->userId) {
            throw new \InvalidArgumentException('Cannot mark own message as read');
        }

        // Set delivered and read timestamps
        $now = new \DateTime();
        $message->setDeliveredAt($message->getDeliveredAt() ?? $now);
        $message->setReadAt($now);

        // Save message
        $this->messageRepository->save($message);

        return new MarkMessageReadResponse($message);
    }
}

/**
 * Response DTO for MarkMessageReadUseCase.
 */
class MarkMessageReadResponse
{
    public \App\Message\Entity\Message $message;

    public function __construct(\App\Message\Entity\Message $message)
    {
        $this->message = $message;
    }
}
