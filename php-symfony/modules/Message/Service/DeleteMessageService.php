<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Message\Entity\Message;
use App\Message\DomainEvent\MessageDeletedEvent;
use App\Message\Repository\MessageRepositoryInterface;
use App\Shared\DomainEvent\DomainEventPublisherInterface;
use Doctrine\ORM\EntityManagerInterface;

class DeleteMessageService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepositoryInterface $messageRepository,
        private DomainEventPublisherInterface $eventPublisher
    ) {
    }

    public function deleteMessage(string $messageId, string $userId, bool $forEveryone = false)
    {
        $message = $this->messageRepository->findById($messageId);
        if (!$message) {
            return null;
        }

        if ($message->getSenderId() !== $userId && !$this->canModerateMessage($userId, $message->getConversationId())) {
            throw new \Exception('You can only delete your own messages');
        }

        if ($message->isDeleted()) {
            throw new \Exception('Message already deleted');
        }

        $message->setIsDeleted(true);
        $message->setDeletedAt(new \DateTime());
        $message->setDeletedBy($userId);

        $this->entityManager->flush();

        $event = new MessageDeletedEvent(
            $messageId,
            $userId,
            $forEveryone,
            $message->getConversationId()
        );

        $this->eventPublisher->publish($event);

        return $message;
    }

    public function undeleteMessage(string $messageId, string $userId)
    {
        $message = $this->messageRepository->findById($messageId);
        if (!$message) {
            return null;
        }

        if ($message->getSenderId() !== $userId) {
            throw new \Exception('You can only undelete your own messages');
        }

        if (!$message->isDeleted()) {
            return $message;
        }

        $elapsedMinutes = (time() - $message->getDeletedAt()->getTimestamp()) / 60;
        if ($elapsedMinutes > 30) {
            throw new \Exception('Can only undelete messages within 30 minutes of deletion');
        }

        $message->setIsDeleted(false);
        $message->setDeletedAt(null);
        $message->setDeletedBy(null);

        $this->entityManager->flush();

        return $message;
    }

    public function canDeleteMessage(string $messageId, string $userId)
    {
        $message = $this->messageRepository->findById($messageId);
        if (!$message) {
            return false;
        }

        if ($message->isDeleted()) {
            return false;
        }

        if ($message->getSenderId() === $userId) {
            return true;
        }

        return $this->canModerateMessage($userId, $message->getConversationId());
    }

    private function canModerateMessage(string $userId, string $conversationId)
    {
        return $this->messageRepository->isConversationAdmin($conversationId, $userId);
    }
}
