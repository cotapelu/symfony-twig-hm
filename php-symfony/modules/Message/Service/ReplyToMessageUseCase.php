<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Conversation\Repository\ConversationRepositoryInterface;
use App\Message\Repository\MessageRepositoryInterface;

/**
 * Use case for replying to a message.
 */
class ReplyToMessageUseCase
{
    private MessageRepositoryInterface $messageRepository;
    private ConversationRepositoryInterface $conversationRepository;

    public function __construct(
        MessageRepositoryInterface $messageRepository,
        ConversationRepositoryInterface $conversationRepository,
    ) {
        $this->messageRepository = $messageRepository;
        $this->conversationRepository = $conversationRepository;
    }

    /**
     * Execute the reply to message use case.
     */
    public function execute(ReplyToMessageRequest $request)
    {
        // Validate that original message exists
        $originalMessage = $this->messageRepository->findById($request->replyToMessageId);
        if (!$originalMessage) {
            throw new \InvalidArgumentException('Original message not found');
        }

        // Validate conversation exists and user is participant
        $conversation = $this->conversationRepository->findById((int) $request->conversationId);
        if (!$conversation) {
            throw new \InvalidArgumentException('Conversation not found');
        }

        if (!$conversation->hasParticipant($request->senderId)) {
            throw new \InvalidArgumentException('User is not a participant in this conversation');
        }

        // Validate conversation ID matches
        if ($originalMessage->getConversationId() !== $request->conversationId) {
            throw new \InvalidArgumentException('Message does not belong to this conversation');
        }

        // Create reply message
        $replyMessage = new \App\Message\Entity\Message(
            $request->messageId,
            $request->conversationId,
            $request->senderId,
            $request->content,
            $request->type,
            null
        );
        $replyMessage->setReplyToMessageId($request->replyToMessageId);

        // Save reply message
        $this->messageRepository->save($replyMessage);

        return new ReplyToMessageResponse($replyMessage, $originalMessage);
    }
}

/**
 * Response DTO for ReplyToMessageUseCase.
 */
class ReplyToMessageResponse
{
    public \App\Message\Entity\Message $replyMessage;
    public \App\Message\Entity\Message $originalMessage;

    public function __construct(
        \App\Message\Entity\Message $replyMessage,
        \App\Message\Entity\Message $originalMessage,
    ) {
        $this->replyMessage = $replyMessage;
        $this->originalMessage = $originalMessage;
    }
}
