<?php

declare(strict_types=1);

namespace App\MiniPrograms\Event;

use App\Shared\DomainEvent\DomainEvent;

class ProgramInstalledEvent extends DomainEvent
{
    public function __construct(
        public readonly string $appId,
        public readonly string $userId,
        public readonly string $developerId,
    ) {
        parent::__construct();
    }

    public function eventType(): string
    {
        return 'mini_program.installed';
    }

    protected function eventData(): array
    {
        return [
            'appId' => $this->appId,
            'userId' => $this->userId,
            'developerId' => $this->developerId,
        ];
    }
}
