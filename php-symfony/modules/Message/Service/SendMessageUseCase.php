<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Conversation\Repository\ConversationRepositoryInterface;
use App\Message\Entity\Message;
use App\Message\Event\MessageSentEvent;
use App\Message\Repository\MessageRepositoryInterface;
use App\Shared\EventBus\EventBus;

class SendMessageUseCase
{
    private ConversationRepositoryInterface $conversationRepository;
    private MessageRepositoryInterface $messageRepository;
    private MessageEncryptionService $encryptionService;
    private EventBus $eventBus;

    public function __construct(
        ConversationRepositoryInterface $conversationRepository,
        MessageRepositoryInterface $messageRepository,
        MessageEncryptionService $encryptionService,
        EventBus $eventBus,
    ) {
        $this->conversationRepository = $conversationRepository;
        $this->messageRepository = $messageRepository;
        $this->encryptionService = $encryptionService;
        $this->eventBus = $eventBus;
    }

    public function execute(SendMessageRequest $request)
    {
        $this->validateRequest($request);
        $conversation = $this->findConversationOrFail((int) $request->conversationId);
        $this->ensureConversationAccess($conversation, $request->getUserId());
        if (!empty($request->attachments)) {
            $this->validateAttachments($request->attachments);
        }
        $sanitizedContent = $this->sanitizeContent($request->content);
        $message = $this->createMessage($request, $sanitizedContent);
        $this->maybeEncrypt($message, $request->userId ?? 'unknown');
        $this->messageRepository->save($message);
        $this->dispatchMessageSentEvent($message);
        return new SendMessageResponse($message);
    }

    private function validateRequest(SendMessageRequest $request): void
    {
        if (empty($request->conversationId) || empty($request->content)) {
            throw new \InvalidArgumentException('Conversation ID and content are required');
        }
        if (strlen($request->content) > 10000) {
            throw new \InvalidArgumentException('Content too long (max 10000 characters)');
        }
        $userId = $request->getUserId();
        if (!$userId) {
            throw new \InvalidArgumentException('User ID is required');
        }
    }

    private function findConversationOrFail(int $conversationId)
    {
        $conversation = $this->conversationRepository->findById($conversationId);
        if (!$conversation) {
            throw new \InvalidArgumentException('Conversation not found');
        }
        return $conversation;
    }

    private function ensureConversationAccess($conversation, string $userId): void
    {
        if (!$conversation->hasParticipant($userId)) {
            throw new \InvalidArgumentException('Access denied to conversation');
        }
    }

    private function validateAttachments(array $attachments): void
    {
        if (count($attachments) > 10) {
            throw new \InvalidArgumentException('Too many files (max 10)');
        }
        foreach ($attachments as $file) {
            $this->validateAttachment($file);
        }
    }

    private function validateAttachment(array $file): void
    {
        if (!is_array($file) || !isset($file['name'], $file['size'], $file['type'])) {
            throw new \InvalidArgumentException('Invalid file format');
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new \InvalidArgumentException('File too large (max 10MB)');
        }
        $allowedTypes = ['image/', 'video/', 'audio/', 'application/pdf', 'text/'];
        $isAllowed = false;
        foreach ($allowedTypes as $allowedType) {
            if (str_starts_with($file['type'], $allowedType)) {
                $isAllowed = true;
                break;
            }
        }
        if (!$isAllowed) {
            throw new \InvalidArgumentException('File type not allowed');
        }
    }

    private function sanitizeContent(string $content): string
    {
        return htmlspecialchars(trim($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function createMessage(SendMessageRequest $request, string $sanitizedContent): Message
    {
        $messageId = uniqid('msg_', true);
        $message = new Message(
            $messageId,
            $request->conversationId,
            $request->getUserId(),
            $sanitizedContent,
            'text',
            $request->threadId,
        );
        if (!empty($request->attachments)) {
            $message->setAttachments($request->attachments);
        }
        return $message;
    }

    private function maybeEncrypt(Message $message, string $userId): void
    {
        if ($message->getContent()) {
            $encrypted = $this->encryptionService->encryptMessage($message->getContent(), $userId);
            $message->setContent($encrypted);
        }
    }

    private function dispatchMessageSentEvent(Message $message): void
    {
        $event = new MessageSentEvent(
            messageId: $message->getId(),
            conversationId: $message->getConversationId(),
            senderId: $message->getSenderId(),
            content: $message->getContent(),
            type: $message->getType(),
            createdAt: $message->getCreatedAt(),
        );
        $this->eventBus->dispatch($event);
    }
}
