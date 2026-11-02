<?php

declare(strict_types=1);

namespace App\Realtime\Service;

use App\Conversation\Entity\Conversation;
use App\Shared\DomainEvent\DomainEventPublisherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class TypingService
{
    private array $typingUsers = [];

    public function __construct(
        private HubInterface $hub,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function startTyping(string $conversationId, string $userId)
    {
        $conversation = $this->entityManager->getRepository(\App\Conversation\Entity\Conversation::class)->find($conversationId);
        if (!$conversation) {
            throw new \InvalidArgumentException('Conversation not found');
        }

        if (!$conversation->hasParticipant($userId)) {
            throw new \InvalidArgumentException('User is not a participant in this conversation');
        }

        $key = "{$conversationId}:{$userId}";
        $this->typingUsers[$key] = time();

        $update = new Update(
            'conversation/'.$conversationId.'/typing',
            json_encode([
                'type' => 'typing_start',
                'userId' => $userId,
                'conversationId' => $conversationId,
                'timestamp' => time(),
            ])
        );
        $this->hub->publish($update);

        return [
            'conversationId' => $conversationId,
            'userId' => $userId,
            'typing' => true,
        ];
    }

    public function stopTyping(string $conversationId, string $userId)
    {
        $key = "{$conversationId}:{$userId}";
        unset($this->typingUsers[$key]);

        $update = new Update(
            'conversation/'.$conversationId.'/typing',
            json_encode([
                'type' => 'typing_stop',
                'userId' => $userId,
                'conversationId' => $conversationId,
                'timestamp' => time(),
            ])
        );
        $this->hub->publish($update);

        return [
            'conversationId' => $conversationId,
            'userId' => $userId,
            'typing' => false,
        ];
    }

    public function getTypingStatus(string $conversationId)
    {
        $timeout = 10;
        $now = time();
        $typingUsers = [];

        foreach ($this->typingUsers as $key => $timestamp) {
            [$convId, $userId] = explode(':', $key);
            if ($convId === $conversationId) {
                if ($now - $timestamp > $timeout) {
                    unset($this->typingUsers[$key]);
                } else {
                    $typingUsers[] = $userId;
                }
            }
        }

        return [
            'conversationId' => $conversationId,
            'typingUsers' => $typingUsers,
        ];
    }
}
