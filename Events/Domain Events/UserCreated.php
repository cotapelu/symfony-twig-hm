<?php
namespace App\Events\Domain;

use App\AppCore\Event\Event;

class UserCreated extends Event
{
    public readonly string $userId;
    public readonly string $email;
    public readonly string $userName;
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct(
        string $userId,
        string $email,
        string $userName,
        ?\DateTimeImmutable $occurredAt = null
    ) {
        $this->userId = $userId;
        $this->email = $email;
        $this->userName = $userName;
        $this->occurredAt = $occurredAt ?? new \DateTimeImmutable();

        parent::__construct(
            'UserCreated',
            [
                'userId' => $userId,
                'email' => $email,
                'userName' => $userName,
                'occurredAt' => $this->occurredAt
            ]
        );
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }
}