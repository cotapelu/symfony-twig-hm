<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Message\Entity\Message;
use App\Message\DomainEvent\MessagePinnedEvent;
use App\Message\Repository\MessageRepositoryInterface;
use App\Shared\DomainEvent\DomainEventPublisherInterface;
use Doctrine\ORM\EntityManagerInterface;

class PinMessageService
{
    private const MAX_PINNED_PER_CONVERSATION = 3;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepositoryInterface $messageRepository,
        private DomainEventPublisherInterface $eventPublisher
    ) {
    }

    public function pinMessage(string $messageId, string $userId)
    {
        $message = $this->messageRepository->findById($messageId);
        if (!$message) {
            return null;
        }

        if ($message->isDeleted()) {
            throw new \Exception('Cannot pin deleted message');
        }

        $conversationId = $message->getConversationId();

        if (!$this->messageRepository->isUserParticipant($conversationId, $userId)) {
            throw new \Exception('You are not a participant in this conversation');
        }

        $pinnedCount = $this->getPinnedCount($conversationId);
        if ($pinnedCount >= self::MAX_PINNED_PER_CONVERSATION) {
            throw new \Exception("Maximum " . self::MAX_PINNED_PER_CONVERSATION . " messages can be pinned per conversation");
        }

        $message->setIsPinned(true);

        $this->entityManager->flush();

        $event = new MessagePinnedEvent(
            $messageId,
            $conversationId,
            $userId,
            true
        );

        $this->eventPublisher->publish($event);

        return $message;
    }

    public function unpinMessage(string $messageId, string $userId)
    {
        $message = $this->messageRepository->findById($messageId);
        if (!$message) {
            return null;
        }

        if (!$message->isPinned()) {
            throw new \Exception('Message is not pinned');
        }

        $message->setIsPinned(false);

        $this->entityManager->flush();

        $event = new MessagePinnedEvent(
            $messageId,
            $message->getConversationId(),
            $userId,
            false
        );

        $this->eventPublisher->publish($event);

        return $message;
    }

    public function getPinnedMessages(string $conversationId, string $userId)
    {
        if (!$this->messageRepository->isUserParticipant($conversationId, $userId)) {
            throw new \Exception('You are not a participant in this conversation');
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('m')
           ->from(Message::class, 'm')
           ->where('m.conversationId = :conversationId')
           ->andWhere('m.isPinned = :isPinned')
           ->andWhere('m.isDeleted = :isDeleted')
           ->setParameter('conversationId', $conversationId)
           ->setParameter('isPinned', true)
           ->setParameter('isDeleted', false)
           ->orderBy('m.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function canPinMessage(string $messageId, string $userId)
    {
        $message = $this->messageRepository->findById($messageId);
        if (!$message) {
            return false;
        }

        if ($message->isDeleted()) {
            return false;
        }

        $conversationId = $message->getConversationId();

        if (!$this->messageRepository->isUserParticipant($conversationId, $userId)) {
            return false;
        }

        $pinnedCount = $this->getPinnedCount($conversationId);

        return $pinnedCount < $this::MAX_PINNED_PER_CONVERSATION;
    }

    private function getPinnedCount(string $conversationId)
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(m.id)')
           ->from(Message::class, 'm')
           ->where('m.conversationId = :conversationId')
           ->andWhere('m.isPinned = :isPinned')
           ->andWhere('m.isDeleted = :isDeleted')
           ->setParameter('conversationId', $conversationId)
           ->setParameter('isPinned', true)
           ->setParameter('isDeleted', false);

        $result = $qb->getQuery()->getSingleScalarResult();

        return (int) $result;
    }
}
