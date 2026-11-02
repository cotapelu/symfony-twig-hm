<?php

declare(strict_types=1);

namespace App\User\DomainEvent;

use App\Shared\DomainEvent\DomainEvent;

class UserRegistered extends DomainEvent
{
    public function __construct(
        private string $userId,
        private string $email,
        private string $name,
    ) {
        parent::__construct([
            'aggregateId' => $userId,
            'aggregateType' => 'User',
        ]);
    }

    public function eventType(): string
    {
        return 'user.registered';
    }

    protected function eventData(): array
    {
        return [
            'userId' => $this->userId,
            'email' => $this->email,
            'name' => $this->name,
        ];
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function name(): string
    {
        return $this->name;
    }
}