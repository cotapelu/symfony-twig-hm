<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Message\Entity\Message;
use App\Message\DomainEvent\MessageForwardedEvent;
use App\Message\Repository\MessageRepositoryInterface;
use App\Shared\Service\UuidService;
use App\Shared\DomainEvent\DomainEventPublisherInterface;
use Doctrine\ORM\EntityManagerInterface;

class ForwardMessageService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepositoryInterface $messageRepository,
        private UuidService $uuidService,
        private DomainEventPublisherInterface $eventPublisher
    ) {
    }

    public function forwardMessage(
        string $originalMessageId,
        string $userId,
        string $targetConversationId,
        bool $includeCaption = true,
        ?string $caption = null
    ) {
        $originalMessage = $this->messageRepository->findById($originalMessageId);
        if (!$originalMessage) {
            throw new \Exception('Original message not found');
        }
        $this->validateOriginalMessage($originalMessage);
        $this->validateTargetConversation($targetConversationId, $userId);

        $forwardedMessage = $this->createForwardedMessage($originalMessage, $userId, $targetConversationId, $includeCaption, $caption);
        $this->publishForwardEvent($forwardedMessage, $originalMessageId, $originalMessage->getConversationId(), $targetConversationId, $userId);

        return $forwardedMessage;
    }

    private function validateOriginalMessage(Message $message): void
    {
        if ($message->isDeleted()) {
            throw new \Exception('Cannot forward deleted message');
        }
    }

    private function validateTargetConversation(string $conversationId, string $userId): void
    {
        if (!$this->messageRepository->isUserParticipant($conversationId, $userId)) {
            throw new \Exception('You are not a participant in target conversation');
        }
    }

    private function determineForwardCaption(Message $original, bool $includeCaption, ?string $caption): ?string
    {
        if (!$includeCaption) {
            return null;
        }
        if ($caption !== null) {
            return $caption;
        }
        return $original->getType() === 'text' ? 'Forwarded message' : 'Forwarded attachment';
    }

    private function determineForwardedContent(Message $original, bool $includeCaption): ?string
    {
        if ($original->getType() === 'text') {
            return $original->getContent();
        }
        return null;
    }

    private function buildForwardMetadata(Message $original, string $userId, bool $includeCaption, ?string $caption): array
    {
        $metadata = [
            'forwarded_from_message_id' => $original->getId(),
            'forwarded_from_conversation_id' => $original->getConversationId(),
            'forwarded_by' => $userId,
            'original_type' => $original->getType(),
            'original_sender_id' => $original->getSenderId(),
            'forwarded_at' => (new \DateTime())->format(\DateTime::RFC3339),
        ];
        if ($includeCaption && $caption) {
            $metadata['caption'] = $caption;
        }
        return $metadata;
    }

    private function persistAndFlush(Message $message): void
    {
        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }

    private function createForwardedMessage(Message $original, string $userId, string $targetConversationId, bool $includeCaption, ?string $caption): Message {
        $forwardCaption = $this->determineForwardCaption($original, $includeCaption, $caption);
        $forwardedContent = $this->determineForwardedContent($original, $includeCaption);
        $forwardMetadata = $this->buildForwardMetadata($original, $userId, $includeCaption, $caption);

        $messageId = $this->uuidService->generate();
        $forwardedMessage = $this->buildMessageEntity($messageId, $targetConversationId, $userId, $forwardedContent);
        $forwardedMessage->setReplyToMessageId(null);

        if ($original->getAttachments()) {
            $forwardedMessage->setAttachments($original->getAttachments());
        }
        $forwardedMessage->setAttachments($forwardMetadata);

        return $forwardedMessage;
    }

    private function buildMessageEntity(string $id, string $conversationId, string $senderId, ?string $content): Message
    {
        return new Message(
            id: $id,
            conversationId: $conversationId,
            senderId: $senderId,
            content: $content ?? '',
            type: 'forward',
        );
    }

    private function publishForwardEvent(
        Message $forwardedMessage,
        string $originalMessageId,
        string $originalConversationId,
        string $targetConversationId,
        string $userId
    ): void {
        $event = new MessageForwardedEvent(
            $forwardedMessage->getId(),
            $originalMessageId,
            $originalConversationId,
            $targetConversationId,
            $userId
        );
        $this->eventPublisher->publish($event);
    }

    public function getForwardedMessages(string $conversationId, string $userId, int $limit = 20)
    {
        if (!$this->messageRepository->isUserParticipant($conversationId, $userId)) {
            throw new \Exception('You are not a participant in this conversation');
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('m')
           ->from(Message::class, 'm')
           ->where('m.conversationId = :conversationId')
           ->andWhere('m.type = :type')
           ->setParameter('conversationId', $conversationId)
           ->setParameter('type', 'forward')
           ->orderBy('m.createdAt', 'DESC')
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getForwardChain(string $messageId, string $userId): array
    {
        $message = $this->messageRepository->findById($messageId);
        if (!$message || $message->getType() !== 'forward') {
            return [];
        }
        if (!is_array($message->getAttachments())) {
            return [];
        }
        return $this->buildForwardChain($messageId, 10);
    }

    private function buildForwardChain(string $startMessageId, int $maxDepth): array
    {
        $chain = [];
        $currentId = $startMessageId;
        $depth = 0;
        while ($currentId !== null && $depth < $maxDepth) {
            $msg = $this->messageRepository->findById($currentId);
            if (!$msg || $msg->getType() !== 'forward') {
                break;
            }
            $att = $msg->getAttachments();
            if (!is_array($att) || !isset($att['forwarded_from_message_id'])) {
                break;
            }
            $chain[] = [
                'message_id' => $msg->getId(),
                'original_message_id' => $att['forwarded_from_message_id'],
                'forwarded_at' => $att['forwarded_at'],
                'caption' => $att['caption'] ?? null,
            ];
            $currentId = $att['forwarded_from_message_id'];
            $depth++;
        }
        return $chain;
    }
}
