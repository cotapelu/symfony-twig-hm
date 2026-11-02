<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Exception\RateLimitException;
use App\Exception\ValidationException;
use App\Message\Entity\Message;
use App\Message\Event\MessageSentEvent;
use App\Message\Repository\MessageRepositoryInterface;
use App\Shared\Infrastructure\RateLimitService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CreateMessageUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $eventDispatcher,
        private RateLimitService $rateLimitService,
    ) {
    }

    public function execute(array $data): Message
    {
        $this->validateInput($data);
        $this->enforceRateLimit($data);

        $message = $this->createMessage($data);
        $this->persistAndFlush($message);
        $this->dispatchMessageSentEvent($message);

        return $message;
    }

    private function validateInput(array $data): void
    {
        $errors = [];
        if (empty($data['conversationId'])) {
            $errors[] = 'Conversation ID is required';
        }
        if (empty($data['content'])) {
            $errors[] = 'Message content is required';
        }
        if (empty($data['senderId'])) {
            $errors[] = 'Sender ID is required';
        }
        if (strlen($data['content'] ?? '') > 10000) {
            $errors[] = 'Message content too long (max 10000 characters)';
        }
        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }
    }

    private function enforceRateLimit(array $data): void
    {
        if (!$this->rateLimitService->checkLimit('send_message', $data['senderId'], 60, 60)) {
            throw new RateLimitException();
        }
    }

    private function createMessage(array $data): Message
    {
        return new Message(
            $data['conversationId'],
            $data['content'],
            $data['senderId'],
            $data['attachments'] ?? []
        );
    }

    private function persistAndFlush(Message $message): void
    {
        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }

    private function dispatchMessageSentEvent(Message $message): void
    {
        $this->eventDispatcher->dispatch(new MessageSentEvent(
            messageId: $message->getId(),
            conversationId: $message->getConversationId(),
            senderId: $message->getSenderId(),
            content: $message->getContent(),
            type: $message->getType() ?? 'text',
            createdAt: $message->getCreatedAt(),
            threadId: $message->getThreadId(),
            attachments: $message->getAttachments(),
        ));
    }
}
