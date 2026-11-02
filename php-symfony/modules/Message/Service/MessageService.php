<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Conversation\Service\ConversationAccessVerifierInterface;
use App\Message\Entity\Message;
use App\Message\Event\MessageSentEvent;
use App\Message\Repository\MessageRepositoryInterface;
use App\Shared\EventBus\EventBus;
use App\Shared\Service\UuidService;
use Doctrine\ORM\EntityManagerInterface;

final class MessageService implements MessageServiceInterface
{
    private const MAX_MESSAGE_LENGTH = 10000;
    private const MAX_ATTACHMENTS = 10;
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    public function __construct(
        private MessageRepositoryInterface $repository,
        private ConversationAccessVerifierInterface $conversationAccessVerifier,
        private EventBus $eventBus,
        private UuidService $uuidService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    // ==================== Message Sending Helpers ====================

    private function validateFiles(array $files): void
    {
        if (count($files) > self::MAX_ATTACHMENTS) {
            throw new \InvalidArgumentException('Too many files (max ' . self::MAX_ATTACHMENTS . ')');
        }
        foreach ($files as $file) {
            $this->validateFile($file);
        }
    }

    private function validateFile(array $file): void
    {
        if (!is_array($file) || !isset($file['name']) || !isset($file['size']) || !isset($file['type'])) {
            throw new \InvalidArgumentException('Invalid file format');
        }
        if ($file['size'] > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException('File too large (max ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB)');
        }
        $this->validateFileType($file['type']);
    }

    private function validateFileType(string $type): void
    {
        $allowedTypes = ['image/', 'video/', 'audio/', 'application/pdf', 'text/'];
        $isAllowed = false;
        foreach ($allowedTypes as $allowedType) {
            if (str_starts_with($type, $allowedType)) {
                $isAllowed = true;
                break;
            }
        }
        if (!$isAllowed) {
            throw new \InvalidArgumentException('File type not allowed');
        }
    }

    private function createMessage(array $data, string $userId, string $conversationId): Message
    {
        $rawContent = trim($data['content']);
        $messageId = $this->uuidService->generateWithPrefix('msg_');
        $message = new Message(
            $messageId,
            $conversationId,
            $userId,
            $rawContent,
            'text',
            $data['threadId'] ?? null,
        );
        if (!empty($data['files'])) {
            $message->setAttachments($data['files']);
        }
        return $message;
    }

    private function dispatchMessageEvent(Message $message): void
    {
        $participantIds = $this->conversationAccessVerifier->getParticipantIds($message->getConversationId());
        $event = new MessageSentEvent(
            messageId: $message->getId(),
            conversationId: $message->getConversationId(),
            senderId: $message->getSenderId(),
            content: $message->getContent(),
            type: $message->getType(),
            createdAt: $message->getCreatedAt(),
            threadId: $message->getThreadId(),
            attachments: $message->getAttachments(),
        );
        $this->eventBus->dispatch($event);
    }

    public function sendMessage(array $data, string $userId): Message
    {
        return $this->entityManager->wrapInTransaction(function () use ($data, $userId) {
            // Validate input
            if (empty($data['conversationId']) || empty($data['content'])) {
                throw new \InvalidArgumentException('Conversation ID and content are required');
            }
            if (strlen($data['content']) > self::MAX_MESSAGE_LENGTH) {
                throw new \InvalidArgumentException('Content too long (max ' . self::MAX_MESSAGE_LENGTH . ' characters)');
            }
            $conversationId = (string) $data['conversationId'];
            if (!$this->conversationAccessVerifier->exists($conversationId)) {
                throw new \InvalidArgumentException('Conversation not found');
            }
            if (!$this->conversationAccessVerifier->isParticipant($conversationId, $userId)) {
                throw new \InvalidArgumentException('Access denied to conversation');
            }

            $this->validateFiles($data['files'] ?? []);
            $message = $this->createMessage($data, $userId, $conversationId);
            $this->repository->save($message);
            $this->dispatchMessageEvent($message);

            return $message;
        });
    }

    public function getMessagesForUser(string $userId, int $limit = 50)
    {
        $messages = $this->repository->findRecentByUserId($userId, $limit);

        return array_map(function (Message $message) use ($userId) {
            $content = $message->getContent();

            return [
                'id' => $message->getId(),
                'conversation_id' => $message->getConversationId(),
                'sender_id' => $message->getSenderId(),
                'content' => $content,
                'type' => $message->getType(),
                'created_at' => $message->getCreatedAt()->format('c'),
                'thread_id' => $message->getThreadId(),
                'attachments' => $message->getAttachments(),
                'is_thread' => $message->getThreadId() !== null,
            ];
        }, $messages);
    }

    public function getConversationMessages(string $conversationId, string $userId, int $page = 1, int $limit = 50): array
    {
        // Verify conversation exists
        if (!$this->conversationAccessVerifier->exists($conversationId)) {
            throw new \InvalidArgumentException('Conversation not found');
        }

        // Verify user has access to conversation
        if (!$this->conversationAccessVerifier->isParticipant($conversationId, $userId)) {
            throw new \InvalidArgumentException('Access denied');
        }

        $offset = ($page - 1) * $limit;
        return $this->repository->findByConversationIdWithPagination($conversationId, $limit, $offset);
    }

    public function getConversationMessagesCursor(string $conversationId, string $userId, ?string $cursor = null, int $limit = 50): array
    {
        // Verify conversation exists
        if (!$this->conversationAccessVerifier->exists($conversationId)) {
            throw new \InvalidArgumentException('Conversation not found');
        }

        // Verify user has access to conversation
        if (!$this->conversationAccessVerifier->isParticipant($conversationId, $userId)) {
            throw new \InvalidArgumentException('Access denied');
        }

        return $this->repository->findByConversationIdWithCursor($conversationId, $cursor, $limit);
    }
}
