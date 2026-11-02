<?php

declare(strict_types=1);

namespace App\Social\Event;

use App\Shared\DomainEvent\DomainEvent;

class PostLikedEvent extends DomainEvent
{
    public function __construct(
        private readonly string $postId,
        private readonly string $userId,
        private readonly string $likerId,
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'PostLiked';
    }

    protected function eventData(): array
    {
        return [
            'post_id' => $this->postId,
            'user_id' => $this->userId,
            'liker_id' => $this->likerId,
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

    public function getLikerId(): string
    {
        return $this->likerId;
    }
}
