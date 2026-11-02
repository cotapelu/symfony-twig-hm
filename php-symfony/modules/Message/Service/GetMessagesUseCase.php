<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Conversation\Repository\ConversationRepositoryInterface;
use App\Message\Repository\MessageRepositoryInterface;

class GetMessagesUseCase
{
    public function __construct(
        private ConversationRepositoryInterface $conversationRepository,
        private MessageRepositoryInterface $messageRepository,
        private MessageEncryptionService $messageEncryptionService,
    ) {
    }

    public function execute(string $userId)
    {
        // Use optimized query to get conversations with last message (no N+1)
        $conversations = $this->conversationRepository->findConversationsWithLastMessage($userId);

        $conversationsData = array_map(function ($conv) use ($userId) {
            $lastMessageContent = 'No messages yet';
            $unreadInConv = 0;

            if ($conv->getLastMessage()) {
                try {
                    $lastMessageContent = $this->messageEncryptionService->decryptMessage(
                        $conv->getLastMessage()->getContent(),
                        $userId
                    );
                } catch (\Exception $e) {
                    $lastMessageContent = 'Unable to load message';
                }

                // Count unread messages in this conversation
                $unreadInConv = $this->messageRepository->countUnreadMessagesInConversation($conv->getId(), $userId);
            }

            return [
                'id' => $conv->getId(),
                'title' => $conv->getTitle(),
                'lastMessage' => $lastMessageContent,
                'unread' => $unreadInConv,
                'updated_at' => $conv->getUpdatedAt()?->format('c'),
            ];
        }, $conversations);

        // Calculate total unread count
        $totalUnread = array_reduce($conversationsData, function ($carry, $conv) {
            return $carry + ($conv['unread'] ?? 0);
        }, 0);

        return [
            'conversations' => $conversationsData,
            'unread_count' => $totalUnread,
        ];
    }
}
