<?php

declare(strict_types=1);

namespace App\Notification\DomainEvent;

use App\Shared\DomainEvent\DomainEvent;

class NotificationSent extends DomainEvent
{
    public function __construct(
        private string $notificationId,
        private string $userId,
        private string $type,
        private string $title,
        private string $message,
        private string $channel, // 'email', 'push', 'in_app'
    ) {
        parent::__construct([
            'aggregateId' => $notificationId,
            'aggregateType' => 'Notification',
            'userId' => $userId,
        ]);
    }

    public function eventType(): string
    {
        return 'notification.sent';
    }

    protected function eventData(): array
    {
        return [
            'notificationId' => $this->notificationId,
            'userId' => $this->userId,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'channel' => $this->channel,
        ];
    }

    public function notificationId(): string
    {
        return $this->notificationId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function channel(): string
    {
        return $this->channel;
    }
}