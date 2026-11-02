<?php

declare(strict_types=1);

namespace App\Social\Event;

use App\Shared\DomainEvent\DomainEvent;

class PostCreatedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $postId,
        private readonly string $userId,
        private readonly bool $isPublic,
        private readonly \DateTime $createdAt,
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'PostCreated';
    }

    protected function eventData(): array
    {
        return [
            'post_id' => $this->postId,
            'user_id' => $this->userId,
            'is_public' => $this->isPublic,
            'created_at' => $this->createdAt->format('c'),
        ];
    }

    public function getPostId(): string
    {
        return $this->postId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}
