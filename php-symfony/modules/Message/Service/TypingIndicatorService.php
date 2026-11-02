<?php

declare(strict_types=1);

namespace App\Message\Service;

use Symfony\Contracts\Cache\CacheInterface;

class TypingIndicatorService
{
    private const TYPING_TIMEOUT = 5;

    public function __construct(
        private CacheInterface $cache
    ) {
    }

    public function setTyping(string $userId, string $conversationId)
    {
        $key = $this->getTypingKey($conversationId);

        $typingUsers = $this->cache->getItem($key)->get() ?? [];
        $typingUsers[$userId] = time();

        $this->cache->getItem($key)->set($typingUsers)->expiresAfter($this::TYPING_TIMEOUT);
    }

    public function removeTyping(string $userId, string $conversationId)
    {
        $key = $this->getTypingKey($conversationId);

        $typingUsers = $this->cache->getItem($key)->get() ?? [];
        unset($typingUsers[$userId]);

        if (empty($typingUsers)) {
            $this->cache->delete($key);
        } else {
            $this->cache->getItem($key)->set($typingUsers)->expiresAfter($this::TYPING_TIMEOUT);
        }
    }

    public function getTypingUsers(string $conversationId)
    {
        $key = $this->getTypingKey($conversationId);

        $typingUsers = $this->cache->getItem($key)->get() ?? [];
        $currentTime = time();

        $activeTyping = [];
        foreach ($typingUsers as $userId => $startTime) {
            if (($currentTime - $startTime) < $this::TYPING_TIMEOUT) {
                $activeTyping[] = $userId;
            }
        }

        return $activeTyping;
    }

    public function clearExpiredTypingIndicators()
    {
        $cleared = 0;
        $currentTime = time();

        $pattern = "typing:*";
        $keys = [];

        foreach ($this->cache->getAdapter() as $cacheItem) {
            if (str_starts_with($cacheItem->getKey(), 'typing:')) {
                $keys[] = $cacheItem->getKey();
            }
        }

        foreach ($keys as $key) {
            $typingUsers = $this->cache->getItem($key)->get();
            if (!is_array($typingUsers)) {
                continue;
            }

            $expired = true;
            foreach ($typingUsers as $startTime) {
                if (($currentTime - $startTime) < $this::TYPING_TIMEOUT) {
                    $expired = false;
                    break;
                }
            }

            if ($expired) {
                $this->cache->delete($key);
                $cleared++;
            }
        }

        return $cleared;
    }

    private function getTypingKey(string $conversationId)
    {
        return "typing:{$conversationId}";
    }
}
