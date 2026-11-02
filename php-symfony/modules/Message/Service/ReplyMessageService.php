<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Message\Entity\Message;
use App\Message\DomainEvent\MessageRepliedEvent;
use App\Message\Repository\MessageRepositoryInterface;
use App\Shared\Service\UuidService;
use App\Shared\DomainEvent\DomainEventPublisherInterface;
use Doctrine\ORM\EntityManagerInterface;

class ReplyMessageService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepositoryInterface $messageRepository,
        private UuidService $uuidService,
        private DomainEventPublisherInterface $eventPublisher
    ) {
    }

    public function replyToMessage(
        string $replyToMessageId,
        string $userId,
        string $content,
        string $type = 'text',
        ?array $attachments = null
    ): Message {
        $replyToMessage = $this->findReplyToMessage($replyToMessageId);
        $this->ensureMessageNotDeleted($replyToMessage);
        $conversationId = $replyToMessage->getConversationId();
        $this->ensureUserParticipant($conversationId, $userId);

        $threadId = $replyToMessage->getThreadId() ?? $replyToMessage->getId();
        $reply = $this->buildReplyMessage($replyToMessageId, $userId, $content, $type, $attachments, $threadId, $conversationId);
        $this->persistAndFlush($reply);
        $this->publishReplyEvent($reply, $replyToMessageId, $threadId, $conversationId, $userId);

        return $reply;
    }

    private function findReplyToMessage(string $replyToMessageId): Message
    {
        $message = $this->messageRepository->findById($replyToMessageId);
        if (!$message) {
            throw new \Exception('Message to reply to not found');
        }
        return $message;
    }

    private function ensureMessageNotDeleted(Message $message): void
    {
        if ($message->isDeleted()) {
            throw new \Exception('Cannot reply to deleted message');
        }
    }

    private function ensureUserParticipant(string $conversationId, string $userId): void
    {
        if (!$this->messageRepository->isUserParticipant($conversationId, $userId)) {
            throw new \Exception('You are not a participant in this conversation');
        }
    }

    private function buildReplyMessage(
        string $replyToMessageId,
        string $userId,
        string $content,
        string $type,
        ?array $attachments,
        string $threadId,
        string $conversationId
    ): Message {
        $messageId = $this->uuidService->generate();
        $reply = new Message(
            id: $messageId,
            conversationId: $conversationId,
            senderId: $userId,
            content: $content,
            type: $type,
            threadId: $threadId
        );
        $reply->setReplyToMessageId($replyToMessageId);
        $reply->setAttachments($attachments);
        return $reply;
    }

    private function persistAndFlush(Message $message): void
    {
        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }

    private function publishReplyEvent(
        Message $reply,
        string $replyToMessageId,
        string $threadId,
        string $conversationId,
        string $userId
    ): void {
        $event = new MessageRepliedEvent(
            $reply->getId(),
            $replyToMessageId,
            $threadId,
            $conversationId,
            $userId
        );
        $this->eventPublisher->publish($event);
    }

    public function getThread(string $messageId, string $userId)
    {
        $originalMessage = $this->messageRepository->findById($messageId);
        if (!$originalMessage) {
            return [];
        }

        $threadId = $originalMessage->getThreadId() ?? $originalMessage->getId();

        $threadMessages = [];
        $currentMessageId = $messageId;

        while ($currentMessageId !== null) {
            $currentMessage = $this->messageRepository->findById($currentMessageId);
            if (!$currentMessage) {
                break;
            }

            $threadMessages[] = $currentMessage;

            if ($currentMessage->getReplyToMessageId()) {
                $currentMessageId = $currentMessage->getReplyToMessageId();
            } else {
                break;
            }

            if (count($threadMessages) > 50) {
                break;
            }
        }

        return array_reverse($threadMessages);
    }

    public function getThreadReplies(string $messageId, string $userId, int $limit = 20)
    {
        $originalMessage = $this->messageRepository->findById($messageId);
        if (!$originalMessage) {
            return [];
        }

        $threadId = $originalMessage->getThreadId() ?? $originalMessage->getId();

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('m')
           ->from(Message::class, 'm')
           ->where('m.threadId = :threadId')
           ->setParameter('threadId', $threadId)
           ->andWhere('m.id != :originalId')
           ->setParameter('originalId', $messageId)
           ->orderBy('m.createdAt', 'ASC')
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
