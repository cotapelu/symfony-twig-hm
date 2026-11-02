<?php

declare(strict_types=1);

namespace App\Realtime\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class RealtimeService
{
    public function __construct(
        private HubInterface $hub,
    ) {
    }

    public function publishUpdate(string $topic, array $data)
    {
        $update = new Update($topic, json_encode($data));
        $this->hub->publish($update);
    }

    public function notifyMessageSent(string $conversationId, array $messageData)
    {
        $this->publishUpdate("conversation/{$conversationId}", [
            'type' => 'message_sent',
            'message' => $messageData,
        ]);
    }
}