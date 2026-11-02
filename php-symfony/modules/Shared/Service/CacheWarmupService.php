<?php

declare(strict_types=1);

namespace App\Shared\Service;

use App\Conversation\Repository\ConversationRepositoryInterface;
use App\Message\Repository\MessageRepositoryInterface;
use App\User\Repository\UserRepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheWarmupService
{
    public function __construct(
        private CacheInterface $cache,
        private ConversationRepositoryInterface $conversationRepository,
        private MessageRepositoryInterface $messageRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function warmupAll()
    {
        $users = $this->userRepository->findUsersPaginated(1, 100);

        foreach ($users['data'] as $user) {
            $userId = $user->getId();
            $this->warmupUserCache($userId);
        }

        $this->cache->get('cache:warmup:status', fn() => 'completed');
    }

    public function warmupUserCache(string $userId)
    {
        $conversationIds = $this->conversationRepository->findConversationIdsByParticipant($userId);

        $conversations = [];
        foreach ($conversationIds as $conversationId) {
            $conversation = $this->conversationRepository->findById($conversationId);
            if ($conversation) {
                $conversationIdStr = (string) $conversationId;
                $latestMessages = $this->messageRepository->findLatestMessagesByConversationIds([$conversationIdStr]);
                $latestMessage = $latestMessages[$conversationIdStr] ?? null;
                $unreadCount = $this->messageRepository->countUnreadMessagesInConversation($conversationIdStr, $userId);

                $conversations[] = [
                    'id' => $conversation->getId(),
                    'type' => $conversation->getType(),
                    'is_archived' => $conversation->isArchived(),
                    'last_message_at' => $latestMessage?->getCreatedAt()->format('Y-m-d H:i:s'),
                    'unread_count' => $unreadCount,
                ];
            }
        }

        $this->cache->get("conversations:{$userId}", fn() => $conversations);

        $recentMessages = $this->messageRepository->findRecentByUserId($userId, 20);
        $this->cache->get("messages:recent:{$userId}", fn() => $recentMessages);
    }

    public function isWarmupComplete()
    {
        $status = $this->cache->get('cache:warmup:status', fn() => 'pending');
        return $status === 'completed';
    }

    public function clearWarmupStatus()
    {
        $this->cache->delete('cache:warmup:status');
    }
}
