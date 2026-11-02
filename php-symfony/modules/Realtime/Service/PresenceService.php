<?php

declare(strict_types=1);

namespace App\Realtime\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class PresenceService
{
    /** @phpstan-ignore-next-line */
    private array $onlineUsers = [];
    /** @phpstan-ignore-next-line */
    private array $userStatus = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private HubInterface $hub,
    ) {
    }

    public function setUserOnline(string $userId)
    {
        $user = $this->entityManager->getRepository(\App\User\Entity\User::class)->find($userId);
        if (!$user) {
            throw new \InvalidArgumentException('User not found');
        }

        $this->onlineUsers[$userId] = time();
        $this->userStatus[$userId] = 'online';

        $update = new Update(
            'user/'.$userId.'/presence',
            json_encode([
                'type' => 'user_online',
                'userId' => $userId,
                'timestamp' => time(),
            ])
        );
        $this->hub->publish($update);

        return ['status' => 'online'];
    }

    public function setUserOffline(string $userId)
    {
        unset($this->onlineUsers[$userId]);
        $this->userStatus[$userId] = 'offline';

        $update = new Update(
            'user/'.$userId.'/presence',
            json_encode([
                'type' => 'user_offline',
                'userId' => $userId,
                'timestamp' => time(),
            ])
        );
        $this->hub->publish($update);

        return ['status' => 'offline'];
    }

    public function getOnlineUsers()
    {
        $timeout = 300;
        $now = time();
        foreach ($this->onlineUsers as $userId => $timestamp) {
            if ($now - $timestamp > $timeout) {
                unset($this->onlineUsers[$userId]);
                $this->userStatus[$userId] = 'offline';
            }
        }

        return [
            'online_users' => array_keys($this->onlineUsers),
            'count' => count($this->onlineUsers),
        ];
    }
}
