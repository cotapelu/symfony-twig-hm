<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Message\Entity\Message;
use App\Message\DomainEvent\MessageEditedEvent;
use App\Message\Repository\MessageRepositoryInterface;
use App\Shared\DomainEvent\DomainEventPublisherInterface;
use Doctrine\ORM\EntityManagerInterface;

class EditMessageService
{
    private const EDIT_TIME_LIMIT = 15;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepositoryInterface $messageRepository,
        private DomainEventPublisherInterface $eventPublisher
    ) {
    }

    public function editMessage(string $messageId, string $userId, string $newContent): ?Message
    {
        $message = $this->messageRepository->findById($messageId);
        if (!$message) {
            return null;
        }

        $this->ensureEditable($message, $userId);
        $oldContent = $message->getContent();

        $this->applyEdit($message, $newContent, $userId, $oldContent);
        $this->entityManager->flush();
        $this->publishEditEvent($message, $oldContent, $newContent, $userId);

        return $message;
    }

    private function ensureEditable(Message $message, string $userId): void
    {
        if ($message->getSenderId() !== $userId) {
            throw new \Exception('You can only edit your own messages');
        }
        if ($message->isDeleted()) {
            throw new \Exception('Cannot edit deleted message');
        }
        $elapsedMinutes = (time() - $message->getCreatedAt()->getTimestamp()) / 60;
        if ($elapsedMinutes > self::EDIT_TIME_LIMIT) {
            throw new \Exception("Message can only be edited within " . self::EDIT_TIME_LIMIT . " minutes");
        }
    }

    private function applyEdit(Message $message, string $newContent, string $userId, string $oldContent): void
    {
        $message->setContent($newContent);
        $message->setEditedAt(new \DateTime());
        $message->setIsEdited(true);
        $message->setEditHistory([
            'original_content' => $oldContent,
            'edited_at' => (new \DateTime())->format(\DateTime::RFC3339),
            'edited_by' => $userId,
        ]);
    }

    private function publishEditEvent(Message $message, string $oldContent, string $newContent, string $userId): void
    {
        $event = new MessageEditedEvent(
            $message->getId(),
            $oldContent,
            $newContent,
            $userId,
            $message->getConversationId()
        );
        $this->eventPublisher->publish($event);
    }

    public function getEditHistory(string $messageId)
    {
        $message = $this->messageRepository->findById($messageId);
        if (!$message) {
            return [];
        }

        if (!$message->isEdited()) {
            return [];
        }

        return $message->getEditHistory() ?? [];
    }

    public function canEditMessage(string $messageId, string $userId)
    {
        $message = $this->messageRepository->findById($messageId);
        if (!$message) {
            return false;
        }

        if ($message->getSenderId() !== $userId) {
            return false;
        }

        if ($message->isDeleted()) {
            return false;
        }

        $createdAt = $message->getCreatedAt();
        $elapsedMinutes = (time() - $createdAt->getTimestamp()) / 60;

        return $elapsedMinutes <= $this::EDIT_TIME_LIMIT;
    }
}
